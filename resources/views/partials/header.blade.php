@php($company = config('shop.company'))
<header x-data="{ scrolled: false, mobile: false, mega: null }"
        x-init="scrolled = window.scrollY > 24"
        @scroll.window.passive="scrolled = window.scrollY > 24"
        @keydown.escape.window="mega = null; mobile = false"
        class="sticky top-0 z-50">
    {{-- Top-Bar --}}
    {{-- Ausgangszustand steht statisch im HTML (vor dem JS-Start), sonst springt die Seite beim Initialisieren (CLS) --}}
    <div class="hidden h-9 border-b border-line bg-ink text-xs text-muted opacity-100 md:block transition-all duration-300"
         :class="{ 'h-0 overflow-hidden opacity-0': scrolled, 'h-9 opacity-100': !scrolled }">
        <div class="container-af flex h-9 items-center justify-between font-ui">
            <p class="flex items-center gap-2"><x-icon name="users" class="size-4 text-accent"/> Ihr persönlicher Waffenberater: kostenlose Kaufberatung per Telefon, Video oder vor Ort</p>
            <div class="flex items-center gap-5">
                <a href="tel:{{ preg_replace('/\s+/', '', $company['phone']) }}" class="flex items-center gap-1.5 hover:text-fg"><x-icon name="phone" class="size-3.5"/>{{ $company['phone'] }}</a>
                <a href="{{ route('appointments.consultation') }}" class="hover:text-fg">Beratung buchen</a>
                <span class="flex items-center gap-1.5"><x-icon name="map-pin" class="size-3.5"/>{{ $company['city'] }}</span>
            </div>
        </div>
    </div>

    {{-- Hauptnavigation --}}
    <div class="border-b border-line bg-bg/70 backdrop-blur-md transition-colors duration-300"
         :class="{ 'bg-bg/90': scrolled, 'bg-bg/70': !scrolled }">
        <div class="container-af flex h-20 items-center gap-4 transition-all duration-300" :class="{ 'h-16': scrolled, 'h-20': !scrolled }">
            <button type="button" class="btn-ghost -ml-3 lg:hidden" @click="mobile = true" aria-label="Menü öffnen" :aria-expanded="mobile">
                <x-icon name="menu" class="size-6"/>
            </button>

            <a href="{{ route('home') }}" class="shrink-0" aria-label="Armory Falter Startseite">
                <x-logo class="h-10 w-auto sm:h-12"/>
            </a>

            <nav data-alpine-chunk class="hidden shrink-0 items-center whitespace-nowrap lg:ml-2 lg:flex 2xl:ml-6 2xl:gap-1" aria-label="Hauptnavigation">
                <button type="button" class="flex items-center gap-1 rounded-md px-2.5 py-2 font-ui text-sm font-medium hover:text-accent 2xl:px-3"
                        @click="mega = mega === 'shop' ? null : 'shop'" @mouseenter="mega = 'shop'" :aria-expanded="mega === 'shop'">
                    Shop <x-icon name="chevron-down" class="size-4 transition" ::class="mega === 'shop' && 'rotate-180'"/>
                </button>
                <button type="button" class="flex items-center gap-1 rounded-md px-2.5 py-2 font-ui text-sm font-medium hover:text-accent 2xl:px-3"
                        @click="mega = mega === 'brands' ? null : 'brands'" @mouseenter="mega = 'brands'" :aria-expanded="mega === 'brands'">
                    Hersteller <x-icon name="chevron-down" class="size-4"/>
                </button>
                <a href="{{ route('trade.consignment') }}" class="rounded-md px-2.5 py-2 font-ui text-sm font-medium hover:text-accent 2xl:px-3" @mouseenter="mega = null">Verkaufsservice</a>
                <a href="{{ route('trade.buyback') }}" class="rounded-md px-2.5 py-2 font-ui text-sm font-medium hover:text-accent 2xl:px-3" @mouseenter="mega = null">Ankauf</a>
                <a href="{{ route('workshop.index') }}" class="rounded-md px-2.5 py-2 font-ui text-sm font-medium hover:text-accent 2xl:px-3" @mouseenter="mega = null">Werkstatt</a>
                <a href="{{ route('events.index') }}" class="rounded-md px-2.5 py-2 font-ui text-sm font-medium hover:text-accent 2xl:px-3" @mouseenter="mega = null">Events</a>
                <a href="{{ route('blog.index') }}" class="rounded-md px-2.5 py-2 font-ui text-sm font-medium hover:text-accent 2xl:px-3" @mouseenter="mega = null">Ratgeber</a>
            </nav>

            <div class="ml-auto flex min-w-0 flex-1 items-center justify-end gap-1 sm:gap-2">
                {{-- Suchfeld ist flexibel (min. 10 rem, max. 18 rem), damit die Kopfzeile nie breiter als der Viewport wird --}}
                <div class="hidden min-w-40 max-w-72 flex-1 xl:block"><livewire:search-autocomplete /></div>
                <a href="{{ route('search') }}" class="btn-ghost shrink-0 xl:hidden" aria-label="Suche"><x-icon name="search"/></a>
                <button type="button" class="btn-ghost hidden shrink-0 sm:inline-flex lg:hidden xl:inline-flex" @click="$store.theme.toggle()" aria-label="Farbschema wechseln">
                    <x-icon name="sun" x-show="$store.theme.current === 'dark'"/>
                    <x-icon name="moon" x-show="$store.theme.current === 'light'" x-cloak/>
                </button>
                <a href="{{ route('account.wishlists') }}" class="btn-ghost hidden shrink-0 sm:inline-flex lg:hidden xl:inline-flex" aria-label="Merkliste"><x-icon name="heart"/></a>
                <a href="{{ route('compare') }}" class="btn-ghost relative hidden shrink-0 sm:inline-flex lg:hidden xl:inline-flex" aria-label="Produktvergleich" x-data>
                    <x-icon name="compare"/>
                    <span x-show="$store.compare.items.length" x-text="$store.compare.items.length" x-cloak class="absolute right-0.5 top-0.5 grid size-4 place-items-center rounded-full bg-brand text-[10px] font-bold text-white"></span>
                </a>
                <a href="{{ auth()->check() ? route('account.dashboard') : route('login') }}" class="btn-ghost shrink-0" aria-label="Kundenkonto"><x-icon name="user"/></a>
                <livewire:cart-counter :count="$cartCount" />
            </div>
        </div>

        {{-- Mega-Menü Shop --}}
        <div x-show="mega === 'shop'" x-cloak data-alpine-chunk x-transition.opacity.duration.200ms @mouseleave="mega = null" @click.outside="mega = null"
             class="absolute inset-x-0 top-full hidden border-b border-line bg-surface/98 shadow-card backdrop-blur lg:block">
            <div class="container-af grid grid-cols-12 gap-8 py-8">
                <div class="col-span-9 grid grid-cols-4 gap-x-8 gap-y-6">
                    @foreach ($navCategories as $cat)
                        <div>
                            <a href="{{ $cat->url() }}" class="group flex items-center gap-2 font-display text-sm font-bold tracking-wide uppercase hover:text-accent">
                                <x-icon :name="$cat->icon ?? 'box'" class="size-4 text-accent"/> {{ $cat->name }}
                            </a>
                            @if ($cat->children->isNotEmpty())
                                <ul class="mt-2 space-y-1.5 border-l border-line pl-6">
                                    @foreach ($cat->children as $child)
                                        <li><a href="{{ $child->url() }}" class="text-sm text-muted hover:text-fg">{{ $child->name }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="col-span-3 space-y-3">
                    <a href="{{ route('shop.index', ['zustand' => 'used']) }}" class="card group block overflow-hidden p-5">
                        <p class="eyebrow">Geprüft & gewartet</p>
                        <p class="mt-1 font-display text-lg font-bold">Gebrauchtwaffen</p>
                        <p class="mt-1 text-sm text-muted">Vom Büchsenmacher kontrolliert.</p>
                        <span class="mt-3 inline-flex items-center gap-1 text-sm text-accent">Ansehen <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-1"/></span>
                    </a>
                    <a href="{{ route('shop.index', ['sortierung' => 'neu']) }}" class="card group block p-5">
                        <p class="eyebrow">Frisch eingetroffen</p>
                        <p class="mt-1 font-display text-lg font-bold">Neuheiten</p>
                    </a>
                </div>
            </div>
        </div>

        {{-- Mega-Menü Hersteller --}}
        <div x-show="mega === 'brands'" x-cloak data-alpine-chunk x-transition.opacity.duration.200ms @mouseleave="mega = null" @click.outside="mega = null"
             class="absolute inset-x-0 top-full hidden border-b border-line bg-surface/98 shadow-card backdrop-blur lg:block">
            <div class="container-af py-8">
                <div class="grid grid-cols-6 gap-3">
                    @foreach ($navBrands as $brand)
                        <a href="{{ route('brands.show', $brand) }}" class="card flex h-20 items-center justify-center px-4 font-display text-sm font-bold tracking-wider uppercase text-muted transition hover:border-accent hover:text-fg">
                            @if ($brand->logo_path)
                                <img src="{{ asset('storage/'.$brand->logo_path) }}" alt="{{ $brand->name }}" class="max-h-10 w-auto opacity-80" loading="lazy">
                            @else
                                {{ $brand->name }}
                            @endif
                        </a>
                    @endforeach
                </div>
                <a href="{{ route('brands.index') }}" class="mt-5 inline-flex items-center gap-1 text-sm text-accent">Alle Hersteller A–Z <x-icon name="arrow-right" class="size-4"/></a>
            </div>
        </div>
    </div>

    @include('partials.mobile-nav')
</header>
