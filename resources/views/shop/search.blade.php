<x-layouts.app>
    <div class="container-af py-10">
        <h1 class="font-display text-3xl font-extrabold uppercase">{{ $q ? 'Suchergebnisse für „'.$q.'“' : 'Suche' }}</h1>
        <div class="mt-8"><livewire:product-listing :search="$q"/></div>
    </div>
</x-layouts.app>
