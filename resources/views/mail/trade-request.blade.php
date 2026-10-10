<x-mail::message>
@if ($internal)
# Neue Anfrage: {{ $r->type->label() }}

**{{ $r->reference }}** · {{ $r->contact['name'] }} · {{ $r->contact['email'] }} · {{ $r->contact['phone'] ?? '–' }}

{{ $r->manufacturerLabel() }} {{ $r->model }} · {{ $r->caliber ?? '–' }} · {{ $r->condition_code->label() }}
@if ($r->desired_price) · Wunschpreis {{ \App\Support\Money::format($r->desired_price) }} @endif

Das Beratungs-PDF mit allen Fotos hängt an.

<x-mail::button :url="url('/admin/trade-requests/'.$r->id)">Im Admin öffnen</x-mail::button>
@else
# Vielen Dank für Ihre Anfrage

Hallo {{ $r->contact['name'] }},

wir haben Ihre Anfrage **{{ $r->reference }}** ({{ $r->type->label() }}) für {{ $r->manufacturerLabel() }} {{ $r->model }} erhalten
und melden uns in der Regel innerhalb von zwei Werktagen mit einer Einschätzung.

Bitte senden Sie uns keine Waffe unaufgefordert zu. Den Ablauf und die Übergabe besprechen wir mit Ihnen persönlich.

Ihr Team von Armory Falter
@endif
</x-mail::message>
