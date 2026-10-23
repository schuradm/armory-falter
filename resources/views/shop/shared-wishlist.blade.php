<x-layouts.app>
    <div class="container-af space-y-6 py-10">
        <h1 class="font-display text-2xl font-extrabold uppercase">Merkliste: {{ $list->name }}</h1>
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($products as $p)
                <x-product-card :product="$p"/>
            @empty
                <p class="card col-span-full p-8 text-center text-sm text-muted">Diese Merkliste ist leer.</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
