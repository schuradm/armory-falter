# Armory Falter – Premium-Waffenshop: Gesamtkonzept

Stand: 10.10.2026 · Version 1.0 · Status: Konzept zur Freigabe

Dieses Paket beschreibt den neuen Online-Shop von Armory Falter vollständig: fachlich, technisch, rechtlich und vermarktungsseitig. Es ist so geschrieben, dass ein Entwicklungsteam (2–4 Personen) direkt in die Umsetzung gehen kann.

## Inhalt

| Nr. | Dokument | Inhalt |
|---|---|---|
| 01 | [Seitenstruktur](01-seitenstruktur.md) | Sitemap, URL-Schema, Navigation, Mega-Menü |
| 02 | [Datenbankmodell & ER-Diagramm](02-datenbankmodell.md) | Alle Entitäten, Beziehungen, Indizes, ER-Beschreibung (Mermaid) |
| 02a | [schema.sql](schema.sql) | Lauffähiges MySQL-8-DDL der Kerntabellen |
| 03 | [Laravel-Backend-Architektur](03-backend-laravel.md) | Module, Schichten, Jobs, Events, Pakete |
| 04 | [API-Struktur](04-api.md) | REST-Endpunkte, Auth, Fehlerformat, Versionierung |
| 05 | [Next.js-Frontend & Design-System](05-frontend-nextjs.md) | App-Router-Struktur, Komponenten, Design-Tokens, Performance |
| 06 | [SEO-Konzept](06-seo.md) | URLs, Meta, Open Graph, Schema.org, Core Web Vitals |
| 07 | [Benutzerrollen & Rechte](07-rollen-rechte.md) | Rollen, Berechtigungsmatrix, Kunden-Berechtigungsstufen |
| 08 | [Wireframes](08-wireframes.md) | Seitenaufbau Desktop/Mobile für alle Kernseiten |
| 09 | [Admin-Dashboard](09-admin-dashboard.md) | Module, Masken, Workflows (u. a. Dokumentenprüfung) |
| 10 | [Waffenrecht & Compliance](10-waffenrecht-compliance.md) | Erwerbsregeln, Checkout-Gates, Zahlungs-/Versandregeln, **anwaltliche Prüfliste** |
| 11 | [Entwicklungsplan](11-entwicklungsplan.md) | Phasen, Sprints, Meilensteine, Abnahmekriterien |
| 12 | [Deployment-Anleitung](12-deployment.md) | Server-Setup, NGINX, Cloudflare, CI/CD, Backups |
| 13 | [Sicherheitskonzept](13-sicherheit.md) | DSGVO, Consent, WAF, Rate Limits, Audit, Verschlüsselung |
| 14 | [Skalierungskonzept](14-skalierung.md) | Lastannahmen, Caching, Ausbaustufen |
| 15 | [Marketingstrategie](15-marketing.md) | Positionierung, Kanäle unter Werbebeschränkungen, KPIs |
| 16 | [Contentplan 12 Monate](16-contentplan-12-monate.md) | Monatsplan Blog, Herstellerseiten, Newsletter, Social |

## Die fünf wichtigsten Entscheidungen auf einen Blick

1. **Headless-Architektur:** Next.js (Frontend, SSR/ISR) spricht ausschließlich über eine versionierte REST-API (`/api/v1`) mit Laravel 12. Admin-Oberfläche läuft in Laravel (Filament 3), nicht in Next.js. Das spart ca. 30 % Entwicklungsaufwand und hält sensible Prüfprozesse im Backend.
2. **Compliance als Kern-Domäne, nicht als Plugin:** Jedes Produkt hat eine *Erwerbsklasse* (z. B. `frei_18`, `wbk_kurzwaffe`, `munition_wbk`). Warenkorb und Checkout prüfen serverseitig gegen die *verifizierten Berechtigungen* des Kunden. Ohne Freigabe kein Kaufabschluss bzw. Bestellung nur als „Reservierung mit Abholung“. Details in Dokument 10.
3. **Dokumentenprüfung = Vorprüfung automatisch, Freigabe immer durch Menschen.** OCR und Plausibilitätsprüfung sortieren vor; eine rechtsverbindliche „automatische Freigabe“ ist nicht vorgesehen, weil der Händler die Erwerbsberechtigung selbst prüfen muss (§ 34 WaffG).
4. **Zahlungsarten nach Produktklasse:** PayPal, Klarna und gängige Kreditkarten-Acquirer schließen Waffen und Munition in ihren Nutzungsbedingungen aus. Für erlaubnispflichtige Ware sind deshalb Vorkasse, Barzahlung bei Abholung und ein ausdrücklich waffenfreundlicher Zahlungsdienstleister vorgesehen; PayPal/Klarna nur für Zubehör, Optiken, Bekleidung (vorher schriftlich mit Anbieter klären).
5. **Werbebeschränkungen eingeplant:** Google Ads und Meta erlauben keine Werbung für Waffen und Munition. Die Marketingstrategie setzt daher auf SEO, Newsletter, Fachcontent, Events und Kooperationen (Dokument 15).

## Abweichungen von der Vorgabe (mit Begründung)

| Vorgabe | Umsetzung im Konzept | Grund |
|---|---|---|
| „Automatische Prüfung und Freigabe“ von WBK/Jagdschein | Automatische Vorprüfung (OCR, Ablaufdatum, Namensabgleich), Freigabe nur durch berechtigtes Personal | Händlerpflicht zur Prüfung der Erwerbsberechtigung; Haftung bei Fehlfreigabe |
| ReCaptcha | Captcha-Abstraktion, Standard **Cloudflare Turnstile**, reCAPTCHA v3 optional nach Consent | Google reCAPTCHA überträgt Daten in die USA und braucht Einwilligung; Turnstile ist ohnehin im Cloudflare-Stack |
| Egun-„Integration“ | Verkaufsservice mit Erfassung, PDF-Beratungsdossier und Export für egun (CSV/manueller Upload); API-Anbindung nur, falls egun eine Händlerschnittstelle bereitstellt | Öffentliche, dokumentierte egun-API ist nicht gesichert; vor Umsetzung mit egun klären |
| Fullscreen-Hintergrundvideo + Lighthouse 95+ | Video erst nach LCP geladen, Poster-Bild als LCP-Element, auf Mobil/„Daten sparen“ nur Standbild | Ein Autoplay-Video im LCP verhindert sonst einen Score ≥ 95 |
| Sofortüberweisung | Läuft heute als „Klarna Sofort“/Pay by Bank; gleiche Einschränkung wie Klarna | Produktumbenennung durch Klarna |

## Was vor dem Go-live zwingend extern geklärt werden muss

Die vollständige Liste steht in [10-waffenrecht-compliance.md](10-waffenrecht-compliance.md#anwaltliche-prüfliste). Kurzfassung:

- Fachanwalt Waffenrecht: Versandprozess für erlaubnispflichtige Waffen/Munition, Prüfung der Erwerbsberechtigung im Fernabsatz, AGB, Widerrufsbelehrung, Altersgrenzen je Produktgruppe, Messer-Sortiment nach aktueller Rechtslage.
- Zuständige Waffenbehörde: elektronische Anzeigen ans Nationale Waffenregister (NWR) und elektronisches Waffenhandelsbuch.
- Zahlungsanbieter: schriftliche Bestätigung, welche Warengruppen erlaubt sind.
- Datenschutz: Datenschutzfolgenabschätzung (DSFA) wegen Verarbeitung von Ausweis-/WBK-Kopien; AV-Verträge mit allen Dienstleistern.

> Dieses Konzept ist keine Rechtsberatung. Rechtliche Aussagen sind nach bestem Stand formuliert und als Arbeitsgrundlage für die anwaltliche Prüfung gedacht.
