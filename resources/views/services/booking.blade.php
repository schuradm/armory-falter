<x-layouts.app>
    <div class="container-af max-w-4xl py-12">
        <p class="eyebrow">{{ $type === 'consultation' ? 'Persönliche Beratung' : 'Werkstatt' }}</p>
        <h1 class="mt-3 font-display text-3xl font-extrabold uppercase sm:text-4xl">{{ $type === 'consultation' ? 'Beratungstermin buchen' : 'Werkstatt-Termin buchen' }}</h1>
        <p class="mt-3 text-muted">{{ $type === 'consultation' ? 'Nehmen Sie sich Zeit für die richtige Waffe oder Optik. Wir reservieren Ihnen exklusiv einen Berater.' : 'Wählen Sie Leistung, Tag und Uhrzeit. Sie erhalten sofort eine Bestätigung per E-Mail.' }}</p>
        <div class="mt-10"><livewire:appointment-booking :type="$type"/></div>
    </div>
</x-layouts.app>
