<?php

namespace App\Services\Vouchers;

use App\Models\Cart;
use App\Models\DiscountCode;
use App\Models\GiftVoucher;
use App\Models\User;
use App\Services\Checkout\Totals;
use App\Services\Checkout\TotalsCalculator;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Codes im Warenkorb: ein Eingabefeld für Rabattcodes und Geschenkgutscheine. Gespeichert am Warenkorb,
 * damit Warenkorb und Kasse dasselbe zeigen. Vor jeder Anzeige und beim Bestellen wird erneut geprüft.
 */
class CartCodeService
{
    /** Höchstens so viele Fehlversuche je Kunde/IP in 10 Minuten (Schutz gegen Durchprobieren von Codes). */
    private const MAX_FAILED_ATTEMPTS = 10;

    public function __construct(
        private DiscountCodeService $discounts,
        private GiftVoucherService $vouchers,
        private TotalsCalculator $calculator,
    ) {}

    /**
     * @return string Erfolgsmeldung
     *
     * @throws VoucherException
     */
    public function apply(Cart $cart, string $input, ?User $user): string
    {
        $key = 'voucher-code:'.($user?->id ?? request()->ip());
        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILED_ATTEMPTS)) {
            throw new VoucherException('Zu viele ungültige Versuche. Bitte warten Sie '.ceil(RateLimiter::availableIn($key) / 60).' Minuten.');
        }
        if (trim($input) === '') {
            throw new VoucherException('Bitte geben Sie einen Code ein.');
        }

        try {
            if ($voucher = $this->vouchers->find($input)) {
                return $this->applyGiftVoucher($cart, $voucher);
            }
            if ($code = $this->discounts->find($input)) {
                $this->discounts->validate($code, $cart, $user);
                $replaced = $cart->discount_code && $cart->discount_code !== $code->code;
                $cart->update(['discount_code' => $code->code]);

                return "Rabattcode {$code->code} ({$code->label()}) wurde angewendet."
                    .($replaced ? ' Pro Bestellung gilt ein Rabattcode; der bisherige wurde ersetzt.' : '');
            }
            throw new VoucherException('Diesen Code kennen wir nicht. Bitte prüfen Sie die Schreibweise.');
        } catch (VoucherException $e) {
            RateLimiter::hit($key, 600);
            throw $e;
        }
    }

    private function applyGiftVoucher(Cart $cart, GiftVoucher $voucher): string
    {
        $this->vouchers->assertRedeemable($voucher);
        $codes = $cart->gift_voucher_codes ?? [];
        if (in_array($voucher->code, $codes, true)) {
            throw new VoucherException('Dieser Gutschein ist bereits angewendet.');
        }
        if (count($codes) >= 5) {
            throw new VoucherException('Pro Bestellung können höchstens fünf Gutscheine eingelöst werden.');
        }
        $cart->update(['gift_voucher_codes' => [...$codes, $voucher->code]]);

        return 'Gutschein '.$voucher->formattedCode().' mit '.Money::format($voucher->balance).' Guthaben wurde angewendet.';
    }

    public function removeDiscountCode(Cart $cart): void
    {
        $cart->update(['discount_code' => null]);
    }

    public function removeGiftVoucher(Cart $cart, string $code): void
    {
        $cart->update(['gift_voucher_codes' => array_values(array_diff($cart->gift_voucher_codes ?? [], [GiftVoucher::normalize($code)]))]);
    }

    /**
     * Gültige Codes des Warenkorbs; ungültig gewordene (abgelaufen, Limit erreicht, Warenkorb geändert) werden entfernt.
     *
     * @return array{0: ?DiscountCode, 1: Collection<int,GiftVoucher>, 2: list<string>} Code, Gutscheine, Hinweise
     */
    public function resolve(Cart $cart, ?User $user): array
    {
        $notices = [];
        $code = null;
        if ($cart->discount_code) {
            $code = $this->discounts->find($cart->discount_code);
            try {
                $code ?? throw new VoucherException('Der Rabattcode ist nicht mehr gültig.');
                $this->discounts->validate($code, $cart, $user);
            } catch (VoucherException $e) {
                $notices[] = "Rabattcode {$cart->discount_code} wurde entfernt: ".$e->getMessage();
                $code = null;
                $this->removeDiscountCode($cart);
            }
        }

        $codes = $cart->gift_voucher_codes ?? [];
        $vouchers = $codes ? GiftVoucher::whereIn('code', $codes)->get()->sortBy(fn ($v) => array_search($v->code, $codes, true))->values() : collect();
        $valid = $vouchers->filter(function (GiftVoucher $v) use (&$notices) {
            try {
                $this->vouchers->assertRedeemable($v);

                return true;
            } catch (VoucherException $e) {
                $notices[] = 'Gutschein '.$v->formattedCode().' wurde entfernt: '.$e->getMessage();

                return false;
            }
        })->values();
        if ($valid->count() !== count($codes)) {
            $cart->update(['gift_voucher_codes' => $valid->pluck('code')->all() ?: null]);
        }

        return [$code, $valid, $notices];
    }

    /** @return array{0: Totals, 1: list<string>} */
    public function totals(Cart $cart, ?User $user, int $shipping): array
    {
        [$code, $vouchers, $notices] = $this->resolve($cart, $user);

        return [$this->calculator->calculate($cart, $shipping, $code, $vouchers), $notices];
    }
}
