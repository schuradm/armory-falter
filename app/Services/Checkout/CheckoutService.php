<?php

namespace App\Services\Checkout;

use App\Enums\ComplianceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ShippingMode;
use App\Events\OrderPlaced;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Cart\CartService;
use App\Services\Compliance\CartComplianceEvaluator;
use App\Services\Compliance\CartEvaluation;
use App\Services\NumberGenerator;
use App\Services\Orders\InvoiceService;
use App\Services\Vouchers\CartCodeService;
use App\Services\Vouchers\DiscountCodeService;
use App\Services\Vouchers\GiftVoucherService;
use App\Services\Vouchers\VoucherException;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        private CartComplianceEvaluator $evaluator,
        private CartService $carts,
        private NumberGenerator $numbers,
        private AuditLogger $audit,
        private CartCodeService $codes,
        private TotalsCalculator $calculator,
        private DiscountCodeService $discounts,
        private GiftVoucherService $giftVouchers,
    ) {}

    /** @return Collection<int,ShippingMethod> */
    public function shippingMethodsFor(CartEvaluation $evaluation): Collection
    {
        return ShippingMethod::where('is_active', true)->orderBy('sort_order')->get()
            // Reine Gutscheinbestellung: nur Versand per E-Mail; sonst nie
            ->filter(fn (ShippingMethod $m) => $evaluation->digitalOnly
                ? $m->isDigital()
                : ! $m->isDigital() && in_array($evaluation->shippingMode->value, $m->allowed_shipping_modes, true))
            ->values();
    }

    /** @return list<PaymentMethod> */
    public function paymentMethodsFor(CartEvaluation $evaluation, ?ShippingMethod $shipping): array
    {
        return array_values(array_filter($evaluation->paymentMethods, function (PaymentMethod $m) use ($shipping) {
            // Barzahlung nur bei Abholung
            return $m !== PaymentMethod::CashPickup || $shipping?->isPickup();
        }));
    }

    /**
     * Legt die Bestellung an. Alle Regeln werden hier serverseitig erneut geprüft,
     * unabhängig davon, was das Frontend angezeigt hat.
     */
    public function placeOrder(User $user, Cart $cart, CheckoutData $data): Order
    {
        if ($existing = Order::where('idempotency_key', $data->idempotencyKey)->first()) {
            return $existing; // Doppelklick / Reload
        }
        if (! $data->termsAccepted) {
            throw new CheckoutException('Bitte akzeptieren Sie die AGB und die Widerrufsbelehrung.');
        }
        if (! $user->hasVerifiedEmail()) {
            throw new CheckoutException('Bitte bestätigen Sie zuerst Ihre E-Mail-Adresse.');
        }

        $cart->load('items.product.legalClass', 'items.product.taxRate', 'items.product.caliber', 'items.variant');
        if ($cart->items->isEmpty()) {
            throw new CheckoutException('Ihr Warenkorb ist leer.');
        }

        $evaluation = $this->evaluator->evaluate($cart, $user);
        if (! $evaluation->allowed()) {
            throw new CheckoutException('Mindestens ein Artikel kann mit Ihren derzeit geprüften Unterlagen nicht bestellt werden.');
        }
        if ($evaluation->requiresReview && ! $data->legalDeclarationAccepted) {
            throw new CheckoutException('Bitte bestätigen Sie die Erklärung zur Erwerbsberechtigung.');
        }

        $shipping = $this->shippingMethodsFor($evaluation)->firstWhere('code', $data->shippingMethodCode)
            ?? throw new CheckoutException('Die gewählte Versandart ist für diesen Warenkorb nicht zulässig.');

        // Rabattcode und Gutscheine erneut prüfen: Hat sich etwas geändert, muss der Kunde die neuen Summen sehen
        [$code, $vouchers, $notices] = $this->codes->resolve($cart, $user);
        if ($notices) {
            throw new CheckoutException(implode(' ', $notices).' Bitte prüfen Sie die Summen.');
        }
        $goods = $cart->items->sum(fn ($i) => $i->lineTotal());
        $totals = $this->calculator->calculate($cart, $shipping->priceFor($goods), $code, $vouchers);

        // Deckt das Gutscheinguthaben alles, ist keine weitere Zahlungsart nötig
        $payment = $totals->amountDue() === 0 && $totals->giftVoucherTotal() > 0 ? PaymentMethod::GiftVoucher : $data->paymentMethod;
        if ($payment === PaymentMethod::GiftVoucher && $totals->amountDue() > 0) {
            throw new CheckoutException('Bitte wählen Sie eine Zahlungsart für den Restbetrag von '.Money::format($totals->amountDue()).'.');
        }
        if ($payment !== PaymentMethod::GiftVoucher && ! in_array($payment, $this->paymentMethodsFor($evaluation, $shipping), true)) {
            throw new CheckoutException('Die gewählte Zahlungsart ist für diesen Warenkorb nicht zulässig.');
        }
        if ($evaluation->shippingMode !== ShippingMode::PickupOnly && ! $shipping->isPickup() && ! $shipping->isDigital() && empty($data->shippingAddress ?? $data->billingAddress)) {
            throw new CheckoutException('Bitte geben Sie eine Lieferadresse an.');
        }

        try {
            $order = DB::transaction(fn () => $this->createOrder($user, $cart, $data, $evaluation, $shipping, $totals, $payment));
        } catch (VoucherException $e) {
            throw new CheckoutException($e->getMessage()); // Limit/Guthaben hat sich parallel geändert
        }

        $this->audit->log('order.placed', $order, new: ['total' => $order->total_gross, 'status' => $order->status->value]);
        event(new OrderPlaced($order));

        // Vollständig per Gutschein bezahlt: Rechnung sofort, gekaufte Gutscheine sofort ausgeben
        if ($order->status === OrderStatus::Paid) {
            app(InvoiceService::class)->createFor($order);
            $this->giftVouchers->issueFor($order);
        }

        return $order;
    }

    private function createOrder(User $user, Cart $cart, CheckoutData $data, CartEvaluation $evaluation, ShippingMethod $shipping, Totals $totals, PaymentMethod $payment): Order
    {
        // Bestand sperren und prüfen
        $products = Product::whereIn('id', $cart->items->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');
        foreach ($cart->items as $item) {
            $p = $products[$item->product_id];
            if ($p->tracksStock() && $p->availableStock() < $item->quantity) {
                throw new CheckoutException("„{$p->name}“ ist nicht mehr in der gewünschten Menge verfügbar.");
            }
        }

        $status = match (true) {
            $evaluation->requiresReview => OrderStatus::PendingCompliance,
            $totals->amountDue() === 0 => OrderStatus::Paid,
            $payment === PaymentMethod::CashPickup => OrderStatus::Processing,
            default => OrderStatus::PendingPayment,
        };

        $order = Order::create([
            'order_number' => $this->numbers->next('order', 'AF'),
            'user_id' => $user->id,
            'status' => $status,
            'compliance_status' => $evaluation->requiresReview ? ComplianceStatus::Pending : ComplianceStatus::NotRequired,
            'billing_address' => $data->billingAddress,
            'shipping_address' => $shipping->isPickup() ? null : ($data->shippingAddress ?? $data->billingAddress),
            'shipping_method_code' => $shipping->code,
            'payment_method_code' => $payment,
            'subtotal_gross' => $totals->subtotal,
            'shipping_gross' => $totals->shipping,
            'discount_gross' => $totals->discount,
            'discount_code' => $totals->discountCode?->code,
            'total_gross' => $totals->total,
            'total_tax' => $totals->tax(),
            'gift_voucher_gross' => $totals->giftVoucherTotal(),
            'review_invite_allowed' => $data->reviewInvite && ! $user->review_requests_opted_out_at,
            'customer_note' => $data->customerNote,
            'terms_accepted_at' => now(),
            'legal_declaration_accepted_at' => $data->legalDeclarationAccepted ? now() : null,
            'ip_address' => request()->ip(),
            'idempotency_key' => $data->idempotencyKey,
            'placed_at' => now(),
        ]);

        foreach ($cart->items as $item) {
            $p = $products[$item->product_id];
            $voucher = $item->isGiftVoucher();
            $orderItem = $order->items()->create([
                'product_id' => $p->id,
                'product_variant_id' => $item->product_variant_id,
                'item_type' => $voucher ? 'gift_voucher' : 'product',
                'sku' => $item->variant?->sku ?? $p->sku,
                'name' => $voucher ? $item->displayName().' (Mehrzweck-Gutschein)' : $p->name.($item->variant ? ' ('.$item->variant->label().')' : ''),
                'legal_class_code' => $item->product->legalClass->code,
                'quantity' => $item->quantity,
                'unit_price_gross' => $item->unitPrice(),
                'tax_rate' => $voucher ? 0 : $item->product->taxRate->rate, // Mehrzweck-Gutschein: USt. erst bei Einlösung
                'line_total_gross' => $item->lineTotal(),
                'discount_gross' => $totals->discountFor($item->id),
                'options' => $voucher ? $item->options : null,
            ]);

            $result = $evaluation->resultFor($item);
            if ($result && ($result->requiresReview || $result->entitlement)) {
                $order->complianceChecks()->create([
                    'order_item_id' => $orderItem->id,
                    'check_type' => 'entitlement',
                    'customer_entitlement_id' => $result->entitlement?->id,
                    'result' => 'pending',
                    'details' => ['precheck' => $result->reasons],
                    'checked_at' => now(),
                ]);
            }

            if ($voucher) {
                continue; // kein Lagerbestand
            }
            $p->decrement('stock_quantity', $item->quantity);
            if ($p->is_unique_item || ($p->tracksStock() && $p->stock_quantity <= 0)) {
                $p->update(['availability' => 'out_of_stock']);
            }
        }

        // Erst mit der Bestellung zählen: Rabatt-Einlösung und Gutschein-Abbuchung (Storno gibt beides frei)
        if ($totals->discountCode && $totals->discount > 0) {
            $this->discounts->redeem($totals->discountCode, $order, $user, $totals->discount);
        }
        $this->giftVouchers->redeem($order, $totals->giftVouchers);

        $order->statusHistory()->create(['to_status' => $status->value, 'comment' => 'Bestellung eingegangen']);
        $this->carts->clear($cart);

        return $order;
    }
}
