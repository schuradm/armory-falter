<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentMethod;
use App\Filament\Pages\Settings as SettingsPage;
use App\Filament\Resources;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\ShopStatsOverview;
use App\Mail\NewsletterConfirm;
use App\Models\Appointment;
use App\Models\Author;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\Faq;
use App\Models\LocalPage;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\ShippingMethod;
use App\Models\TaxRate;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\WorkshopService;
use App\Services\AuditLogger;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** Rauchtest für Inhalts-, Service-, Marketing- und System-Bereiche des Backends (/kms-login). */
class AdminContentSmokeTest extends TestCase
{
    use RefreshDatabase;

    /** Ressourcen mit Listen- und ggf. Anlegen-Seite. */
    private const RESOURCES = [
        Resources\AppointmentResource::class,
        Resources\AuditLogResource::class,
        Resources\AuthorResource::class,
        Resources\BlogCategoryResource::class,
        Resources\BlogPostResource::class,
        Resources\CommentResource::class,
        Resources\ContactMessageResource::class,
        Resources\EventResource::class,
        Resources\FaqResource::class,
        Resources\LocalPageResource::class,
        Resources\NewsletterCampaignResource::class,
        Resources\NewsletterSubscriberResource::class,
        Resources\PageResource::class,
        Resources\RedirectResource::class,
        Resources\ShippingMethodResource::class,
        Resources\TaxRateResource::class,
        Resources\TestimonialResource::class,
        Resources\WeaponBookEntryResource::class,
        Resources\WorkshopServiceResource::class,
    ];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('super-admin', 'web');

        $this->admin = User::forceCreate([
            'first_name' => 'Test',
            'last_name' => 'Admin',
            'email' => 'admin-smoke@example.test',
            'password' => 'Geheim-Passwort-123',
            'email_verified_at' => now(),
            'is_staff' => true,
            'is_active' => true,
            'two_factor_confirmed_at' => now(), // RequireStaffTwoFactor
        ]);
        $this->admin->assignRole('super-admin');

        $this->actingAs($this->admin);
    }

    public function test_dashboard_and_settings_render(): void
    {
        $this->seedContent();

        $this->get('/kms-login')->assertOk();
        // Widgets laden per Livewire nach (lazy) – direkt rendern
        Livewire::test(ShopStatsOverview::class)
            ->assertSee('Bestellungen heute')->assertSee('Offene Compliance-Freigaben')->assertSee('1.199,00 €');
        Livewire::test(LatestOrders::class)->assertSee('AF-TEST-1');
        $this->get(SettingsPage::getUrl())->assertOk()->assertSee('Einstellungen');
    }

    public function test_resource_index_and_create_pages_render(): void
    {
        $this->seedContent();

        foreach (self::RESOURCES as $resource) {
            $this->get($resource::getUrl('index'))->assertOk();
            if ($resource::hasPage('create')) {
                $this->get($resource::getUrl('create'))->assertOk();
            }
        }
    }

    public function test_record_pages_render(): void
    {
        $records = $this->seedContent();

        foreach ($records as $resource => $record) {
            foreach (['edit', 'view'] as $page) {
                if ($resource::hasPage($page)) {
                    $this->get($resource::getUrl($page, ['record' => $record]))->assertOk();
                }
            }
        }
    }

    public function test_read_only_resources_cannot_be_changed(): void
    {
        $this->assertFalse(Resources\AuditLogResource::hasPage('create'));
        $this->assertFalse(Resources\AuditLogResource::hasPage('edit'));
        $this->assertFalse(Resources\ContactMessageResource::canCreate());
        $this->assertFalse(Resources\NewsletterSubscriberResource::canCreate());
    }

    public function test_settings_page_saves_values_and_flushes_caches(): void
    {
        Cache::put('home.v2', ['stale' => true], 300);
        $pageCacheVersion = (int) Cache::get('page-cache-version', 1);

        Livewire::test(SettingsPage::class)
            ->fillForm([
                'home.hero.eyebrow' => 'Neue Dachzeile',
                'home.hero.headline_lines' => [['line' => 'Jagd.'], ['line' => 'Sport.']],
                'home.stats' => [['value' => 25, 'suffix' => '+', 'label' => 'Jahre Erfahrung']],
                'promo_bar' => '<strong>Herbstaktion</strong> <a href="/shop" onclick="alert(1)">jetzt</a><script>x</script>',
                'seo.home_title' => 'Armory Falter Testtitel',
                'elite_price' => '129,00',
                'payment_methods.prepayment' => false,
                'payment_methods.cash_pickup' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(Settings::class);
        $this->assertSame('Neue Dachzeile', $settings->get('home.hero.eyebrow'));
        $this->assertSame('Jagd.|Sport.', $settings->get('home.hero.headline'));
        $this->assertSame('Armory Falter Testtitel', $settings->get('seo.home_title'));
        $this->assertSame(12900, $settings->get('elite.price'));
        $this->assertSame('<strong>Herbstaktion</strong> <a href="/shop">jetzt</a>x', $settings->get('promo_bar'));

        // Listen ersetzen die Standardwerte komplett (nicht 1 neue + 2 alte Kennzahlen)
        $this->assertCount(1, $settings->home()['stats']);
        $this->assertSame('Jahre Erfahrung', $settings->home()['stats'][0]['label']);

        // Zahlart-Schalter wirkt im Checkout (PaymentMethod::isEnabled)
        $this->assertFalse(PaymentMethod::Prepayment->isEnabled());
        $this->assertTrue(PaymentMethod::CashPickup->isEnabled());

        $this->assertFalse(Cache::has('home.v2'));
        $this->assertGreaterThan($pageCacheVersion, (int) Cache::get('page-cache-version', 1));

        // Startseite rendert mit den neuen Werten
        $this->get('/')->assertOk()->assertSee('Neue Dachzeile')->assertSee('Jahre Erfahrung');
    }

    public function test_forms_store_cents_slug_and_seo(): void
    {
        $category = BlogCategory::create(['name' => 'Jagd', 'slug' => 'jagd']);
        $author = Author::create(['name' => 'Mark Falter', 'slug' => 'mark-falter']);

        Livewire::test(Resources\BlogPostResource\Pages\CreateBlogPost::class)
            ->fillForm([
                'title' => 'Die richtige Optik',
                'blog_category_id' => $category->id,
                'author_id' => $author->id,
                'body' => '<p>Inhalt</p>',
                'status' => 'published',
                'seo.meta_title' => 'Optik-Ratgeber',
            ])
            ->assertFormSet(['slug' => 'die-richtige-optik'])
            ->call('create')
            ->assertHasNoFormErrors();
        $post = BlogPost::where('slug', 'die-richtige-optik')->with('seo')->firstOrFail();
        $this->assertSame('Optik-Ratgeber', $post->seo?->meta_title);

        Livewire::test(Resources\ShippingMethodResource\Pages\CreateShippingMethod::class)
            ->fillForm([
                'name' => 'DHL Alterssichtprüfung', 'code' => 'dhl_age_check', 'allowed_shipping_modes' => ['age_check'],
                'price_gross' => '7,95', 'free_from_gross' => '150', 'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        $method = ShippingMethod::where('code', 'dhl_age_check')->firstOrFail();
        $this->assertSame(795, $method->price_gross);
        $this->assertSame(15000, $method->free_from_gross);

        Livewire::test(Resources\ShippingMethodResource\Pages\EditShippingMethod::class, ['record' => $method->getRouteKey()])
            ->assertFormSet(['price_gross' => '7,95'])
            ->fillForm(['price_gross' => '8.5'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(850, $method->fresh()->price_gross);
    }

    public function test_contact_message_can_be_marked_handled_and_newsletter_invite_uses_double_opt_in(): void
    {
        Mail::fake();
        $message = ContactMessage::create(['name' => 'Kunde', 'email' => 'kunde@example.test', 'subject' => 'Frage', 'message' => 'Hallo'])->fresh();

        Livewire::test(Resources\ContactMessageResource\Pages\ListContactMessages::class)
            ->callTableAction('handled', $message);
        $this->assertSame('handled', $message->fresh()->status);

        Livewire::test(Resources\NewsletterSubscriberResource\Pages\ListNewsletterSubscribers::class)
            ->callAction('invite', ['email' => 'Neu@Example.test', 'consent_note' => 'Im Laden gewünscht', 'consent' => true])
            ->assertHasNoActionErrors();
        $sub = NewsletterSubscriber::where('email', 'neu@example.test')->firstOrFail();
        $this->assertSame('pending', $sub->status);
        $this->assertSame('backend', $sub->source);
        Mail::assertQueued(NewsletterConfirm::class);

        Livewire::test(Resources\NewsletterSubscriberResource\Pages\ListNewsletterSubscribers::class)
            ->callAction('invite', ['email' => 'ohne@example.test', 'consent_note' => 'x', 'consent' => false])
            ->assertHasActionErrors(['consent']);
    }

    /** @return array<class-string, Model> */
    private function seedContent(): array
    {
        $service = WorkshopService::create(['slug' => 'optikmontage', 'name' => 'Optikmontage', 'price_from_gross' => 4900, 'duration_minutes' => 60]);
        $category = BlogCategory::create(['name' => 'Jagd', 'slug' => 'jagd']);
        $author = Author::create(['name' => 'Mark Falter', 'slug' => 'mark-falter', 'same_as' => ['https://example.test']]);
        $post = BlogPost::create(['blog_category_id' => $category->id, 'author_id' => $author->id, 'title' => 'Kaliberwahl', 'slug' => 'kaliberwahl',
            'body' => '<p>Text</p>', 'status' => 'published', 'published_at' => now()->subDay()]);
        $post->seo()->create(['meta_title' => 'Kaliberwahl für Jäger']);
        $comment = Comment::create(['blog_post_id' => $post->id, 'author_name' => 'Leser', 'body' => 'Danke!', 'status' => 'pending']);
        $contact = ContactMessage::create(['name' => 'Kunde', 'email' => 'kunde@example.test', 'subject' => 'Frage', 'message' => "Hallo\nWelt"]);
        $event = Event::create(['slug' => 'shooting-day', 'title' => 'Shooting Day', 'starts_at' => now()->addWeek(), 'capacity' => 20, 'price_gross' => 2500, 'is_published' => true]);
        $event->registrations()->create(['name' => 'Teilnehmer', 'email' => 'teilnehmer@example.test', 'persons' => 2]);
        $faq = Faq::create(['question' => 'Wie kaufe ich eine Waffe?', 'answer' => '<p>Mit WBK.</p>', 'group_name' => 'Allgemein']);
        $local = LocalPage::create(['slug' => 'esslingen', 'city' => 'Esslingen', 'title' => 'Waffenhändler Esslingen', 'headline' => 'Für Esslingen',
            'body' => '<p>Text</p>', 'faqs' => [['question' => 'Wie weit?', 'answer' => '10 km']], 'is_published' => true]);
        $subscriber = NewsletterSubscriber::create(['email' => 'abo@example.test', 'status' => 'confirmed', 'interests' => ['jagd'], 'opt_in_at' => now(), 'confirmed_at' => now(), 'source' => 'footer']);
        $campaign = NewsletterCampaign::create(['name' => 'Herbst', 'subject' => 'Neuheiten', 'body' => '**Hallo**', 'status' => 'draft']);
        $page = Page::create(['slug' => 'impressum', 'title' => 'Impressum', 'body' => '<p>Angaben</p>', 'status' => 'published']);
        $redirect = Redirect::create(['from_path' => '/alte-seite/', 'to_path' => '/shop']);
        $shipping = ShippingMethod::create(['code' => 'dhl_standard', 'name' => 'DHL Paket', 'allowed_shipping_modes' => ['standard'], 'price_gross' => 595, 'free_from_gross' => 15000]);
        $tax = TaxRate::create(['name' => 'Regelsteuersatz', 'rate' => 19]);
        $testimonial = Testimonial::create(['author_name' => 'Thomas K.', 'body' => 'Top Beratung', 'rating' => 5, 'is_published' => true]);
        $appointment = Appointment::create(['type' => 'workshop', 'workshop_service_id' => $service->id, 'contact' => ['name' => 'Kunde', 'email' => 'k@example.test', 'phone' => '0711'],
            'starts_at' => now()->addDays(2)->setTime(10, 0), 'ends_at' => now()->addDays(2)->setTime(10, 30), 'resource' => 'werkstatt', 'status' => 'requested']);
        $audit = app(AuditLogger::class)->log('smoke.test', $post, ['status' => 'draft'], ['status' => 'published', 'nested' => ['a' => 1]]);

        // Bestellung für Dashboard-Kennzahlen und „Neueste Bestellungen“
        Order::forceCreate([
            'order_number' => 'AF-TEST-1', 'user_id' => $this->admin->id, 'status' => 'pending_compliance', 'compliance_status' => 'pending',
            'billing_address' => ['first_name' => 'Test'], 'shipping_method_code' => 'dhl_standard', 'payment_method_code' => 'prepayment',
            'subtotal_gross' => 119900, 'shipping_gross' => 0, 'total_gross' => 119900, 'total_tax' => 19144,
            'terms_accepted_at' => now(), 'idempotency_key' => (string) Str::uuid(), 'placed_at' => now(),
        ]);

        return [
            Resources\AppointmentResource::class => $appointment,
            Resources\AuditLogResource::class => $audit,
            Resources\AuthorResource::class => $author,
            Resources\BlogCategoryResource::class => $category,
            Resources\BlogPostResource::class => $post,
            Resources\CommentResource::class => $comment,
            Resources\ContactMessageResource::class => $contact,
            Resources\EventResource::class => $event,
            Resources\FaqResource::class => $faq,
            Resources\LocalPageResource::class => $local,
            Resources\NewsletterCampaignResource::class => $campaign,
            Resources\NewsletterSubscriberResource::class => $subscriber,
            Resources\PageResource::class => $page,
            Resources\RedirectResource::class => $redirect,
            Resources\ShippingMethodResource::class => $shipping,
            Resources\TaxRateResource::class => $tax,
            Resources\TestimonialResource::class => $testimonial,
            Resources\WorkshopServiceResource::class => $service,
        ];
    }
}
