@include('services._trade-landing', [
    'type' => 'consignment',
    'eyebrow' => 'egun-Verkaufsservice',
    'title' => 'Ihre Waffe.<br><span class="fx-brand-text">Unser Verkauf.</span>',
    'lead' => 'Wir übernehmen den kompletten Verkauf über egun: Bewertung, Profi-Fotos, Beschreibung, Auktion und den rechtssicheren Versand an den Erwerber. Sie lehnen sich zurück.',
    'benefits' => [
        ['camera', 'Professionelle Präsentation', 'Studiofotos und fachkundige Beschreibung erzielen bessere Preise.'],
        ['shield', 'Rechtssichere Abwicklung', 'Prüfung der Erwerbsberechtigung und Versand nach WaffG durch uns.'],
        ['euro', 'Transparente Provision', 'Sie zahlen nur bei Erfolg. Auszahlung nach Zahlungseingang.'],
    ],
    'steps' => [
        ['Anfrage senden', 'Formular ausfüllen und bis zu 20 Fotos hochladen.'],
        ['Bewertung', 'Wir melden uns mit einer Preiseinschätzung und den Konditionen.'],
        ['Übergabe', 'Waffe im Geschäft abgeben oder nach Absprache an uns senden.'],
        ['Verkauf & Auszahlung', 'Wir verkaufen, versenden und zahlen Ihnen den Erlös aus.'],
    ],
    'faqs' => $faqs,
])
