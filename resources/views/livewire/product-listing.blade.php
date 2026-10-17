<div x-data="{ filters: false }" class="grid gap-8 lg:grid-cols-[260px_1fr]">
    {{-- Filter --}}
    {{-- Mobil standardmäßig zu (statisch "hidden", sonst blitzt der Filter vor dem JS-Start auf → Layout-Shift) --}}
    <aside data-alpine-chunk class="hidden lg:block" :class="filters && 'fixed inset-0 z-[60] block! overflow-y-auto bg-bg p-4'" aria-label="Filter">
        <div class="mb-4 flex items-center justify-between lg:hidden">
            <p class="font-display text-lg font-bold">Filter</p>
            <button type="button" class="btn-ghost" @click="filters = false" aria-label="Filter schließen"><x-icon name="x" class="size-6"/></button>
        </div>
        <div class="space-y-6 lg:sticky lg:top-28">
            @if ($activeCount)
                <button type="button" wire:click="resetFilters" class="flex items-center gap-1 text-sm text-accent"><x-icon name="x" class="size-4"/> Alle Filter zurücksetzen ({{ $activeCount }})</button>
            @endif

            <fieldset>
                <legend class="label">Einsatzbereich</legend>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['' => 'Alle', 'jagd' => 'Jagd', 'sport' => 'Sport'] as $val => $lbl)
                        <label class="cursor-pointer"><input type="radio" wire:model.live="usage" value="{{ $val }}" class="peer sr-only">
                            <span class="block rounded-md border border-line px-2 py-2 text-center text-sm peer-checked:border-accent peer-checked:text-accent">{{ $lbl }}</span></label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset>
                <legend class="label">Zustand</legend>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['' => 'Alle', 'new' => 'Neu', 'used' => 'Gebraucht'] as $val => $lbl)
                        <label class="cursor-pointer"><input type="radio" wire:model.live="condition" value="{{ $val }}" class="peer sr-only">
                            <span class="block rounded-md border border-line px-2 py-2 text-center text-sm peer-checked:border-accent peer-checked:text-accent">{{ $lbl }}</span></label>
                    @endforeach
                </div>
            </fieldset>

            <label class="flex items-center gap-3 text-sm"><input type="checkbox" wire:model.live="inStock" class="rounded border-line bg-surface-2 text-accent focus:ring-accent"> Nur sofort lieferbar</label>

            <fieldset>
                <legend class="label">Preis (€)</legend>
                <div class="flex items-center gap-2">
                    <input type="number" min="0" inputmode="numeric" wire:model.live.debounce.600ms="priceFrom" placeholder="von" class="input py-2 text-sm" aria-label="Preis von">
                    <span class="text-muted">–</span>
                    <input type="number" min="0" inputmode="numeric" wire:model.live.debounce.600ms="priceTo" placeholder="bis" class="input py-2 text-sm" aria-label="Preis bis">
                </div>
            </fieldset>

            @if (! $manufacturerId && $this->facets['brands']->isNotEmpty())
                <fieldset x-data="{ all: false }">
                    <legend class="label">Hersteller</legend>
                    <div class="space-y-1.5">
                        @foreach ($this->facets['brands'] as $i => $brand)
                            <label class="flex items-center justify-between gap-2 text-sm" @if($i >= 8) x-show="all" x-cloak @endif>
                                <span class="flex items-center gap-2"><input type="checkbox" wire:model.live="brands" value="{{ $brand->slug }}" class="rounded border-line bg-surface-2 text-accent focus:ring-accent">{{ $brand->name }}</span>
                                <span class="text-xs text-muted">{{ $brand->products_count }}</span>
                            </label>
                        @endforeach
                    </div>
                    @if ($this->facets['brands']->count() > 8)<button type="button" class="mt-2 text-xs text-accent" @click="all = !all" x-text="all ? 'Weniger' : 'Alle anzeigen'"></button>@endif
                </fieldset>
            @endif

            @if (! $caliberId && $this->facets['calibers']->isNotEmpty())
                <fieldset x-data="{ all: false }">
                    <legend class="label">Kaliber</legend>
                    <div class="space-y-1.5">
                        @foreach ($this->facets['calibers'] as $i => $cal)
                            <label class="flex items-center justify-between gap-2 text-sm" @if($i >= 8) x-show="all" x-cloak @endif>
                                <span class="flex items-center gap-2"><input type="checkbox" wire:model.live="calibers" value="{{ $cal->slug }}" class="rounded border-line bg-surface-2 text-accent focus:ring-accent">{{ $cal->name }}</span>
                                <span class="text-xs text-muted">{{ $cal->products_count }}</span>
                            </label>
                        @endforeach
                    </div>
                    @if ($this->facets['calibers']->count() > 8)<button type="button" class="mt-2 text-xs text-accent" @click="all = !all" x-text="all ? 'Weniger' : 'Alle anzeigen'"></button>@endif
                </fieldset>
            @endif

            <button type="button" class="btn-primary w-full lg:hidden" @click="filters = false">{{ $products->total() }} Artikel anzeigen</button>
        </div>
    </aside>

    {{-- Ergebnisse --}}
    <section data-alpine-chunk aria-live="polite">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-ui text-sm font-normal text-muted"><span class="font-semibold text-fg">{{ $products->total() }}</span> Artikel</h2>
            <div class="flex items-center gap-2">
                <button type="button" class="btn-outline py-2 lg:hidden" @click="filters = true"><x-icon name="filter" class="size-4"/> Filter @if($activeCount)({{ $activeCount }})@endif</button>
                <label class="sr-only" for="sort">Sortierung</label>
                <select id="sort" wire:model.live="sort" class="input w-auto py-2 text-sm">
                    <option value="relevanz">Empfehlung</option>
                    <option value="neu">Neueste zuerst</option>
                    <option value="preis_auf">Preis aufsteigend</option>
                    <option value="preis_ab">Preis absteigend</option>
                    <option value="name">Name A–Z</option>
                </select>
            </div>
        </div>

        <div wire:loading.class="opacity-50" class="grid grid-cols-1 gap-5 transition sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($products as $product)
                {{-- Erste Reihe (Desktop: 3 Karten) sofort und mit hoher Priorität laden – das LCP-Bild kann jede der drei sein; Rest lazy --}}
                <x-product-card :product="$product" :priority="$loop->index < 3 ? 'high' : false" wire:key="p-{{ $product->id }}"/>
            @empty
                <div class="card col-span-full p-10 text-center">
                    <x-icon name="search" class="mx-auto size-10 text-muted"/>
                    <p class="mt-3 font-display text-lg font-bold">Keine Artikel gefunden</p>
                    <p class="mt-1 text-sm text-muted">Passen Sie die Filter an oder fragen Sie uns: Viele Artikel besorgen wir kurzfristig.</p>
                    <div class="mt-5 flex justify-center gap-3"><button type="button" wire:click="resetFilters" class="btn-outline">Filter zurücksetzen</button><a href="{{ route('contact') }}" class="btn-primary">Anfrage stellen</a></div>
                </div>
            @endforelse
        </div>

        <div class="mt-10">{{ $products->onEachSide(1)->links(data: ['scrollTo' => '#inhalt']) }}</div>
    </section>
</div>
