<?php

namespace App\Services\Compliance;

use App\Enums\PaymentMethod;
use App\Enums\ShippingMode;
use App\Models\Cart;
use App\Models\User;

/** Fasst die Produktprüfungen eines Warenkorbs zusammen: erlaubte Zahlungsarten und strengste Versandart. */
class CartComplianceEvaluator
{
    public function __construct(private ComplianceService $compliance) {}

    public function evaluate(Cart $cart, ?User $user): CartEvaluation
    {
        $cart->loadMissing('items.product.legalClass', 'items.product.caliber');
        $consumed = [];
        $results = [];
        $modes = [];
        $payment = null;
        $review = false;

        foreach ($cart->items as $item) {
            $result = $this->compliance->evaluate($item->product, $user, $item->quantity, $consumed);
            $results[$item->id] = $result;
            $review = $review || $result->requiresReview;

            $legal = $item->product->legalClass;
            $modes[] = $legal->shipping_mode;
            $codes = array_map(fn (PaymentMethod $m) => $m->value, $legal->allowedPaymentMethods());
            $payment = $payment === null ? $codes : array_values(array_intersect($payment, $codes));
        }

        $methods = array_values(array_filter(
            array_map(fn ($c) => PaymentMethod::from($c), $payment ?? []),
            fn (PaymentMethod $m) => $m->isEnabled(),
        ));

        $digitalOnly = $cart->items->isNotEmpty() && $cart->items->every(fn ($item) => $item->product->isGiftVoucher());

        return new CartEvaluation($results, ShippingMode::strictest($modes), $methods, $review, $digitalOnly);
    }
}
