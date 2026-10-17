<x-mail::message>
@if ($internal)
# Neuer Widerruf über die Website

@else
# Ihr Widerruf ist bei uns eingegangen

Guten Tag {{ $w->name }},

hiermit bestätigen wir den Eingang Ihrer Widerrufserklärung. Bitte bewahren Sie diese E-Mail auf.

@endif
<x-mail::table>
| | |
|:--|:--|
| Vorgang | {{ $w->reference }} |
| Eingegangen am | {{ $w->declared_at->timezone('Europe/Berlin')->format('d.m.Y') }} um {{ $w->declared_at->timezone('Europe/Berlin')->format('H:i:s') }} Uhr |
| Name | {{ $w->name }} |
| E-Mail | {{ $w->email }} |
| Bestellnummer | {{ $w->order_number }} |
@if ($w->items)
| Widerrufene Artikel | {{ str_replace(["\r", "\n", '|'], [' ', ' ', '/'], $w->items) }} |
@endif
@if ($w->message)
| Nachricht | {{ str_replace(["\r", "\n", '|'], [' ', ' ', '/'], $w->message) }} |
@endif
</x-mail::table>

**Erklärung:** Hiermit widerrufe ich den oben genannten Vertrag{{ $w->items ? ' über die angegebenen Artikel' : '' }}.

@if ($internal)
@if ($w->order_id)
<x-mail::button :url="\App\Filament\Resources\OrderResource::getUrl('view', ['record' => $w->order])">Bestellung im Backend öffnen</x-mail::button>
@else
Die Bestellnummer konnte keiner Bestellung des Kunden zugeordnet werden. Bitte manuell prüfen.
@endif
@else
**So geht es weiter:** Wir melden uns zur Rückabwicklung. Frei verkäufliche Ware senden Sie bitte an {{ config('shop.company.name') }}, {{ config('shop.company.street') }}, {{ config('shop.company.postal_code') }} {{ config('shop.company.city') }}. **Erlaubnispflichtige Waffen und Munition** bitte nicht als normales Paket zurücksenden: Geben Sie sie persönlich im Geschäft zurück oder stimmen Sie den Versand mit persönlicher Übergabe mit uns ab.

Fragen? Telefon {{ config('shop.company.phone') }} oder einfach auf diese E-Mail antworten.

Ihr Team von {{ config('shop.company.name') }}
@endif
</x-mail::message>
