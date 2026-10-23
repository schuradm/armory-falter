<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Documents\DocumentService;
use App\Services\Orders\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\ShopFixtures;
use Tests\TestCase;

/** Kundenkonto und DSGVO: Zugriff nur auf eigene Daten, Datenauskunft (Art. 15/20) und Kontolöschung (Art. 17). */
class AccountPrivacyTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedShop();
        Storage::fake('documents');
        config(['shop.documents.disk' => 'documents']);
    }

    private function customerWithData(): array
    {
        $user = $this->customer('Erika', 'Export');
        $user->forceFill(['phone' => '0711 123456'])->save();
        $user->addresses()->create(['type' => 'billing', 'first_name' => 'Erika', 'last_name' => 'Export', 'street' => 'Hauptstraße',
            'house_number' => '12', 'postal_code' => '72649', 'city' => 'Wolfschlugen']);
        $user->defaultWishlist()->products()->attach(Product::where('sku', 'SIG-ROMEO5')->value('id'));
        $doc = app(DocumentService::class)->store($user, UploadedFile::fake()->image('ausweis.png', 1200, 900),
            ['type' => 'id_card', 'document_number' => 'L01X00T47', 'valid_until' => now()->addYears(5)->toDateString()]);
        $this->fillCart($user, ['WAL-CLEAN-UNI' => 1]);
        $order = $this->checkout($user);

        return [$user, $order, $doc];
    }

    public function test_fremde_bestellungen_und_rechnungen_sind_nicht_abrufbar(): void
    {
        [$owner, $order] = $this->customerWithData();
        app(OrderService::class)->markPaid($order, $this->admin);
        $invoice = $order->invoices()->sole();

        $this->actingAs($owner)->get(route('account.orders.show', $order))->assertOk()->assertSee($order->order_number);
        $this->actingAs($owner)->get(route('account.invoices.download', $invoice))->assertOk();

        $intruder = $this->customer('Eve', 'Fremd');
        $this->actingAs($intruder)->get(route('account.orders.show', $order))->assertNotFound();
        $this->actingAs($intruder)->get(route('account.invoices.download', $invoice))->assertNotFound();
        $this->actingAs($intruder)->get(route('account.orders'))->assertOk()->assertDontSee($order->order_number);

        // Kontoseiten sind nie für Suchmaschinen gedacht
        $this->assertStringContainsString('noindex', $this->actingAs($owner)->get(route('account.dashboard'))->headers->get('X-Robots-Tag'));
    }

    public function test_datenauskunft_nur_nach_passwortbestaetigung_und_nur_eigene_daten(): void
    {
        [$user, $order] = $this->customerWithData();
        $other = $this->customer('Otto', 'Anders');
        $this->fillCart($other, ['HK-CASE-PISTOL' => 1]);
        $otherOrder = $this->checkout($other);

        $this->actingAs($user)->get(route('account.privacy.export'))->assertRedirect(route('password.confirm'));

        $response = $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('account.privacy.export'))->assertOk();
        $this->assertStringContainsString('armory-falter-datenauskunft.json', $response->headers->get('Content-Disposition'));
        $data = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame($user->email, $data['konto']['email']);
        $this->assertSame('0711 123456', $data['konto']['phone']);
        $this->assertSame('Wolfschlugen', $data['adressen'][0]['city']);
        $this->assertSame([$order->order_number], array_column($data['bestellungen'], 'order_number'));
        $this->assertSame('WAL-CLEAN-UNI', $data['bestellungen'][0]['positionen'][0]['sku']);
        $this->assertCount(1, $data['dokumente']);
        $this->assertSame(['Sig Sauer Romeo5 Red Dot'], $data['merklisten'][0]['produkte']);
        $this->assertStringNotContainsString($otherOrder->order_number, $response->streamedContent());
        $this->assertStringNotContainsString('password', $response->streamedContent());

        $this->assertTrue(AuditLog::where('action', 'privacy.export')->exists(), 'Auskunft wird protokolliert');
    }

    public function test_kontoloeschung_braucht_das_passwort(): void
    {
        [$user] = $this->customerWithData();

        $this->actingAs($user)->from(route('account.privacy'))
            ->delete(route('account.privacy.destroy'), ['password' => 'falsch'])
            ->assertRedirect(route('account.privacy'))->assertSessionHasErrors('password');

        $this->assertNotSoftDeleted($user);
        $this->assertSame('Erika', $user->fresh()->first_name);
    }

    public function test_kontoloeschung_anonymisiert_und_behaelt_aufbewahrungspflichtige_daten(): void
    {
        /** @var User $user */
        [$user, $order, $doc] = $this->customerWithData();
        $email = $user->email;
        Storage::disk('documents')->assertExists($doc->getRawOriginal('storage_path'));

        $this->actingAs($user)->delete(route('account.privacy.destroy'), ['password' => 'password'])
            ->assertRedirect(route('home'))->assertSessionHas('status', 'Ihr Konto wurde gelöscht.');

        $this->assertGuest();
        $this->assertSoftDeleted($user);
        $deleted = User::withTrashed()->find($user->id);
        $this->assertSame('Gelöscht', $deleted->first_name);
        $this->assertSame('geloescht-'.$user->id.'@invalid.local', $deleted->email);
        $this->assertNull($deleted->phone);
        $this->assertFalse((bool) $deleted->is_active);
        $this->assertSame(0, User::withTrashed()->where('email', $email)->count(), 'Alte E-Mail-Adresse ist nicht mehr gespeichert');

        // Ausweisdatei weg, Nummer gelöscht; Adressen und Merklisten entfernt
        Storage::disk('documents')->assertMissing($doc->getRawOriginal('storage_path'));
        $doc->refresh();
        $this->assertSame('deleted', $doc->getRawOriginal('storage_path'));
        $this->assertNull($doc->document_number);
        $this->assertSame(0, $deleted->addresses()->count());
        $this->assertSame(0, $deleted->wishlists()->count());

        // Bestellung bleibt (handels- und steuerrechtliche Aufbewahrung)
        $this->assertTrue(Order::whereKey($order->id)->where('user_id', $user->id)->exists());

        // Mit den alten Zugangsdaten ist keine Anmeldung mehr möglich
        $this->post('/konto/anmelden', ['email' => $email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
