<?php

namespace App\Livewire;

use App\Services\Cart\CartService;
use App\Services\Compliance\CartComplianceEvaluator;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Slide-over-Warenkorb. Lädt Positionen erst beim Öffnen (kein DB-Zugriff beim Seitenaufbau),
 * öffnet sich automatisch nach „In den Warenkorb“.
 */
class MiniCart extends Component
{
    public bool $loaded = false;

    public ?int $justAdded = null;

    #[On('cart-updated')]
    public function onCartUpdated(?int $added = null): void
    {
        $this->loaded = true;
        $this->justAdded = $added;
    }

    #[On('open-mini-cart')]
    public function load(): void
    {
        $this->loaded = true;
    }

    public function updateQuantity(int $itemId, int $quantity, CartService $cart): void
    {
        $cart->update($itemId, $quantity);
        $this->dispatch('cart-updated');
    }

    public function remove(int $itemId, CartService $cart): void
    {
        $cart->remove($itemId);
        $this->justAdded = null;
        $this->dispatch('cart-updated');
    }

    public function render(CartService $cart, CartComplianceEvaluator $evaluator)
    {
        if (! $this->loaded) {
            return view('livewire.mini-cart', ['suggestions' => collect(), 'cart' => null, 'evaluation' => null, 'subtotal' => 0, 'freeFrom' => 0, 'progress' => 0]);
        }

        $current = $cart->current();
        $current?->load('items.product.media', 'items.product.manufacturer', 'items.product.legalClass', 'items.variant');
        $hasItems = $current && $current->items->isNotEmpty();
        $subtotal = $cart->subtotal($current);
        $freeFrom = (int) config('shop.free_shipping_from');

        // Upsell: Zubehör zum zuletzt hinzugefügten Artikel, das noch nicht im Warenkorb liegt
        $inCart = $hasItems ? $current->items->pluck('product_id')->all() : [];
        $suggestions = $this->justAdded
            ? \App\Models\Product::find($this->justAdded)?->accessories()->with(['media', 'legalClass', 'taxRate', 'variants'])
                ->whereNotIn('products.id', $inCart)->take(6)->get()->filter->isPurchasable()->take(3)
            : collect();

        return view('livewire.mini-cart', [
            'suggestions' => $suggestions ?? collect(),
            'cart' => $hasItems ? $current : null,
            'evaluation' => $hasItems ? $evaluator->evaluate($current, auth()->user()) : null,
            'subtotal' => $subtotal,
            'freeFrom' => $freeFrom,
            'progress' => $freeFrom > 0 ? min(100, (int) round($subtotal / $freeFrom * 100)) : 100,
        ]);
    }
}
