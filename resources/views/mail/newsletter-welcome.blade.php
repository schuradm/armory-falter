<x-mail::message>
@if ($subscriber->first_name)
Hallo {{ $subscriber->first_name }},
@else
Guten Tag,
@endif

@switch($step)
@case(1)
schön, dass Sie dabei sind! Armory Falter ist Ihr Waffenfachhandel in Wolfschlugen bei Stuttgart, für Jäger, Sportschützen, Sammler und Behörden.

@if ($subscriber->source === 'ratgeber')
Wie versprochen, hier noch einmal Ihr **großer Waffenratgeber** zum Herunterladen:

<x-mail::button :url="$subscriber->ratgeberUrl()" color="success">
Waffenratgeber herunterladen (PDF)
</x-mail::button>
@endif

Bei uns haben Sie **einen festen Ansprechpartner**, der Ihr Vorhaben kennt: vor Ort, am Telefon, per Video oder WhatsApp. Ob erste Büchse, Wechsel auf Geradezug oder die passende Optik für den Ansitz: Wir beraten ehrlich und ohne Verkaufsdruck.

<x-mail::button :url="route('appointments.consultation')">
Kostenlose Beratung vereinbaren
</x-mail::button>
@break

@case(2)
viele Kunden fragen uns: Wie funktioniert der Waffenkauf online? Ganz einfach, in drei Schritten:

1. **Auswählen:** Waffe in den Warenkorb legen oder bei uns reservieren lassen.
2. **Nachweis hochladen:** WBK mit Voreintrag, gelbe WBK oder Jagdschein sicher und verschlüsselt im Kundenkonto hochladen.
3. **Sicher erhalten:** Nach unserer Prüfung versenden wir mit persönlicher Übergabe, oder Sie holen in Wolfschlugen ab.

<x-mail::button :url="route('shop.index')">
Zum Shop
</x-mail::button>
@break

@case(3)
wussten Sie, dass wir eine eigene Werkstatt haben? Wir montieren Ihr Zielfernrohr, schießen Ihre Waffe ein, reinigen, reparieren und beschichten mit Cerakote.

Sie möchten eine Waffe verkaufen? Über unseren **Ankauf** machen wir Ihnen ein faires Angebot, oder wir verkaufen sie für Sie über unseren **Verkaufsservice** bei egun.

<x-mail::button :url="route('workshop.index')">
Werkstatt-Leistungen ansehen
</x-mail::button>

[Waffe verkaufen lassen]({{ route('trade.consignment') }}) · [Ankauf anfragen]({{ route('trade.buyback') }})
@break

@default
bei uns gibt es mehr als Waffen: Auf unseren **Testtagen** probieren Sie Neuheiten von Blaser, Sauer, Zeiss und Swarovski selbst aus, bei Workshops und Jagdabenden treffen Sie Gleichgesinnte.

Im **Elite-Club** erhalten Sie exklusive Vorteile, frühen Zugang zu Neuheiten und Einladungen zu Events.

<x-mail::button :url="route('events.index')">
Kommende Events
</x-mail::button>

[Mehr zum Elite-Club]({{ route('elite') }})
@endswitch

Herzliche Grüße<br>
Ihr Team von {{ config('shop.company.name') }}

<small>Sie erhalten diese E-Mail, weil Sie sich für den Newsletter von Armory Falter angemeldet haben.
[Abmelden]({{ $subscriber->unsubscribeUrl() }})</small>
</x-mail::message>
