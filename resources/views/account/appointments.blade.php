<x-layouts.account active="appointments">
    <div class="flex items-center justify-between"><h1 class="font-display text-2xl font-extrabold uppercase">Termine</h1><a href="{{ route('appointments.workshop') }}" class="btn-primary py-2">Termin buchen</a></div>
    <div class="card mt-6 divide-y divide-line">
        @forelse ($appointments as $a)
            <div class="flex items-center justify-between p-4 text-sm"><div><p class="font-medium">{{ $a->service?->name ?? $a->typeLabel() }}</p><p class="text-xs text-muted">{{ $a->starts_at->translatedFormat('l, d.m.Y H:i') }} Uhr</p></div><span class="badge bg-surface-2">{{ ['requested' => 'Angefragt', 'confirmed' => 'Bestätigt', 'cancelled' => 'Abgesagt', 'done' => 'Erledigt'][$a->status] ?? $a->status }}</span></div>
        @empty
            <p class="p-6 text-sm text-muted">Keine Termine.</p>
        @endforelse
    </div>
</x-layouts.account>
