# 05 – Frontend & Design-System (Blade · Livewire 3 · Alpine.js · Tailwind CSS)

Stand: 10.10.2026 · Ersetzt das ursprünglich geplante Dokument „Next.js-Frontend-Struktur“.

## 1. Technischer Rahmen

| Baustein | Einsatz |
|---|---|
| **Blade** | Serverseitiges Rendering aller Seiten (SEO, schnelle LCP), anonyme Komponenten in `resources/views/components` |
| **Livewire 3** | Interaktive Inseln: Filter, Suche, Warenkorb, Checkout, Uploads, Formulare, Terminbuchung |
| **Alpine.js** (in Livewire enthalten) | Lokale UI-Zustände: Menüs, Galerie, Tabs, Dark-Mode, Animationen (`x-transition`, `x-intersect`, `x-collapse`) |
| **Tailwind CSS** | Utility-CSS mit Design-Tokens aus CSS-Variablen |
| **Vite** | Build lokal bzw. in CI (`npm ci && npm run build`); hochgeladen wird nur `public/build` – **kein Node auf dem Server** |

Animationen im Stil von Framer Motion werden mit CSS-Transitions/-Keyframes + Alpine-Plugins umgesetzt (Einblenden beim Scrollen mit `x-intersect.once`, gestaffelte Verzögerung über `style="--d: 80ms"`). Kein JS-Animationsframework.

## 2. Verzeichnisstruktur `resources/`

```
resources/
├── css/
│   ├── app.css              @import tailwind; Tokens (:root / .dark), Basis-Typografie, Komponenten-Layer
│   └── critical/            home.css, listing.css, product.css (inline in <head>)
├── js/
│   ├── app.js               Alpine-Plugins (intersect, collapse, focus), Theme-Toggle, Consent-Bridge
│   └── modules/             hero-video.js, gallery.js, consent.js (lazy importiert)
├── fonts/                   montserrat-*.woff2, poppins-*.woff2, roboto-*.woff2 (gesubsettet: Latin + Latin-1)
├── images/                  Logo (SVG), Icons (SVG-Sprite)
└── views/
    ├── layouts/             app.blade.php, checkout.blade.php (reduziert), account.blade.php, minimal.blade.php
    ├── partials/            header, topbar, mega-menu, mobile-nav, footer, consent-banner, trust-bar, breadcrumbs, seo-head
    ├── components/          (siehe 3.)
    ├── pages/               home, category, product, brand, caliber, landing, blog/*, faq, legal
    ├── livewire/            Views der Livewire-Komponenten
    └── emails/              Markdown-Mails im CI
```

## 3. Blade-Komponenten

| Komponente | Zweck / Props |
|---|---|
| `<x-button>` | `variant` (primary-gold, secondary, ghost, danger), `size`, `href`, `icon`; Fokusring sichtbar |
| `<x-icon>` | SVG-Sprite, `aria-hidden` Standard |
| `<x-picture>` | `src`, `alt`, `sizes`, `priority`; erzeugt `<picture>` mit AVIF/WebP/JPEG-`srcset`, `width/height`, `loading`, `fetchpriority` |
| `<x-price>` | Preis inkl. MwSt., Sonderpreis mit **„Niedrigster Preis der letzten 30 Tage“**, UVP, Grundpreis bei Munition (€/Schuss bzw. €/100 Stk.) |
| `<x-legal-badge>` | Rechtsklasse: „Ab 18“, „Erwerbsberechtigung erforderlich“, „Nur Abholung“ mit Tooltip/Link zur Erklärung |
| `<x-availability>` | Ampel + Text (nie nur Farbe – Barrierefreiheit) |
| `<x-product-card>` | Bild, Hersteller, Name, Kaliber, Preis, Badges (Neu, Gebraucht, Aktion), Aktionen Merken/Vergleichen/QuickView |
| `<x-category-tile>` | Bild, Titel, Anzahl Artikel |
| `<x-brand-strip>` | Herstellerlogos (SVG, graustufig, Hover in Farbe) |
| `<x-section-heading>` | Overline (Gold, Versalien), H2, Lead |
| `<x-breadcrumbs>` | inkl. JSON-LD |
| `<x-faq-accordion>` | `<details>/<summary>` (funktioniert ohne JS) + FAQPage-JSON-LD |
| `<x-tech-table>` | Technische Daten gruppiert |
| `<x-alert>` | info/success/warning/danger mit `role="status"`/`role="alert"` |
| `<x-modal>`, `<x-drawer>` | Fokusfalle, `Esc`, `aria-modal` |
| `<x-form.*>` | input, select, checkbox, file, textarea mit Label, Hilfetext, Fehlertext (`aria-describedby`) |
| `<x-captcha>` | Turnstile-Widget (lädt Skript erst bei Formular-Interaktion) |
| `<x-video-embed>` | YouTube-nocookie erst nach Consent (Zwei-Klick-Lösung), sonst Vorschaubild |
| `<x-trust-bar>` | Fachhändler seit…, Werkstatt im Haus, Versand an Berechtigte, Beratung vor Ort |

## 4. Livewire-Komponenten

| Komponente | Verhalten | Technik |
|---|---|---|
| `Catalog\ProductFilter` | Live-Filter Hersteller, Kaliber, Preis (Range), Verfügbarkeit, Zustand neu/gebraucht, Einsatz Jagd/Sport, dynamische Attribute; Trefferzahl je Facette | `#[Url]`-Properties (teilbare URLs, `noindex`), `wire:model.live.debounce.300ms`, Ergebnisliste als Kind-Komponente, Pagination `?seite=` |
| `Catalog\SearchAutocomplete` | AJAX-Vorschläge ab 2 Zeichen: Produkte mit Bild/Preis, Kategorien, Hersteller, Kaliber-Aliase („7,62x51“ → .308 Win.) | `wire:model.live.debounce.200ms`, Tastaturnavigation (Combobox-Pattern ARIA 1.2), Ergebnis-Cache 5 Min. |
| `Catalog\QuickView` | Produkt-Kurzansicht im Modal (Bilder, Preis, Varianten, In den Warenkorb) | `#[Lazy]`, lädt erst beim Öffnen |
| `Catalog\CompareBar` | Bis 4 Produkte vergleichen, Leiste unten, Vergleichsseite mit Attributtabelle | Session-Speicher, Event `compare-updated` |
| `Catalog\WishlistButton` / `Account\Wishlists` | Merken (Gast: Session, Kunde: DB), mehrere Listen, Preis-/Verfügbarkeitsalarm | |
| `Cart\MiniCart` | Off-Canvas-Warenkorb mit Compliance-Hinweisen je Position | Event `cart-updated` |
| `Cart\CartPage` | Mengen ändern, Hinweise „Berechtigung erforderlich – jetzt Dokument hochladen“, Versandkostenvorschau | |
| `Checkout\Wizard` mit Schritten `AddressStep`, `EntitlementStep`, `ShippingStep`, `PaymentStep`, `ReviewStep` | Schritt „Berechtigung“ nur bei erlaubnispflichtigen Positionen; serverseitige Neuprüfung bei jedem Schritt und beim Absenden | Zustand in `CheckoutSession` (DB), nicht im Client |
| `Account\DocumentUpload` | Dokumenttyp wählen, Datei (PDF/JPG/PNG, ≤ 10 MB), Ablaufdatum, Dokumentnummer; Fortschritt, Vorschau, Statusliste | `WithFileUploads`, temporäre Uploads auf private Disk, Validierung `mimetypes` + Größe |
| `Trade\TradeRequestForm` | Mehrstufig: Art (Verkaufsservice/Ankauf) → Waffe (Hersteller, Modell, Kaliber, Zustand, Wunschpreis) → **bis 20 Fotos** (Drag & Drop, Sortierung, clientseitige Verkleinerung auf 2560 px) → Kontakt → Bestätigung | `WithFileUploads` mit Mehrfachauswahl, Upload in Chunks pro Foto, Turnstile |
| `Workshop\AppointmentBooking` | Leistung → Datum (Kalender) → freier Slot → Waffenbeschreibung → Kontakt → Bestätigung + ICS | `SlotFinder`, Slot-Reservierung 10 Min. |
| `Newsletter\SignupForm` | Double-Opt-in, Interessen | Honeypot + Zeitfalle |
| `Blog\CommentForm` | Kommentar mit Moderation | Turnstile, Honeypot |

Regeln: Jede Livewire-Aktion wird serverseitig autorisiert; keine sensiblen Daten in öffentlichen Properties (`#[Locked]` für IDs); große Komponenten `lazy`, damit sie den First Paint nicht blockieren.

## 5. Design-Tokens

### 5.1 Farben (CSS-Variablen, Tailwind-Mapping)

| Token | Dark (Standard) | Light | Verwendung |
|---|---|---|---|
| `--color-bg` | `#111111` | `#ffffff` | Seitenhintergrund |
| `--color-surface` | `#1e1e1e` | `#f5f5f5` | Karten, Header |
| `--color-surface-2` | `#262626` | `#ebebeb` | Hover, Eingabefelder |
| `--color-border` | `#333333` | `#d8d8d8` | Linien |
| `--color-text` | `#ffffff` | `#111111` | Fließtext |
| `--color-text-muted` | `#d8d8d8` | `#4a4a4a` | Sekundärtext |
| `--color-accent` | `#caa44a` | `#8a6a1f` | Gold: CTAs, Akzente |
| `--color-accent-contrast` | `#111111` | `#ffffff` | Text auf Gold |
| `--color-success` / `--warning` / `--danger` | `#4caf7a` / `#e0a526` / `#e5484d` | `#1f7a4a` / `#8a5a00` / `#b42318` | Status |

Kontrast: Gold `#caa44a` auf `#111111` ≈ 8:1 (AA ✓). Auf Weiß erreicht `#caa44a` nur ca. 2,4:1 → im Light-Mode für Text das dunklere Gold `#8a6a1f` (≈ 5:1); `#caa44a` nur als Flächenfarbe mit dunklem Text.

### 5.2 Typografie

| Rolle | Schrift | Größe (mobil → desktop, `clamp`) | Gewicht / Zeilenhöhe |
|---|---|---|---|
| Display (Hero) | Montserrat | 2.25rem → 4rem | 700 / 1.05, Versalien optional, Laufweite 0.02em |
| H1 | Montserrat | 1.875rem → 3rem | 700 / 1.1 |
| H2 | Montserrat | 1.5rem → 2.25rem | 600 / 1.2 |
| H3 | Montserrat | 1.25rem → 1.5rem | 600 / 1.3 |
| Overline | Poppins | 0.75rem | 600, Versalien, Laufweite 0.12em, Gold |
| UI (Buttons, Navigation, Labels) | Poppins | 0.875rem–1rem | 500 |
| Body | Roboto | 1rem (16 px) → 1.0625rem | 400 / 1.6 |
| Small | Roboto | 0.875rem | 400 / 1.5 |
| Preis | Poppins | 1.5rem → 2rem | 600, `font-variant-numeric: tabular-nums` |

Fonts **selbst gehostet** (kein Google-Fonts-CDN → DSGVO), WOFF2, gesubsettet, `font-display: swap`, nur benötigte Schnitte (Montserrat 600/700, Poppins 500/600, Roboto 400/500/700). Preload nur für Roboto 400 und Montserrat 700. Fallback-Fonts mit `size-adjust` gegen Layout-Sprünge.

### 5.3 Abstände, Radius, Schatten, Raster

| Token | Wert |
|---|---|
| Spacing-Skala | 4 px-Basis: 1=4, 2=8, 3=12, 4=16, 6=24, 8=32, 12=48, 16=64, 24=96 |
| Sektionsabstand | `py-16` mobil, `py-24` desktop |
| Container | max. 1440 px, Seitenrand 16 px (mobil) / 32 px (≥ lg) |
| Radius | `sm` 4 px (Inputs), `md` 8 px (Karten, Buttons), `lg` 16 px (Modals), `full` (Badges) – bewusst zurückhaltend, „militärisch-modern“ |
| Schatten (dark) | Statt Schatten: 1 px Border + leichter Gold-Glow bei Fokus/Hover `0 0 0 1px rgb(202 164 74 / .4)` |
| Schatten (light) | `sm 0 1px 2px rgb(0 0 0 / .06)`, `md 0 4px 12px rgb(0 0 0 / .08)`, `lg 0 12px 32px rgb(0 0 0 / .12)` |
| Grid | 4 Spalten mobil, 8 Tablet, 12 Desktop; Produktraster 2 / 3 / 4 Spalten |

## 6. Dark Mode

- **Standard: Dark.** Klasse `dark` am `<html>`; Tailwind `darkMode: 'class'`.
- Reihenfolge: (1) gespeicherte Wahl in `localStorage('theme')`, (2) sonst `prefers-color-scheme` **nur wenn `light`** gewählt ist, (3) sonst Dark.
- Inline-Skript im `<head>` (vor CSS-Rendering, < 300 Byte) setzt die Klasse → kein Aufblitzen.
- Umschalter im Header (Sonne/Mond, `aria-pressed`), Synchronisation über `storage`-Event zwischen Tabs.
- Bilder: Logos als SVG mit `currentColor`; Produktbilder auf neutralem Hintergrund, im Dark-Mode in Karten mit `#1e1e1e`.

## 7. Navigation

- **Sticky Header**: 80 px, ab 80 px Scroll auf 64 px verkleinert, `#111111` mit 90 % Deckkraft und `backdrop-blur`. Top-Bar blendet beim Herunterscrollen aus (`x-data` mit Scrollrichtung).
- **Mega-Menü** (Struktur siehe [01-seitenstruktur.md](01-seitenstruktur.md)): Öffnen per Hover mit 150 ms Verzögerung **und** per Klick/Enter; `aria-expanded`, Pfeiltasten, `Esc` schließt; Teaser-Spalte lazy.
- **Mobil**: Hamburger → Off-Canvas von links, Kategorien als Akkordeon; Suche als eigenes Vollbild-Overlay; auf Produktseiten fixierte Kaufleiste unten.
- Skip-Link „Zum Inhalt springen“ als erstes fokussierbares Element.

## 8. Breakpoints (Mobile First)

| Name | Min-Breite | Typische Änderung |
|---|---|---|
| (Basis) | 0 | 1-spaltig, Produktraster 2 Spalten |
| `sm` | 640 px | Formulare 2-spaltig |
| `md` | 768 px | Produktraster 3 Spalten, Filter weiterhin als Drawer |
| `lg` | 1024 px | Desktop-Navigation + Mega-Menü, Filter-Sidebar |
| `xl` | 1280 px | Produktraster 4 Spalten |
| `2xl` | 1536 px | max. Containerbreite |

## 9. Motion-Richtlinien

| Regel | Wert |
|---|---|
| Dauer | Mikro 150 ms, Standard 250 ms, Seitenelemente 400–600 ms |
| Easing | `cubic-bezier(.2,.8,.2,1)` (Ausrollen), keine Bounce-Effekte |
| Scroll-Reveal | Fade + 16 px Translate-Y, einmalig, gestaffelt 60–80 ms; nur `opacity`/`transform` (GPU, kein Layout) |
| Hover | Produktkarte: Bildzoom 1.03, Gold-Border; Buttons: Hintergrundwechsel |
| Hero | Langsamer Ken-Burns auf Poster, Text-Reveal 600 ms |
| `prefers-reduced-motion` | Alle Animationen auf 0 ms, Hero-Video wird nicht geladen (nur Standbild) |
| Verboten | Animationen auf LCP-Element vor dem ersten Paint, Parallax auf Mobil, Autoplay mit Ton |

## 10. Performance-Plan (Ziel: Lighthouse ≥ 95 mobil auf Start, Kategorie, Produkt)

| Maßnahme | Umsetzung |
|---|---|
| **LCP-Element = Poster-Bild** des Hero-Videos | `<img fetchpriority="high">` AVIF/WebP, ≤ 120 KB mobil; Preload per `<link rel="preload" as="image" imagesrcset=…>` |
| **Hero-Video nach LCP** | `hero-video.js` startet nach `load` + `requestIdleCallback`; nur ≥ 1024 px, nicht bei `saveData`/`prefers-reduced-motion`; MP4 H.264 + WebM, ≤ 4 MB, 1080p, ohne Ton, `playsinline muted loop`; Pause-Button (WCAG 2.2.2) |
| Bilder | Konvertierung beim Upload (Queue-Job) in AVIF + WebP + JPEG, Breiten 320/480/640/960/1280/1920; `srcset` + `sizes`; `loading="lazy"` außer above the fold; `width/height` gegen CLS |
| Fonts | Self-hosted, gesubsettet, `font-display: swap`, 2 Preloads, `size-adjust`-Fallback |
| CSS | Tailwind-Purge → < 30 KB gzip; **Critical CSS** je Template inline, Rest `media="print" onload` |
| JS | Livewire + Alpine ≈ 50 KB gzip; Module (Galerie, Video, Consent-Details) dynamisch importiert; kein jQuery, keine Slider-Bibliotheken |
| Livewire | `#[Lazy]` für QuickView, CompareBar, Empfehlungen, Kommentare; Platzhalter mit fester Höhe |
| **Full-Page-Cache** für anonyme Besucher | Middleware `CacheAnonymousResponse` (spatie/laravel-responsecache): cached GET-Antworten ohne Session-Cookie-Login und ohne Warenkorbinhalt; personalisierte Teile (Mini-Cart-Zähler, Merklisten-Herz) per Livewire-Lazy oder kleinem JSON-Endpunkt nachgeladen; Invalidierung bei Produkt-/Inhaltsänderung |
| Cloudflare | Statische Assets (`/build/*`, `/storage/*`, Fonts) `Cache-Control: public, max-age=31536000, immutable`; HTML-Edge-Cache nur für anonyme Seiten per Cache Rule (Bypass bei Cookie `af_session`/`laravel_session` mit Login, `/konto`, `/kasse`, `/warenkorb`, `/admin`, `/livewire`) |
| Server | OPcache an, `config/route/view/event:cache`, keine N+1 (Lazy-Loading-Schutz), Listing-Abfragen < 50 ms |
| Drittanbieter | Keine Skripte vor Consent; Analytics (Matomo/Plausible) async, < 5 KB |
| Messung | Lighthouse CI in GitHub Actions (Budget: LCP < 2,0 s, CLS < 0,05, TBT < 150 ms im Lab), Real-User-Daten aus Search Console (CrUX) |

## 11. Barrierefreiheit (BFSG / WCAG 2.1 AA)

Semantisches HTML, Landmarken, eine H1 je Seite, Fokus sichtbar (Gold-Outline 2 px + Offset), alle Funktionen per Tastatur, Formularfehler textlich und programmatisch zugeordnet, Kontraste ≥ 4,5:1, Zoom 200 % ohne Verlust, Touch-Ziele ≥ 44 px, Alternativtexte Pflichtfeld im Admin, Video mit Pause-Steuerung, Sprache `lang="de"`. Prüfung mit axe-core in CI + manueller Screenreader-Test (NVDA, VoiceOver) vor Launch. Barrierefreiheitserklärung unter `/barrierefreiheit`.
