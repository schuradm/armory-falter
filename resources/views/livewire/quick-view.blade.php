<div>
    @if ($product)
        <div class="fixed inset-0 z-[75] grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Schnellansicht {{ $product->name }}" x-data x-trap.noscroll="true" @keydown.escape.window="$wire.close()">
            <div class="fx-fade-in absolute inset-0 bg-black/75 backdrop-blur-sm" wire:click="close"></div>
            <div class="card fx-pop-in relative grid max-h-[90vh] w-full max-w-4xl overflow-y-auto md:grid-cols-2">
                <button type="button" wire:click="close" class="btn-ghost absolute right-2 top-2 z-10" aria-label="Schließen"><x-icon name="x" class="size-6"/></button>
                <div class="grid aspect-square place-items-center bg-surface-2 p-6">
                    @if ($img = $product->primaryImage())<x-img :media="$img" size="medium" sizes="(min-width: 768px) 450px, 100vw" class="max-h-full object-contain"/>@else<x-icon name="box" class="size-20 text-muted"/>@endif
                </div>
                <div class="flex flex-col gap-4 p-6">
                    <p class="eyebrow">{{ $product->manufacturer?->name }}</p>
                    <h2 class="font-display text-2xl font-extrabold">{{ $product->name }}</h2>
                    <x-legal-badge :legal-class="$product->legalClass" class="self-start"/>
                    <x-price :product="$product" size="lg"/>
                    <p class="text-sm text-muted">{{ $product->short_description }}</p>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                        <dt class="text-muted">Artikelnummer</dt><dd>{{ $product->sku }}</dd>
                        @if ($product->caliber)<dt class="text-muted">Kaliber</dt><dd>{{ $product->caliber->name }}</dd>@endif
                        <dt class="text-muted">Zustand</dt><dd>{{ $product->condition_code->label() }}</dd>
                        @foreach ($product->attributes->take(4) as $a)<dt class="text-muted">{{ $a->name }}</dt><dd>{{ $a->pivot->value_text ?? rtrim(rtrim((string) $a->pivot->value_number, '0'), '.') }} {{ $a->unit }}</dd>@endforeach
                    </dl>
                    <p class="text-sm {{ $product->availability->color() }}">● {{ $product->availability->label() }}</p>
                    <a href="{{ $product->url() }}" class="btn-primary mt-auto">Zum Produkt</a>
                </div>
            </div>
        </div>
    @endif
</div>
