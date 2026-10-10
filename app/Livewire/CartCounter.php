<?php

namespace App\Livewire;

use App\Services\Cart\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCounter extends Component
{
    public int $count = 0;

    #[On('cart-updated')]
    public function refresh(CartService $cart): void
    {
        $this->count = $cart->count();
    }

    public function render()
    {
        return <<<'BLADE'
            <a href="{{ route('cart') }}" x-data="{ bump: false }" x-on:cart-updated.window="bump = true; setTimeout(() => bump = false, 500)"
               @click.prevent="Livewire.dispatch('open-mini-cart')" class="btn-ghost relative" aria-label="Warenkorb öffnen, {{ $count }} Artikel" data-no-prerender>
                <x-icon name="bag"/>
                @if ($count > 0)
                    <span class="absolute right-0.5 top-0.5 grid min-w-[1.125rem] place-items-center rounded-full bg-brand px-1 text-[10px] leading-[1.125rem] font-bold text-white" :class="bump && 'fx-bump'">{{ $count }}</span>
                @endif
            </a>
        BLADE;
    }
}
