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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        private CartComplianceEvaluator $evaluator,
        private CartService $carts,
        private NumberGenerator $numbers,
        private AuditLogger $audit,
    ) {}

    /** @return Collection<int,ShippingMethod> */
    public function shippingMethodsFor(CartEvaluation $evaluation): Collection
    {
        return ShippingMethod::where('is_active', true)->orderBy('sort_order')->get()
            ->filter(fn (ShippingMethod $m) => in_array($evaluation->shippingMode->value, $m->allowed_shipping_modes, true))
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

        if (! in_array($data->paymentMethod, $this->paymentMethodsFor($evaluation, $shipping), true)) {
            throw new CheckoutException('Die gewählte Zahlungsart ist für diesen Warenkorb nicht zulässig.');
        }
        if ($evaluation->shippingMode !== ShippingMode::PickupOnly && ! $shipping->isPickup() && empty($data->shippingAddress ?? $data->billingAddress)) {
            throw new CheckoutException('Bitte geben Sie eine Lieferadresse an.');
        }

        $order = DB::transaction(function () use ($user, $cart, $data, $evaluation, $shipping) {
            // Bestand sperren und prüfen
            $products = Product::whereIn('id', $cart->items->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');
            foreach ($cart->items as $item) {
                $p = $products[$item->product_id];
                if (in_array($p->availability->value, ['in_stock', 'low_stock'], true) && $p->availableStock() < $item->quantity) {
                    throw new CheckoutException("„{$p->name}“ ist nicht mehr in der gewünschten Menge verfügbar.");
                }
            }

            $subtotal = $cart->items->sum(fn ($i) => $i->lineTotal());
            $shippingGross = $shipping->priceFor($subtotal);
            $tax = $cart->items->sum(fn ($i) => $i->product->taxAmount($i->lineTotal()))
                + (int) round($shippingGross - $shippingGross / 1.19);

            $status = match (true) {
                $evaluation->requiresReview => OrderStatus::PendingCompliance,
                $data->paymentMethod === PaymentMethod::CashPickup => OrderStatus::Processing,
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
                'payment_method_code' => $data->paymentMethod,
                'subtotal_gross' => $subtotal,
                'shipping_gross' => $shippingGross,
                'total_gross' => $subtotal + $shippingGross,
                'total_tax' => $tax,
                'customer_note' => $data->customerNote,
                'terms_accepted_at' => now(),
                'legal_declaration_accepted_at' => $data->legalDeclarationAccepted ? now() : null,
                'ip_address' => request()->ip(),
                'idempotency_key' => $data->idempotencyKey,
                'placed_at' => now(),
            ]);

            foreach ($cart->items as $item) {
                $p = $products[$item->product_id];
                $orderItem = $order->items()->create([
                    'product_id' => $p->id,
                    'product_variant_id' => $item->product_variant_id,
                    'sku' => $item->variant?->sku ?? $p->sku,
                    'name' => $p->name.($item->variant ? ' ('.$item->variant->label().')' : ''),
                    'legal_class_code' => $item->product->legalClass->code,
                    'quantity' => $item->quantity,
                    'unit_price_gross' => $item->unitPrice(),
                    'tax_rate' => $item->product->taxRate->rate,
                    'line_total_gross' => $item->lineTotal(),
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

                $p->decrement('stock_quantity', $item->quantity);
                $tracksStock = in_array($p->availability->value, ['in_stock', 'low_stock'], true);
                if ($p->is_unique_item || ($tracksStock && $p->stock_quantity <= 0)) {
                    $p->update(['availability' => 'out_of_stock']);
                }
            }

            $order->statusHistory()->create(['to_status' => $status->value, 'comment' => 'Bestellung eingegangen']);
            $this->carts->clear($cart);

            return $order;
        });

        $this->audit->log('order.placed', $order, new: ['total' => $order->total_gross, 'status' => $order->status->value]);
        event(new OrderPlaced($order));

        return $order;
    }
}
