<x-mail::message>
# {{ $voucher->recipient_name ? 'Hallo '.$voucher->recipient_name.',' : 'Ihr Geschenkgutschein' }}

@if ($voucher->recipient_name && $voucher->purchaser)
{{ $voucher->purchaser->first_name }} schenkt Ihnen einen Gutschein von Armory Falter im Wert von **{{ \App\Support\Money::format($voucher->initial_value) }}**.
@else
vielen Dank für Ihren Einkauf. Ihr Geschenkgutschein im Wert von **{{ \App\Support\Money::format($voucher->initial_value) }}** ist bereit.
@endif

@if ($voucher->message)
<x-mail::panel>
„{{ $voucher->message }}“
</x-mail::panel>
@endif

Gutscheincode: **{{ $voucher->formattedCode() }}**<br>
Gültig bis: {{ $voucher->expires_at->format('d.m.Y') }}

Den Gutschein im Shop-Design finden Sie als PDF im Anhang, zum Ausdrucken oder Weiterleiten. Einlösen können Sie ihn im Warenkorb oder in der Kasse, auch in mehreren Teilbeträgen.

<x-mail::button :url="route('shop.index')">
Jetzt im Shop stöbern
</x-mail::button>

Bitte behandeln Sie den Code wie Bargeld: Wer ihn kennt, kann ihn einlösen.

Ihr Team von Armory Falter
</x-mail::message>
