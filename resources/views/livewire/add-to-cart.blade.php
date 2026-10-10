<div class="space-y-4">
    @if ($product->variants->isNotEmpty())
        <div>
            <label for="variant" class="label">Ausführung</label>
            <select id="variant" wire:model.live="variantId" class="input">
                @foreach ($product->variants as $v)
                    <option value="{{ $v->id }}" @disabled($v->stock_quantity < 1)>{{ $v->label() }}@if($v->price_gross) – {{ \App\Support\Money::format($v->price_gross) }}@endif @if($v->stock_quantity < 1) (ausverkauft)@endif</option>
                @endforeach
            </select>
        </div>
    @endif

    @if ($product->isPurchasable())
        <div class="flex gap-3">
            @unless ($product->is_unique_item)
                <div class="flex items-center rounded-md border border-line" role="group" aria-label="Menge">
                    <button type="button" class="px-3 py-3 text-muted hover:text-fg" wire:click="$set('quantity', {{ max(1, $quantity - 1) }})" aria-label="Weniger"><x-icon name="minus" class="size-4"/></button>
                    <input type="number" min="1" max="99" wire:model.live.debounce.400ms="quantity" class="w-12 border-0 bg-transparent p-0 text-center focus:ring-0" aria-label="Menge">
                    <button type="button" class="px-3 py-3 text-muted hover:text-fg" wire:click="$set('quantity', {{ min(99, $quantity + 1) }})" aria-label="Mehr"><x-icon name="plus" class="size-4"/></button>
                </div>
            @endunless
            <button type="button" wire:click="add" wire:loading.attr="disabled" class="btn-primary flex-1">
                <x-icon name="cart" class="size-5"/> In den Warenkorb
            </button>
        </div>
    @else
        <div class="card p-4 text-sm">
            <p class="font-medium">{{ $product->availability->label() }}</p>
            <p class="mt-1 text-muted">Gerne prüfen wir die Verfügbarkeit oder reservieren für Sie.</p>
            <a href="{{ route('contact', ['betreff' => 'Anfrage '.$product->sku]) }}" class="btn-outline mt-3 w-full">Anfrage stellen</a>
        </div>
    @endif

    {{-- Ergebnis der Erwerbsprüfung für den aktuellen Kunden --}}
    @if ($product->legalClass->requires_entitlement || $product->legalClass->min_age > 0)
        <div @class(['card p-4 text-sm', 'border-emerald-500/40' => $check->allowed, 'border-amber-500/40' => ! $check->allowed])>
            <p class="flex items-center gap-2 font-medium">
                <x-icon :name="$check->allowed ? 'check' : 'shield'" @class(['size-5', 'text-emerald-400' => $check->allowed, 'text-amber-400' => ! $check->allowed])/>
                {{ $check->allowed ? 'Ihre Unterlagen passen zu diesem Artikel.' : 'Erwerbsnachweis erforderlich' }}
            </p>
            @foreach ($check->reasons as $reason)<p class="mt-1 text-muted">{{ $reason }}</p>@endforeach
            @if (! $check->allowed && $check->hint && isset($hints[$check->hint]))
                <a href="{{ $hints[$check->hint][1] }}" class="mt-3 inline-flex items-center gap-1 text-accent">{{ $hints[$check->hint][0] }} <x-icon name="arrow-right" class="size-4"/></a>
            @endif
            @if (! $check->allowed)
                <p class="mt-2 text-xs text-muted">Sie können den Artikel trotzdem in den Warenkorb legen. Bestellen ist möglich, sobald Ihre Unterlagen geprüft sind. Alternativ reservieren wir ihn zur Abholung.</p>
            @endif
        </div>
    @endif
</div>
