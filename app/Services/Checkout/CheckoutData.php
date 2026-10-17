<?php

namespace App\Services\Checkout;

use App\Enums\PaymentMethod;

final class CheckoutData
{
    public function __construct(
        public readonly array $billingAddress,
        public readonly ?array $shippingAddress,
        public readonly string $shippingMethodCode,
        public readonly PaymentMethod $paymentMethod,
        public readonly bool $termsAccepted,
        public readonly bool $legalDeclarationAccepted,
        public readonly string $idempotencyKey,
        public readonly ?string $customerNote = null,
        // Kunde hat der Bewertungseinladung beim Bestellen nicht widersprochen (§ 7 Abs. 3 UWG)
        public readonly bool $reviewInvite = true,
    ) {}
}
