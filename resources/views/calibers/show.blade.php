<x-layouts.app>
    <div class="container-af py-10">
        <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs"/>
        <h1 class="mt-4 font-display text-3xl font-extrabold uppercase">Kaliber {{ $caliber->name }}</h1>
        @if ($caliber->aliases)<p class="mt-2 text-sm text-muted">Auch bekannt als: {{ implode(', ', $caliber->aliases) }}</p>@endif
        <div class="mt-8"><livewire:product-listing :caliber-id="$caliber->id"/></div>
        @if ($caliber->content)<article class="prose-af mt-16 max-w-4xl border-t border-line pt-10">{!! $caliber->content !!}</article>@endif
    </div>
</x-layouts.app>
