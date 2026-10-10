<x-layouts.account active="downloads">
    <h1 class="font-display text-2xl font-extrabold uppercase">Downloads</h1>
    <p class="mt-2 text-sm text-muted">Bedienungsanleitungen und Dokumente zu Ihren gekauften Produkten.</p>
    <div class="mt-6 space-y-4">
        @forelse ($downloads as $product => $docs)
            <div class="card p-5"><p class="font-display font-bold">{{ $product }}</p>
                <ul class="mt-2 space-y-1 text-sm">@foreach ($docs as $d)<li><a href="{{ route('product-document.download', $d) }}" class="inline-flex items-center gap-2 text-accent"><x-icon name="file" class="size-4"/>{{ $d->title }} <span class="text-xs text-muted">({{ $d->typeLabel() }})</span></a></li>@endforeach</ul></div>
        @empty
            <p class="card p-6 text-sm text-muted">Keine Downloads vorhanden.</p>
        @endforelse
    </div>
</x-layouts.account>
