<?php

namespace App\Services\Checkout;

use App\Models\DiscountCode;

/**
 * Ergebnis der Preisberechnung eines Warenkorbs (alle Beträge in Cent, brutto).
 * Reihenfolge: Warenwert − Rabatt + Versand = Gesamtbetrag; Geschenkgutscheine mindern danach nur den Zahlbetrag.
 */
final class Totals
{
    /**
     * @param  array<int,int>  $lineDiscounts  cart_item_id => anteiliger Rabatt
     * @param  array<string,int>  $taxByRate  Steuersatz => enthaltene USt. (nach Rabatt, inkl. Versand)
     * @param  array<string,int>  $giftVouchers  Gutscheincode => angerechneter Betrag
     */
    public function __construct(
        public readonly int $subtotal,
        public readonly int $discount,
        public readonly array $lineDiscounts,
        public readonly int $shipping,
        public readonly int $total,
        public readonly array $taxByRate,
        public readonly array $giftVouchers = [],
        public readonly ?DiscountCode $discountCode = null,
    ) {}

    public function tax(): int
    {
        return array_sum($this->taxByRate);
    }

    public function giftVoucherTotal(): int
    {
        return array_sum($this->giftVouchers);
    }

    public function amountDue(): int
    {
        return max(0, $this->total - $this->giftVoucherTotal());
    }

    public function discountFor(int $cartItemId): int
    {
        return $this->lineDiscounts[$cartItemId] ?? 0;
    }
}
