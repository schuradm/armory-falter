<x-layouts.account active="invoices">
    <h1 class="font-display text-2xl font-extrabold uppercase">Rechnungen</h1>
    <div class="card mt-6 divide-y divide-line">
        @forelse ($invoices as $inv)
            <div class="flex items-center justify-between p-4 text-sm"><div><p class="font-medium">{{ $inv->invoice_number }}</p><p class="text-xs text-muted">{{ $inv->issued_at->format('d.m.Y') }} · Bestellung {{ $inv->order->order_number }}</p></div>
                <div class="flex items-center gap-4"><span>{{ \App\Support\Money::format($inv->total_gross) }}</span><a href="{{ route('account.invoices.download', $inv) }}" class="text-accent"><x-icon name="download" class="size-5"/></a></div></div>
        @empty
            <p class="p-6 text-sm text-muted">Noch keine Rechnungen.</p>
        @endforelse
    </div>
</x-layouts.account>
