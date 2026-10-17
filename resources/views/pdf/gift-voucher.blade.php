{{-- Geschenkgutschein (dompdf), CI wie pdf/invoice. Variablen: $voucher, $company. Beträge in Cent. --}}
@php
    use App\Support\Money;
    $logoFile = public_path('images/brand/armory-falter-logo-original.jpg');
    $logo = is_readable($logoFile) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoFile)) : null;
    $web = $company['web'] ?? 'www.armory-falter.de';
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Geschenkgutschein {{ $voucher->formattedCode() }}</title>
    <style>
        @page { margin: 22mm 18mm 28mm 20mm; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { font-size: 9.5pt; color: #111; line-height: 1.4; }
        table { border-collapse: collapse; width: 100%; }
        .brand-bar { height: 3px; background: #9d4640; margin-bottom: 8mm; }
        .logo { height: 16mm; }
        .kicker { font-size: 7.5pt; color: #555; letter-spacing: 1.5pt; text-transform: uppercase; text-align: right; }
        .card { background: #111; color: #fff; padding: 14mm 12mm 12mm; margin-top: 12mm; }
        .card .eyebrow { color: #e0786f; font-size: 8pt; letter-spacing: 2.5pt; text-transform: uppercase; font-weight: bold; }
        .card h1 { font-size: 26pt; margin: 3mm 0 2mm; letter-spacing: 0.5pt; }
        .card .accent { width: 22mm; height: 2.5pt; background: #9d4640; margin: 4mm 0 6mm; }
        .card .value { font-size: 40pt; font-weight: bold; letter-spacing: 0.5pt; }
        .card .for { font-size: 11pt; color: #ddd; margin-top: 4mm; }
        .card .message { font-size: 10.5pt; color: #eee; font-style: italic; margin-top: 5mm; border-left: 2.5pt solid #9d4640; padding-left: 4mm; }
        .code-box { margin-top: 8mm; border: 1.2pt solid #111; padding: 5mm 6mm; }
        .code-box td { vertical-align: middle; }
        .code-label { font-size: 7.5pt; color: #555; letter-spacing: 1.5pt; text-transform: uppercase; }
        .code { font-family: "DejaVu Sans Mono", monospace; font-size: 18pt; font-weight: bold; letter-spacing: 2pt; color: #9d4640; }
        .meta td { padding: 0.6mm 0; }
        .meta td:first-child { color: #555; padding-right: 4mm; }
        h2 { font-size: 11pt; margin: 9mm 0 2mm; }
        ol { margin: 0; padding-left: 5mm; }
        li { margin-bottom: 1.2mm; }
        .small { font-size: 7.5pt; color: #555; margin-top: 7mm; }
        .footer { position: fixed; bottom: -20mm; left: 0; right: 0; font-size: 7pt; color: #555; border-top: 0.5pt solid #999; padding-top: 2mm; }
        .footer td { vertical-align: top; width: 33%; }
    </style>
</head>
<body>
<div class="footer">
    <table><tr>
        <td>{{ $company['name'] }}<br>Inhaber: {{ $company['owner'] }}<br>{{ $company['street'] }}<br>{{ $company['postal_code'] }} {{ $company['city'] }}</td>
        <td>Tel. {{ $company['phone'] }}<br>{{ $company['email'] }}<br>{{ $web }}</td>
        <td>USt-IdNr.: {{ $company['vat_id'] }}@if (! empty($company['tax_number']))<br>St.-Nr.: {{ $company['tax_number'] }}@endif</td>
    </tr></table>
</div>

<div class="brand-bar"></div>
<table>
    <tr>
        <td>@if ($logo)<img src="{{ $logo }}" class="logo" alt="Armory Falter">@else<strong>{{ $company['name'] }}</strong>@endif</td>
        <td class="kicker">Geschenkgutschein · Präzision. Vertrauen. Waffenkunde.</td>
    </tr>
</table>

<div class="card">
    <div class="eyebrow">Armory Falter · Wolfschlugen</div>
    <h1>GESCHENKGUTSCHEIN</h1>
    <div class="accent"></div>
    <div class="value">{{ Money::format($voucher->initial_value) }}</div>
    @if ($voucher->recipient_name)
        <div class="for">Für {{ $voucher->recipient_name }}@if ($voucher->purchaser) · von {{ $voucher->purchaser->first_name }}@endif</div>
    @endif
    @if ($voucher->message)
        <div class="message">„{{ $voucher->message }}“</div>
    @endif
</div>

<div class="code-box">
    <table>
        <tr>
            <td>
                <div class="code-label">Gutscheincode</div>
                <div class="code">{{ $voucher->formattedCode() }}</div>
            </td>
            <td style="text-align: right;">
                <table class="meta" style="width: auto; margin-left: auto;">
                    <tr><td>Wert</td><td>{{ Money::format($voucher->initial_value) }}</td></tr>
                    <tr><td>Ausgestellt am</td><td>{{ $voucher->created_at->format('d.m.Y') }}</td></tr>
                    <tr><td>Gültig bis</td><td><strong>{{ $voucher->expires_at->format('d.m.Y') }}</strong></td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<h2>So lösen Sie den Gutschein ein</h2>
<ol>
    <li>Online unter {{ $web }}: Code im Warenkorb oder in der Kasse eingeben. Das Guthaben wird wie eine Zahlung vom Rechnungsbetrag abgezogen.</li>
    <li>Im Geschäft in {{ $company['city'] }}: Gutschein ausgedruckt oder auf dem Smartphone vorzeigen.</li>
    <li>Teileinlösung ist möglich, das Restguthaben bleibt bis zum Ablaufdatum erhalten. Reicht das Guthaben nicht, zahlen Sie den Rest mit einer anderen Zahlungsart.</li>
</ol>

<p class="small">
    Gültig drei Jahre zum Jahresende (§§ 195, 199 BGB), keine Barauszahlung. Der Gutschein ist übertragbar; wer den Code kennt, kann ihn einlösen. Bitte behandeln Sie ihn wie Bargeld.
    Mehrzweck-Gutschein im Sinne von § 3 Abs. 15 UStG: Die Umsatzsteuer wird bei Einlösung auf die gelieferte Ware berechnet.
    Für erlaubnispflichtige Waffen und Munition gelten auch bei Zahlung per Gutschein die gesetzlichen Erwerbsvoraussetzungen (§ 34 WaffG).
</p>
</body>
</html>
