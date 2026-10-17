<?php

namespace Tests\Feature\Concerns;

use App\Livewire\CartPage;
use App\Livewire\Checkout;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\GiftVoucherProductSeeder;
use Database\Seeders\LegalClassSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ShippingMethodSeeder;
use Database\Seeders\TaxRateSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/** Gemeinsame Grunddaten und Kassen-Hilfen für Bewertungs- und Gutscheintests (wie OrderFlowTest). */
trait ShopFixtures
{
    protected User $admin;

    protected function seedShop(): void
    {
        Mail::fake();
        Notification::fake();
        Storage::fake('local');

        $this->seed([RolesAndPermissionsSeeder::class, LegalClassSeeder::class, TaxRateSeeder::class,
            ShippingMethodSeeder::class, CatalogSeeder::class, ProductSeeder::class, GiftVoucherProductSeeder::class]);

        $this->admin = new User(['first_name' => 'Ada', 'last_name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'Geheim-Passwort-123']);
        $this->admin->forceFill(['is_staff' => true, 'is_active' => true, 'email_verified_at' => now(), 'two_factor_confirmed_at' => now()])->save();
        $this->admin->assignRole('super-admin');
    }

    protected function customer(string $first = 'Max', string $last = 'Testkunde'): User
    {
        $user = User::factory()->create(['first_name' => $first, 'last_name' => $last, 'birth_date' => '1985-05-01']);
        $user->assignRole('customer');

        return $user;
    }

    /** @param array<string,int> $skus */
    protected function fillCart(User $user, array $skus): void
    {
        $this->actingAs($user);
        foreach ($skus as $sku => $qty) {
            app(CartService::class)->add(Product::where('sku', $sku)->firstOrFail(), $qty);
        }
    }

    protected function applyCode(User $user, string $code)
    {
        return Livewire::actingAs($user)->test(CartPage::class)->set('voucherCode', $code)->call('applyCode');
    }

    /** Kasse durchlaufen; $payment = null, wenn Gutscheine alles decken. */
    protected function checkout(User $user, string $shipping = 'dhl_standard', ?string $payment = 'prepayment', bool $noReviewInvite = false): Order
    {
        $page = Livewire::actingAs($user)->test(Checkout::class)
            ->set('billing.street', 'Hauptstraße')->set('billing.house_number', '12')
            ->set('billing.postal_code', '72649')->set('billing.city', 'Wolfschlugen')
            ->call('toStep', 2)->assertHasNoErrors()
            ->set('shippingMethod', $shipping)->call('toStep', 3)->assertHasNoErrors();
        if ($payment) {
            $page->set('paymentMethod', $payment);
        }
        $page->call('toStep', 4)->assertHasNoErrors()
            ->set('noReviewInvite', $noReviewInvite)->set('terms', true)
            ->call('placeOrder')
            ->assertHasNoErrors()
            ->assertRedirect();

        return $user->orders()->latest('id')->firstOrFail();
    }
}
