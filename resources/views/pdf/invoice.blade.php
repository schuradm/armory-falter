{{-- Rechnung (dompdf). Variablen: $order (mit items, user, payments), $number, $company. Beträge in Cent. --}}
@php
    use App\Support\Money;
    $logoFile = public_path('images/brand/armory-falter-logo-original.jpg');
    $logo = is_readable($logoFile) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoFile)) : null;
    $billing = $order->billing_address ?? [];
    $shippingAddr = $order->shipping_address;
    $shippingName = \App\Models\ShippingMethod::where('code', $order->shipping_method_code)->value('name') ?? $order->shipping_method_code;
    $payment = $order->payment_method_code instanceof \App\Enums\PaymentMethod ? $order->payment_method_code->label() : (string) $order->payment_method_code;
    // USt-Ausweis je Steuersatz nach anteiligem Rabatt (Order::vatBreakdown), Versand mit Regelsteuersatz
    $byRate = $order->vatBreakdown();
    // Eingelöste Geschenkgutscheine sind Zahlungen (Mehrzweck-Gutscheine): nach dem Gesamtbetrag abziehen
    $voucherPayments = $order->payments->where('provider', 'gift_voucher')->where('status', 'captured');
    $hasVoucherItems = $order->items->contains(fn ($i) => $i->isGiftVoucher());
    $paid = $order->payments->where('status', 'captured')->sum('amount');
    $issued = now();
    $addr = function (array $a): array {
        return array_values(array_filter([
            $a['company_name'] ?? null,
            trim(($a['first_name'] ?? '').' '.($a['last_name'] ?? '')),
            trim(($a['street'] ?? '').' '.($a['house_number'] ?? '')),
            $a['address_extra'] ?? null,
            trim(($a['postal_code'] ?? '').' '.($a['city'] ?? '')),
            ($a['country_code'] ?? 'DE') !== 'DE' ? ($a['country_code'] ?? null) : null,
        ]));
    };
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Rechnung {{ $number }}</title>
    <style>
        @page { margin: 22mm 18mm 28mm 20mm; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { font-size: 9.5pt; color: #111; line-height: 1.4; }
        .brand-bar { height: 3px; background: #9d4640; margin-bottom: 8mm; }
        .header td { vertical-align: top; }
        .logo { height: 16mm; }
        .sender { font-size: 7pt; color: #555; border-bottom: 0.5pt solid #999; padding-bottom: 1mm; margin-bottom: 2mm; }
        .meta td { padding: 0.3mm 0; line-height: 1.25; }
        .meta td:first-child { color: #555; padding-right: 4mm; }
        h1 { font-size: 15pt; margin: 7mm 0 4mm; letter-spacing: 0.5pt; }
        table { border-collapse: collapse; width: 100%; }
        .items th { background: #111; color: #fff; font-size: 8pt; text-align: left; padding: 2mm; }
        .items td { padding: 2mm; border-bottom: 0.5pt solid #ddd; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        .sku { color: #666; font-size: 7.5pt; }
        .totals { width: 52%; margin-left: 48%; margin-top: 4mm; }
        .totals td { padding: 1mm 2mm; }
        .totals .grand td { border-top: 1pt solid #111; font-weight: bold; font-size: 11pt; padding-top: 2mm; }
        .muted { color: #555; }
        .note { margin-top: 6mm; font-size: 8.5pt; }
        .note p { margin: 0 0 2mm; }
        .footer { position: fixed; bottom: -20mm; left: 0; right: 0; font-size: 7pt; color: #555; border-top: 0.5pt solid #999; padding-top: 2mm; }
        .footer td { vertical-align: top; width: 33%; }
    </style>
</head>
<body>
<div class="footer">
    <table><tr>
        <td>{{ $company['name'] }}<br>Inhaber: {{ $company['owner'] }}<br>{{ $company['street'] }}<br>{{ $company['postal_code'] }} {{ $company['city'] }}</td>
        <td>Tel. {{ $company['phone'] }}<br>{{ $company['email'] }}<br>USt-IdNr.: {{ $company['vat_id'] }}@if (! empty($company['tax_number']))<br>St.-Nr.: {{ $company['tax_number'] }}@endif</td>
        <td>{{ $company['bank']['name'] ?? '' }}<br>IBAN: {{ $company['bank']['iban'] ?? '' }}@if (! empty($company['bank']['bic']))<br>BIC: {{ $company['bank']['bic'] }}@endif</td>
    </tr></table>
</div>

<div class="brand-bar"></div>
<table class="header">
    <tr>
        <td style="width: 60%;">
            <div class="sender">{{ $company['name'] }} · {{ $company['street'] }} · {{ $company['postal_code'] }} {{ $company['city'] }}</div>
            @foreach ($addr($billing) as $line){{ $line }}<br>@endforeach
        </td>
        <td style="text-align: right;">
            @if ($logo)<img src="{{ $logo }}" class="logo" alt="Armory Falter">@else<strong>{{ $company['name'] }}</strong>@endif
            <table class="meta" style="width: auto; margin-left: auto; margin-top: 6mm;">
                <tr><td>Rechnungsnr.</td><td class="num"><strong>{{ $number }}</strong></td></tr>
                <tr><td>Rechnungsdatum</td><td class="num">{{ $issued->format('d.m.Y') }}</td></tr>
                <tr><td>Bestellnr.</td><td class="num">{{ $order->order_number }}</td></tr>
                <tr><td>Bestelldatum</td><td class="num">{{ $order->placed_at?->format('d.m.Y') }}</td></tr>
                @if ($order->user?->customer_number)<tr><td>Kundennr.</td><td class="num">{{ $order->user->customer_number }}</td></tr>@endif
                <tr><td>Lieferdatum</td><td class="num">{{ $issued->format('d.m.Y') }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<h1>RECHNUNG</h1>

<table class="items">
    <thead>
        <tr><th style="width: 6%;">Pos.</th><th>Artikel</th><th class="num" style="width: 8%;">Menge</th><th class="num" style="width: 10%;">USt.</th><th class="num" style="width: 15%;">Einzelpreis</th><th class="num" style="width: 15%;">Gesamt</th></tr>
    </thead>
    <tbody>
        @foreach ($order->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->name }}<br><span class="sku">Art.-Nr. {{ $item->sku }}</span></td>
                <td class="num">{{ $item->quantity }}</td>
                <td class="num">{{ $item->isGiftVoucher() ? '—' : rtrim(rtrim(number_format((float) $item->tax_rate, 2, ',', ''), '0'), ',').' %' }}</td>
                <td class="num">{{ Money::format($item->unit_price_gross) }}</td>
                <td class="num">{{ Money::format($item->line_total_gross) }}</td>
            </tr>
        @endforeach
        <tr>
            <td></td>
            <td>Versand: {{ $shippingName }}</td>
            <td class="num">1</td>
            <td class="num">19 %</td>
            <td class="num">{{ Money::format($order->shipping_gross) }}</td>
            <td class="num">{{ Money::format($order->shipping_gross) }}</td>
        </tr>
    </tbody>
</table>

<table class="totals">
    <tr><td>Zwischensumme</td><td class="num">{{ Money::format($order->subtotal_gross) }}</td></tr>
    <tr><td>Versand</td><td class="num">{{ Money::format($order->shipping_gross) }}</td></tr>
    @if ($order->discount_gross > 0)
        <tr><td>Rabatt{{ $order->discount_code ? ' ('.$order->discount_code.')' : '' }}</td><td class="num">−{{ Money::format($order->discount_gross) }}</td></tr>
    @endif
    <tr class="grand"><td>Gesamtbetrag</td><td class="num">{{ Money::format($order->total_gross) }}</td></tr>
    @foreach ($byRate as $rate => $v)
        <tr class="muted"><td>enthaltene USt. {{ str_replace('.', ',', $rate) }} % (netto {{ str_replace(" ", "\u{00A0}", Money::format($v['net'])) }})</td><td class="num">{{ Money::format($v['tax']) }}</td></tr>
    @endforeach
    @foreach ($voucherPayments as $vp)
        <tr><td>abzgl. Geschenkgutschein …{{ substr(\Illuminate\Support\Str::before($vp->provider_reference, '-'), -4) }}</td><td class="num">−{{ Money::format($vp->amount) }}</td></tr>
    @endforeach
    @if ($voucherPayments->isNotEmpty())
        <tr class="grand"><td>Zahlbetrag</td><td class="num">{{ Money::format($order->amountDue()) }}</td></tr>
    @endif
</table>

<div class="note">
    <p><strong>Zahlungsart:</strong> {{ $payment }}</p>
    @if ($shippingAddr)
        <p><strong>Lieferanschrift:</strong> {{ implode(', ', $addr($shippingAddr)) }}</p>
    @endif
    @if ($paid >= $order->total_gross)
        <p>Der Rechnungsbetrag wurde bereits beglichen. Vielen Dank.</p>
    @elseif (($order->payment_method_code?->value ?? null) === 'prepayment')
        <p>Bitte überweisen Sie {{ $voucherPayments->isNotEmpty() ? 'den Zahlbetrag' : 'den Gesamtbetrag' }} unter Angabe der Rechnungsnummer <strong>{{ $number }}</strong> auf das unten genannte Konto.</p>
    @endif
    @if ($order->discount_gross > 0)
        <p class="muted">Der Rabatt ist anteilig auf die rabattierten Positionen verteilt und mindert das Entgelt je Steuersatz (§ 17 UStG).</p>
    @endif
    @if ($hasVoucherItems)
        <p class="muted">Geschenkgutscheine sind Mehrzweck-Gutscheine (§ 3 Abs. 15 UStG). Beim Verkauf fällt keine Umsatzsteuer an; sie wird bei Einlösung auf die dann gelieferten Waren berechnet und ausgewiesen.</p>
    @endif
    @if ($voucherPayments->isNotEmpty())
        <p class="muted">Der eingelöste Geschenkgutschein (Mehrzweck-Gutschein) gilt als Zahlung und mindert die ausgewiesene Umsatzsteuer nicht.</p>
    @endif
    <p class="muted">Das Lieferdatum entspricht dem Rechnungsdatum, sofern nicht anders angegeben. Bei erlaubnispflichtigen Waffen und Munition erfolgt die Überlassung nach Prüfung der Erwerbsberechtigung gemäß § 34 WaffG.</p>
    <p>Vielen Dank für Ihren Einkauf bei {{ $company['name'] }}.</p>
</div>
</body>
</html>
