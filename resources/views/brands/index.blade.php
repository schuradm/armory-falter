<x-layouts.app>
    <div class="container-af py-10">
        <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs"/>
        <h1 class="mt-4 font-display text-3xl font-extrabold uppercase">Hersteller A–Z</h1>
        <nav class="mt-6 flex flex-wrap gap-2 text-sm" aria-label="Buchstaben">@foreach ($brands->keys() as $l)<a href="#b-{{ $l }}" class="badge border border-line px-2.5 py-1 hover:border-accent">{{ $l }}</a>@endforeach</nav>
        <div class="mt-10 space-y-10">
            @foreach ($brands as $letter => $group)
                <section id="b-{{ $letter }}"><h2 class="font-display text-2xl font-bold text-accent">{{ $letter }}</h2>
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">@foreach ($group as $b)<a href="{{ route('brands.show', $b) }}" class="card p-4 hover:border-accent"><span class="font-display font-bold">{{ $b->name }}</span><span class="block text-xs text-muted">{{ $b->products_count }} Artikel</span></a>@endforeach</div></section>
            @endforeach
        </div>
    </div>
</x-layouts.app>
