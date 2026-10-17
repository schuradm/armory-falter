<x-mail::message>
# Hallo {{ $name }},

Ihre Bestellung {{ $order->order_number }} ist bei Ihnen angekommen. Wie zufrieden sind Sie mit den Artikeln? Ihre ehrliche Bewertung hilft anderen Schützen und Jägern bei der Auswahl, und uns, besser zu werden.

@foreach ($products as $product)
- [{{ $product->name }}]({{ route('account.reviews', ['produkt' => $product->slug]) }})
@endforeach

<x-mail::button :url="route('account.reviews')">
Jetzt bewerten
</x-mail::button>

Bewerten können nur Kunden, die den Artikel bei uns gekauft und erhalten haben. Wir veröffentlichen positive wie negative Bewertungen und gewähren für Bewertungen keine Gegenleistung.

Ihr Team von Armory Falter

<small>Wir senden Ihnen diese einmalige Einladung als Kunde zu einem bei uns gekauften Artikel (§ 7 Abs. 3 UWG). Sie können der Verwendung Ihrer E-Mail-Adresse für Bewertungseinladungen jederzeit widersprechen, ohne dass andere als die Übermittlungskosten nach den Basistarifen entstehen: [keine Bewertungseinladungen mehr erhalten]({{ $optOutUrl }}).</small>
</x-mail::message>
