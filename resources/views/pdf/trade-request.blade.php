{{-- PDF-Dossier zu einer Ankauf-/Verkaufsservice-Anfrage (dompdf). Variablen: $request (mit media, manufacturer), $photos (data-URIs), $company. --}}
@php
    use App\Support\Money;
    $logoFile = public_path('images/brand/armory-falter-logo-original.jpg');
    $logo = is_readable($logoFile) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoFile)) : null;
    $contact = $request->contact ?? [];
    $rows = [
        'Art der Waffe' => $request->weapon_type,
        'Hersteller' => $request->manufacturerLabel(),
        'Modell' => $request->model,
        'Kaliber' => $request->caliber,
        'Zustand' => $request->condition_code?->label(),
        'Preisvorstellung' => $request->desired_price ? Money::format($request->desired_price) : 'keine Angabe',
    ];
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>{{ $request->type->label() }} {{ $request->reference }}</title>
    <style>
        @page { margin: 18mm 18mm 20mm 18mm; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { font-size: 9.5pt; color: #111; line-height: 1.45; }
        .brand-bar { height: 3px; background: #9d4640; margin-bottom: 6mm; }
        table { border-collapse: collapse; width: 100%; }
        .logo { height: 14mm; }
        h1 { font-size: 15pt; margin: 6mm 0 1mm; }
        h2 { font-size: 10.5pt; margin: 7mm 0 2mm; padding-bottom: 1mm; border-bottom: 1pt solid #9d4640; text-transform: uppercase; letter-spacing: 0.5pt; }
        .muted { color: #555; }
        .data td { padding: 1.4mm 2mm; border-bottom: 0.5pt solid #ddd; vertical-align: top; }
        .data td:first-child { width: 35%; color: #555; }
        .desc { white-space: pre-wrap; }
        .photos td { width: 50%; padding: 2mm; vertical-align: top; text-align: center; }
        .photos img { max-width: 80mm; max-height: 70mm; border: 0.5pt solid #ccc; }
        .box { border: 0.5pt solid #999; padding: 3mm; margin-top: 3mm; }
        .footer { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 7pt; color: #555; text-align: center; }
    </style>
</head>
<body>
<div class="footer">{{ $company['name'] }} · {{ $company['street'] }} · {{ $company['postal_code'] }} {{ $company['city'] }} · {{ $company['phone'] }} · {{ $company['email'] }} – Vertraulich, nur für das Beratungsgespräch</div>

<div class="brand-bar"></div>
<table>
    <tr>
        <td>
            <h1>{{ $request->type->label() }}</h1>
            <div class="muted">Vorgang <strong>{{ $request->reference }}</strong> · eingegangen am {{ $request->created_at?->format('d.m.Y, H:i') }} Uhr</div>
        </td>
        <td style="text-align: right; vertical-align: top;">
            @if ($logo)<img src="{{ $logo }}" class="logo" alt="Armory Falter">@else<strong>{{ $company['name'] }}</strong>@endif
        </td>
    </tr>
</table>

<h2>Kontakt</h2>
<table class="data">
    <tr><td>Name</td><td>{{ $contact['name'] ?? '' }}</td></tr>
    @if (! empty($contact['street']) || ! empty($contact['city']))
        <tr><td>Anschrift</td><td>{{ $contact['street'] ?? '' }}<br>{{ trim(($contact['postal_code'] ?? '').' '.($contact['city'] ?? '')) }}</td></tr>
    @endif
    @if (! empty($contact['phone']))<tr><td>Telefon</td><td>{{ $contact['phone'] }}</td></tr>@endif
    <tr><td>E-Mail</td><td>{{ $contact['email'] ?? '' }}</td></tr>
    <tr><td>Kundenkonto</td><td>{{ $request->user_id ? 'ja (ID '.$request->user_id.')' : 'nein (Gast)' }}</td></tr>
</table>

<h2>Waffe</h2>
<table class="data">
    @foreach ($rows as $label => $value)
        @if (filled($value))<tr><td>{{ $label }}</td><td>{{ $value }}</td></tr>@endif
    @endforeach
</table>

@if (filled($request->description))
    <h2>Beschreibung des Kunden</h2>
    <div class="desc">{{ $request->description }}</div>
@endif

<h2>Interne Bewertung</h2>
<div class="box">
    <table class="data">
        <tr><td>Geprüft von / am</td><td>&nbsp;</td></tr>
        <tr><td>Zustand bestätigt</td><td>&nbsp;</td></tr>
        <tr><td>Marktwert (Recherche egun/Händler)</td><td>&nbsp;</td></tr>
        <tr><td>{{ $request->type->value === 'buyback' ? 'Ankaufsangebot' : 'Empfohlener Startpreis' }}</td><td>{{ $request->offer_price ? Money::format($request->offer_price) : '' }}&nbsp;</td></tr>
        <tr><td>Notizen</td><td style="height: 18mm;">&nbsp;</td></tr>
    </table>
</div>

@if (! empty($photos))
    <h2>Fotos ({{ count($photos) }})</h2>
    <table class="photos">
        @foreach (array_chunk($photos, 2) as $pair)
            <tr>
                @foreach ($pair as $src)<td><img src="{{ $src }}" alt="Foto"></td>@endforeach
                @if (count($pair) === 1)<td></td>@endif
            </tr>
        @endforeach
    </table>
@endif

<p class="muted" style="margin-top: 8mm; font-size: 7.5pt;">Der Kunde hat der Verarbeitung seiner Daten am {{ $request->privacy_accepted_at?->format('d.m.Y, H:i') }} Uhr zugestimmt. Die Überlassung erlaubnispflichtiger Waffen erfolgt ausschließlich persönlich oder per zugelassenem Waffenversand nach Prüfung der Berechtigung.</p>
</body>
</html>
