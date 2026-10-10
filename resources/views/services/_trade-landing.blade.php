{{-- Gemeinsames Layout für Verkaufsservice und Ankauf. Erwartet: $type, $eyebrow, $title, $lead, $steps, $benefits, $faqs --}}
<x-layouts.app>
    <section class="relative overflow-hidden border-b border-line bg-ink text-white" style="--af-fg:#fff;--af-muted:#bdbdbd">
        <div class="fx-aurora absolute inset-0" aria-hidden="true"></div>
        <div class="container-af relative grid gap-10 py-16 lg:grid-cols-[1.2fr_1fr] lg:py-24">
            <div>
                <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs" class="text-white/60"/>
                <p class="eyebrow mt-6">{{ $eyebrow }}</p>
                <h1 class="mt-4 font-display text-4xl leading-tight font-extrabold uppercase sm:text-5xl">{!! $title !!}</h1>
                <p class="mt-6 max-w-xl text-lg text-white/75">{{ $lead }}</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="#anfrage" class="btn-primary fx-shine px-7 py-4">Jetzt anfragen <x-icon name="arrow-right" class="size-4"/></a>
                    <a href="tel:{{ preg_replace('/\s+/', '', config('shop.company.phone')) }}" class="btn-outline border-white/30 px-7 py-4 text-white"><x-icon name="phone" class="size-4"/> {{ config('shop.company.phone') }}</a>
                </div>
            </div>
            <ul class="grid content-center gap-3">
                @foreach ($benefits as [$icon, $head, $text])
                    <li class="reveal fx-glass flex gap-4 rounded-card p-5" style="transition-delay: {{ $loop->index * 80 }}ms">
                        <x-icon :name="$icon" class="size-7 shrink-0 text-accent"/>
                        <div><p class="font-display font-bold">{{ $head }}</p><p class="mt-1 text-sm text-white/65">{{ $text }}</p></div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="container-af py-20">
        <x-section-heading eyebrow="Ablauf" title="So einfach geht's"/>
        <ol class="grid gap-5 md:grid-cols-{{ count($steps) }}">
            @foreach ($steps as [$head, $text])
                <li class="card reveal fx-tilt relative p-6" style="transition-delay: {{ $loop->index * 80 }}ms">
                    <span class="font-display text-5xl font-extrabold text-accent/25">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3 class="mt-3 font-display text-lg font-bold">{{ $head }}</h3>
                    <p class="mt-2 text-sm text-muted">{{ $text }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    <section id="anfrage" class="scroll-mt-28 bg-surface py-20">
        <div class="container-af grid gap-12 lg:grid-cols-[1fr_340px]">
            <div>
                <x-section-heading eyebrow="Anfrage" :title="$type === 'buyback' ? 'Waffe zum Ankauf anbieten' : 'Waffe zum Verkauf anmelden'"/>
                <livewire:trade-request-form :type="$type"/>
            </div>
            <aside class="space-y-5 text-sm">
                <div class="card p-6">
                    <p class="label">Wichtig</p>
                    <p class="mt-2 text-muted">Bitte senden Sie keine Waffen unangemeldet ein. Die Übergabe erfolgt nach Terminabsprache im Geschäft oder per Versand an uns als Waffenhändler gemäß § 34 WaffG. Eine Austragung aus Ihrer WBK übernehmen wir.</p>
                </div>
                <div class="card p-6">
                    <p class="label">Lieber persönlich?</p>
                    <p class="mt-2 text-muted">Bringen Sie die Waffe mit WBK zur Bewertung vorbei.</p>
                    <a href="{{ route('appointments.consultation') }}" class="btn-outline mt-4 w-full py-2"><x-icon name="calendar" class="size-4"/> Termin buchen</a>
                </div>
            </aside>
        </div>
    </section>

    @if ($faqs->isNotEmpty())
        <section class="container-af max-w-4xl py-20">
            <x-section-heading eyebrow="FAQ" title="Häufige Fragen"/>
            <x-faq-list :faqs="$faqs"/>
        </section>
    @endif
</x-layouts.app>
