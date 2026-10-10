<?php

return [
    'company' => [
        'name' => env('SHOP_COMPANY_NAME', 'Armory Falter'),
        'owner' => env('SHOP_COMPANY_OWNER', 'Mark Falter'),
        'street' => env('SHOP_COMPANY_STREET', 'Stuttgarter Straße 2/1'),
        'postal_code' => env('SHOP_COMPANY_ZIP', '72649'),
        'city' => env('SHOP_COMPANY_CITY', 'Wolfschlugen'),
        'country' => 'DE',
        'phone' => env('SHOP_COMPANY_PHONE', '+49 151 29107670'),
        'email' => env('SHOP_COMPANY_EMAIL', 'info@armory-falter.de'),
        'vat_id' => env('SHOP_COMPANY_VAT_ID', 'DE…'),            // TODO: echte USt-IdNr.
        'tax_number' => env('SHOP_COMPANY_TAX_NUMBER'),
        'weapon_license_authority' => env('SHOP_WEAPON_AUTHORITY', 'Landratsamt Esslingen'), // TODO bestätigen
        'bank' => [
            'holder' => env('SHOP_BANK_HOLDER', 'Armory Falter'),
            'iban' => env('SHOP_BANK_IBAN', 'DE00 0000 0000 0000 0000 00'),
            'bic' => env('SHOP_BANK_BIC', ''),
            'name' => env('SHOP_BANK_NAME', ''),
        ],
        'geo' => ['lat' => 48.6497, 'lng' => 9.2953],
        // Öffnungszeiten (für Terminbuchung und Schema.org). TODO: mit echten Zeiten abgleichen.
        'opening_hours' => [
            1 => ['10:00', '18:00'], 2 => ['10:00', '18:00'], 3 => ['10:00', '18:00'],
            4 => ['10:00', '18:00'], 5 => ['10:00', '18:00'], 6 => ['10:00', '14:00'],
        ],
        'social' => [
            'instagram' => env('SHOP_SOCIAL_INSTAGRAM'),
            'youtube' => env('SHOP_SOCIAL_YOUTUBE'),
            'facebook' => env('SHOP_SOCIAL_FACEBOOK'),
            'whatsapp' => env('SHOP_SOCIAL_WHATSAPP', '4915129107670'),
        ],
    ],

    'logo' => [
        // Logo von www.armory-falter.de als Vektor (dunkle Schrift für helle Flächen, helle Variante fürs Dark-Theme)
        'path' => 'images/brand/armory-falter-logo.svg',
        'path_light' => 'images/brand/armory-falter-logo-light.svg',
    ],

    'payment_methods' => [
        'prepayment' => ['enabled' => true],
        'cash_pickup' => ['enabled' => true],
        'paypal' => [
            'enabled' => (bool) env('PAYPAL_ENABLED', false),
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'secret' => env('PAYPAL_SECRET'),
            'sandbox' => (bool) env('PAYPAL_SANDBOX', true),
            'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
        ],
        'card' => ['enabled' => (bool) env('CARD_ENABLED', false)],
        // Klarna Payments + Hosted Payment Page. „Sofort“ heißt bei Klarna heute „Pay now“ (Kategorie pay_now).
        'sofort' => ['enabled' => (bool) env('KLARNA_SOFORT_ENABLED', false)],
        'klarna' => [
            'enabled' => (bool) env('KLARNA_ENABLED', false),
            'username' => env('KLARNA_USERNAME'),
            'password' => env('KLARNA_PASSWORD'),
            'playground' => (bool) env('KLARNA_PLAYGROUND', true),
        ],
        'psp_weapon' => ['enabled' => (bool) env('WEAPON_PSP_ENABLED', false)],
    ],

    'prepayment_days' => 7,

    'documents' => [
        'disk' => 'documents',
        'max_kb' => 10240,
        'mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
        // Aufbewahrung nach letzter Nutzung, danach automatische Löschung (Löschkonzept, anwaltlich bestätigen)
        'retention_months_after_expiry' => 6,
        'retention_months_rejected' => 1,
    ],

    'trade_requests' => [
        'max_photos' => 20,
        'max_photo_kb' => 8192,
    ],

    'appointments' => [
        'slot_minutes' => 30,
        'lead_time_hours' => 24,
        'days_ahead' => 30,
        'resources' => ['werkstatt' => 'Werkstatt', 'beratung' => 'Beratung'],
    ],

    'captcha' => [
        'driver' => env('CAPTCHA_DRIVER', 'turnstile'), // turnstile | recaptcha | none
        'turnstile_site_key' => env('TURNSTILE_SITE_KEY'),
        'turnstile_secret' => env('TURNSTILE_SECRET'),
        'recaptcha_site_key' => env('RECAPTCHA_SITE_KEY'),
        'recaptcha_secret' => env('RECAPTCHA_SECRET'),
    ],

    'blind_index_key' => env('BLIND_INDEX_KEY'),

    'free_shipping_from' => 15000, // Cent, nur Standardversand

    // Ganzseiten-Cache für Gäste ohne Warenkorb (App\Http\Middleware\CachePublicPages)
    'page_cache' => [
        'enabled' => env('PAGE_CACHE', true),
        'ttl' => 900,
        'except' => ['konto*', 'kasse*', 'warenkorb', 'zahlung*', 'admin*', 'livewire*', 'newsletter*', 'downloads*', 'suche', 'vergleich', 'sitemap.xml', 'robots.txt', 'up'],
    ],

    // Performance-Schalter
    'performance' => [
        'inline_css' => env('INLINE_CSS', true),
    ],

    // Standardinhalte der Startseite (AIDA). Überschreibbar im Backend unter Einstellungen → Startseite.
    'home' => [
        'hero' => [
            'eyebrow' => 'Willkommen bei Armory Falter',
            'headline' => 'Präzision.|Vertrauen.|Leidenschaft.',
            'text' => 'Ihr Partner für Jagd, Sport und Präzision. Die erste Anlaufstelle für Jäger, Sportschützen, Sammler und Behörden, die höchste Qualität, echte Expertise und persönlichen Service erwarten.',
            'cta_primary' => ['label' => 'Shop entdecken', 'url' => '/shop'],
            'cta_secondary' => ['label' => 'Beratung vereinbaren', 'url' => '/beratung'],
            'image' => null,
            'video' => null,
        ],
        'stats' => [
            ['value' => 11, 'suffix' => '', 'label' => 'Premium-Marken'],
            ['value' => 6, 'suffix' => '', 'label' => 'Werkstatt-Leistungen'],
            ['value' => 1, 'suffix' => '', 'label' => 'fester Ansprechpartner für Sie'],
        ],
        'promise_title' => 'Mehr als Waffen.',
        'promise_text' => 'Die meisten Händler verkaufen Produkte. Wir bieten Sicherheit, Fachwissen und Beratung, auf die Sie sich verlassen können. Vom ersten Gespräch bis zur Übergabe.',
        'usps' => [
            ['icon' => 'users', 'title' => 'Ihr persönlicher Waffenberater', 'text' => 'Feste Ansprechperson per Telefon, Video oder WhatsApp. Sie erklären Ihr Vorhaben einmal, wir kennen Sie beim nächsten Mal.'],
            ['icon' => 'target', 'title' => 'Kostenlose Kaufberatung', 'text' => 'Ist die Waffe die richtige für Sie? Wir analysieren Jagd- oder Sportzweck und empfehlen das passende Kaliber.'],
            ['icon' => 'euro', 'title' => 'Verkaufsservice', 'text' => 'Bilder, Daten, Wunschpreis hochladen. Wir bewerten, vermarkten, verkaufen und versenden für Sie.'],
            ['icon' => 'wrench', 'title' => 'Eigene Werkstatt', 'text' => 'Optikmontage, Einschießen, Reinigung, Reparatur und Cerakote vom Fachmann.'],
        ],
        'steps_title' => 'In 3 Schritten zur neuen Waffe',
        'steps' => [
            ['title' => 'Auswählen', 'text' => 'Waffe in den Warenkorb legen oder reservieren. Fragen? Ihr Berater ist für Sie da.'],
            ['title' => 'Nachweis hochladen', 'text' => 'WBK oder Jagdschein sicher im Kundenkonto hochladen. Wir prüfen und geben frei.'],
            ['title' => 'Sicher erhalten', 'text' => 'Persönliche Übergabe durch den Zusteller oder Abholung im Laden in Wolfschlugen.'],
        ],
        'lead' => [
            'title' => 'Der große Waffenratgeber 2026',
            'text' => 'Kaliber, Optik, Waffenrecht und Kaufberatung kompakt als PDF. Kostenlos per E-Mail.',
            'file' => null,
        ],
        'cta' => [
            'headline' => 'Bereit für Ihre nächste Saison?',
            'text' => 'Sichern Sie sich Ihren persönlichen Beratungstermin. Vor Ort, am Telefon oder per Video.',
            'label' => 'Beratung vereinbaren',
            'url' => '/beratung',
        ],
    ],

    // „Warum Armory Falter?“ unter jedem Produkt (Backend: Einstellungen → Produktseite)
    'product_why' => [
        'Geprüfte Ware vom Fachhändler',
        'Persönliche Beratung vor und nach dem Kauf',
        'Persönliche Zustellung oder Abholung vor Ort',
        'Fachhändler aus Deutschland',
        'Sichere, verschlüsselte Dokumentenprüfung',
        'Workshops und Schulungen für Kunden',
    ],

    // Community-Kanäle (Backend: Einstellungen → Community). Leer = ausgeblendet.
    'community' => [
        'facebook' => env('SOCIAL_FACEBOOK'),
        'instagram' => env('SOCIAL_INSTAGRAM'),
        'youtube' => env('SOCIAL_YOUTUBE'),
        'whatsapp_channel' => env('SOCIAL_WHATSAPP_CHANNEL'),
        'telegram' => env('SOCIAL_TELEGRAM'),
    ],
];
