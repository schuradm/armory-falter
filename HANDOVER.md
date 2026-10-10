# Übergabe: Armory Falter Webshop (Stand 10.10.2026, 21:20)

Dieses Dokument ist der Arbeitsauftrag für die Weiterentwicklung auf dem HOME-SERVER. Konzeptdokumente liegen in `docs/` (00–17).

## Kundenwünsche (verbindlich)

- Laravel-Monolith für **Shared Hosting mit PHP 8.5** (Code PHP 8.3+-kompatibel halten). Kein Node-Server in Produktion, Assets werden vorab gebaut.
- Domain: www.armory-falter.de (heute WordPress) → 301-Weiterleitungen über Tabelle `redirects`.
- **Farben aus dem Logo**: Schwarz #111111 / #0e0e0e, Anthrazit, Weiß, Logo-Rot `#9d4640` (Flächen, weiße Schrift), helleres Rot `#e0786f` für Text auf Dunkel, Silber. Gold aus dem ersten Briefing ist ersetzt.
- Schriften: Montserrat (Display), Poppins (UI), Roboto (Text), selbst gehostet in `public/fonts/` (variable Fonts).
- **Backend unter `/kms-login`** (Filament 3). Alles soll dort pflegbar sein: Produkte, Inhalte, Startseite, Einstellungen, Zahlarten, Events, Regionalseiten, Kundenstimmen.
- **Lighthouse/PageSpeed 100** in Leistung, Barrierefreiheit, Best Practices und SEO (mobil + Desktop).
- Startseite und Produktseiten nach **AIDA** mit starker Verkaufskraft; Marketingkonzept des Kunden in `docs/17-marketing-verkaufskonzept-kunde.md`.
- PayPal + Sofortüberweisung (Klarna „pay_now“) + Warenkorb-Drawer + Premium-Effekte (immer `prefers-reduced-motion` beachten).
- Rechtlich: Waffen/Munition nur an Berechtigte, Freigabe durch Personal (§ 34 WaffG). PayPal/Klarna nur für frei verkäufliche Ware. Anwalts-Prüfliste in `docs/10-waffenrecht-compliance.md`.

## Was fertig ist

- Migrationen (alle Tabellen inkl. settings, testimonials, events, local_pages, product_relations), Enums, Models, Morph-Map.
- Services: Compliance (Vorprüfung + Staff-Freigabe), Warenkorb, Checkout (idempotent, Lagersperre), Orders, Rechnungen (dompdf), PayPal (Orders v2 + Webhook), Klarna HPP (Sofort/Rechnung, Webhook, Capture bei Versand), Dokumente (verschlüsselt), Ankauf/Verkaufsservice, Terminbuchung, Newsletter (DOI), Audit-Log mit Hash-Kette.
- Frontend: Layout mit SEO/JSON-LD, Header mit Mega-Menü, Footer, Startseite (AIDA), Shop-Listing mit Filtern, Produktseite (Zoom, Upsell „Passend dazu“, „Warum Armory Falter?“), Suche, Vergleich, Warenkorb + Mini-Cart-Drawer mit Upsell, Checkout, Konto (alle Seiten), Auth (Fortify, 2FA), Service-Seiten, Blog, FAQ, Kontakt, Events, Elite-Club, Regionalseiten, Leadmagnet.
- Performance: Ganzseiten-Cache für Gäste (`CachePublicPages`), WebP-Varianten mit srcset (`ImageVariants`, Job), Inline-CSS, Font-Preload mit Fallback-Metriken, Speculation Rules (Prerender), View Transitions, `content-visibility`, keine Livewire-Komponente pro Produktkarte.
- Backend: Panel unter `/kms-login`, Login läuft über die Shop-Anmeldung (wegen 2FA), Staff braucht 2FA. Ressourcen sind **roh generiert** (siehe offene Punkte).

## Offene Punkte (Reihenfolge = Priorität)

1. **Lokale Umgebung** aufsetzen (PHP 8.3+/8.5 mit Extensions pdo_mysql, gd, intl, zip, bcmath; Composer; MySQL 8/MariaDB 10.6+; Node 20+ nur für den Build). `composer install`, `npm ci && npm run build`, `.env` aus `.env.example`, `php artisan key:generate`, `php artisan migrate --seed`, `php artisan storage:link`.
2. **Filament-Ressourcen fertigstellen** (`app/Filament/Resources`, aktuell automatisch generiert, englische Labels, Cent-Werte roh, Enum-Felder als TextInput → muss auf `Select::make()->options(Enum::class)` umgestellt werden):
   - Produkte: Tabs (Basis, Preise in Euro mit Umrechnung in Cent, Lager, Medien-Upload → `ProductMedia`, Merkmale, Zubehör/Upsell über `product_relations`, SEO).
   - Bestellungen: Detailansicht, Aktionen Compliance freigeben/zurückhalten/ablehnen (`OrderService`), Zahlung erhalten, Versand mit Sendungsnummer, Rechnung PDF. Berechtigung `compliance.release`.
   - Kundendokumente: Viewer (Datei über `DocumentService` entschlüsselt streamen), Freigeben mit Berechtigungen / Ablehnen mit Grund.
   - Ankauf/Verkaufsservice: PDF-Dossier, egun-CSV-Export.
   - **Einstellungen-Seite** (Filament Page) auf `App\Support\Settings`: Startseite (`home.*`, Struktur siehe `config/shop.php` → `home`), „Warum Armory Falter?“ (`product_why`), Community-Links (`community`), Promo-Leiste (`promo_bar`), SEO-Startseite (`seo.home_title`, `seo.home_description`), Elite-Preis (`elite.price`), Zahlarten an/aus.
   - Dashboard-Widgets: Bestellungen heute, offene Freigaben, zu prüfende Dokumente, Umsatz Monat, neue Anfragen.
   - Deutsche Labels überall, Navigationsgruppen: Verkauf, Katalog, Kunden & Prüfung, Service, Marketing, Inhalte, System.
3. **Seeder**: Rollen/Rechte (customer, super-admin, compliance, shop-manager; Recht `compliance.release`), Rechtsklassen (inkl. `allowed_payment_codes` mit `sofort` für freie Ware), Steuersätze, Kategorien (Kurzwaffen, Langwaffen, Jagdwaffen, Sportwaffen, Optiken, Munition, Messer, Waffenzubehör, Wiederladen, Bekleidung), 11 Hersteller, Kaliber, Versandarten (dhl_standard, dhl_age_check, weapon_personal_overnight, pickup), Werkstatt-Leistungen, Rechtstexte mit Platzhaltern, FAQs, Demo-Produkte, Admin-Benutzer.
4. Fehlende Views: `pdf/invoice.blade.php`, `pdf/trade-request.blade.php`, `errors/404.blade.php` (+ 500, 503), `sitemap.blade.php` (Controller `SitemapController` prüfen, inkl. Events und Regionalseiten).
5. `config/filesystems.php`: Disk `documents` (privat, `storage/app/private/documents`).
6. Scheduler (`routes/console.php`): `queue:work --stop-when-empty --max-time=50` jede Minute, Dokumenten-Housekeeping täglich, Willkommensstrecke Newsletter (Tag 1/3/7/14, Spalte `welcome_step`, nur bestätigte Abonnenten), Cache-Pruning für den Datei-Cache, Befehl `audit:verify`.
7. API `/api/v1` (Katalog lesen, Sanctum).
8. Tests (Pest/PHPUnit): Compliance-Gate, Checkout, Dokumentenfreigabe, Seiten-Cache (CSRF-Platzhalter), Zahlungs-Webhooks.
9. **Lighthouse-Messung** (mobil + Desktop) auf Startseite, Kategorie, Produkt; nachbessern bis 100/100/100/100. Bekannte Stellschrauben: CSS ist 128 kB roh / 22 kB gzip, Hauptanteil ist `.prose-af` (`@apply prose …` dupliziert die Typography-Regeln) – verschlanken, dann `INLINE_CSS=true` testen (Inline lohnt nur bis ca. 15 kB gzip); Hero ohne Bild als LCP-Text, Bild-`sizes`, Kontraste (`text-muted` auf `surface`), Tap-Targets ≥ 24 px, eindeutige Linktexte.
10. Deployment-Paket: `public/.htaccess` (Kompression, Cache-Header 1 Jahr für `/build`, `/fonts`), Root-`.htaccess` auf `public/` für Hosts ohne Docroot-Wahl, Build-Skript (ZIP mit vendor + public/build), README Shared Hosting.
11. Favicon `.ico` und `apple-touch-icon.png` (180×180) erzeugen, `site.webmanifest` anlegen, `public/images/og-default.jpg` (1200×630).

## Bekannte Baustellen aus dem letzten Test

- Alle öffentlichen Seiten liefern 200 (leere DB), außer `/sitemap.xml` (View fehlt).
- Desktop-Screenshot 1440 px war 1487 px breit: horizontale Überbreite gesucht, vorläufig `overflow-x: clip` auf `body`. Ursache finden.
- `<x-icon>` ist die shopeigene Komponente; blade-icons ist deshalb auf `<x-blade-icon>` umgestellt (`config/blade-icons.php`).

## Konventionen

- Geldbeträge immer in Cent (`unsignedInteger`), Anzeige über `App\Support\Money`.
- SEO über Singleton `App\Support\Seo` (title, description, canonical, breadcrumbs, JSON-LD).
- Seiten-Cache leeren über `App\Http\Middleware\PageCache::flush()` (passiert automatisch beim Speichern relevanter Models).
- CSS-Klassen: `btn-primary`, `btn-outline`, `btn-ghost`, `card`, `input`, `label`, `eyebrow`, `brand-rule`, Effekte `fx-*` (in `resources/css/app.css`).
- Commit-Nachrichten auf Deutsch, kleine Commits, auf `main` pushen.
