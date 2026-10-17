<x-mail::message>
# Neue Bestellung {{ $order->order_number }}

Kunde: {{ $order->user->name }} ({{ $order->user->customer_number }}) · {{ $order->user->email }}

Status: **{{ $order->status->label() }}** · Compliance: **{{ $order->compliance_status->label() }}**

@foreach ($order->items as $item)
- {{ $item->quantity }} × {{ $item->name }} ({{ $item->legal_class_code }})
@endforeach

Summe: {{ \App\Support\Money::format($order->total_gross) }} · {{ $order->payment_method_code->label() }} · {{ $order->shipping_method_code }}

<x-mail::button :url="\App\Filament\Resources\OrderResource::getUrl('view', ['record' => $order])">
Im Admin öffnen
</x-mail::button>
</x-mail::message>
