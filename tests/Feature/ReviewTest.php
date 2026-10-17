<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Resources\ProductReviewResource\Pages\EditProductReview;
use App\Filament\Resources\ProductReviewResource\Pages\ListProductReviews;
use App\Livewire\Account\Reviews;
use App\Mail\ReviewRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\Orders\OrderService;
use App\Services\Reviews\ReviewService;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\Feature\Concerns\ShopFixtures;
use Tests\TestCase;

/**
 * Produktbewertungen: nur verifizierte Käufer, Moderation, Anzeige mit JSON-LD, Bewertungseinladung mit Widerspruch.
 * Ausführen gegen MySQL: DB_CONNECTION=mysql DB_DATABASE=armory_test php artisan test --filter=ReviewTest
 */
class ReviewTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedShop();
        $this->product = Product::where('sku', 'WAL-CLEAN-UNI')->firstOrFail();
    }

    /** Bestellung über die Kasse, Versand und Zustellung durch das Personal. */
    private function deliveredOrder(User $user, bool $noReviewInvite = false): Order
    {
        $this->fillCart($user, ['WAL-CLEAN-UNI' => 1]);
        $order = $this->checkout($user, noReviewInvite: $noReviewInvite);
        $service = app(OrderService::class);
        $service->markPaid($order, $this->admin);
        $service->ship($order->refresh(), $this->admin, 'dhl', '00340434161234567890');
        $service->markDelivered($order->refresh(), $this->admin);

        return $order->refresh();
    }

    public function test_only_verified_buyers_with_delivered_order_may_review_once(): void
    {
        $reviews = app(ReviewService::class);
        $user = $this->customer('Max', 'Mustermann');

        // Ohne Kauf: kein Bewertungsrecht
        $this->assertFalse($reviews->canReview($user, $this->product));
        Livewire::actingAs($user)->test(Reviews::class, ['productSlug' => $this->product->slug])
            ->assertSee('Derzeit gibt es keine Artikel zum Bewerten');

        // Bestellt und versendet, aber noch nicht zugestellt: weiterhin nein
        $this->fillCart($user, ['WAL-CLEAN-UNI' => 1]);
        $order = $this->checkout($user);
        app(OrderService::class)->markPaid($order, $this->admin);
        app(OrderService::class)->ship($order->refresh(), $this->admin, 'dhl', '123');
        $this->assertFalse($reviews->canReview($user, $this->product));
        try {
            $reviews->submit($user, $this->product, ['rating' => 5]);
            $this->fail('Bewertung ohne Zustellung darf nicht möglich sein.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('gekauft und erhalten', $e->getMessage());
        }

        // Zugestellt: Formular im Konto, Bewertung wartet auf Prüfung, Anzeigename „Max M.“
        app(OrderService::class)->markDelivered($order->refresh(), $this->admin);
        $this->assertNotNull($order->refresh()->delivered_at);
        $this->assertSame(OrderStatus::Delivered, $order->status);
        Livewire::actingAs($user)->test(Reviews::class, ['productSlug' => $this->product->slug])
            ->call('submit')->assertHasErrors('rating')
            ->set('rating', 4)->set('title', 'Gutes Set')->set('body', '<b>Alles</b> drin.')
            ->call('submit')->assertHasNoErrors()
            ->assertSee('Wartet auf Prüfung');

        $review = ProductReview::firstOrFail();
        $this->assertSame('pending', $review->status);
        $this->assertSame('Max M.', $review->author_name);
        $this->assertSame('Alles drin.', $review->body); // kein HTML
        $this->assertSame($order->items->first()->id, $review->order_item_id); // Kaufnachweis

        // Nur eine Bewertung je Produkt und Kunde; Gutscheine sind nicht bewertbar
        $this->assertFalse($reviews->canReview($user, $this->product));
        $this->expectException(InvalidArgumentException::class);
        $reviews->submit($user, $this->product, ['rating' => 1]);
    }

    public function test_moderation_rating_aggregate_product_page_and_json_ld(): void
    {
        $reviews = app(ReviewService::class);
        $a = $this->customer('Anna', 'Berger');
        $b = $this->customer('Bernd', 'Christ');
        $this->deliveredOrder($a);
        $this->deliveredOrder($b);
        $ra = $reviews->submit($a, $this->product, ['rating' => 5, 'title' => 'Top', 'body' => 'Sehr zufrieden.']);
        $rb = $reviews->submit($b, $this->product, ['rating' => 2, 'title' => 'Geht so', 'body' => 'Bürsten zu weich.']);

        // Wartende Bewertungen sind unsichtbar, kein AggregateRating ohne freigegebene Bewertungen
        $html = $this->get($this->product->url())->assertOk()->getContent();
        $this->assertStringNotContainsString('Sehr zufrieden.', $html);
        $this->assertStringNotContainsString('AggregateRating', $html);
        $this->assertStringContainsString('Bewertungen stammen ausschließlich von verifizierten Käufern', $html);

        // Freigabe im Backend
        $this->actingAs($this->admin);
        Livewire::test(ListProductReviews::class)->assertCanSeeTableRecords([$ra, $rb])
            ->callTableAction('approve', $ra)->callTableAction('approve', $rb);
        $this->product->refresh();
        $this->assertSame(2, $this->product->rating_count);
        $this->assertEqualsWithDelta(3.5, $this->product->rating_avg, 0.001);

        // Öffentliche Antwort, dann Ablehnung mit Grund (nur bei Verstoß)
        Livewire::test(EditProductReview::class, ['record' => $rb->getKey()])
            ->fillForm(['reply' => 'Danke, wir senden Ihnen festere Bürsten.'])->call('save')->assertHasNoFormErrors();
        $this->assertNotNull($rb->refresh()->replied_at);

        auth()->logout();
        $html = $this->get($this->product->url())->assertOk()->getContent();
        foreach (['Sehr zufrieden.', 'Bürsten zu weich.', 'Anna B.', 'Verifizierter Kauf', 'Danke, wir senden Ihnen festere Bürsten.',
            '"@type":"AggregateRating"', '"ratingValue":"3.5"', '"reviewCount":2', 'Bewertet mit 3,5 von 5 Sternen'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringNotContainsString('Bernd Christ', $html); // nur Vorname + Initiale

        $this->actingAs($this->admin);
        Livewire::test(ListProductReviews::class)
            ->removeTableFilter('status') // Standardfilter zeigt nur Wartende
            ->callTableAction('reject', $rb, ['reason' => 'Personenbezogene Daten Dritter'])->assertHasNoTableActionErrors();
        $this->assertSame('rejected', $rb->refresh()->status);
        $this->assertSame(1, $this->product->refresh()->rating_count);
        $this->assertEqualsWithDelta(5.0, $this->product->rating_avg, 0.001);

        // Produktkarte (Listing) zeigt kompakte Sterne
        auth()->logout();
        $this->get(route('shop.category', 'waffenzubehoer'))->assertOk()->assertSee('Bewertet mit 5 von 5 Sternen, 1 Bewertung');
    }

    public function test_more_reviews_fragment_is_paginated(): void
    {
        config(['shop.reviews.per_page' => 2]);
        foreach (['Anna', 'Bert', 'Carl'] as $i => $name) {
            $user = $this->customer($name, 'Kunde');
            $this->deliveredOrder($user);
            $review = app(ReviewService::class)->submit($user, $this->product, ['rating' => 4, 'body' => "Text von {$name}"]);
            $review->forceFill(['created_at' => now()->subDays(10 - $i)])->save();
            app(ReviewService::class)->approve($review, $this->admin);
        }

        $page = $this->get($this->product->url())->assertOk();
        $page->assertSee('Text von Carl')->assertSee('Text von Bert')->assertDontSee('Text von Anna')->assertSee('Mehr anzeigen');

        $this->get(route('product.reviews', ['product' => $this->product, 'seite' => 2]))->assertOk()
            ->assertSee('Text von Anna')->assertDontSee('Text von Carl')->assertDontSee('data-next-page', false);
    }

    public function test_review_request_respects_delay_objection_and_runs_once(): void
    {
        app(Settings::class)->set('reviews', ['request_enabled' => true, 'request_days' => 7]);
        $willing = $this->customer('Wilma', 'Will');
        $objectsAtCheckout = $this->customer('Olaf', 'Opt');
        $objectsLater = $this->customer('Lena', 'Later');

        $o1 = $this->deliveredOrder($willing);
        $o2 = $this->deliveredOrder($objectsAtCheckout, noReviewInvite: true);
        $o3 = $this->deliveredOrder($objectsLater);
        $this->assertTrue($o1->review_invite_allowed);
        $this->assertFalse($o2->review_invite_allowed);
        Mail::fake(); // Bestellmails ausblenden

        // Vor Ablauf der Frist nichts
        $this->artisan('reviews:send-requests')->assertSuccessful();
        Mail::assertNothingQueued();

        // Widerspruch über den signierten Link aus der E-Mail
        $this->get(URL::signedRoute('reviews.opt-out', ['user' => $objectsLater->id]))->assertOk()->assertSee('keine Bewertungseinladungen mehr');
        $this->assertNotNull($objectsLater->refresh()->review_requests_opted_out_at);
        $this->get(route('reviews.opt-out', ['user' => $willing->id]))->assertForbidden(); // ohne Signatur

        $this->travel(8)->days();
        $this->artisan('reviews:send-requests')->assertSuccessful();
        Mail::assertQueuedCount(1);
        Mail::assertQueued(ReviewRequest::class, function (ReviewRequest $mail) use ($willing) {
            $html = $mail->render();

            return $mail->hasTo($willing->email)
                && str_contains($html, 'Walther Universal-Reinigungsset')
                && str_contains($html, '/bewertungen/abbestellen/'.$willing->id)
                && str_contains($html, '§ 7 Abs. 3 UWG')
                && str_contains($mail->headers()->text['List-Unsubscribe'], 'signature=');
        });
        $this->assertNotNull($o1->refresh()->review_requested_at);
        $this->assertNull($o3->refresh()->review_requested_at);

        // Zweiter Lauf: keine Wiederholung
        $this->artisan('reviews:send-requests')->assertSuccessful();
        Mail::assertQueuedCount(1);

        // Im Konto wieder einschalten (Widerruf des Widerspruchs)
        $this->actingAs($objectsLater)->post(route('account.privacy.review-invites'), ['allow' => 1])->assertRedirect();
        $this->assertNull($objectsLater->refresh()->review_requests_opted_out_at);
    }

    public function test_account_deletion_removes_reviews_and_updates_rating(): void
    {
        $user = $this->customer('Del', 'Eted');
        $this->deliveredOrder($user);
        app(ReviewService::class)->approve(app(ReviewService::class)->submit($user, $this->product, ['rating' => 1]), $this->admin);
        $this->assertSame(1, $this->product->refresh()->rating_count);

        $this->actingAs($user)->delete(route('account.privacy.destroy'), ['password' => 'password'])->assertRedirect(route('home'));
        $this->assertSame(0, ProductReview::count());
        $this->assertSame(0, $this->product->refresh()->rating_count);
        $this->assertNull($this->product->rating_avg);
    }
}
