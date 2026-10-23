<?php

namespace Tests\Feature;

use App\Enums\EntitlementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutData;
use App\Services\Checkout\CheckoutException;
use App\Services\Checkout\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\ShopFixtures;
use Tests\TestCase;

/**
 * Serverseitige Kassenregeln unabhängig vom Frontend: Versandkosten, Zulässigkeit von Versand- und Zahlungsart,
 * Bestand, Pflichtbestätigungen und Schutz vor Doppelbestellungen.
 */
class CheckoutRulesTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedShop();
    }

    private function place(User $user, string $shipping = 'dhl_standard', PaymentMethod $payment = PaymentMethod::Prepayment,
        bool $terms = true, bool $declaration = false, ?string $key = null): Order
    {
        $cart = Cart::where('user_id', $user->id)->latest('id')->firstOrFail();

        return app(CheckoutService::class)->placeOrder($user, $cart, new CheckoutData(
            billingAddress: ['first_name' => 'Max', 'last_name' => 'Testkunde', 'street' => 'Hauptstraße', 'house_number' => '12',
                'postal_code' => '72649', 'city' => 'Wolfschlugen', 'country' => 'DE'],
            shippingAddress: null,
            shippingMethodCode: $shipping,
            paymentMethod: $payment,
            termsAccepted: $terms,
            legalDeclarationAccepted: $declaration,
            idempotencyKey: $key ?? (string) Str::uuid(),
        ));
    }

    private function assertRejected(string $message, callable $place): void
    {
        try {
            $place();
            $this->fail("Bestellung angenommen, erwartet: {$message}");
        } catch (CheckoutException $e) {
            $this->assertStringContainsString($message, $e->getMessage());
        }
    }

    public function test_versandkostenfrei_erst_ab_150_euro(): void
    {
        $user = $this->customer();
        $this->fillCart($user, ['WAL-CLEAN-UNI' => 3]); // 149,70 €
        $order = $this->place($user);
        $this->assertSame(595, $order->shipping_gross);
        $this->assertSame(14970 + 595, $order->total_gross);

        $this->fillCart($user, ['WAL-CLEAN-UNI' => 1, 'SIG-ROMEO5' => 1]); // 228,90 €
        $order = $this->place($user);
        $this->assertSame(0, $order->shipping_gross);
        $this->assertSame(22890, $order->total_gross);
        // 19 % USt. aus dem Bruttobetrag
        $this->assertSame((int) round(22890 - 22890 / 1.19), $order->total_tax);
    }

    public function test_doppelklick_legt_nur_eine_bestellung_an(): void
    {
        $user = $this->customer();
        $stock = Product::where('sku', 'WAL-CLEAN-UNI')->value('stock_quantity');
        $this->fillCart($user, ['WAL-CLEAN-UNI' => 1]);

        $first = $this->place($user, key: 'klick-1');
        $second = $this->place($user, key: 'klick-1');

        $this->assertTrue($first->is($second));
        $this->assertSame(1, Order::count());
        $this->assertSame($stock - 1, Product::where('sku', 'WAL-CLEAN-UNI')->value('stock_quantity'));
        $this->assertMatchesRegularExpression('/^AF-'.now()->year.'-000001$/', $first->order_number);
    }

    public function test_abholung_mit_barzahlung_ohne_lieferadresse(): void
    {
        $user = $this->customer();
        $this->fillCart($user, ['WAL-CLEAN-UNI' => 1]);

        $order = $this->place($user, 'pickup', PaymentMethod::CashPickup);

        $this->assertSame(OrderStatus::Processing, $order->status, 'Bar bei Abholung: keine Zahlungsaufforderung');
        $this->assertSame(0, $order->shipping_gross);
        $this->assertNull($order->shipping_address);
        $this->assertSame('Wolfschlugen', $order->billing_address['city']);
    }

    public function test_unzulaessige_versand_und_zahlungsarten_werden_abgelehnt(): void
    {
        config(['shop.payment_methods.paypal.enabled' => true]);
        $user = $this->verifyAge($this->customer());
        $this->fillCart($user, ['WAL-BWK2' => 1]);

        $this->assertRejected('Versandart ist für diesen Warenkorb nicht zulässig', fn () => $this->place($user, 'dhl_standard'));
        $this->assertRejected('Zahlungsart ist für diesen Warenkorb nicht zulässig', fn () => $this->place($user, 'dhl_age_check', PaymentMethod::PayPal));
        $this->assertRejected('Zahlungsart ist für diesen Warenkorb nicht zulässig', fn () => $this->place($user, 'dhl_age_check', PaymentMethod::CashPickup));
        $this->assertSame(0, Order::count());

        $order = $this->place($user, 'dhl_age_check');
        $this->assertSame(990, $order->shipping_gross);
    }

    public function test_pflichtangaben_und_berechtigungen_werden_serverseitig_geprueft(): void
    {
        $user = $this->customer();
        $this->fillCart($user, ['WAL-CLEAN-UNI' => 1]);
        $this->assertRejected('AGB', fn () => $this->place($user, terms: false));

        $user->forceFill(['email_verified_at' => null])->save();
        $this->assertRejected('E-Mail-Adresse', fn () => $this->place($user));
        $user->forceFill(['email_verified_at' => now()])->save();

        // Messer ohne bestätigtes Alter
        $this->fillCart($user, ['WAL-BWK2' => 1]);
        $this->assertRejected('derzeit geprüften Unterlagen nicht bestellt werden', fn () => $this->place($user, 'pickup'));

        // Waffe mit Berechtigung, aber ohne Erklärung zur Erwerbsberechtigung
        $this->verifyAge($user);
        $this->grantEntitlement($user, EntitlementType::WbkVoreintrag, ['weapon_kind' => 'any', 'quantity_total' => 1]);
        $this->fillCart($user, ['GLO-G17-G5' => 1]);
        $this->assertRejected('Erklärung zur Erwerbsberechtigung', fn () => $this->place($user, 'pickup'));
        $this->assertSame(0, Order::count());

        $order = $this->place($user, 'pickup', declaration: true);
        $this->assertSame(OrderStatus::PendingCompliance, $order->status);
        $this->assertNotNull($order->legal_declaration_accepted_at);
        $this->assertSame(1, $order->complianceChecks()->where('result', 'pending')->whereNotNull('customer_entitlement_id')->count());
    }

    public function test_bestand_wird_beim_bestellen_erneut_geprueft(): void
    {
        // Nach dem Verkauf des letzten Stücks steht der Artikel auf „ausverkauft“: die zweite Bestellung muss scheitern
        $user = $this->customer();
        $this->fillCart($user, ['SWA-Z8I-216-50' => 1]);
        $other = $this->customer('Erika', 'Schneller');
        $this->fillCart($other, ['SWA-Z8I-216-50' => 1]);

        // Der andere Kunde bestellt das letzte Stück zuerst
        $this->place($other);
        $optic = Product::where('sku', 'SWA-Z8I-216-50')->first();
        $this->assertSame(0, $optic->stock_quantity);
        $this->assertSame('out_of_stock', $optic->availability->value);

        $this->assertRejected('nicht mehr in der gewünschten Menge verfügbar', fn () => $this->place($user));
        $this->assertSame(1, Order::count());
        $this->assertSame(0, $optic->fresh()->stock_quantity, 'Kein negativer Bestand');
        $this->actingAs($user);
        $this->assertSame(1, app(CartService::class)->count(), 'Warenkorb bleibt bei Ablehnung erhalten');
    }

    public function test_leerer_warenkorb(): void
    {
        $user = $this->customer();
        $this->fillCart($user, ['WAL-CLEAN-UNI' => 1]);
        $this->place($user);

        $this->assertRejected('Warenkorb ist leer', fn () => $this->place($user));
    }
}
