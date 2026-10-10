# 04 – REST-API `/api/v1`

Stand: 10.10.2026

## 1. Zweck und Abgrenzung

Die Website rendert serverseitig (Blade/Livewire) und benötigt die API **nicht**. Die API ist für:

- künftige Apps (iOS/Android), Kassensystem im Ladengeschäft, Warenwirtschaft/ERP,
- Partner-Integrationen (z. B. Bestandsabgleich mit Herstellern/Großhändlern),
- interne Automatisierung (Preis-/Bestandsimport).

Sie nutzt dieselben Actions/Services wie Website und Admin (z. B. `ComplianceGate`, `PlaceOrderAction`). Es gibt keine API-exklusive Geschäftslogik.

## 2. Grundlagen

| Thema | Festlegung |
|---|---|
| Basis-URL | `https://www.armory-falter.de/api/v1` |
| Format | JSON, UTF-8, `Content-Type: application/json`; Zeitstempel ISO 8601 UTC; Geld als `{ "amount": 129900, "currency": "EUR", "formatted": "1.299,00 €" }` |
| Auth | Laravel **Sanctum**: (a) Personal Access Tokens für Apps/Integrationen (`Authorization: Bearer …`), (b) Cookie-basierte SPA-Auth für eigene Frontends auf derselben Domain |
| Token-Fähigkeiten (abilities) | `catalog:read`, `cart:write`, `account:read`, `account:write`, `orders:read`, `orders:write`, `admin:stock:write`, `admin:orders:read` |
| Token-Laufzeit | Kunden-App 90 Tage (Refresh durch erneuten Login), Integrationen 365 Tage, rotierbar im Admin |
| Versionierung | URL-Präfix `/v1`; additive Änderungen ohne Versionssprung; Breaking Changes nur in `/v2`, Parallelbetrieb ≥ 6 Monate, Header `Deprecation` und `Sunset` (RFC 8594) auf alten Endpunkten |
| Sprache | `Accept-Language: de` (Standard), Fehlertexte deutsch |
| Idempotenz | `Idempotency-Key`-Header (UUID) Pflicht für `POST /orders`, `POST /checkout/*`; Speicherung 24 h; gleicher Key + anderer Body → `422` |
| CORS | Nur eigene Origins; Integrationen serverseitig (kein CORS nötig) |
| Caching | `GET /catalog/*` mit `ETag` + `Cache-Control: public, max-age=60, stale-while-revalidate=300`; persönliche Endpunkte `private, no-store` |

## 3. Paginierung, Filter, Sortierung

- Listen: Cursor-Paginierung für große Listen (`/products`, `/orders`), Seiten-Paginierung für kleine (`/categories`).
- Parameter: `?page[size]=24` (max. 100), `?page[cursor]=…`
- Filter: `?filter[manufacturer]=glock,walther&filter[caliber]=9mm-luger&filter[price_min]=50000&filter[condition]=used&filter[usage]=hunting&filter[availability]=in_stock`
- Sortierung: `?sort=-price` / `price` / `-published_at` / `name` / `relevance` (nur mit `q`)
- Sparse Fields/Includes: `?include=media,attributes,manufacturer`

Antwortschema Liste:

```json
{
  "data": [ { "id": "glock-17-gen5-9mm-luger", "type": "product", "...": "..." } ],
  "meta": { "page_size": 24, "total_estimate": 312 },
  "links": { "next": "https://www.armory-falter.de/api/v1/products?page[cursor]=eyJpZCI6...", "prev": null }
}
```

Öffentliche Kennungen sind Slugs bzw. Bestellnummern, nie Datenbank-IDs.

## 4. Endpunkte

### 4.1 Katalog (öffentlich, `catalog:read` optional)

| Methode | Pfad | Beschreibung |
|---|---|---|
| GET | `/catalog/categories` | Kategoriebaum |
| GET | `/catalog/categories/{path}` | Kategorie inkl. Facetten |
| GET | `/catalog/products` | Listing mit Filtern/Sortierung |
| GET | `/catalog/products/{slug}` | Produktdetail inkl. Rechtsklasse (`legal_class.code`, `notice`), Preise inkl. `lowest_price_30d`, Medien, Attribute |
| GET | `/catalog/products/{slug}/related` | Zubehör/Alternativen |
| GET | `/catalog/manufacturers` / `/{slug}` | Hersteller |
| GET | `/catalog/calibers` / `/{slug}` | Kaliber |
| GET | `/catalog/search?q=` | Volltextsuche (MySQL FULLTEXT) |
| GET | `/catalog/search/suggest?q=` | Autocomplete (max. 8 Produkte, 3 Kategorien, 3 Hersteller) |

Produkte mit `usage_defense_authority = true` erscheinen nur für Tokens von Behördenkonten.

### 4.2 Warenkorb (`cart:write` oder Gast-Token)

| Methode | Pfad | Beschreibung |
|---|---|---|
| POST | `/cart` | Gastwarenkorb anlegen → `cart_token` |
| GET | `/cart` | Warenkorb inkl. `compliance` je Position (`allowed`/`needs_documents`/`blocked` + Gründe), verfügbare Versand-/Zahlarten |
| POST | `/cart/items` | Position hinzufügen `{ product, variant?, quantity }` |
| PATCH | `/cart/items/{id}` | Menge ändern |
| DELETE | `/cart/items/{id}` | Position entfernen |
| POST | `/cart/merge` | Gastkorb in Kundenkorb übernehmen (nach Login) |

Gastwarenkörbe werden über Header `X-Cart-Token` adressiert.

### 4.3 Konto (`account:*`)

| Methode | Pfad | Beschreibung |
|---|---|---|
| POST | `/auth/token` | Login → Token (E-Mail, Passwort, `device_name`, ggf. `otp`) |
| DELETE | `/auth/token` | Logout (aktuelles Token widerrufen) |
| POST | `/auth/register` | Registrierung (Turnstile-Token Pflicht) |
| POST | `/auth/password/forgot` | Reset-Mail |
| GET/PATCH | `/me` | Profil (Geburtsdatum nur setzbar, solange nicht verifiziert) |
| GET/POST/PATCH/DELETE | `/me/addresses[/{id}]` | Adressbuch |
| GET | `/me/documents` | Hochgeladene Nachweise (Status, Ablauf; **keine Dateien**) |
| POST | `/me/documents` | Upload (multipart; `type`, `valid_until`, Datei) |
| GET | `/me/entitlements` | Aktive Berechtigungen (Typ, Kaliber, Restmenge, Ablauf) |
| GET | `/me/wishlists` / POST `/me/wishlists/{id}/items` | Merklisten |
| GET | `/me/appointments` / POST `/appointments` | Termine |
| GET | `/me/trade-requests` | Ankauf-/Verkaufsanfragen |
| POST | `/me/data-export` | DSGVO-Export anstoßen (Mail mit Download-Link) |

### 4.4 Checkout & Bestellungen (`orders:*`)

| Methode | Pfad | Beschreibung |
|---|---|---|
| POST | `/checkout/quote` | Preise, Versand-/Zahloptionen, Compliance-Ergebnis für den aktuellen Korb |
| POST | `/orders` | Bestellung auslösen (`Idempotency-Key` Pflicht; Body: Adressen, Versand, Zahlung, `terms_accepted`, `legal_declaration_accepted`) |
| GET | `/orders` | Eigene Bestellungen |
| GET | `/orders/{order_number}` | Detail inkl. Status, Compliance-Status, Sendungen |
| GET | `/orders/{order_number}/invoices/{invoice_number}` | Rechnungs-PDF (signierte Weiterleitung) |
| POST | `/orders/{order_number}/cancel` | Storno, solange `pending_payment`/`pending_compliance` |

Weitere öffentliche Endpunkte: `POST /trade-requests` (multipart, max. 20 Fotos), `GET /workshop/services`, `GET /workshop/slots?service=&from=&to=`, `POST /newsletter/subscriptions` (Double-Opt-in).

### 4.5 Integrationen (Server-zu-Server, Admin-Tokens)

| Methode | Pfad | Beschreibung |
|---|---|---|
| PUT | `/admin/stock/{sku}` | Bestand setzen (Kasse/ERP) |
| POST | `/admin/stock/bulk` | Bis 500 SKUs je Aufruf |
| PUT | `/admin/prices/{sku}` | Preis setzen (schreibt `price_history`) |
| GET | `/admin/orders?updated_since=` | Bestellexport für ERP/Buchhaltung |

Waffenhandelsbuch, Kundendokumente und Seriennummern sind **nicht** über die API verfügbar.

## 5. Fehlerformat (RFC 9457 `application/problem+json`)

```json
{
  "type": "https://www.armory-falter.de/api/problems/entitlement-missing",
  "title": "Erwerbsberechtigung fehlt",
  "status": 422,
  "detail": "Für „Blaser R8 Professional .308 Win.“ ist eine gültige Erwerbsberechtigung erforderlich.",
  "instance": "/api/v1/orders",
  "request_id": "01JABCDXYZ…",
  "errors": [
    { "pointer": "/items/0", "code": "entitlement_missing", "detail": "Kein aktiver Voreintrag/Jagdschein für Langwaffe .308 Win." }
  ]
}
```

| Status | `type`-Suffix | Wann |
|---|---|---|
| 400 | `bad-request` | Syntaxfehler |
| 401 | `unauthenticated` | Kein/abgelaufenes Token |
| 403 | `forbidden` | Ability fehlt, Policy verweigert |
| 404 | `not-found` | Auch für fremde Ressourcen (kein Existenz-Leak) |
| 409 | `conflict` / `idempotency-conflict` | Doppelte Verarbeitung, Bestand vergriffen |
| 422 | `validation-failed`, `age-restricted`, `entitlement-missing`, `payment-method-not-allowed`, `shipping-not-possible` | Fachliche Fehler |
| 429 | `rate-limited` | mit `Retry-After` |
| 503 | `maintenance` | Wartungsmodus |

Implementierung: zentraler Exception-Renderer in `bootstrap/app.php` für Pfade `api/*`; jede Antwort trägt `X-Request-Id`.

## 6. Rate Limits (Laravel `RateLimiter`, zusätzlich Cloudflare)

| Limiter | Grenze | Schlüssel |
|---|---|---|
| `api-public` | 120/min | IP |
| `api-auth` (`/auth/*`) | 5/min, 20/h | IP + E-Mail |
| `api-user` | 300/min | Token |
| `api-search` | 60/min | IP |
| `api-upload` (Dokumente, Trade) | 10/h | Benutzer bzw. IP |
| `api-orders` (POST) | 10/h | Benutzer |
| `api-integration` | 600/min | Token |

Header: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After`.

## 7. Webhooks (eingehend)

Route-Gruppe `/webhooks/*` ohne CSRF, ohne Session, eigener Rate Limiter, Antwort immer schnell (`200` nach Persistieren), Verarbeitung im Queue-Job.

| Quelle | Pfad | Verifizierung | Verarbeitung |
|---|---|---|---|
| PayPal | `/webhooks/paypal` | PayPal-Signaturprüfung über `verify-webhook-signature` API (Webhook-ID aus Konfiguration) | `PAYMENT.CAPTURE.COMPLETED/DENIED/REFUNDED` → `payments` |
| Klarna | `/webhooks/klarna` | Klarna-Push nur als **Hinweis**: Status wird immer per Order-Management-API nachgeladen (keine Payload-Vertrauensstellung) | Autorisierung, Capture, Refund |
| Waffen-PSP (noch zu wählen) | `/webhooks/psp` | HMAC-Signatur laut Anbieter | wie oben |
| DHL | – | **Kein Webhook**: Tracking per Polling (`shipments:sync-tracking`, alle 30 Min.) über DHL Parcel DE Tracking API (Unified Tracking API), max. Abfragen laut Vertragskontingent | `shipment_events`, Status `delivered` |

Idempotenz: `payment_webhook_events.event_id` unique; doppelte Events werden mit `200` quittiert und ignoriert. Reihenfolgeunabhängig: Statusübergänge prüfen den aktuellen Zustand über die StateMachine.

Auf Shared Hosting gilt: Webhook-Verarbeitung erfolgt verzögert (max. ~1 Minute über Cron-Queue). Für zeitkritische Rückmeldungen (Rückkehr vom PayPal-Checkout) wird zusätzlich synchron per API-Abruf bestätigt.

## 8. Webhooks (ausgehend, optional Phase 2)

Für ERP/Kasse: `order.placed`, `order.released`, `order.shipped`, `stock.low`. Signatur `X-AF-Signature: t=…,v1=HMAC-SHA256`, Wiederholung mit exponentiellem Backoff über Queue, max. 8 Versuche.

## 9. Dokumentation & Tests

- OpenAPI 3.1 automatisch via `dedoc/scramble` unter `/api/docs` (nur für eingeloggte Admins bzw. Staging).
- Contract-Tests (Pest) je Endpunkt: Status, Schema, Policy (fremde Bestellung → 404), Rate Limit.
- Postman/Bruno-Collection im Repo `docs/api/`.
