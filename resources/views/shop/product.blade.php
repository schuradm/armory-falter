@php($images = $product->media->where('type', 'image')->values())
<x-layouts.app>
    <div class="container-af py-8">
        <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs"/>
        <div class="mt-6 grid gap-10 lg:grid-cols-[1.1fr_1fr]">
            {{-- Galerie --}}
            <div x-data="{ active: 0, count: {{ max(1, $images->count()) }} }" class="space-y-3">
                <div class="card fx-zoom relative grid aspect-[4/3] place-items-center overflow-hidden bg-gradient-to-br from-surface-2 to-bg" style="view-transition-name: product-hero"
                     x-data="{ zx: 50, zy: 50, z: false }" @pointermove="if ($event.pointerType === 'mouse') { const r = $el.getBoundingClientRect(); zx = ($event.clientX - r.left) / r.width * 100; zy = ($event.clientY - r.top) / r.height * 100; z = true }" @pointerleave="z = false">
                    @forelse ($images as $i => $img)
                        <x-img :media="$img" size="large" sizes="(min-width: 1280px) 616px, (min-width: 1024px) 50vw, calc(100vw - 32px)" :priority="$i === 0" x-show="active === {{ $i }}" :x-cloak="$i > 0"
                               class="size-full object-contain transition-transform duration-300 ease-out" ::style="z && `transform: scale(1.8); transform-origin: ${zx}% ${zy}%`"/>
                    @empty
                        <x-icon :name="$product->weapon_kind === 'kurzwaffe' ? 'pistol' : ($product->product_type->value === 'optic' ? 'scope' : 'rifle')" class="size-32 text-muted/30"/>
                    @endforelse
                    @if ($images->count() > 1)
                        <button type="button" class="absolute left-2 top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-surface/80" @click="active = (active - 1 + count) % count" aria-label="Vorheriges Bild"><x-icon name="chevron-right" class="size-5 rotate-180"/></button>
                        <button type="button" class="absolute right-2 top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-surface/80" @click="active = (active + 1) % count" aria-label="Nächstes Bild"><x-icon name="chevron-right" class="size-5"/></button>
                    @endif
                </div>
                @if ($images->count() > 1)
                    <div class="grid grid-cols-5 gap-2">@foreach ($images as $i => $img)<button type="button" @click="active = {{ $i }}" class="card aspect-square overflow-hidden p-1" :class="active === {{ $i }} && 'border-accent'" aria-label="Bild {{ $i + 1 }}"><x-img :media="$img" size="thumb" sizes="96px" alt="" class="size-full object-contain"/></button>@endforeach</div>
                @endif
                @if ($product->video_url)
                    <div class="card aspect-video overflow-hidden" x-data="{ ok: window.afConsent?.media }" @consent-updated.window="ok = $event.detail.media">
                        @if (str_contains($product->video_url, 'youtube'))
                            <template x-if="ok"><iframe class="size-full" src="{{ str_replace('youtube.com', 'youtube-nocookie.com', $product->video_url) }}" title="Produktvideo" allow="encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe></template>
                            <div x-show="!ok" class="grid size-full place-items-center p-6 text-center text-sm text-muted"><div><x-icon name="play" class="mx-auto size-10 text-accent"/><p class="mt-2">Das Video wird von YouTube geladen.</p><button type="button" class="btn-outline mt-3 py-2" @click="$dispatch('open-consent')">Externe Medien erlauben</button></div></div>
                        @else
                            <video controls preload="none" class="size-full" src="{{ $product->video_url }}"></video>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Kaufbereich --}}
            <div class="space-y-5">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($product->manufacturer)<a href="{{ route('brands.show', $product->manufacturer) }}" class="eyebrow hover:underline">{{ $product->manufacturer->name }}</a>@endif
                    <x-legal-badge :legal-class="$product->legalClass"/>
                    @if ($product->condition_code->value !== 'new')<span class="badge bg-surface-2">{{ $product->condition_code->label() }}</span>@endif
                </div>
                <h1 class="font-display text-3xl leading-tight font-extrabold sm:text-4xl">{{ $product->name }}</h1>
                <p class="text-sm text-muted">Art.-Nr. {{ $product->sku }}@if($product->caliber) · Kaliber <a href="{{ route('calibers.show', $product->caliber) }}" class="hover:text-accent">{{ $product->caliber->name }}</a>@endif</p>
                <x-price :product="$product" size="lg"/>
                <p class="text-sm {{ $product->availability->color() }}">● {{ $product->availability->label() }}@if($product->delivery_time) · Lieferzeit {{ $product->delivery_time }}@endif</p>
                @if ($product->short_description)<p class="text-fg/85">{{ $product->short_description }}</p>@endif

                <livewire:add-to-cart :product="$product"/>

                @if ($product->legalClass->notice_text)
                    <p class="flex gap-2 rounded-md bg-surface-2 p-3 text-xs text-muted"><x-icon name="info" class="size-4 shrink-0 text-accent"/>{{ $product->legalClass->notice_text }}</p>
                @endif
                <div class="flex gap-4 text-sm">
                    <button type="button" x-data @click="$store.wishlist.toggle({{ $product->id }})" class="flex items-center gap-1.5 text-muted hover:text-accent" :class="$store.wishlist.has({{ $product->id }}) && 'text-accent'" :aria-pressed="$store.wishlist.has({{ $product->id }})"><x-icon name="heart" class="size-4" ::class="$store.wishlist.has({{ $product->id }}) && 'fill-current'"/> Merken</button>
                    <button type="button" x-data @click="$store.compare.toggle({ id: {{ $product->id }}, name: @js($product->name) })" class="flex items-center gap-1.5 text-muted hover:text-accent"><x-icon name="compare" class="size-4"/> Vergleichen</button>
                    <a href="{{ route('appointments.consultation') }}" class="flex items-center gap-1.5 text-muted hover:text-accent"><x-icon name="calendar" class="size-4"/> Beratung</a>
                </div>
                <ul class="grid gap-2 border-t border-line pt-5 text-sm text-muted">
                    <li class="flex gap-2"><x-icon name="truck" class="size-5 text-accent"/>{{ $product->legalClass->shipping_mode->label() }}</li>
                    <li class="flex gap-2"><x-icon name="map-pin" class="size-5 text-accent"/>Abholung in Wolfschlugen möglich</li>
                </ul>

                {{-- Verkaufspsychologie: „Warum Armory Falter?“ (Texte im Backend pflegbar) --}}
                <div class="card fx-spotlight p-5">
                    <h2 class="font-display text-sm font-extrabold tracking-wider uppercase">Warum Armory Falter?</h2>
                    <ul class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                        @foreach (app(\App\Support\Settings::class)->get('product_why', config('shop.product_why')) as $why)
                            <li class="flex items-start gap-2"><span class="mt-0.5 grid size-4 shrink-0 place-items-center rounded-full bg-brand text-white"><x-icon name="check" class="size-3"/></span>{{ $why }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-line pt-3 text-sm">
                        <span class="text-muted">Fragen zu diesem Artikel?</span>
                        <a href="tel:{{ preg_replace('/\s+/', '', config('shop.company.phone')) }}" class="inline-flex items-center gap-1 font-medium text-accent"><x-icon name="phone" class="size-4"/>{{ config('shop.company.phone') }}</a>
                    </p>
                </div>
            </div>
        </div>

        {{-- Details --}}
        <div x-data="{ tab: 'beschreibung' }" class="mt-16">
            <div class="flex gap-6 overflow-x-auto border-b border-line font-ui text-sm" role="tablist">
                @foreach (['beschreibung' => 'Beschreibung', 'technik' => 'Technische Daten', 'downloads' => 'Downloads', 'faq' => 'Fragen'] as $k => $v)
                    @if ($k === 'downloads' && $product->downloads->isEmpty()) @continue @endif
                    @if ($k === 'faq' && $product->faqs->isEmpty()) @continue @endif
                    <button type="button" role="tab" @click="tab = '{{ $k }}'" :aria-selected="tab === '{{ $k }}'" class="-mb-px shrink-0 border-b-2 py-3" :class="tab === '{{ $k }}' ? 'border-accent text-fg' : 'border-transparent text-muted'">{{ $v }}</button>
                @endforeach
            </div>
            <div class="py-8">
                <div x-show="tab === 'beschreibung'" role="tabpanel" class="prose-af max-w-4xl">
                    {!! $product->description !!}
                    @if ($product->condition_notes)<h3>Zustandsbericht</h3><p>{{ $product->condition_notes }}</p>@endif
                </div>
                <div x-show="tab === 'technik'" x-cloak role="tabpanel" class="max-w-3xl">
                    <table class="w-full text-sm"><tbody class="divide-y divide-line">
                        <tr><th class="w-1/2 py-2.5 text-left font-normal text-muted">Hersteller</th><td>{{ $product->manufacturer?->name }}</td></tr>
                        <tr><th class="py-2.5 text-left font-normal text-muted">Artikelnummer</th><td>{{ $product->sku }}</td></tr>
                        @if ($product->ean)<tr><th class="py-2.5 text-left font-normal text-muted">EAN</th><td>{{ $product->ean }}</td></tr>@endif
                        @if ($product->caliber)<tr><th class="py-2.5 text-left font-normal text-muted">Kaliber</th><td>{{ $product->caliber->name }}</td></tr>@endif
                        <tr><th class="py-2.5 text-left font-normal text-muted">Zustand</th><td>{{ $product->condition_code->label() }}</td></tr>
                        @foreach ($product->attributes->groupBy('group_name') as $group => $attrs)
                            @if ($group)<tr><th colspan="2" class="pt-5 pb-1 text-left font-display text-xs tracking-widest text-accent uppercase">{{ $group }}</th></tr>@endif
                            @foreach ($attrs as $a)<tr><th class="py-2.5 text-left font-normal text-muted">{{ $a->name }}</th><td>{{ $a->pivot->value_text ?? rtrim(rtrim((string) $a->pivot->value_number, '0'), '.') }} {{ $a->unit }}</td></tr>@endforeach
                        @endforeach
                        <tr><th class="py-2.5 text-left font-normal text-muted">Erwerb</th><td>{{ $product->legalClass->name }}</td></tr>
                    </tbody></table>
                </div>
                <div x-show="tab === 'downloads'" x-cloak role="tabpanel" class="grid max-w-3xl gap-2">
                    @foreach ($product->downloads as $d)
                        <a href="{{ route('product-document.download', $d) }}" class="card flex items-center gap-3 p-4 hover:border-accent"><x-icon name="file" class="size-6 text-accent"/><span class="flex-1"><span class="font-medium">{{ $d->title }}</span><span class="block text-xs text-muted">{{ $d->typeLabel() }} · {{ strtoupper($d->language) }}@if($d->visibility !== 'public') · nur für Kunden @endif</span></span><x-icon name="download" class="size-5"/></a>
                    @endforeach
                </div>
                <div x-show="tab === 'faq'" x-cloak role="tabpanel" class="max-w-3xl"><x-faq-list :faqs="$product->faqs"/></div>
            </div>
        </div>

        @if ($product->accessories->isNotEmpty())
            {{-- Upsell: Zubehör-Set mit einem Klick --}}
            <section class="mt-6" x-data="{ picked: @js($product->accessories->filter->isPurchasable()->pluck('id')->values()), busy: false,
                    async addAll() { this.busy = true; for (const id of this.picked) { await $store.cart.add(id); } this.busy = false; } }">
                <x-section-heading eyebrow="Passend dazu" title="Komplettieren Sie Ihre Ausrüstung"/>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($product->accessories as $acc)
                        <label class="card fx-card flex cursor-pointer items-center gap-3 p-3 has-[:checked]:border-brand">
                            @if ($acc->isPurchasable())<input type="checkbox" value="{{ $acc->id }}" x-model.number="picked" class="rounded border-line bg-surface-2 text-brand-600 focus:ring-accent">@endif
                            <span class="grid size-16 shrink-0 place-items-center overflow-hidden rounded bg-surface-2">@if ($img = $acc->primaryImage())<x-img :media="$img" size="thumb" sizes="64px" alt="" class="size-full object-contain p-1"/>@else<x-icon name="box" class="size-6 text-muted"/>@endif</span>
                            <span class="min-w-0"><span class="line-clamp-2 text-sm font-medium">{{ $acc->name }}</span><span class="font-display text-sm font-bold">{{ \App\Support\Money::format($acc->currentPrice()) }}</span></span>
                        </label>
                    @endforeach
                </div>
                <button type="button" class="btn-primary fx-shine mt-4" @click="addAll()" :disabled="busy || picked.length === 0">
                    <x-icon name="cart" class="size-4"/> <span x-text="picked.length + ' Artikel in den Warenkorb'"></span>
                </button>
            </section>
        @endif

        @if ($related->isNotEmpty())
            <section class="mt-16"><x-section-heading eyebrow="Alternativen" title="Ähnliche Artikel"/>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">@foreach ($related as $p)<x-product-card :product="$p"/>@endforeach</div></section>
        @endif
    </div>

    {{-- Mobile: fixierte Kaufleiste --}}
    <div class="fixed inset-x-0 bottom-0 z-30 flex items-center justify-between gap-3 border-t border-line bg-surface/95 p-3 backdrop-blur lg:hidden">
        <x-price :product="$product"/>
        <a href="#inhalt" class="btn-primary py-2.5" onclick="window.scrollTo({top:0,behavior:'smooth'});return false;">Kaufen</a>
    </div>
</x-layouts.app>
