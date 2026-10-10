<x-mail::message>
# Vielen Dank für Ihre Bestellung

Bestellnummer: **{{ $order->order_number }}** vom {{ $order->placed_at->format('d.m.Y H:i') }}

<x-mail::table>
| Artikel | Menge | Summe |
|:--------|:-----:|------:|
@foreach ($order->items as $item)
| {{ $item->name }} ({{ $item->sku }}) | {{ $item->quantity }} | {{ \App\Support\Money::format($item->line_total_gross) }} |
@endforeach
| Versand | | {{ \App\Support\Money::format($order->shipping_gross) }} |
| **Gesamt inkl. MwSt.** | | **{{ \App\Support\Money::format($order->total_gross) }}** |
</x-mail::table>

Enthaltene MwSt.: {{ \App\Support\Money::format($order->total_tax) }} · Zahlungsart: {{ $order->payment_method_code->label() }}

@if ($order->requiresCompliance())
<x-mail::panel>
**Ihre Bestellung enthält erlaubnispflichtige Artikel.** Unsere Waffensachbearbeitung prüft Ihre Erwerbsberechtigung.
Erst nach dieser Freigabe erhalten Sie die Zahlungsaufforderung. Für das Überlassen benötigen wir Ihre Erlaubnis
(z. B. WBK mit Voreintrag oder Jagdschein) im Original bzw. wie in unseren Versandinformationen beschrieben.
</x-mail::panel>
@elseif ($order->payment_method_code === \App\Enums\PaymentMethod::Prepayment)
Bitte überweisen Sie den Betrag innerhalb von {{ config('shop.prepayment_days') }} Tagen an
{{ $company['bank']['holder'] }}, IBAN {{ $company['bank']['iban'] }}, Verwendungszweck **{{ $order->order_number }}**.
@endif

<x-mail::button :url="route('account.orders.show', $order)">
Bestellung ansehen
</x-mail::button>

Die AGB und die Widerrufsbelehrung finden Sie unter {{ route('page.show', 'agb') }} und {{ route('page.show', 'widerruf') }}.

Ihr Team von {{ $company['name'] }}<br>
{{ $company['street'] }}, {{ $company['postal_code'] }} {{ $company['city'] }} · {{ $company['phone'] }}
</x-mail::message>
