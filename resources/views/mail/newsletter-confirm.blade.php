<x-mail::message>
# Fast geschafft

@if ($ratgeber)
Bitte bestätigen Sie Ihre E-Mail-Adresse. Direkt danach können Sie den **großen Waffenratgeber** als PDF herunterladen. Mit der Bestätigung melden Sie sich auch zum Newsletter von Armory Falter an.
@else
Bitte bestätigen Sie Ihre Anmeldung zum Newsletter von Armory Falter. Ohne Bestätigung senden wir Ihnen keine E-Mails.
@endif

<x-mail::button :url="$url">
{{ $ratgeber ? 'Bestätigen und Ratgeber herunterladen' : 'Anmeldung bestätigen' }}
</x-mail::button>

Der Link ist 7 Tage gültig. Wenn Sie sich nicht angemeldet haben, ignorieren Sie diese E-Mail einfach.

Ihr Team von Armory Falter
</x-mail::message>
