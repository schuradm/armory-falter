@props(['product', 'size' => 'md'])
@php
    $special = $product->hasActiveSpecialPrice();
    $cls = $size === 'lg' ? 'text-3xl' : 'text-lg';
@endphp
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-baseline gap-x-3 gap-y-1']) }}>
    <span class="font-display font-bold whitespace-nowrap {{ $cls }} {{ $special ? 'text-accent' : '' }}">{{ \App\Support\Money::format($product->currentPrice()) }}</span>
    @if ($special)
        {{-- § 11 PAngV: Referenz ist der niedrigste Preis der letzten 30 Tage --}}
        <span class="text-sm text-muted line-through">{{ \App\Support\Money::format($product->referencePrice()) }}</span>
        <span class="badge bg-brand text-accent-fg">−{{ max(1, (int) round(100 - $product->currentPrice() / max(1, $product->referencePrice()) * 100)) }} %</span>
    @endif
    @if ($size === 'lg' && $product->isGiftVoucher())
        <span class="w-full text-xs text-muted">Mehrzweck-Gutschein: beim Kauf keine Umsatzsteuer, sie fällt erst bei Einlösung an. Versand kostenlos per E-Mail.</span>
    @elseif ($size === 'lg')
        <span class="w-full text-xs text-muted">
            inkl. {{ rtrim(rtrim(number_format((float) $product->taxRate->rate, 2, ',', ''), '0'), ',') }} % MwSt., zzgl. <a href="{{ route('page.show', 'versandinformationen') }}" class="underline hover:text-fg">Versand</a>
            @if ($special) · niedrigster Preis der letzten 30 Tage: {{ \App\Support\Money::format($product->referencePrice()) }} @endif
        </span>
    @endif
</div>
