@php($c = config('shop.company'))
<footer class="cv-auto [--cv-h:1900px] md:[--cv-h:1100px] lg:[--cv-h:800px] mt-24 border-t border-line bg-ink text-sm text-white/80" style="--af-fg:#fff;--af-muted:#a8a8a8">
    <div class="container-af grid gap-10 py-14 md:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <x-logo variant="light" class="h-14 w-auto"/>
            <p class="mt-4 max-w-sm text-white/60">Ihr Fachhändler für Jagd- und Sportwaffen, Optik, Munition und Zubehör. Beratung, Werkstatt und Service aus Wolfschlugen bei Stuttgart.</p>
            <address class="mt-5 space-y-1.5 not-italic text-white/70">
                <p class="flex items-start gap-2"><x-icon name="map-pin" class="mt-0.5 size-4 text-brand-400"/>{{ $c['name'] }}, Inh. {{ $c['owner'] }}<br>{{ $c['street'] }}, {{ $c['postal_code'] }} {{ $c['city'] }}</p>
                <p class="flex items-center gap-2"><x-icon name="phone" class="size-4 text-brand-400"/><a href="tel:{{ preg_replace('/\s+/', '', $c['phone']) }}" class="hover:text-white">{{ $c['phone'] }}</a></p>
                <p class="flex items-center gap-2"><x-icon name="mail" class="size-4 text-brand-400"/><a href="mailto:{{ $c['email'] }}" class="hover:text-white">{{ $c['email'] }}</a></p>
            </address>
        </div>
        <div>
            <h2 class="font-display text-xs font-bold tracking-[0.2em] text-brand-400 uppercase">Shop</h2>
            <ul class="mt-4 space-y-2">
                @foreach ($navCategories->take(8) as $cat)
                    <li><a href="{{ $cat->url() }}" class="hover:text-white">{{ $cat->name }}</a></li>
                @endforeach
                <li><a href="{{ route('brands.index') }}" class="hover:text-white">Hersteller</a></li>
                <li><a href="{{ url('/produkt/geschenkgutschein') }}" class="hover:text-white">Geschenkgutscheine</a></li>
            </ul>
        </div>
        <div>
            <h2 class="font-display text-xs font-bold tracking-[0.2em] text-brand-400 uppercase">Service</h2>
            <ul class="mt-4 space-y-2">
                <li><a href="{{ route('trade.consignment') }}" class="hover:text-white">Verkaufsservice (egun)</a></li>
                <li><a href="{{ route('trade.buyback') }}" class="hover:text-white">Waffen-Ankauf</a></li>
                <li><a href="{{ route('workshop.index') }}" class="hover:text-white">Werkstatt</a></li>
                <li><a href="{{ route('appointments.consultation') }}" class="hover:text-white">Beratung buchen</a></li>
                <li><a href="{{ route('faq') }}" class="hover:text-white">FAQ</a></li>
                <li><a href="{{ route('page.show', 'versandinformationen') }}" class="hover:text-white">Versandinformationen</a></li>
                <li><a href="{{ route('page.show', 'zahlungsarten') }}" class="hover:text-white">Zahlungsarten</a></li>
                <li><a href="{{ route('page.show', 'ueber-uns') }}" class="hover:text-white">Über uns</a></li>
                <li><a href="{{ route('contact') }}" class="hover:text-white">Kontakt</a></li>
            </ul>
        </div>
        <div>
            <h2 class="font-display text-xs font-bold tracking-[0.2em] text-brand-400 uppercase">Newsletter</h2>
            <p class="mt-4 text-white/60">Neuheiten, Aktionen und Fachartikel. Abmeldung jederzeit.</p>
            @include('partials.newsletter-footer')
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="container-af flex flex-col gap-4 py-6 text-xs text-white/50 md:flex-row md:items-center md:justify-between">
            <p>Abgabe erlaubnispflichtiger Waffen und Munition nur an Berechtigte gegen Nachweis der Erwerbserlaubnis. Alle Preise inkl. gesetzl. MwSt., zzgl. <a href="{{ route('page.show', 'versandinformationen') }}" class="underline">Versand</a>.</p>
            <nav class="flex flex-wrap gap-x-4 gap-y-1" aria-label="Rechtliches">
                <a href="{{ route('page.show', 'impressum') }}" class="hover:text-white">Impressum</a>
                <a href="{{ route('page.show', 'datenschutz') }}" class="hover:text-white">Datenschutz</a>
                <a href="{{ route('page.show', 'agb') }}" class="hover:text-white">AGB</a>
                <a href="{{ route('page.show', 'widerruf') }}" class="hover:text-white">Widerruf</a>
                <a href="{{ route('page.show', 'barrierefreiheit') }}" class="hover:text-white">Barrierefreiheit</a>
                <button type="button" class="hover:text-white" x-data @click="$dispatch('open-consent')">Cookie-Einstellungen</button>
            </nav>
        </div>
        <p class="container-af pb-6 text-xs text-white/60">© {{ date('Y') }} {{ $c['name'] }}</p>
    </div>
</footer>
