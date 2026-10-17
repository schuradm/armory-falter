<?php

namespace App\Services\Compliance;

use App\Enums\PaymentMethod;
use App\Enums\ShippingMode;
use App\Models\CartItem;

final class CartEvaluation
{
    /**
     * @param  array<int,ComplianceResult>  $lineResults  key = cart_item id
     * @param  list<PaymentMethod>  $paymentMethods
     */
    public function __construct(
        public readonly array $lineResults,
        public readonly ShippingMode $shippingMode,
        public readonly array $paymentMethods,
        public readonly bool $requiresReview,
        public readonly bool $digitalOnly = false, // nur Geschenkgutscheine: Versand per E-Mail
    ) {}

    public function allowed(): bool
    {
        foreach ($this->lineResults as $r) {
            if (! $r->allowed) {
                return false;
            }
        }

        return true;
    }

    public function resultFor(CartItem $item): ?ComplianceResult
    {
        return $this->lineResults[$item->id] ?? null;
    }

    /** @return list<string> */
    public function hints(): array
    {
        return array_values(array_unique(array_filter(array_map(fn ($r) => $r->hint, $this->lineResults))));
    }
}
