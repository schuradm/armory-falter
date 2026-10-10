@include('services._trade-landing', [
    'type' => 'buyback',
    'eyebrow' => 'Waffen-Ankauf',
    'title' => 'Wir kaufen<br><span class="fx-brand-text">Ihre Waffen.</span>',
    'lead' => 'Jagdwaffen, Sportwaffen, Kurzwaffen, Optiken, Nachlässe und ganze Sammlungen: faire Bewertung durch Fachleute, sofortige Auszahlung und rechtssichere Austragung.',
    'benefits' => [
        ['euro', 'Faire Preise', 'Marktgerechte Bewertung auf Basis aktueller Verkaufsdaten.'],
        ['clock', 'Schnelle Auszahlung', 'Bei Annahme Auszahlung per Überweisung oder bar.'],
        ['users', 'Nachlässe & Sammlungen', 'Diskrete Abwicklung, auch für Erben ohne eigene WBK.'],
    ],
    'steps' => [
        ['Angebot anfragen', 'Daten und Fotos über das Formular senden.'],
        ['Unverbindliches Angebot', 'Sie erhalten unseren Ankaufspreis.'],
        ['Übergabe & Auszahlung', 'Übergabe mit WBK, Austragung und Auszahlung sofort.'],
    ],
    'faqs' => $faqs,
])
