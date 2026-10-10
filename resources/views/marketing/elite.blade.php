<x-layouts.app>
    <section class="fx-aurora fx-grain relative isolate overflow-hidden bg-ink py-20 text-white sm:py-28" style="--af-fg:#fff;--af-muted:#c4c4c4">
        <div class="container-af relative text-center">
            <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs" class="justify-center"/>
            <p class="eyebrow mt-8 justify-center">Mitgliedschaft</p>
            <h1 class="mt-4 font-display text-5xl font-extrabold uppercase sm:text-7xl">Armory Falter <span class="fx-brand-text">Elite</span></h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-white/80">Der Club für alle, die mehr wollen: früher dran, exklusiver ausgestattet, näher am Geschehen.</p>
            <p class="mt-8 font-display text-4xl font-extrabold">{{ \App\Support\Money::format((int) app(\App\Support\Settings::class)->get('elite.price', 9900)) }} <span class="text-base font-medium text-white/70">pro Jahr</span></p>
            <a href="#warteliste" class="btn-primary fx-shine fx-shine-auto mt-8 px-8 py-4 text-base">Jetzt auf die Warteliste</a>
        </div>
    </section>
    <section class="container-af py-20">
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([['bolt', 'Frühzugriff', 'Neuheiten und Gebrauchtwaffen 48 Stunden vor allen anderen.'], ['star', 'Exklusive Waffen', 'Sondermodelle und Sammlerstücke nur für Mitglieder.'], ['euro', 'VIP-Angebote', 'Mitgliederpreise auf Zubehör, Optik und Werkstatt.'], ['calendar', 'Eventeinladungen', 'Bevorzugte Plätze bei Testtagen und Jagdabenden.'], ['target', 'Schießstandtage', 'Exklusive Termine auf dem Stand mit Fachbetreuung.']] as [$icon, $title, $text])
                <div class="card fx-tilt fx-spotlight reveal p-6 text-center" style="transition-delay: {{ $loop->index * 70 }}ms">
                    <span class="mx-auto grid size-14 place-items-center rounded-full bg-brand text-white"><x-icon :name="$icon" class="size-6"/></span>
                    <h2 class="mt-5 font-display text-lg font-bold">{{ $title }}</h2>
                    <p class="mt-2 text-sm text-muted">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>
    <section id="warteliste" class="scroll-mt-28 bg-surface py-20">
        <div class="container-af max-w-2xl">
            <x-section-heading eyebrow="Start in Kürze" title="Platz auf der Warteliste sichern"/>
            <p class="-mt-4 mb-8 text-muted">Die Mitgliedschaft startet in Kürze. Wer auf der Warteliste steht, wird zuerst eingeladen.</p>
            <livewire:contact-form subject="Warteliste Armory Falter Elite"/>
        </div>
    </section>
</x-layouts.app>
