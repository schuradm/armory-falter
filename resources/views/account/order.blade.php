<x-layouts.account active="orders">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div><p class="eyebrow">Bestellung</p><h1 class="font-display text-2xl font-extrabold">{{ $order->order_number }}</h1><p class="text-sm text-muted">vom {{ $order->placed_at->format('d.m.Y H:i') }}</p></div>
        <span class="badge bg-surface-2 px-3 py-1.5 text-sm">{{ $order->status->label() }}</span>
    </div>
    @error('payment')<div class="card mt-4 border-red-500/40 p-4 text-sm">{{ $message }}</div>@enderror
    @if ($order->status === \App\Enums\OrderStatus::PendingPayment && $order->payment_method_code->requiresOnlineCapture())
        <a href="{{ route('payment.start', $order) }}" class="btn-primary mt-4">Jetzt bezahlen</a>
    @elseif ($order->status === \App\Enums\OrderStatus::PendingPayment && $order->payment_method_code === \App\Enums\PaymentMethod::Prepayment)
        @php($bank = config('shop.company.bank'))
        <div class="card mt-4 p-4 text-sm">Bitte überweisen Sie <strong>{{ \App\Support\Money::format($order->amountDue()) }}</strong> an {{ $bank['holder'] }}, IBAN <span class="font-mono">{{ $bank['iban'] }}</span>, Verwendungszweck <strong>{{ $order->order_number }}</strong>.</div>
    @endif

    <div class="mt-6 grid gap-6 md:grid-cols-2">
        <section class="card p-5">
            <h2 class="font-display font-bold">Sendungsverfolgung</h2>
            @forelse ($order->shipments as $s)
                <div class="mt-3 text-sm"><p>{{ strtoupper($s->carrier ?? '') }} · {{ $s->statusLabel() }}</p>
                    @if ($s->trackingUrl())<a href="{{ $s->trackingUrl() }}" target="_blank" rel="noopener" class="text-accent">Sendung {{ $s->tracking_number }} verfolgen</a>@endif
                    <ol class="mt-2 space-y-1 border-l border-line pl-4 text-xs text-muted">@foreach ($s->events as $e)<li>{{ $e->occurred_at->format('d.m. H:i') }} – {{ $e->description ?? $e->status }}</li>@endforeach</ol>
                </div>
            @empty
                <p class="mt-2 text-sm text-muted">Noch nicht versendet.</p>
            @endforelse
        </section>
        <section class="card p-5">
            <h2 class="font-display font-bold">Verlauf</h2>
            <ol class="mt-3 space-y-2 text-sm">@foreach ($order->statusHistory as $h)<li><span class="text-muted">{{ $h->created_at->format('d.m.Y H:i') }}</span> · {{ \App\Enums\OrderStatus::tryFrom($h->to_status)?->label() ?? $h->to_status }}@if($h->comment && $h->notify_customer)<br><span class="text-xs text-muted">{{ $h->comment }}</span>@endif</li>@endforeach</ol>
        </section>
    </div>

    <section class="card mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-line text-left text-xs text-muted uppercase"><tr><th class="p-4">Artikel</th><th class="p-4">Menge</th><th class="p-4 text-right">Summe</th></tr></thead>
            <tbody class="divide-y divide-line">@foreach ($order->items as $i)<tr><td class="p-4">{{ $i->name }}<br><span class="text-xs text-muted">{{ $i->sku }}</span></td><td class="p-4">{{ $i->quantity }}</td><td class="p-4 text-right">{{ \App\Support\Money::format($i->line_total_gross) }}</td></tr>@endforeach</tbody>
            <tfoot class="border-t border-line">
                @if ($order->discount_gross > 0)<tr><td class="p-4" colspan="2">Rabatt{{ $order->discount_code ? ' ('.$order->discount_code.')' : '' }}</td><td class="p-4 text-right">−{{ \App\Support\Money::format($order->discount_gross) }}</td></tr>@endif
                <tr><td class="p-4" colspan="2">Versand</td><td class="p-4 text-right">{{ \App\Support\Money::format($order->shipping_gross) }}</td></tr><tr class="font-display font-bold"><td class="p-4" colspan="2">Gesamt inkl. MwSt.</td><td class="p-4 text-right">{{ \App\Support\Money::format($order->total_gross) }}</td></tr>
                @if ($order->gift_voucher_gross > 0)<tr><td class="p-4" colspan="2">Geschenkgutschein eingelöst</td><td class="p-4 text-right">−{{ \App\Support\Money::format($order->gift_voucher_gross) }}</td></tr><tr class="font-bold"><td class="p-4" colspan="2">Zu zahlen</td><td class="p-4 text-right">{{ \App\Support\Money::format($order->amountDue()) }}</td></tr>@endif
            </tfoot>
        </table>
    </section>
    @if ($order->invoices->isNotEmpty())
        <div class="mt-4 flex gap-3">@foreach ($order->invoices as $inv)<a href="{{ route('account.invoices.download', $inv) }}" class="btn-outline py-2"><x-icon name="download" class="size-4"/> Rechnung {{ $inv->invoice_number }}</a>@endforeach</div>
    @endif
</x-layouts.account>
