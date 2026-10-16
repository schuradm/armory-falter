@php
    $hero = $home['hero'];
    $lines = explode('|', $hero['headline']);
    $company = config('shop.company');
    $phoneHref = 'tel:'.preg_replace('/\s+/', '', $company['phone']);
    $whatsapp = $company['social']['whatsapp'] ?? null;
    $assembly = (array) ($home['hero_assembly'] ?? []);
    $showAssembly = (bool) ($assembly['enabled'] ?? false);
@endphp
<x-layouts.app>
    {{-- ============ A · ATTENTION: Hero ============ --}}
    {{-- Mit Explosionszeichnung wird der Hero zur Scroll-Bühne: Desktop ~220vh mit gepinntem Inhalt (sticky), mobil eine eigene
         Bühne nur für die Grafik. Der Fortschritt kommt aus CSS-Scroll-Timelines (--asm-d / --asm-m), sonst aus app.js. --}}
    <section @class(['fx-aurora fx-grain relative isolate -mt-px bg-ink text-white',
        'flex min-h-[calc(100svh-4rem)] items-center overflow-hidden lg:min-h-[calc(100svh-7.25rem)]' => ! $showAssembly,
        'asm-hero' => $showAssembly])
        style="--af-fg:#fff;--af-muted:#c4c4c4{{ $showAssembly ? ';view-timeline:--asm-d block' : '' }}" @if ($showAssembly) data-asm-hero @endif>
        @if ($showAssembly) @include('partials.hero-assembly-styles') @endif
        <div class="fx-crosshair absolute inset-0 -z-10" aria-hidden="true"></div>
        @if ($hero['image'])
            <img src="{{ Storage::disk('public')->url($hero['image']) }}" alt="" fetchpriority="high" decoding="async" class="absolute inset-0 -z-10 size-full object-cover opacity-40" width="1920" height="1080">
        @endif
        @if ($hero['video'])
            <video data-lazy-hero muted loop playsinline preload="none" class="absolute inset-0 -z-10 size-full object-cover opacity-0 transition-opacity duration-1000" aria-hidden="true">
                <source data-src="{{ Storage::disk('public')->url($hero['video']) }}" type="video/mp4">
            </video>
        @endif
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-ink via-ink/80 to-ink/10"></div>

        {{-- Logo-Dreieck als großes Hintergrundmotiv mit Parallax --}}
        @unless ($showAssembly)
        <svg data-parallax="0.18" class="pointer-events-none absolute -right-24 top-1/2 -z-10 hidden h-[115%] -translate-y-1/2 opacity-[0.16] lg:block" viewBox="0 0 316 290" aria-hidden="true">
            <path d="M24 268 158 20 292 268H264L158 72 52 268Z" fill="none" stroke="#c25a50" stroke-width="2"/>
            <circle cx="157" cy="212" r="27" fill="none" stroke="#c25a50" stroke-width="2"/>
            <path d="M157 150v124M95 212h124" stroke="#c25a50" stroke-width="1"/>
        </svg>
        @endunless

        @if ($showAssembly)<div class="asm-pin">@endif
        <div @class(['container-af relative', 'py-16 sm:py-24' => ! $showAssembly, 'asm-grid' => $showAssembly])>
            @if ($showAssembly)<div class="min-w-0">@endif
            <p class="eyebrow fx-pop-in">{{ $hero['eyebrow'] }}</p>
            <h1 @class(['mt-6 max-w-4xl font-display text-[2.6rem] leading-[1.02] font-extrabold uppercase sm:text-6xl', 'lg:text-[5.5rem]' => ! $showAssembly])>
                @foreach ($lines as $i => $line)
                    <span class="block {{ $i === 1 ? 'fx-brand-text' : '' }}">{{ trim($line) }}</span>
                @endforeach
            </h1>
            <p @class(['mt-7 max-w-2xl text-lg leading-relaxed text-white/80 sm:text-xl', 'asm-lead' => $showAssembly])>{{ $hero['text'] }}</p>
            <div @class(['mt-10 flex flex-col gap-3 sm:flex-row', 'asm-ctas' => $showAssembly])>
                <a href="{{ $hero['cta_primary']['url'] }}" class="btn-primary fx-shine fx-shine-auto px-8 py-4 text-base">{{ $hero['cta_primary']['label'] }} <x-icon name="arrow-right" class="size-5"/></a>
                <a href="{{ $hero['cta_secondary']['url'] }}" class="btn-outline border-white/35 px-8 py-4 text-base text-white"><x-icon name="calendar" class="size-5"/> {{ $hero['cta_secondary']['label'] }}</a>
            </div>
            @if ($showAssembly)
                </div>
                <div data-asm-stage class="asm-stage" style="view-timeline:--asm-m block">
                    <x-hero-assembly :config="$assembly"/>
                </div>
                <div class="min-w-0">
            @endif
            <ul @class(['mt-8 flex flex-wrap gap-x-6 gap-y-2 font-ui text-sm text-white/80', 'asm-trust' => $showAssembly])>
                <li class="flex items-center gap-2"><x-icon name="shield" class="size-4 text-brand-400"/> Rechtssichere Abwicklung</li>
                <li class="flex items-center gap-2"><x-icon name="users" class="size-4 text-brand-400"/> Persönlicher Waffenberater</li>
                <li class="flex items-center gap-2"><x-icon name="map-pin" class="size-4 text-brand-400"/> Ladengeschäft bei Stuttgart</li>
            </ul>
            <dl @class(['mt-14 grid max-w-3xl grid-cols-3 gap-4 border-t border-white/15 pt-8 sm:gap-8', 'asm-stats' => $showAssembly])>
                @foreach ($home['stats'] as $stat)
                    <div class="flex flex-col">
                        <dt class="order-2 mt-1 text-xs text-white/70 sm:text-sm">{{ $stat['label'] }}</dt>
                        <dd class="font-display text-3xl font-extrabold sm:text-5xl"><span data-count="{{ (int) $stat['value'] }}" data-suffix="{{ $stat['suffix'] ?? '' }}">{{ (int) $stat['value'] }}{{ $stat['suffix'] ?? '' }}</span></dd>
                    </div>
                @endforeach
            </dl>
            @if ($showAssembly)</div>@endif
        </div>
        @unless ($showAssembly)
        <a href="#entdecken" class="absolute bottom-6 left-1/2 hidden -translate-x-1/2 flex-col items-center gap-2 text-xs tracking-[0.3em] text-white/70 uppercase sm:flex" aria-label="Zum Inhalt scrollen">
            <span class="fx-float grid h-10 w-6 place-items-start justify-center rounded-full border border-white/30 pt-2"><span class="h-2 w-0.5 rounded bg-white/70"></span></span>
        </a>
        @endunless
        @if ($showAssembly)</div>@endif
    </section>

    {{-- Marken-Laufband --}}
    @if ($brands->isNotEmpty())
        <section class="overflow-hidden border-y border-line bg-surface py-6" aria-label="Unsere Marken">
            <div class="fx-marquee flex w-max gap-14 pr-14">
                @foreach ([false, true] as $dup)
                    @foreach ($brands as $brand)
                        <a href="{{ route('brands.show', $brand) }}" @if ($dup) aria-hidden="true" tabindex="-1" @endif class="font-display text-lg font-extrabold tracking-[0.25em] whitespace-nowrap text-muted uppercase transition hover:text-accent">{{ $brand->name }}</a>
                    @endforeach
                @endforeach
            </div>
        </section>
    @endif

    {{-- ============ I · INTEREST: Kategorien ============ --}}
    <section id="entdecken" class="cv-auto [--cv-h:1400px] md:[--cv-h:1150px] lg:[--cv-h:1150px] container-af scroll-mt-24 py-20 sm:py-28">
        <x-section-heading eyebrow="Sortiment" title="Finden Sie Ihre Ausrüstung" :link="route('shop.index')" link-text="Zum Shop"/>
        <div class="grid auto-rows-[9.5rem] grid-cols-2 gap-3 sm:auto-rows-[11rem] sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($categories as $cat)
                <a href="{{ $cat->url() }}" @class(['card fx-card fx-spotlight reveal group relative flex flex-col justify-end overflow-hidden p-5', 'col-span-2 row-span-2 sm:col-span-1 lg:col-span-2' => $loop->first, 'lg:row-span-2' => $loop->index === 1]) style="transition-delay: {{ $loop->index * 40 }}ms">
                    <x-icon :name="$cat->icon ?? 'box'" @class(['absolute right-5 top-5 text-accent/25 transition duration-700 ease-out-expo group-hover:scale-110 group-hover:text-accent', 'size-24' => $loop->first, 'size-10' => ! $loop->first])/>
                    <span @class(['font-display font-extrabold tracking-wide uppercase', 'text-2xl sm:text-3xl' => $loop->first, 'text-sm sm:text-base' => ! $loop->first])>{{ $cat->name }}</span>
                    <span class="mt-1 flex items-center gap-1 text-xs text-muted group-hover:text-accent">Entdecken <x-icon name="arrow-right" class="size-3 transition group-hover:translate-x-1"/></span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Versprechen: „Mehr als Waffen.“ --}}
    <section class="cv-auto [--cv-h:1950px] md:[--cv-h:1250px] lg:[--cv-h:950px] relative overflow-hidden bg-surface py-20 sm:py-28">
        <div class="container-af grid gap-14 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
            <div class="reveal">
                <p class="eyebrow">Das Armory-Falter-Versprechen</p>
                <h2 class="mt-4 font-display text-4xl font-extrabold uppercase sm:text-5xl">{{ $home['promise_title'] }}</h2>
                <p class="mt-5 text-lg text-muted">{{ $home['promise_text'] }}</p>
                <ul class="mt-8 grid grid-cols-2 gap-3 font-ui text-sm font-medium sm:grid-cols-3">
                    @foreach (['Sicherheit', 'Fachwissen', 'Vertrauen', 'Community', 'Beratung', 'Leidenschaft'] as $value)
                        <li class="flex items-center gap-2"><span class="grid size-6 place-items-center rounded-full bg-brand text-white"><x-icon name="check" class="size-3.5"/></span>{{ $value }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($home['usps'] as $usp)
                    <div class="card fx-tilt fx-spotlight reveal p-6" style="transition-delay: {{ $loop->index * 80 }}ms">
                        <span class="grid size-12 place-items-center rounded-lg bg-brand/15 text-accent"><x-icon :name="$usp['icon']" class="size-6"/></span>
                        <h3 class="mt-5 font-display text-lg font-bold">{{ $usp['title'] }}</h3>
                        <p class="mt-2 text-sm text-muted">{{ $usp['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ D · DESIRE ============ --}}
    @if ($featured->isNotEmpty())
        <section class="cv-auto [--cv-h:2600px] md:[--cv-h:1500px] lg:[--cv-h:950px] container-af py-20 sm:py-28">
            <x-section-heading eyebrow="Handverlesen" title="Unsere Empfehlungen" :link="route('shop.index')"/>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">@foreach ($featured as $p)<x-product-card :product="$p"/>@endforeach</div>
        </section>
    @endif

    @if ($offers->isNotEmpty())
        <section class="cv-auto [--cv-h:2600px] md:[--cv-h:1500px] lg:[--cv-h:950px] relative overflow-hidden border-y border-brand/30 bg-gradient-to-br from-brand-900/60 via-bg to-bg py-20">
            <div class="container-af">
                <x-section-heading eyebrow="Nur für kurze Zeit" title="Aktuelle Angebote" :link="route('shop.index', ['sortierung' => 'angebote'])"/>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">@foreach ($offers as $p)<x-product-card :product="$p"/>@endforeach</div>
            </div>
        </section>
    @endif

    {{-- So einfach geht's (nimmt die größte Kaufhürde: den Erwerbsnachweis) --}}
    <section class="cv-auto [--cv-h:1100px] md:[--cv-h:750px] lg:[--cv-h:700px] container-af py-20 sm:py-28">
        <x-section-heading eyebrow="Rechtssicher & einfach" :title="$home['steps_title']"/>
        <ol class="relative grid gap-6 md:grid-cols-3">
            <span class="absolute left-0 right-0 top-8 hidden h-px bg-gradient-to-r from-transparent via-brand to-transparent md:block" aria-hidden="true"></span>
            @foreach ($home['steps'] as $step)
                <li class="reveal relative text-center" style="transition-delay: {{ $loop->index * 120 }}ms">
                    <span class="relative mx-auto grid size-16 place-items-center rounded-full border border-brand bg-bg font-display text-2xl font-extrabold text-accent shadow-[0_0_0_8px_var(--af-bg)]">{{ $loop->iteration }}</span>
                    <h3 class="mt-5 font-display text-xl font-bold">{{ $step['title'] }}</h3>
                    <p class="mx-auto mt-2 max-w-xs text-sm text-muted">{{ $step['text'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Persönlicher Waffenberater --}}
    <section class="cv-auto [--cv-h:750px] md:[--cv-h:550px] lg:[--cv-h:650px] container-af">
        <div class="card fx-spotlight relative grid overflow-hidden lg:grid-cols-[1.3fr_1fr]">
            <div class="p-8 sm:p-12">
                <p class="eyebrow">Ihr persönlicher Waffenberater</p>
                <h2 class="mt-4 font-display text-3xl font-extrabold uppercase sm:text-4xl">Ist das die richtige Waffe für Sie?</h2>
                <p class="mt-4 max-w-xl text-muted">Kostenlose Kaufberatung mit Jagd- oder Sportzweck-Analyse und Kaliberempfehlung. Eine feste Ansprechperson, so wie es Ihnen passt.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ $phoneHref }}" class="btn-primary"><x-icon name="phone" class="size-4"/> Anrufen</a>
                    <a href="{{ route('appointments.consultation') }}" class="btn-outline"><x-icon name="camera" class="size-4"/> Video-Beratung</a>
                    @if ($whatsapp)<a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="btn-outline"><x-icon name="whatsapp" class="size-4"/> WhatsApp</a>@endif
                </div>
            </div>
            <div class="relative hidden min-h-64 bg-gradient-to-br from-brand-800 to-ink lg:block" aria-hidden="true">
                <svg class="fx-float absolute inset-0 m-auto size-56 text-white/15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.6"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/><path d="M22 12h-4M6 12H2M12 6V2M12 22v-4"/></svg>
            </div>
        </div>
    </section>

    @if ($testimonials->isNotEmpty())
        <section class="cv-auto [--cv-h:1600px] md:[--cv-h:1000px] lg:[--cv-h:850px] container-af py-20 sm:py-28">
            <x-section-heading eyebrow="Stimmen unserer Kunden" title="Vertrauen, das man spürt"/>
            <div class="grid gap-5 md:grid-cols-3">
                @foreach ($testimonials as $t)
                    <figure class="card reveal flex flex-col p-7" style="transition-delay: {{ $loop->index * 80 }}ms">
                        <div class="flex gap-0.5 text-accent" role="img" aria-label="{{ $t->rating }} von 5 Sternen">@for ($i = 0; $i < 5; $i++)<x-icon name="star" @class(['size-4', 'fill-current' => $i < $t->rating])/>@endfor</div>
                        <blockquote class="mt-4 flex-1 text-fg/90">„{{ $t->body }}“</blockquote>
                        <figcaption class="mt-5 border-t border-line pt-4 text-sm"><span class="font-ui font-semibold">{{ $t->author_name }}</span>@if ($t->author_meta)<span class="text-muted"> · {{ $t->author_meta }}</span>@endif</figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Services --}}
    <section class="cv-auto [--cv-h:1500px] md:[--cv-h:1000px] lg:[--cv-h:900px] bg-surface py-20 sm:py-28">
        <div class="container-af">
            <x-section-heading eyebrow="Mehr als ein Shop" title="Service aus einer Hand"/>
            <div class="grid gap-5 md:grid-cols-3">
                @foreach ([
                    ['euro', 'Verkaufsservice', 'Sie laden Bilder, Daten und Wunschpreis hoch. Wir übernehmen Bewertung, Vermarktung, Verkauf und Versand.', route('trade.consignment'), 'Waffe verkaufen lassen'],
                    ['target', 'Wir kaufen Waffen', 'Einzelstücke, Nachlässe und Sammlungen: faire Bewertung, schnelle Auszahlung, rechtssichere Abwicklung.', route('trade.buyback'), 'Ankauf anfragen'],
                    ['wrench', 'Werkstatt', 'Optikmontage, Einschießen, Reinigung, Reparatur und Cerakote vom Fachmann.', route('workshop.index'), 'Leistungen ansehen'],
                ] as [$icon, $title, $text, $url, $cta])
                    <a href="{{ $url }}" class="card fx-card fx-spotlight reveal group p-8" style="transition-delay: {{ $loop->index * 80 }}ms">
                        <span class="grid size-14 place-items-center rounded-xl bg-brand text-white shadow-[0_10px_30px_-10px_rgb(157_70_64/0.9)]"><x-icon :name="$icon" class="size-7"/></span>
                        <h3 class="mt-6 font-display text-xl font-bold">{{ $title }}</h3>
                        <p class="mt-2 text-sm text-muted">{{ $text }}</p>
                        <span class="mt-6 inline-flex items-center gap-1 font-ui text-sm font-semibold text-accent">{{ $cta }} <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-1"/></span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @if ($newest->isNotEmpty())
        <section class="cv-auto [--cv-h:2600px] md:[--cv-h:1500px] lg:[--cv-h:950px] container-af py-20 sm:py-28">
            <x-section-heading eyebrow="Frisch eingetroffen" title="Neuheiten" :link="route('shop.index', ['sortierung' => 'neu'])"/>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">@foreach ($newest as $p)<x-product-card :product="$p"/>@endforeach</div>
        </section>
    @endif

    @if ($events->isNotEmpty())
        <section class="cv-auto [--cv-h:1500px] md:[--cv-h:900px] lg:[--cv-h:700px] container-af pb-20 sm:pb-28">
            <x-section-heading eyebrow="Erleben" title="Events & Workshops" :link="route('events.index')"/>
            <div class="grid gap-5 md:grid-cols-3">
                @foreach ($events as $event)
                    <a href="{{ route('events.show', $event) }}" class="card fx-card group flex gap-5 p-6">
                        <span class="grid h-20 w-16 shrink-0 place-items-center rounded-lg bg-brand text-center text-white"><span><span class="block font-display text-2xl leading-none font-extrabold">{{ $event->starts_at->format('d') }}</span><span class="text-xs uppercase">{{ $event->starts_at->translatedFormat('M') }}</span></span></span>
                        <span><span class="block font-display text-lg font-bold group-hover:text-accent">{{ $event->title }}</span><span class="mt-1 block text-sm text-muted">{{ $event->teaser }}</span></span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Leadmagnet + Community --}}
    <section class="cv-auto [--cv-h:1500px] md:[--cv-h:1100px] lg:[--cv-h:850px] fx-aurora relative bg-ink py-20 text-white sm:py-28" style="--af-fg:#fff;--af-muted:#c4c4c4">
        <div class="container-af relative grid gap-12 lg:grid-cols-2 lg:items-center">
            <div>
                <p class="eyebrow">Kostenloser Download</p>
                <h2 class="mt-4 font-display text-3xl font-extrabold uppercase sm:text-4xl">{{ $home['lead']['title'] }}</h2>
                <p class="mt-4 text-white/80">{{ $home['lead']['text'] }}</p>
                <div class="mt-8"><livewire:newsletter-signup variant="lead"/></div>
            </div>
            <div class="fx-glass rounded-card p-8">
                <h2 class="font-display text-2xl font-extrabold uppercase">Armory Falter Community</h2>
                <p class="mt-3 text-white/80">Jagderlebnisse, Sportschießen, Neuheiten und exklusive Angebote. Werden Sie Teil der Community.</p>
                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    @php($channels = ['youtube' => ['play', 'Armory Falter TV'], 'whatsapp_channel' => ['whatsapp', 'WhatsApp-Kanal'], 'telegram' => ['mail', 'Telegram'], 'facebook' => ['users', 'Facebook-Gruppe'], 'instagram' => ['camera', 'Instagram']])
                    @forelse (array_intersect_key($channels, $community) as $key => [$icon, $label])
                        <a href="{{ $community[$key] }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-lg border border-white/15 px-4 py-3 font-ui text-sm font-medium transition hover:border-brand-400 hover:bg-white/5"><x-icon :name="$icon" class="size-5 text-brand-400"/>{{ $label }}</a>
                    @empty
                        <a href="{{ route('events.index') }}" class="flex items-center gap-3 rounded-lg border border-white/15 px-4 py-3 font-ui text-sm font-medium hover:border-brand-400"><x-icon name="calendar" class="size-5 text-brand-400"/>Events ansehen</a>
                        <a href="{{ route('blog.index') }}" class="flex items-center gap-3 rounded-lg border border-white/15 px-4 py-3 font-ui text-sm font-medium hover:border-brand-400"><x-icon name="file" class="size-5 text-brand-400"/>Fachartikel lesen</a>
                    @endforelse
                </div>
                <a href="{{ route('elite') }}" class="mt-6 flex items-center justify-between gap-4 rounded-lg bg-gradient-to-r from-brand-700 to-brand-600 px-5 py-4 transition hover:brightness-110">
                    <span><span class="block font-display font-extrabold tracking-wider uppercase">Armory Falter Elite</span><span class="text-sm text-white/85">Frühzugriff, VIP-Angebote, Schießstandtage</span></span>
                    <x-icon name="arrow-right" class="size-5"/>
                </a>
            </div>
        </div>
    </section>

    @if ($posts->isNotEmpty())
        <section class="cv-auto [--cv-h:1800px] md:[--cv-h:800px] lg:[--cv-h:700px] container-af py-20 sm:py-28">
            <x-section-heading eyebrow="Wissen" title="Aus dem Blog" :link="route('blog.index')"/>
            <div class="grid gap-5 md:grid-cols-3">@foreach ($posts as $post)@include('blog.partials.card', ['post' => $post])@endforeach</div>
        </section>
    @endif

    {{-- ============ A · ACTION ============ --}}
    <section class="cv-auto [--cv-h:800px] md:[--cv-h:600px] lg:[--cv-h:600px] container-af pb-8">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 px-8 py-14 text-center text-white sm:px-16">
            <svg class="absolute -right-10 -top-10 size-72 text-white/10" viewBox="0 0 316 290" aria-hidden="true"><path d="M24 268 158 20 292 268H264L158 72 52 268Z" fill="currentColor"/></svg>
            <h2 class="relative font-display text-3xl font-extrabold uppercase sm:text-5xl">{{ $home['cta']['headline'] }}</h2>
            <p class="relative mx-auto mt-4 max-w-2xl text-lg text-white/90">{{ $home['cta']['text'] }}</p>
            <div class="relative mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ $home['cta']['url'] }}" class="btn fx-shine fx-shine-auto bg-white px-8 py-4 text-base text-brand-700 hover:bg-white/90">{{ $home['cta']['label'] }} <x-icon name="arrow-right" class="size-5"/></a>
                <a href="{{ $phoneHref }}" class="btn border border-white/50 px-8 py-4 text-base text-white hover:bg-white/10"><x-icon name="phone" class="size-5"/> {{ $company['phone'] }}</a>
            </div>
        </div>
    </section>
</x-layouts.app>
