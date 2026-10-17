@props(['product', 'priority' => false])
@php($img = $product->primaryImage())
<article class="card fx-card group relative flex flex-col overflow-hidden">
    <div class="relative">
        <a href="{{ $product->url() }}" data-vt-product class="relative block aspect-[4/3] overflow-hidden bg-gradient-to-br from-surface-2 to-bg" tabindex="-1" aria-hidden="true">
            @if ($img)
                <x-img :media="$img" size="card" sizes="(min-width: 1024px) 330px, (min-width: 640px) calc(50vw - 34px), calc(100vw - 32px)" :priority="$priority" class="size-full object-contain transition duration-700 ease-out-expo group-hover:scale-[1.06]"/>
            @else
                <div class="grid size-full place-items-center text-muted/40"><x-icon :name="match($product->product_type->value) { 'weapon' => $product->weapon_kind === 'kurzwaffe' ? 'pistol' : 'rifle', 'optic' => 'scope', 'ammo' => 'target', 'knife' => 'knife', 'apparel' => 'shirt', default => 'box' }" class="size-16"/></div>
            @endif
            <span class="fx-card-glow pointer-events-none absolute inset-0" aria-hidden="true"></span>
        </a>
        <div class="pointer-events-none absolute left-3 top-3 flex flex-col items-start gap-1">
            @if ($product->hasActiveSpecialPrice())<span class="badge fx-shine bg-brand text-accent-fg">Angebot</span>@endif
            @if ($product->condition_code->value !== 'new')<span class="badge bg-surface text-fg">{{ $product->condition_code->label() }}</span>@endif
        </div>
        <div class="absolute right-3 top-3 z-10 flex flex-col gap-1.5 transition duration-300 sm:translate-x-2 sm:opacity-0 sm:group-hover:translate-x-0 sm:group-hover:opacity-100 sm:group-focus-within:translate-x-0 sm:group-focus-within:opacity-100">
            <button type="button" x-data @click="$store.wishlist.toggle({{ $product->id }})"
                    class="grid size-9 place-items-center rounded-full border border-line bg-surface/90 backdrop-blur hover:text-accent"
                    :class="$store.wishlist.has({{ $product->id }}) && 'text-accent border-accent'" :aria-pressed="$store.wishlist.has({{ $product->id }})" aria-label="Merkliste">
                <x-icon name="heart" class="size-4" ::class="$store.wishlist.has({{ $product->id }}) && 'fill-current'"/>
            </button>
            <button type="button" x-data @click="$store.compare.toggle({ id: {{ $product->id }}, name: @js($product->name) })"
                    class="grid size-9 place-items-center rounded-full border border-line bg-surface/90 backdrop-blur hover:text-accent"
                    :class="$store.compare.has({{ $product->id }}) && 'text-accent border-accent'" aria-label="Zum Vergleich hinzufügen">
                <x-icon name="compare" class="size-4"/>
            </button>
            <button type="button" x-data @click="Livewire.dispatch('quick-view', { productId: {{ $product->id }} })"
                    class="grid size-9 place-items-center rounded-full border border-line bg-surface/90 backdrop-blur hover:text-accent" aria-label="Schnellansicht">
                <x-icon name="eye" class="size-4"/>
            </button>
        </div>
        @if ($product->isPurchasable())
            {{-- Schnellkauf: gleitet bei Hover ein, auf Touch-Geräten immer sichtbar --}}
            <button type="button" x-data="{ busy: false }" :disabled="busy"
                    @click="busy = true; $store.cart.add({{ $product->id }}).finally(() => busy = false)"
                    class="fx-quickadd absolute bottom-3 right-3 z-10 grid size-10 place-items-center rounded-full bg-brand text-accent-fg shadow-[0_8px_24px_-6px_rgb(157_70_64/0.7)]"
                    aria-label="{{ $product->name }} in den Warenkorb">
                <x-icon name="plus" class="size-5" x-show="!busy"/>
                <span x-show="busy" x-cloak class="size-4 animate-spin rounded-full border-2 border-accent-fg/30 border-t-accent-fg"></span>
            </button>
        @endif
    </div>
    <div class="flex flex-1 flex-col gap-2 p-4">
        <div class="flex items-center justify-between gap-2">
            <p class="font-ui text-[11px] font-semibold tracking-[0.18em] text-muted uppercase">{{ $product->manufacturer?->name }}</p>
            <x-legal-badge :legal-class="$product->legalClass"/>
        </div>
        <h3 class="font-display text-base leading-snug font-bold"><a href="{{ $product->url() }}" data-vt-product class="hover:text-accent">{{ $product->name }}</a></h3>
        @if ($product->caliber)<p class="text-xs text-muted">Kaliber {{ $product->caliber->name }}</p>@endif
        @if ($product->rating_count > 0)<x-stars :rating="$product->rating_avg" :count="$product->rating_count" size="size-3.5"/>@endif
        <div class="mt-auto flex items-end justify-between gap-2 pt-2">
            <x-price :product="$product" class="shrink-0"/>
            <span class="text-right text-xs {{ $product->availability->color() }}">● {{ $product->availability->label() }}</span>
        </div>
    </div>
</article>
