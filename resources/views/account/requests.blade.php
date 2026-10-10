<x-layouts.account active="requests">
    <h1 class="font-display text-2xl font-extrabold uppercase">Verkauf & Ankauf</h1>
    <div class="mt-4 flex gap-3"><a href="{{ route('trade.consignment') }}" class="btn-outline py-2">Waffe verkaufen lassen</a><a href="{{ route('trade.buyback') }}" class="btn-outline py-2">Ankauf anfragen</a></div>
    <div class="card mt-6 divide-y divide-line">
        @forelse ($requests as $r)
            <div class="flex items-center justify-between p-4 text-sm"><div><p class="font-medium">{{ $r->reference }} · {{ $r->manufacturerLabel() }} {{ $r->model }}</p><p class="text-xs text-muted">{{ $r->type->label() }} · {{ $r->created_at->format('d.m.Y') }}</p></div>
                <div class="text-right"><span class="badge bg-surface-2">{{ $r->status->label() }}</span>@if($r->offer_price)<p class="mt-1 text-xs">Angebot: {{ \App\Support\Money::format($r->offer_price) }}</p>@endif</div></div>
        @empty
            <p class="p-6 text-sm text-muted">Keine Anfragen.</p>
        @endforelse
    </div>
</x-layouts.account>
