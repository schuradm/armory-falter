# 03 – Backend-Architektur (Laravel-12-Monolith)

Stand: 10.10.2026 · Zielplattform: **Shared Hosting mit PHP 8.5**, MySQL 8, Apache + `.htaccess`, Cloudflare davor.

## 1. Architekturentscheidung

| Vorgabe aus dem Briefing | Umsetzung | Begründung |
|---|---|---|
| Next.js-Frontend + Laravel-REST-API + NGINX + eigener Linux-Server | **Ein Laravel-12-Monolith**: Blade + Livewire 3 + Alpine.js + Tailwind, Admin mit Filament 3 | Shared Hosting bietet keinen Node-Prozess, kein Root, kein Supervisor. Ein Monolith läuft dort vollständig, ist günstiger zu betreiben und kann später unverändert auf einen vServer umziehen |
| REST-API | `/api/v1` mit Sanctum bleibt erhalten – für Apps, Warenwirtschaft, Partner | Die Website selbst rendert serverseitig und hängt **nicht** von der API ab |
| Redis/Horizon | Queue- und Cache-Treiber `database` (bzw. `file`), Queue-Abarbeitung per Cron | Redis ist auf Shared Hosting nicht garantiert; Umstieg per `.env` ohne Codeänderung |
| Meilisearch | MySQL FULLTEXT (+ optional Laravel Scout `database`-Engine) | Kein Dienstprozess nötig |

Schichtenmodell:

```
HTTP (Routes) ─► Controller / Livewire-Komponente  (dünn: Validierung, Autorisierung, Antwort)
                 │
                 ▼
          Actions / Services  (Domänenlogik, transaktional, testbar)
                 │
                 ▼
          Eloquent-Modelle + Query-Objekte  (Persistenz, Scopes)
                 │
       Events ──► Listener ──► Jobs (Queue: database)
```

Regeln: Kein Domänenwissen in Blade, Livewire oder Filament-Ressourcen. Filament und Livewire rufen dieselben Actions auf wie die API-Controller (eine Wahrheit, z. B. `PlaceOrderAction`).

## 2. Verzeichnisstruktur

```
armory-falter/
├── app/
│   ├── Domain/
│   │   ├── Catalog/         Models (Product, Category, Manufacturer, Caliber, Attribute…), Queries/ProductListingQuery,
│   │   │                    Actions/UpdatePrice (schreibt price_history + lowest_price_30d), Search/ProductSearch
│   │   ├── Compliance/      Models (LegalClass, CustomerDocument, CustomerEntitlement, WeaponItem, WeaponBookEntry),
│   │   │                    Services/ComplianceGate, Services/DocumentPrecheck, Actions/ApproveDocument,
│   │   │                    Actions/CreateEntitlement, Actions/ReleaseOrder, Exports/WeaponBookExport, Enums/*
│   │   ├── Cart/            Models (Cart, CartItem), Services/CartService, Actions/MergeGuestCart
│   │   ├── Checkout/        Services/CheckoutSession, Services/PaymentMethodResolver, Services/ShippingMethodResolver,
│   │   │                    Actions/PlaceOrderAction, DTO/CheckoutData
│   │   ├── Orders/          Models (Order, OrderItem, OrderStatusHistory, OrderComplianceCheck),
│   │   │                    StateMachine/OrderStateMachine, Actions/CancelOrder, Actions/AssignWeaponItem
│   │   ├── Payments/        Contracts/PaymentGateway, Gateways/{Prepayment, CashPickup, PayPal, Klarna, WeaponPsp},
│   │   │                    Webhooks/*, Models/Payment
│   │   ├── Shipping/        Contracts/Carrier, Carriers/{DhlParcelDe, Pickup, WeaponCourier}, Jobs/SyncTracking
│   │   ├── Customers/       Actions/RegisterCustomer, Actions/AnonymizeCustomer, Actions/ExportPersonalData
│   │   ├── Trade/           Models (TradeRequest, TradeRequestMedia), Actions/SubmitTradeRequest,
│   │   │                    Pdf/AdvisoryDossier, Exports/EgunCsvExport
│   │   ├── Workshop/        Models (WorkshopService, Appointment), Services/SlotFinder, Actions/BookAppointment
│   │   ├── Content/         Models (Page, BlogPost, Author, Comment, Faq), Blocks/*, Actions/ModerateComment
│   │   ├── Newsletter/      Models (Subscriber, Segment, Campaign), Actions/Subscribe, Actions/ConfirmOptIn,
│   │   │                    Jobs/SendCampaignChunk
│   │   └── Seo/             Models (SeoMeta, Redirect, SeoLandingPage), Services/MetaBuilder, Services/JsonLd,
│   │                        Sitemap/SitemapGenerator, Middleware/HandleRedirects
│   ├── Http/
│   │   ├── Controllers/     Web/* (Produkt, Kategorie, Hersteller, Blog, Sitemaps, DocumentDownload),
│   │   │                    Api/V1/* (Catalog, Cart, Account, Orders), Webhooks/*
│   │   ├── Middleware/      CacheAnonymousResponse, SecurityHeaders, EnsureAgeVerified, TrimTrailingSlash
│   │   └── Requests/        FormRequests je Formular
│   ├── Livewire/            siehe 05-frontend.md
│   ├── Filament/            Resources/*, Pages/*, Widgets/* (siehe 09-admin-dashboard.md)
│   ├── Policies/            OrderPolicy, CustomerDocumentPolicy, WeaponItemPolicy …
│   ├── Support/             Money, Captcha (TurnstileVerifier, RecaptchaVerifier), Audit/AuditLogger, Hashing/BlindIndex
│   └── Providers/
├── bootstrap/app.php        Middleware, Exceptions, Routing (Laravel-11+-Struktur)
├── config/                  shop.php (Versandgrenzen, Reservierungsfristen), compliance.php, captcha.php
├── database/                migrations, seeders (LegalClassSeeder, CategorySeeder, DemoCatalogSeeder), factories
├── resources/               views (Blade), css, js, images, fonts (self-hosted)
├── routes/                  web.php, api.php, console.php (Scheduler), webhooks.php
├── storage/app/private/     documents/, trade/, invoices/ (nie öffentlich)
├── storage/app/public/      Produktbilder, Blogbilder (via storage:link)
├── public/                  index.php, .htaccess, build/ (Vite-Output), robots.txt
└── tests/                   Pest: Unit/, Feature/, Arch/
```

## 3. Zentrale Services

### 3.1 `ComplianceGate`

Einziger Ort, an dem entschieden wird, ob ein Kunde einen Artikel bestellen darf.

```php
final class ComplianceGate
{
    /** @return ComplianceResult je CartItem: allowed | needs_documents | blocked (+ Gründe) */
    public function evaluateCart(Cart $cart, ?User $user): CartComplianceResult;

    /** Wird in PlaceOrderAction innerhalb der DB-Transaktion erneut aufgerufen (kein Vertrauen in UI-Zustand) */
    public function assertOrderable(Cart $cart, User $user): void; // wirft ComplianceViolation
}
```

Prüfschritte je Position: (1) Mindestalter aus `legal_classes.min_age` gegen `birth_date` **und** `age_verified_at`; (2) wenn `requires_entitlement`: passende aktive Berechtigung (`type ∈ accepted_entitlements`, Waffenart, Kaliber, `valid_until ≥ heute`, Restmenge ≥ Menge); (3) Mengenregeln (Voreintrag = 1 Waffe); (4) Versand-/Zahlungsschnittmenge nicht leer. Ergebnis wird als `order_compliance_checks` (automatische Vorprüfung, `checked_by = NULL`) gespeichert.

### 3.2 `PaymentMethodResolver` / `ShippingMethodResolver`

Zahlarten = Schnittmenge der `allowed_payment_codes` aller Warenkorb-Rechtsklassen, gefiltert nach aktivierten Gateways. Versandarten = Methoden, deren `allowed_shipping_modes` **alle** `shipping_mode`-Werte des Warenkorbs abdecken; ist das nicht möglich (z. B. Messer + Kurzwaffe), schlägt der Checkout eine Aufteilung in zwei Bestellungen oder Abholung vor.

### 3.3 `DocumentPrecheck` (Job)

Prüft hochgeladene Nachweise automatisch vor: MIME per `finfo`, Größe ≤ 10 MB, PDF-Seitenzahl ≤ 10, Bildauflösung ≥ 1000 px Kantenlänge, Lesbarkeit (Unschärfe-Heuristik via Laplace-Varianz mit GD/Imagick), Ablaufdatum aus Kundenangabe, Namensabgleich Kundenangabe ↔ Konto (Levenshtein), Dublette über `sha256` und Blind Index. OCR (z. B. `tesseract`) ist auf Shared Hosting in der Regel nicht verfügbar → optional über EU-Dienst nach DSFA, standardmäßig **aus**. Ergebnis: `precheck_passed` oder `precheck_flagged`. **Freigabe immer durch Mitarbeiter.**

### 3.4 `OrderStateMachine`

Erlaubte Übergänge zentral definiert; jeder Übergang schreibt `order_status_history` + `audit_logs` und feuert `OrderStatusChanged`. Details der Zustände in [10-waffenrecht-compliance.md](10-waffenrecht-compliance.md).

### 3.5 Weitere

| Service | Aufgabe |
|---|---|
| `CartService` | Gast-Token, Merge beim Login, Preis-/Bestandsrevalidierung bei jedem Laden |
| `PriceService` | Aktueller Preis, Sonderpreis-Fenster, `lowest_price_30d` (PAngV § 11) aus `price_history` |
| `InvoiceService` | Nummernkreis mit `SELECT … FOR UPDATE`, PDF via dompdf, optional ZUGFeRD/XRechnung (`horstoeko/zugferd`) für B2B/Behörden |
| `SlotFinder` | Freie Termine aus Öffnungszeiten, Ressourcen, Dauer, Pufferzeiten, Sperrtagen |
| `MetaBuilder` / `JsonLd` | Titel/Description-Muster, Schema.org-Ausgabe (siehe [06-seo.md](06-seo.md)) |
| `AuditLogger` | Hash-Kette: `hash = sha256(prev_hash + json(payload))`, sequenziell über `cache lock` |
| `Captcha` | Interface mit `TurnstileVerifier` (Standard) und `RecaptchaV3Verifier` (nur nach Consent) |

## 4. Events, Listener, Jobs

| Event | Listener / Job (Queue) | Wirkung |
|---|---|---|
| `CustomerRegistered` | `SendVerificationEmail` (Fortify), `LinkNewsletterSubscription` | |
| `DocumentUploaded` | `RunDocumentPrecheck` (Job, Queue `documents`) | Vorprüfung, Status setzen |
| `DocumentPrechecked` | `NotifyComplianceTeam` | Badge im Admin, tägliche Sammelmail |
| `DocumentApproved` / `DocumentRejected` | `NotifyCustomerDocumentStatus`, `ReevaluatePendingOrders` | Offene Bestellungen erneut prüfen |
| `OrderPlaced` | `ReserveStock`, `SendOrderConfirmation`, `NotifyStaffNewOrder`, `CreatePrepaymentInstructions` | |
| `OrderRequiresCompliance` | `NotifyComplianceTeam`, `SendCustomerOriginalDocumentInstructions` | Kunde erhält Anleitung zur Vorlage der Original-WBK |
| `OrderReleased` | `AssignWeaponItems` (manuell bestätigt), `CreateInvoice`, `UnlockPayment` | |
| `PaymentCaptured` | `MarkOrderPaid`, `CreateInvoice` (falls noch nicht) | |
| `ShipmentCreated` | `SendShippingNotification` | |
| `WeaponHandedOver` | `CreateWeaponBookEntry(out)`, `CreateNwrReportTask` | Frist 2 Wochen überwacht |
| `TradeRequestSubmitted` | `GenerateAdvisoryPdf`, `NotifyTradeTeam`, `SendTradeConfirmation` | |
| `AppointmentBooked` | `SendIcsInvitation`, `NotifyWorkshop` | |
| `CommentSubmitted` | `ScoreSpam` | Moderations-Queue |
| `ProductPriceChanged` | `WritePriceHistory`, `NotifyWishlistPriceDrop` (gedrosselt) | |
| `ProductBackInStock` | `NotifyWishlistBackInStock` | |
| `ContentPublished` | `PurgeResponseCache`, `PurgeCloudflareUrls` | Cache-Invalidierung |

Alle Jobs sind **idempotent** (z. B. `ShouldBeUnique`, Prüfung „bereits erledigt?“), haben `tries = 3`, `backoff = [60, 300, 900]` und `timeout ≤ 45 s` (Cron-Fenster).

## 5. Scheduler (`routes/console.php`)

Ein einziger Cron-Eintrag beim Hoster: `* * * * * cd ~/armory-falter && php artisan schedule:run >> /dev/null 2>&1`

| Befehl | Takt | Zweck |
|---|---|---|
| `queue:work database --queue=high,default,documents,mail,low --stop-when-empty --max-time=50 --tries=3` | jede Minute, `withoutOverlapping()` | Queue-Abarbeitung ohne Supervisor |
| `shipments:sync-tracking` | alle 30 Min. | DHL-Tracking-Polling für offene Sendungen |
| `entitlements:expire` | täglich 02:00 | abgelaufene Berechtigungen/Dokumente auf `expired`, Kunden informieren (30 Tage vorher Erinnerung) |
| `documents:purge` | täglich 02:30 | Dateien + Datensätze mit `delete_after < heute` löschen |
| `reservations:release` | stündlich | abgelaufene Reservierungen freigeben, Kunde informieren |
| `orders:remind-prepayment` | täglich 09:00 | Zahlungserinnerung Vorkasse nach 5 Tagen, Storno nach 14 Tagen (konfigurierbar) |
| `nwr:check-deadlines` | täglich 08:00 | Warnung an Compliance bei NWR-Meldungen älter als 10 Tage |
| `prices:recalculate-lowest` | täglich 03:00 | `lowest_price_30d` aktualisieren |
| `sitemap:generate` | täglich 04:00 | XML-Sitemaps in `public/sitemaps/` |
| `model:prune` | täglich 03:30 | Warenkörbe, Consent-Records, Tracking |
| `backup:run --only-db` (spatie/laravel-backup) | täglich 01:00 | DB-Dump off-site |
| `backup:clean` / `backup:monitor` | täglich | Aufbewahrung, Alarm |
| `audit:verify-chain` | täglich 05:00 | Hash-Kette prüfen, Alarm bei Bruch |
| `newsletter:dispatch-scheduled` | alle 5 Min. | Kampagnen in Chunks à 200 Mails an die Queue |
| `queue:prune-failed --hours=168` | täglich | |

Lange Aufgaben (Sitemap, Exporte) werden in Chunks zerlegt, damit kein Lauf das PHP-`max_execution_time` des Hosters überschreitet.

## 6. Pakete

| Paket | Zweck |
|---|---|
| `laravel/framework` ^12 | Basis (PHP-8.5-Kompatibilität vor Projektstart mit aktuellem Minor-Release verifizieren) |
| `livewire/livewire` ^3 | Interaktive Komponenten |
| `filament/filament` ^3 | Admin-Panel (bei Projektstart prüfen, ob Filament 4 stabil und PHP-8.5-kompatibel ist) |
| `laravel/fortify` | Registrierung, Login, Passwort-Reset, E-Mail-Verifizierung, TOTP-2FA |
| `laravel/sanctum` | API-Tokens + SPA-Auth |
| `spatie/laravel-permission` | Rollen & Rechte |
| `barryvdh/laravel-dompdf` | Rechnungen, Beratungs-PDF, Waffenbuch-Export |
| `spatie/laravel-backup` | DB-/Datei-Backups auf S3-kompatiblen EU-Speicher |
| `spatie/laravel-sitemap` | Sitemaps |
| `spatie/laravel-responsecache` | Full-Page-Cache für anonyme Besucher (file/database-Store) |
| `spatie/laravel-medialibrary` *(optional)* | Bildkonvertierungen AVIF/WebP + responsive `srcset` |
| `intervention/image` ^3 | Re-Encoding von Uploads (entfernt EXIF/Payloads) |
| `league/flysystem-aws-s3-v3` | Off-Site-Backups, später Objektspeicher |
| `horstoeko/zugferd` *(optional)* | E-Rechnung B2B/Behörde |
| `sentry/sentry-laravel` *(optional, EU-Region)* | Fehlertracking |
| Dev: `pestphp/pest`, `pestphp/pest-plugin-laravel`, `pestphp/pest-plugin-arch`, `larastan/larastan` (Level 8), `laravel/pint`, `rector/rector`, `barryvdh/laravel-debugbar`, `laravel/pail` | Qualität |

Keine Pakete mit nativen Erweiterungen, die der Hoster nicht bietet. Vor Aufnahme eines Pakets: Wartung aktiv, PHP-8.5-Support, keine Telemetrie.

## 7. Konfiguration für Shared Hosting

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.armory-falter.de
APP_TIMEZONE=UTC
APP_LOCALE=de

DB_CONNECTION=mysql
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=database          # alternativ file; redis sobald verfügbar
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
RESPONSE_CACHE_DRIVER=file
LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14

CAPTCHA_DRIVER=turnstile
TURNSTILE_SITE_KEY=...
TURNSTILE_SECRET_KEY=...
BLIND_INDEX_KEY=base64:...
TRUSTED_PROXIES=*             # Cloudflare; echte IP aus CF-Connecting-IP
```

Besonderheiten:

- **Trusted Proxies:** Cloudflare-IP-Ranges in `bootstrap/app.php` (`trustProxies(at: [...])`) hinterlegen; Client-IP aus `CF-Connecting-IP` nur übernehmen, wenn die Anfrage von einer Cloudflare-IP kommt.
- **Kein `exec`/`proc_open` voraussetzen:** Viele Hoster deaktivieren diese Funktionen. dompdf, GD/Imagick laufen in-process.
- **Speicher:** `memory_limit` ≥ 256 MB für PDF/Bildverarbeitung; große Bilder werden in einem Queue-Job verarbeitet, nicht im Request.
- **Mail:** SMTP (Hoster oder EU-Anbieter wie Brevo/Mailjet), immer über Queue `mail`.
- **Caches beim Deploy:** `config:cache`, `route:cache`, `view:cache`, `event:cache`, `filament:optimize`.

## 8. Sicherheit im Code (Kurzfassung)

Policies für jede Ressource (IDOR-Schutz: Kunden sehen nur eigene Bestellungen/Dokumente), Mass-Assignment-Schutz über `$fillable`, Form Requests mit expliziten Regeln, Rate Limiter je Route (siehe [13-sicherheit.md](13-sicherheit.md)), Dateiauslieferung privater Dokumente nur via `DocumentDownloadController` mit Policy + signierter, 5 Minuten gültiger URL, Audit-Log für alle Compliance-Aktionen.

## 9. Coding-Standards

- PHP 8.5, `declare(strict_types=1);` in allen Dateien, `final` Klassen als Standard, `readonly` DTOs.
- PSR-12 via **Pint** (Preset `laravel`), statische Analyse **Larastan Level 8**, **Rector** für Upgrades.
- Geldbeträge nur über `App\Support\Money` (int Cent), niemals `float`.
- Enums statt Magic Strings; Statuswechsel nur über StateMachine/Actions.
- Eloquent: `Model::preventLazyLoading()` und `preventSilentlyDiscardingAttributes()` in Nicht-Produktion; `->with()` verpflichtend in Listen.
- Commits nach Conventional Commits; Pull Requests mit Review durch zweite Person; Branch-Schutz auf `main`.
- Sprache: Code/Bezeichner Englisch, UI-Texte Deutsch über `lang/de/*.php`.

## 10. Tests

| Ebene | Werkzeug | Schwerpunkt | Ziel |
|---|---|---|---|
| Unit | Pest | `ComplianceGate`, `PaymentMethodResolver`, `PriceService` (PAngV-30-Tage), `OrderStateMachine`, `SlotFinder`, `Money` | 100 % Verzweigungsabdeckung im Compliance-Code |
| Feature | Pest + `RefreshDatabase` (MySQL 8 in CI, nicht SQLite – FULLTEXT/JSON) | Checkout-Flows je Rechtsklasse, Dokument-Upload, Policies/IDOR, Webhooks (Signaturprüfung, Idempotenz), Rate Limits | alle Kernflows |
| Livewire | `Livewire::test()` | Filter, Warenkorb, Checkout-Schritte, Upload | |
| Architektur | `pest-plugin-arch` | Domain hängt nicht von Http/Livewire/Filament ab; keine `dd()/dump()`; Controller nutzen keine Modelle anderer Domänen direkt | |
| Browser/E2E | Playwright (in CI, gegen Staging) | 5 Smoke-Flows: Kauf Zubehör, Kauf Munition mit Berechtigung, Waffe → pending_compliance, Terminbuchung, Ankaufformular | vor jedem Release |
| Performance | Lighthouse CI | Start, Kategorie, Produkt ≥ 95 (mobil) | Gate im CI |

Pflichttests für Compliance (Auszug): Kunde ohne Geburtsdatum kann `free_18` nicht kaufen; Voreintrag Kaliber .308 erlaubt keine 9-mm-Kurzwaffe; abgelaufene Berechtigung blockiert; Voreintrag mit Menge 1 erlaubt nicht 2 Waffen; Warenkorb mit Munition bietet kein PayPal an; Manipulierte Livewire-Requests (Zahlart `paypal` erzwungen) werden serverseitig abgelehnt.
