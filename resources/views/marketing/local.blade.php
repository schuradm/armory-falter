@php($faqs = collect($page->faqs ?? [])->map(fn ($f) => (object) ['question' => $f['question'] ?? '', 'answer' => $f['answer'] ?? '']))
<x-layouts.app>
    <section class="fx-aurora relative bg-ink py-16 text-white sm:py-24" style="--af-fg:#fff;--af-muted:#c4c4c4">
        <div class="container-af relative">
            <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs"/>
            <p class="eyebrow mt-6">Ihr Waffenhändler für {{ $page->city }}</p>
            <h1 class="mt-4 max-w-4xl font-display text-4xl font-extrabold uppercase sm:text-5xl">{{ $page->headline }}</h1>
            <p class="mt-5 max-w-2xl text-lg text-white/80">@if ($page->distance_km)Nur rund {{ $page->distance_km }} km von {{ $page->city }}: @endif Armory Falter in {{ config('shop.company.city') }}, {{ config('shop.company.street') }}.</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('appointments.consultation') }}" class="btn-primary fx-shine px-7 py-4">Beratung vereinbaren</a>
                <a href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode(config('shop.company.street').', '.config('shop.company.postal_code').' '.config('shop.company.city')) }}" target="_blank" rel="noopener" class="btn-outline border-white/35 px-7 py-4 text-white"><x-icon name="map-pin" class="size-4"/> Route planen</a>
            </div>
        </div>
    </section>
    <div class="container-af grid gap-12 py-16 lg:grid-cols-[1fr_340px]">
        <article class="prose-af">{!! $page->body !!}</article>
        <aside class="space-y-5">@include('pages.partials.opening-hours')</aside>
    </div>
    @if ($products->isNotEmpty())
        <section class="container-af pb-16">
            <x-section-heading eyebrow="Beliebt" title="Empfehlungen aus unserem Sortiment" :link="route('shop.index')"/>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">@foreach ($products as $p)<x-product-card :product="$p"/>@endforeach</div>
        </section>
    @endif
    @if ($faqs->isNotEmpty())
        <section class="container-af max-w-4xl pb-16"><x-section-heading eyebrow="FAQ" title="Häufige Fragen"/><x-faq-list :faqs="$faqs"/></section>
    @endif
</x-layouts.app>
