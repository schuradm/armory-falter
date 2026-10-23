<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class WishlistToggle extends Component
{
    #[Locked]
    public int $productId;

    public bool $active = false;

    public function mount(int $productId): void
    {
        $this->productId = $productId;
        $this->active = Auth::check() && Auth::user()->wishlists()->whereHas('products', fn ($q) => $q->whereKey($productId))->exists();
    }

    public function toggle(): void
    {
        if (! Auth::check()) {
            session()->put('url.intended', url()->previous());

            $this->redirectRoute('login');

            return;
        }
        $list = Auth::user()->defaultWishlist();
        $list->products()->toggle([$this->productId]);
        $this->active = ! $this->active;
        $this->dispatch('toast', message: $this->active ? 'Zur Merkliste hinzugefügt.' : 'Von der Merkliste entfernt.');
    }

    public function render(): string
    {
        return <<<'BLADE'
            <button type="button" wire:click="toggle" class="grid size-9 place-items-center rounded-full border border-line bg-surface/90 backdrop-blur hover:text-accent {{ $active ? 'text-accent border-accent' : '' }}"
                    aria-pressed="{{ $active ? 'true' : 'false' }}" aria-label="Merkliste">
                <x-icon name="heart" class="size-4 {{ $active ? 'fill-current' : '' }}"/>
            </button>
        BLADE;
    }
}
