<?php

namespace App\Services\Checkout;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DiscountCode;
use App\Models\GiftVoucher;
use App\Services\Vouchers\DiscountCodeService;

/**
 * Rechnet Warenkorb-Summen mit Rabattcode und Geschenkgutscheinen. Reine Berechnung ohne Gültigkeitsprüfung
 * (die macht DiscountCodeService), damit Warenkorb, Kasse und Bestellung dieselben Zahlen erhalten.
 *
 * Umsatzsteuer: Ein Rabatt ist eine Entgeltminderung (§ 17 UStG). Er wird anteilig nach Positionswert auf die
 * berechtigten Positionen verteilt; so mindert er jeden Steuersatz im richtigen Verhältnis und der USt-Ausweis
 * der Rechnung bleibt stimmig. Geschenkgutscheine (Mehrzweck-Gutscheine) sind dagegen Zahlungsmittel: Sie mindern
 * nur den Zahlbetrag, nicht die Bemessungsgrundlage. Gekaufte Gutscheine sind beim Verkauf nicht steuerbar.
 */
class TotalsCalculator
{
    public function __construct(private DiscountCodeService $codes) {}

    /** @param  iterable<GiftVoucher>  $giftVouchers */
    public function calculate(Cart $cart, int $shipping, ?DiscountCode $code = null, iterable $giftVouchers = []): Totals
    {
        $cart->loadMissing('items.product.legalClass', 'items.product.taxRate', 'items.product.categories', 'items.variant');
        $subtotal = $cart->items->sum(fn (CartItem $i) => $i->lineTotal());

        $lineDiscounts = [];
        if ($code) {
            $eligible = $this->codes->eligibleItems($code, $cart);
            $base = $eligible->sum(fn (CartItem $i) => $i->lineTotal());
            $discount = $code->type === 'percent'
                ? (int) round($base * (float) $code->percent / 100)
                : min((int) $code->amount_gross, $base);
            $lineDiscounts = self::apportion($discount, $eligible->mapWithKeys(fn (CartItem $i) => [$i->id => $i->lineTotal()])->all());
        }
        $discount = array_sum($lineDiscounts);
        $total = $subtotal - $discount + $shipping;

        // Steuer je Satz auf die Summe (nicht je Zeile gerundet), genau wie der Ausweis auf der Rechnung
        $gross = [];
        foreach ($cart->items as $item) {
            if ($item->isGiftVoucher()) {
                continue;
            }
            $rate = (string) (float) $item->product->taxRate->rate;
            $gross[$rate] = ($gross[$rate] ?? 0) + $item->lineTotal() - ($lineDiscounts[$item->id] ?? 0);
        }
        if ($shipping > 0) {
            $gross['19'] = ($gross['19'] ?? 0) + $shipping;
        }
        $taxByRate = [];
        foreach ($gross as $rate => $g) {
            $taxByRate[(string) $rate] = (int) round($g - $g / (1 + ((float) $rate) / 100));
        }

        // Gutscheine in der eingegebenen Reihenfolge anrechnen, höchstens bis zum Gesamtbetrag
        $applied = [];
        $open = $total;
        foreach ($giftVouchers as $voucher) {
            $use = min($voucher->balance, $open);
            if ($use > 0) {
                $applied[$voucher->code] = $use;
                $open -= $use;
            }
        }

        return new Totals($subtotal, $discount, $lineDiscounts, $shipping, $total, $taxByRate, $applied, $code);
    }

    /**
     * Verteilt einen Betrag proportional auf Gewichte (Restcent-Verfahren, Summe stimmt exakt).
     *
     * @param  array<int,int>  $weights  key => Positionswert
     * @return array<int,int>
     */
    public static function apportion(int $amount, array $weights): array
    {
        $sum = array_sum($weights);
        if ($amount <= 0 || $sum <= 0) {
            return [];
        }
        $amount = min($amount, $sum);
        $shares = [];
        $remainders = [];
        foreach ($weights as $key => $w) {
            $exact = $amount * $w / $sum;
            $shares[$key] = (int) floor($exact);
            $remainders[$key] = $exact - $shares[$key];
        }
        arsort($remainders);
        $left = $amount - array_sum($shares);
        foreach (array_keys($remainders) as $key) {
            if ($left-- <= 0) {
                break;
            }
            $shares[$key]++;
        }

        return array_filter($shares);
    }
}
