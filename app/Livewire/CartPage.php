<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesVoucherCodes;
use App\Services\Cart\CartService;
use App\Services\Compliance\CartComplianceEvaluator;
use App\Services\Vouchers\CartCodeService;
use App\Support\Seo;
use Livewire\Component;

class CartPage extends Component
{
    use HandlesVoucherCodes;

    public function mount(Seo $seo): void
    {
        $seo->title('Warenkorb')->noindex();
    }

    public function updateQuantity(int $itemId, int $quantity, CartService $cart): void
    {
        $cart->update($itemId, $quantity);
        $this->dispatch('cart-updated');
    }

    public function remove(int $itemId, CartService $cart): void
    {
        $cart->remove($itemId);
        $this->dispatch('cart-updated');
    }

    public function render(CartService $cart, CartComplianceEvaluator $evaluator, CartCodeService $codes)
    {
        $current = $cart->current();
        $current?->load('items.product.media', 'items.product.manufacturer', 'items.product.legalClass', 'items.product.taxRate', 'items.product.categories', 'items.variant');
        $evaluation = $current && $current->items->isNotEmpty() ? $evaluator->evaluate($current, auth()->user()) : null;
        $subtotal = $cart->subtotal($current);
        // Summen ohne Versand (der folgt in der Kasse); Rabatt und Gutscheine schon sichtbar
        [$totals, $codeNotices] = $current && $current->items->isNotEmpty() ? $codes->totals($current, auth()->user(), 0) : [null, []];

        return view('livewire.cart-page', [
            'cart' => $current,
            'evaluation' => $evaluation,
            'subtotal' => $subtotal,
            'totals' => $totals,
            'codeNotices' => $codeNotices,
            'freeShippingGap' => max(0, config('shop.free_shipping_from') - $subtotal),
        ]);
    }
}
