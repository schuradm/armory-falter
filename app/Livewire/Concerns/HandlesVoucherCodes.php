<?php

namespace App\Livewire\Concerns;

use App\Services\Cart\CartService;
use App\Services\Vouchers\CartCodeService;
use App\Services\Vouchers\VoucherException;
use Illuminate\Support\Facades\Auth;

/** Eingabefeld „Gutschein- oder Rabattcode“ für Warenkorb und Kasse (Codes liegen am Warenkorb). */
trait HandlesVoucherCodes
{
    public string $voucherCode = '';

    public ?string $codeMessage = null;

    public function applyCode(CartService $carts, CartCodeService $codes): void
    {
        $this->codeMessage = null;
        $this->resetErrorBag('voucherCode');
        if (! $cart = $carts->current()) {
            return;
        }
        try {
            $this->codeMessage = $codes->apply($cart, $this->voucherCode, Auth::user());
            $this->voucherCode = '';
        } catch (VoucherException $e) {
            $this->addError('voucherCode', $e->getMessage());
        }
    }

    public function removeDiscountCode(CartService $carts, CartCodeService $codes): void
    {
        if ($cart = $carts->current()) {
            $codes->removeDiscountCode($cart);
        }
        $this->codeMessage = null;
    }

    public function removeGiftVoucher(string $code, CartService $carts, CartCodeService $codes): void
    {
        if ($cart = $carts->current()) {
            $codes->removeGiftVoucher($cart, $code);
        }
        $this->codeMessage = null;
    }
}
