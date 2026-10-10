<div class="card mt-4 overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-line text-xs text-muted uppercase"><tr><th class="p-4">Bestellung</th><th class="p-4">Datum</th><th class="p-4">Status</th><th class="p-4 text-right">Summe</th></tr></thead>
        <tbody class="divide-y divide-line">
            @forelse ($orders as $o)
                <tr><td class="p-4"><a href="{{ route('account.orders.show', $o) }}" class="font-medium text-accent">{{ $o->order_number }}</a></td><td class="p-4">{{ $o->placed_at->format('d.m.Y') }}</td><td class="p-4">{{ $o->status->label() }}</td><td class="p-4 text-right">{{ \App\Support\Money::format($o->total_gross) }}</td></tr>
            @empty
                <tr><td colspan="4" class="p-6 text-center text-muted">Noch keine Bestellungen.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
