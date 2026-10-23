<?php

namespace App\Livewire\Account;

use App\Models\Product;
use App\Models\Wishlist;
use App\Services\Cart\CartService;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

/** Favoriten und mehrere benannte Merklisten, teilbar per Link. */
class Wishlists extends Component
{
    public ?int $activeId = null;

    public string $newName = '';

    public function mount(Seo $seo): void
    {
        $seo->title('Merklisten')->noindex();
        $this->activeId = Auth::user()->defaultWishlist()->id;
    }

    protected function list(int $id): Wishlist
    {
        return Auth::user()->wishlists()->findOrFail($id);
    }

    public function create(): void
    {
        $this->validate(['newName' => ['required', 'string', 'max:100']]);
        $this->activeId = Auth::user()->wishlists()->create(['name' => $this->newName])->id;
        $this->newName = '';
    }

    public function remove(int $productId): void
    {
        $this->list($this->activeId)->products()->detach($productId);
    }

    public function toCart(int $productId, CartService $cart): void
    {
        $product = Product::visible(Auth::user())->findOrFail($productId);
        try {
            $cart->add($product);
            $this->dispatch('cart-updated');
            $this->dispatch('toast', message: 'In den Warenkorb gelegt.');
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', message: $e->getMessage());
        }
    }

    public function share(): void
    {
        $list = $this->list($this->activeId);
        $list->update(['share_token' => $list->share_token ?? Str::random(32)]);
    }

    public function deleteList(): void
    {
        $list = $this->list($this->activeId);
        if (! $list->is_default) {
            $list->delete();
            $this->activeId = Auth::user()->defaultWishlist()->id;
        }
    }

    public function render(): View
    {
        $lists = Auth::user()->wishlists()->withCount('products')->orderByDesc('is_default')->get();
        $active = $lists->firstWhere('id', $this->activeId) ?? $lists->first();
        $products = $active->products()->with(['manufacturer', 'caliber', 'legalClass', 'taxRate', 'media'])->get();

        return view('livewire.account.wishlists', compact('lists', 'active', 'products'))
            ->layout('components.layouts.account', ['active' => 'wishlists']);
    }
}
