<x-layouts.app>
    <section class="relative overflow-hidden border-b border-line bg-ink" style="--af-fg:#fff;--af-muted:#bdbdbd">
        @if ($manufacturer->hero_image_path)<img src="{{ asset('storage/'.$manufacturer->hero_image_path) }}" alt="" class="absolute inset-0 size-full object-cover opacity-30" fetchpriority="high">@endif
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_80%_30%,rgba(157,70,64,0.15),transparent_60%)]"></div>
        <div class="container-af relative py-16 text-white">
            <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs"/>
            <p class="eyebrow mt-6">Autorisierter Fachhändler</p>
            <h1 class="mt-3 font-display text-4xl font-extrabold uppercase sm:text-5xl">{{ $manufacturer->name }}</h1>
            @if ($manufacturer->intro)<p class="mt-4 max-w-2xl text-lg text-white/75">{{ $manufacturer->intro }}</p>@endif
            <div class="mt-6 flex gap-6 text-sm text-white/60">
                @if ($manufacturer->country_code)<span>Herkunft: {{ \Locale::getDisplayRegion('-'.$manufacturer->country_code, 'de') }}</span>@endif
                @if ($manufacturer->founded_year)<span>Gegründet {{ $manufacturer->founded_year }}</span>@endif
            </div>
        </div>
    </section>
    <div class="container-af py-12">
        <livewire:product-listing :manufacturer-id="$manufacturer->id"/>
        @if ($manufacturer->content)<article class="prose-af mt-16 max-w-4xl border-t border-line pt-10">{!! $manufacturer->content !!}</article>@endif
        @if ($manufacturer->faqs->isNotEmpty())<section class="mt-12 max-w-4xl"><h2 class="font-display text-2xl font-bold">Häufige Fragen zu {{ $manufacturer->name }}</h2><x-faq-list :faqs="$manufacturer->faqs" class="mt-6"/></section>@endif
    </div>
</x-layouts.app>
