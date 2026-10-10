<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
    <form action="{{ route('search') }}" method="get" role="search">
        <label for="search-{{ $mobile ? 'm' : 'd' }}" class="sr-only">Suche</label>
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted"/>
            <input id="search-{{ $mobile ? 'm' : 'd' }}" type="search" name="q" autocomplete="off" placeholder="Suche nach Modell, Marke, Kaliber …"
                   wire:model.live.debounce.250ms="q" @focus="open = true" @input="open = true"
                   class="input rounded-full py-2 pl-9 pr-3 text-sm" role="combobox" aria-autocomplete="list" :aria-expanded="open">
        </div>
    </form>
    @if (mb_strlen(trim($q)) >= 2)
        <div x-show="open" x-cloak x-transition.opacity class="card absolute inset-x-0 top-full z-50 mt-2 max-h-[70vh] overflow-y-auto p-2 text-sm" role="listbox">
            @forelse ($products as $p)
                <a href="{{ $p->url() }}" class="flex items-center gap-3 rounded-md p-2 hover:bg-surface-2" role="option">
                    <span class="grid size-12 shrink-0 place-items-center overflow-hidden rounded bg-surface-2">
                        @if ($img = $p->primaryImage())<img src="{{ $img->url() }}" alt="" class="size-full object-contain" loading="lazy">@else<x-icon name="box" class="size-5 text-muted"/>@endif
                    </span>
                    <span class="min-w-0 flex-1"><span class="block truncate font-medium">{{ $p->name }}</span><span class="text-xs text-muted">{{ $p->manufacturer?->name }}</span></span>
                    <span class="font-display font-bold">{{ \App\Support\Money::format($p->currentPrice()) }}</span>
                </a>
            @empty
                @if ($brands->isEmpty() && $categories->isEmpty())<p class="p-3 text-muted">Keine Treffer für „{{ $q }}“.</p>@endif
            @endforelse
            @if ($brands->isNotEmpty() || $categories->isNotEmpty())
                <div class="mt-1 flex flex-wrap gap-2 border-t border-line p-2">
                    @foreach ($brands as $b)<a href="{{ route('brands.show', $b) }}" class="badge bg-surface-2 normal-case tracking-normal hover:text-accent">Marke: {{ $b->name }}</a>@endforeach
                    @foreach ($categories as $c)<a href="{{ $c->url() }}" class="badge bg-surface-2 normal-case tracking-normal hover:text-accent">Kategorie: {{ $c->name }}</a>@endforeach
                </div>
            @endif
            <a href="{{ route('search', ['q' => $q]) }}" class="mt-1 block rounded-md p-2 text-center text-accent hover:bg-surface-2">Alle Ergebnisse anzeigen</a>
        </div>
    @endif
</div>
