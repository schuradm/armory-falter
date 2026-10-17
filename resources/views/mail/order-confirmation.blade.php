<x-mail::message>
# Vielen Dank für Ihre Bestellung

Bestellnummer: **{{ $order->order_number }}** vom {{ $order->placed_at->format('d.m.Y H:i') }}

<x-mail::table>
| Artikel | Menge | Summe |
|:--------|:-----:|------:|
@foreach ($order->items as $item)
| {{ $item->name }} ({{ $item->sku }}) | {{ $item->quantity }} | {{ \App\Support\Money::format($item->line_total_gross) }} |
@endforeach
@if ($order->discount_gross > 0)
| Rabatt{{ $order->discount_code ? ' ('.$order->discount_code.')' : '' }} | | −{{ \App\Support\Money::format($order->discount_gross) }} |
@endif
| Versand | | {{ \App\Support\Money::format($order->shipping_gross) }} |
| **Gesamt inkl. MwSt.** | | **{{ \App\Support\Money::format($order->total_gross) }}** |
@if ($order->gift_voucher_gross > 0)
| Geschenkgutschein eingelöst | | −{{ \App\Support\Money::format($order->gift_voucher_gross) }} |
| **Zu zahlen** | | **{{ \App\Support\Money::format($order->amountDue()) }}** |
@endif
</x-mail::table>

Enthaltene MwSt.: {{ \App\Support\Money::format($order->total_tax) }} · Zahlungsart: {{ $order->payment_method_code->label() }}
@if ($order->items->contains(fn ($i) => $i->isGiftVoucher()))

Ihre Geschenkgutscheine (Mehrzweck-Gutscheine, beim Kauf ohne Umsatzsteuer) senden wir per E-Mail als PDF, sobald die Zahlung eingegangen ist.
@endif

@if ($order->requiresCompliance())
<x-mail::panel>
**Ihre Bestellung enthält erlaubnispflichtige Artikel.** Unsere Waffensachbearbeitung prüft Ihre Erwerbsberechtigung.
Erst nach dieser Freigabe erhalten Sie die Zahlungsaufforderung. Für das Überlassen benötigen wir Ihre Erlaubnis
(z. B. WBK mit Voreintrag oder Jagdschein) im Original bzw. wie in unseren Versandinformationen beschrieben.
</x-mail::panel>
@elseif ($order->payment_method_code === \App\Enums\PaymentMethod::GiftVoucher)
Der Gesamtbetrag ist vollständig durch Ihre Geschenkgutscheine bezahlt. Eine weitere Zahlung ist nicht nötig.
@elseif ($order->payment_method_code === \App\Enums\PaymentMethod::Prepayment)
Bitte überweisen Sie den Betrag von {{ \App\Support\Money::format($order->amountDue()) }} innerhalb von {{ config('shop.prepayment_days') }} Tagen an
{{ $company['bank']['holder'] }}, IBAN {{ $company['bank']['iban'] }}, Verwendungszweck **{{ $order->order_number }}**.
@endif

<x-mail::button :url="route('account.orders.show', $order)">
Bestellung ansehen
</x-mail::button>

@if ($order->review_invite_allowed)
<small>Nach der Zustellung bitten wir Sie ggf. per E-Mail um eine Bewertung der gekauften Artikel. Dem können Sie jederzeit widersprechen,
ohne dass andere als die Übermittlungskosten nach den Basistarifen entstehen: im Kundenkonto unter „Datenschutz“, über den Link in der E-Mail
oder formlos per Antwort auf diese E-Mail.</small>

@endif
Die AGB und die Widerrufsbelehrung finden Sie unter {{ route('page.show', 'agb') }} und {{ route('page.show', 'widerruf') }}.

Ihr Team von {{ $company['name'] }}<br>
{{ $company['street'] }}, {{ $company['postal_code'] }} {{ $company['city'] }} · {{ $company['phone'] }}
</x-mail::message>
