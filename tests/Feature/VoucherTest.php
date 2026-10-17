<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\DiscountCodeResource\Pages\CreateDiscountCode;
use App\Filament\Resources\DiscountCodeResource\Pages\EditDiscountCode;
use App\Filament\Resources\DiscountCodeResource\Pages\ListDiscountCodes;
use App\Filament\Resources\GiftVoucherResource\Pages\CreateGiftVoucher;
use App\Filament\Resources\GiftVoucherResource\Pages\ListGiftVouchers;
use App\Filament\Resources\GiftVoucherResource\Pages\ViewGiftVoucher;
use App\Filament\Resources\GiftVoucherResource\Widgets\VoucherStats;
use App\Livewire\AddToCart;
use App\Mail\GiftVoucherMail;
use App\Mail\OrderConfirmation;
use App\Models\Category;
use App\Models\DiscountCode;
use App\Models\GiftVoucher;
use App\Models\LegalClass;
use App\Models\Order;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Checkout\TotalsCalculator;
use App\Services\Orders\OrderService;
use App\Services\Vouchers\CartCodeService;
use App\Services\Vouchers\GiftVoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Concerns\ShopFixtures;
use Tests\TestCase;

/**
 * Rabattcodes und Geschenkgutscheine: Geltungsbereich, Fehlermeldungen, Limits, USt-Verteilung, Storno,
 * Kauf und Ausgabe von Gutscheinen, Teileinlösung wie eine Zahlung, Rechnung.
 * Ausführen gegen MySQL: DB_CONNECTION=mysql DB_DATABASE=armory_test php artisan test --filter=VoucherTest
 */
class VoucherTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedShop();
    }

    private function code(string $code, array $attributes = []): DiscountCode
    {
        $discount = new DiscountCode;
        $discount->forceFill($attributes + ['code' => $code, 'type' => 'percent', 'percent' => 10, 'is_active' => true])->save(); // times_used ist nicht fillable

        return $discount;
    }

    private function totalsFor(User $user, int $shipping = 0)
    {
        return app(CartCodeService::class)->totals(app(CartService::class)->current(), $user, $shipping)[0];
    }

    private function invoiceHtml(Order $order): string
    {
        return view('pdf.invoice', ['order' => $order->fresh()->load('items', 'user', 'payments'), 'number' => 'RE-TEST', 'company' => config('shop.company')])->render();
    }

    public function test_code_scope_categories_and_permit_items(): void
    {
        $user = $this->customer();
        $accessories = Category::where('path', 'waffenzubehoer')->value('id');
        $this->code('WILLKOMMEN10', ['include_category_ids' => [$accessories]]);
        $this->fillCart($user, ['WAL-CLEAN-UNI' => 1, 'ZEI-V4-312-56' => 1, 'GLO-G17-G5' => 1]);

        // Nur Zubehör: 10 % von 49,90 €
        $this->applyCode($user, 'willkommen-10')->assertHasNoErrors()->assertSee('WILLKOMMEN10 (10 %) wurde angewendet');
        $totals = $this->totalsFor($user);
        $this->assertSame(499, $totals->discount);
        $this->assertCount(1, $totals->lineDiscounts);

        // Ohne Einschränkung: alles außer der erlaubnispflichtigen Glock (Standard)
        $zeiss = Product::where('sku', 'ZEI-V4-312-56')->value('price_gross');
        $this->code('ALLES5', ['percent' => 5]);
        $this->applyCode($user, 'ALLES5')->assertSee('der bisherige wurde ersetzt');
        $this->assertSame((int) round((4990 + $zeiss) * 0.05), $this->totalsFor($user)->discount);

        // Je Code umschaltbar: auch für WBK-Ware
        DiscountCode::where('code', 'ALLES5')->update(['applies_to_permit_items' => true]);
        $this->assertSame((int) round((4990 + $zeiss + 79900) * 0.05), $this->totalsFor($user)->discount);

        // Ausgenommene Kategorie und Code nur für Waffen ohne Freigabe
        $this->code('OPTIK', ['exclude_category_ids' => [Category::where('path', 'optiken')->value('id')]]);
        $this->applyCode($user, 'OPTIK')->assertHasNoErrors();
        $this->assertSame(499, $this->totalsFor($user)->discount);
        $this->code('GLOCK', ['include_product_ids' => [Product::where('sku', 'GLO-G17-G5')->value('id')]]);
        $this->applyCode($user, 'GLOCK')->assertHasErrors('voucherCode')
            ->assertSee('gilt nicht für erlaubnispflichtige Waffen und Munition');
    }

    public function test_german_error_messages_and_limits(): void
    {
        $user = $this->customer();
        $this->fillCart($user, ['WAL-CLEAN-UNI' => 1]);
        $this->code('AUS', ['is_active' => false]);
        $this->code('SPAETER', ['starts_at' => now()->addWeek()]);
        $this->code('VORBEI', ['ends_at' => now()->subDay()]);
        $this->code('AB100', ['min_order_gross' => 10000]);
        $this->code('VERGRIFFEN', ['usage_limit' => 1, 'times_used' => 1]);

        foreach ([
            'GIBTSNICHT' => 'Diesen Code kennen wir nicht',
            'AUS' => 'nicht mehr gültig',
            'SPAETER' => 'gilt erst ab '.now()->addWeek()->format('d.m.Y'),
            'VORBEI' => 'am '.now()->subDay()->format('d.m.Y').' abgelaufen',
            'AB100' => 'ab einem Warenwert von 100,00 €. Es fehlen noch 50,10 €',
            'VERGRIFFEN' => 'bereits so oft eingelöst wie vorgesehen',
        ] as $code => $message) {
            $this->applyCode($user, $code)->assertHasErrors('voucherCode')->assertSee($message);
        }
        $this->assertNull(app(CartService::class)->current()->discount_code);
    }

    public function test_discount_is_apportioned_across_vat_rates_in_order_mail_and_invoice(): void
    {
        $book = Product::create([
            'sku' => 'BUCH-WK', 'name' => 'Handbuch Waffenkunde', 'slug' => 'handbuch-waffenkunde',
            'legal_class_id' => LegalClass::where('code', 'free')->value('id'), 'tax_rate_id' => TaxRate::where('rate', 7)->value('id'),
            'product_type' => 'accessory', 'price_gross' => 1990, 'stock_quantity' => 5, 'status' => 'active',
        ]);
        $this->code('ZEHN', ['type' => 'fixed', 'percent' => null, 'amount_gross' => 1000]);
        $user = $this->customer();
        $this->fillCart($user, ['WAL-CLEAN-UNI' => 1, 'BUCH-WK' => 1]);
        $this->applyCode($user, 'ZEHN')->assertHasNoErrors()->assertSee('−10,00 €');

        $order = $this->checkout($user);

        // 10 € im Verhältnis 49,90 : 19,90 verteilt → 7,15 € auf 19 %, 2,85 € auf 7 %
        $this->assertSame(1000, $order->discount_gross);
        $this->assertSame('ZEHN', $order->discount_code);
        $this->assertSame([715, 285], $order->items->sortBy('id')->pluck('discount_gross')->all());
        $this->assertSame(6980 - 1000 + 595, $order->total_gross);
        $vat = $order->vatBreakdown();
        $this->assertSame(['gross' => 4990 - 715 + 595, 'net' => 4092, 'tax' => 778], $vat['19']);
        $this->assertSame(['gross' => 1990 - 285, 'net' => 1593, 'tax' => 112], $vat['7']);
        $this->assertSame(778 + 112, $order->total_tax);
        $this->assertSame(1, DiscountCode::where('code', 'ZEHN')->value('times_used'));
        $this->assertSame($book->id, $order->items->sortBy('id')->last()->product_id);

        Mail::assertQueued(OrderConfirmation::class, fn ($m) => str_contains($m->render(), 'Rabatt (ZEHN)')
            && str_contains($m->render(), '−10,00 €') && str_contains($m->render(), '65,75 €'));

        $html = $this->invoiceHtml($order);
        foreach (['Rabatt (ZEHN)', '−10,00 €', '65,75 €', "netto 40,92\u{00A0}€", '7,78 €', "netto 15,93\u{00A0}€", '1,12 €', 'mindert das Entgelt je Steuersatz'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
    }

    public function test_usage_counts_on_order_and_is_released_on_cancellation(): void
    {
        $this->code('EINMAL', ['usage_limit_per_customer' => 1, 'usage_limit' => 5]);
        $user = $this->customer();

        // Eingeben allein zählt nicht
        $this->fillCart($user, ['WAL-CLEAN-UNI' => 1]);
        $this->applyCode($user, 'EINMAL')->assertHasNoErrors();
        $this->assertSame(0, DiscountCode::first()->times_used);

        $order = $this->checkout($user);
        $this->assertSame(1, DiscountCode::first()->times_used);
        $this->assertSame(499, $order->discountRedemption->amount_gross);

        $this->fillCart($user, ['HK-CASE-PISTOL' => 1]);
        $this->applyCode($user, 'EINMAL')->assertHasErrors('voucherCode')->assertSee('Sie haben diesen Rabattcode bereits eingelöst.');

        // Storno über die bestehende Storno-Logik gibt die Einlösung frei
        app(OrderService::class)->cancel($order, $this->admin, 'Kunde hat storniert.');
        $this->assertSame(0, DiscountCode::first()->times_used);
        $this->assertSame('released', $order->discountRedemption()->first()->status);
        $this->applyCode($user, 'EINMAL')->assertHasNoErrors();
        $this->assertSame(590, $this->checkout($user)->discount_gross);
    }

    public function test_gift_voucher_purchase_is_vat_free_and_issued_after_payment_with_pdf(): void
    {
        $user = $this->customer('Erika', 'Käufer');
        $product = Product::where('sku', 'GUTSCHEIN')->firstOrFail();
        $this->actingAs($user);
        Livewire::actingAs($user)->test(AddToCart::class, ['product' => $product])
            ->set('voucherAmount', 'custom')->set('customAmount', '5')->call('add')->assertHasErrors('voucherAmount')
            ->set('customAmount', '75')->set('forSomeoneElse', true)
            ->set('recipientName', 'Paul')->set('recipientEmail', 'paul@example.de')->set('message', 'Waidmannsheil!')
            ->call('add')->assertHasNoErrors();

        // Reiner Gutscheinwarenkorb: Versand per E-Mail, kostenlos; Rabattcodes greifen nicht
        $this->code('ALLES10');
        $this->applyCode($user, 'ALLES10')->assertHasErrors('voucherCode');
        $order = $this->checkout($user, 'email');

        $this->assertSame(7500, $order->total_gross);
        $this->assertSame(0, $order->shipping_gross);
        $this->assertSame(0, $order->total_tax); // Mehrzweck-Gutschein: keine USt. beim Verkauf
        $item = $order->items->first();
        $this->assertTrue($item->isGiftVoucher());
        $this->assertSame('0.00', $item->tax_rate);
        $this->assertSame([], $order->vatBreakdown());
        $this->assertSame(0, GiftVoucher::count()); // erst nach Zahlung

        app(OrderService::class)->markPaid($order, $this->admin, 'Kontoauszug');
        app(OrderService::class)->markPaid($order->refresh(), $this->admin); // doppelt gemeldet: kein zweiter Gutschein

        $voucher = GiftVoucher::sole();
        $this->assertSame(7500, $voucher->initial_value);
        $this->assertSame(7500, $voucher->balance);
        $this->assertSame(now()->year + 3 .'-12-31', $voucher->expires_at->toDateString()); // §§ 195, 199 BGB
        $this->assertSame(16, strlen($voucher->code));
        Storage::disk('local')->assertExists($voucher->pdf_path);
        $this->assertSame(OrderStatus::Completed, $order->refresh()->status); // nichts mehr zu versenden

        Mail::assertQueued(GiftVoucherMail::class, function (GiftVoucherMail $m) use ($voucher, $user) {
            return $m->hasTo('paul@example.de') && $m->hasCc($user->email)
                && str_contains($m->render(), $voucher->formattedCode()) && str_contains($m->render(), 'Waidmannsheil!')
                && count($m->attachments()) === 1;
        });
        $pdfHtml = view('pdf.gift-voucher', ['voucher' => $voucher, 'company' => config('shop.company')])->render();
        foreach (['GESCHENKGUTSCHEIN', '75,00 €', $voucher->formattedCode(), 'Für Paul', '31.12.'.(now()->year + 3), '§ 3 Abs. 15 UStG'] as $needle) {
            $this->assertStringContainsString($needle, $pdfHtml);
        }

        $html = $this->invoiceHtml($order);
        $this->assertStringContainsString('Mehrzweck-Gutscheine (§ 3 Abs. 15 UStG)', $html);
        $this->assertStringNotContainsString('enthaltene USt.', $html);
    }

    public function test_partial_redemption_works_like_payment_and_cancellation_restores_balance(): void
    {
        $voucher = app(GiftVoucherService::class)->create(10000, null);
        $user = $this->customer();

        // 1. Bestellung 55,85 € (49,90 + 5,95 Versand) vollständig per Gutschein, keine Zahlart nötig
        $this->fillCart($user, ['WAL-CLEAN-UNI' => 1]);
        $this->applyCode($user, strtolower($voucher->formattedCode()))->assertHasNoErrors()->assertSee('100,00 € Guthaben');
        $first = $this->checkout($user, payment: null);
        $this->assertSame(PaymentMethod::GiftVoucher, $first->payment_method_code);
        $this->assertSame(OrderStatus::Paid, $first->status);
        $this->assertSame(5585, $first->gift_voucher_gross);
        $this->assertSame(0, $first->amountDue());
        $this->assertSame(892, $first->total_tax); // USt. auf den vollen Warenwert, der Gutschein ist Zahlung
        $this->assertSame(10000 - 5585, $voucher->refresh()->balance);
        $this->assertCount(1, $first->invoices);
        $html = $this->invoiceHtml($first);
        foreach (['abzgl. Geschenkgutschein …'.substr($voucher->code, -4), 'Zahlbetrag', '0,00 €', '8,92 €', 'bereits beglichen', 'mindert die ausgewiesene Umsatzsteuer nicht'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }

        // 2. Bestellung 64,95 €: Restguthaben 44,15 € angerechnet, 20,80 € per Vorkasse
        $this->fillCart($user, ['HK-CASE-PISTOL' => 1]);
        $this->applyCode($user, $voucher->code)->assertHasNoErrors();
        $second = $this->checkout($user);
        $this->assertSame(OrderStatus::PendingPayment, $second->status);
        $this->assertSame(4415, $second->gift_voucher_gross);
        $this->assertSame(2080, $second->amountDue());
        $this->assertSame('redeemed', $voucher->refresh()->status);
        Mail::assertQueued(OrderConfirmation::class, fn ($m) => $m->order->is($second) && str_contains($m->render(), 'Zu zahlen') && str_contains($m->render(), '20,80 €'));

        // Leeres Guthaben wird abgelehnt
        $this->fillCart($user, ['WAL-CLEAN-UNI' => 1]);
        $this->applyCode($user, $voucher->code)->assertHasErrors('voucherCode')->assertSee('bereits vollständig eingelöst');

        // Storno bucht das Guthaben zurück
        app(OrderService::class)->cancel($second, $this->admin, 'Storno');
        $voucher->refresh();
        $this->assertSame(4415, $voucher->balance);
        $this->assertSame('active', $voucher->status);
        $this->assertSame('refunded', $second->payments()->where('provider', 'gift_voucher')->value('status'));
        $this->assertSame(['issue', 'redeem', 'redeem', 'release'], $voucher->transactions()->reorder('id')->pluck('type')->all());

        // Abgelaufene Gutscheine
        $voucher->update(['expires_at' => now()->subDay()]);
        $this->applyCode($user, $voucher->code)->assertHasErrors('voucherCode')->assertSee('abgelaufen');
    }

    public function test_apportion_sums_exactly(): void
    {
        $this->assertSame([1 => 34, 2 => 33, 3 => 33], TotalsCalculator::apportion(100, [1 => 100, 2 => 100, 3 => 100]));
        $this->assertSame(1000, array_sum(TotalsCalculator::apportion(1000, [7 => 4990, 8 => 1990, 9 => 1])));
        $this->assertSame([], TotalsCalculator::apportion(500, []));
    }

    public function test_backend_resources_for_codes_and_vouchers(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(CreateDiscountCode::class)
            ->fillForm(['code' => 'sommer-25', 'type' => 'fixed', 'amount_gross' => '25,00', 'min_order_gross' => '100', 'usage_limit_per_customer' => 1, 'is_active' => true])
            ->call('create')->assertHasNoFormErrors();
        $code = DiscountCode::where('code', 'SOMMER25')->firstOrFail();
        $this->assertSame(2500, $code->amount_gross);
        $this->assertSame(10000, $code->min_order_gross);
        $this->assertFalse($code->applies_to_permit_items);
        Livewire::test(ListDiscountCodes::class)->assertOk()->assertCanSeeTableRecords([$code]);
        Livewire::test(EditDiscountCode::class, ['record' => $code->getKey()])->assertOk();

        Livewire::test(CreateGiftVoucher::class)
            ->fillForm(['initial_value' => '50', 'recipient_email' => 'laden@example.de', 'note' => 'Verkauf im Laden'])
            ->call('create')->assertHasNoFormErrors();
        $voucher = GiftVoucher::sole();
        $this->assertSame(5000, $voucher->balance);
        Mail::assertQueued(GiftVoucherMail::class, fn ($m) => $m->hasTo('laden@example.de'));

        Livewire::test(ViewGiftVoucher::class, ['record' => $voucher->getKey()])->assertOk()
            ->callAction('adjust', ['direction' => 'redeem', 'amount' => '20,00', 'note' => 'Kassenbon 4711'])
            ->callAction('adjust', ['direction' => 'redeem', 'amount' => '40,00', 'note' => 'zu viel']);
        $this->assertSame(3000, $voucher->refresh()->balance);
        Livewire::test(ListGiftVouchers::class)->assertOk()->assertCanSeeTableRecords([$voucher])->assertSee('30,00 €');
        Livewire::test(VoucherStats::class)->assertSee('Offenes Gutscheinguthaben')->assertSee('30,00 €');
    }
}
