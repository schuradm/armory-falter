# 11 – Entwicklungsplan

Stand: 10.10.2026 · Team-Annahme: 1 Lead-Entwickler (Laravel), 1 Entwickler (Frontend/Livewire), 0,5 UI/UX-Design, 0,25 QA; fachlich: Mark Falter (Product Owner, Compliance), Redaktion. Sprints à **2 Wochen**, Start **Montag, 12.10.2026**.

## 1. Ausgangslage

Eine lauffähige Codebasis entsteht parallel in `/mnt/project-files/armory-falter/code` (Laravel 12 + Livewire 3 + Filament 3) mit Gerüsten für Katalog, Warenkorb, Compliance-Gate, Checkout, Kundenkonto, Dokumenten-Upload, Ankauf-/Verkaufsformulare, Werkstatt-Terminbuchung, Blog, Newsletter und Admin. **Phase 1 ist daher kein Neuaufbau, sondern „Review + Ausbau des Scaffolds“.** Alle Aufwände unten gehen davon aus, dass das Scaffold den Datenmodell-Stand aus [schema.sql](schema.sql) abbildet; Abweichungen werden im Review (Sprint 1) erfasst.

## 2. Release-Strategie: zwei Go-lives

| Stufe | Termin (Plan) | Umfang | Warum |
|---|---|---|---|
| **Go-live 1 „Shop & Services“** | Di **24.11.2026** (Ende Sprint 3) | Neue Website ersetzt WordPress: Katalog **aller** Produkte, Online-Kauf **frei verkäuflicher Ware** (`free_general`), **Reservierung zur Abholung** für erlaubnispflichtige Ware (kein Online-Kaufvertrag), Kundenkonto, Ankauf/Verkaufsservice, Werkstatt-Termine, Blog, Newsletter, SEO-Migration | Nutzt Weihnachts- und Drückjagdsaison; rechtlich risikoarm, weil Waffen/Munition weiterhin im Laden geprüft und verkauft werden |
| **Go-live 2 „Erwerbsberechtigte Online-Bestellung“** | Di **02.03.2027** (Ende Sprint 9, vor der IWA) | Dokumentenprüfung, Berechtigungen, Compliance-Checkout, Bestellfreigabe, Waffenbuch-Modul, Versand mit persönlicher Übergabe, Waffen-PSP | Erst nach Abschluss der [Anwaltlichen Prüfliste](10-waffenrecht-compliance.md#anwaltliche-prüfliste) |

Gesamtdauer: 10 Sprints ≈ 20 Arbeitswochen (+ 2 Wochen Weihnachtspause 21.12.2026–01.01.2027).

## 3. Sprintplan

### Sprint 1 · 12.10.–23.10.2026 · Review & Fundament

| Lieferobjekt | Abnahmekriterium |
|---|---|
| Code-Review des Scaffolds (Architektur, Sicherheit, Datenmodell vs. `schema.sql`) | Review-Protokoll mit priorisierter Mängelliste, alle „kritisch“-Punkte als Tickets |
| Repository, Branch-Schutz, CI (Pint, Larastan L8, Pest, MySQL-8-Service) | Pipeline grün, PR ohne Review nicht mergebar |
| Hoster-Auswahl und Bestellung (Checkliste [12-deployment.md](12-deployment.md)) inkl. AGB-Prüfung Waffenhandel | Vertrag, PHP 8.5 + Erweiterungen verifiziert (`php -m`), SSH, Cron 1 Min. |
| Staging-Umgebung `staging.armory-falter.de` (Cloudflare Access geschützt) | Deployment per GitHub Actions läuft |
| Logo-Übernahme von www.armory-falter.de als SVG, Design-Tokens, Figma-Komponenten | Freigabe durch Mark Falter |
| Crawl der WordPress-Seite, URL-Inventar | CSV mit allen URLs + Traffic |
| Anwalt/Behörde/DL beauftragt (Prüfliste übergeben) | Termine stehen |

### Sprint 2 · 26.10.–06.11.2026 · Katalog & Design

| Lieferobjekt | Abnahmekriterium |
|---|---|
| Layouts, Header/Mega-Menü, Footer, Dark/Light-Mode, Startseite mit Hero-Video | Lighthouse mobil ≥ 90 (Ziel 95 in Sprint 9), axe ohne kritische Fehler |
| Kategorie-Listing mit `ProductFilter`, Suche + Autocomplete (FULLTEXT) | Filter < 300 ms Serverzeit bei 5 000 Produkten; Suche findet Aliase („7,62x51“) |
| Produktdetail inkl. Rechtsklassen-Badge, PAngV-30-Tage-Preis, Tech-Daten, Downloads, FAQ | Preislogik durch Unit-Tests belegt |
| Hersteller- und Kaliberseiten (11 Marken angelegt) | Seiten mit Text, Logo, Produkten |
| Produktimport (CSV aus Warenwirtschaft/Excel) | ≥ 95 % der Artikel importiert, Bilder zugeordnet |
| Bildpipeline AVIF/WebP + `srcset` | Alle Bilder mit Maßen, kein CLS > 0,05 |

### Sprint 3 · 09.11.–20.11.2026 · Checkout freie Ware, Services, Migration → Go-live 1

| Lieferobjekt | Abnahmekriterium |
|---|---|
| Warenkorb, Merkliste, Vergleich, QuickView | E2E-Test „Zubehör kaufen“ grün |
| Checkout für `free_general` mit PayPal, Klarna, Vorkasse; Rechnungs-PDF | Testkäufe in Sandbox, Rechnungsnummern lückenlos |
| Reservierung zur Abholung für erlaubnispflichtige Artikel | Reservierung erzeugt Admin-Aufgabe + Kundenmail, kein Zahlungsprozess |
| Fortify (Registrierung, Login, Reset, 2FA), Konto-Grundfunktionen | Mitarbeiter-2FA erzwungen |
| Ankauf/Verkaufsservice-Formular (20 Fotos), Werkstatt-Terminbuchung | Upload von 20 Fotos à 8 MB auf Hosting erfolgreich (Limits geprüft) |
| Blog (migrierte Beiträge), FAQ, Rechtstexte (anwaltlich geprüfte Fassung für Stufe 1), Consent-Banner, Barrierefreiheitserklärung | Inhalte freigegeben |
| 301-Redirect-Map importiert, Staging-Test aller Alt-URLs | 100 % Alt-URLs mit Traffic → 301 auf 200 |
| Security-Basics: Header, Rate Limits, Turnstile, Cloudflare-WAF | securityheaders.com ≥ A |
| **Go-live 1** am 24.11.2026 (Umstellung siehe [12-deployment.md](12-deployment.md#9-umstellung-wordpress--shop)) | Checkliste abgehakt, Rollback-Plan getestet |

### Sprint 4 · 23.11.–04.12.2026 · Hypercare & Dokumente

| Lieferobjekt | Abnahmekriterium |
|---|---|
| Hypercare Go-live 1: 404-Monitoring, Fehlerbehebung, Search Console | Keine offenen P1-Fehler, 404-Rate < 0,5 % |
| `DocumentUpload` (Kunde) + `DocumentPrecheck` (Job) | Vorprüfung setzt Flags korrekt (Testset 30 Dokumente) |
| Verschlüsselte Ablage, signierte Admin-URLs, Löschjob | Pentest-Fall: Datei nicht ohne Login abrufbar; Datei auf Disk nicht lesbar ohne Schlüssel |
| Admin: Dokumentenprüfung Side-by-Side, Berechtigungen anlegen | Prüfung eines Dokuments < 2 Min. für geübten Nutzer |

### Sprint 5 · 07.12.–18.12.2026 · Compliance-Gate & Berechtigungs-Checkout

| Lieferobjekt | Abnahmekriterium |
|---|---|
| `ComplianceGate` vollständig (Alter, Entitlement-Matching inkl. Kaliber/Waffenart/Menge/Gültigkeit) | 100 % Branch-Coverage, Testkatalog mit ≥ 40 Fällen |
| Checkout-Schritt „Berechtigung“, Erklärung, Zahlarten-/Versandarten-Schnittmenge | Manipulierte Requests werden abgelehnt (Feature-Tests) |
| Warenkorb-Hinweise und Split „Zubehör separat bestellen“ | UX-Test mit 3 Kunden |
| Ergebnisse Anwalt/Behörde (erste Runde) eingearbeitet | Prüfliste ≥ 60 % geklärt |

*Weihnachtspause 21.12.2026–01.01.2027 (nur Bereitschaft für Shop-Betrieb).*

### Sprint 6 · 04.01.–15.01.2027 · Bestellfreigabe, Seriennummern, Waffenbuch

| Lieferobjekt | Abnahmekriterium |
|---|---|
| `OrderStateMachine` mit `pending_compliance`/`compliance_hold`, Reservierungslogik | Alle Übergänge getestet, unzulässige blockiert |
| Admin: Compliance-Freigabe, Abholprotokoll, Original-Dokument-Status | Durchlauf Bestellung → Abholung in Schulung erfolgreich |
| `weapon_items`, Waffenbuch-Ein-/Ausgang, Gegenbuchung, Export | Export entspricht Behördenvorgabe (Feedback BH) |
| NWR-Fristen-Tracking, Dashboard-Widgets | Warnung ab Tag 10 |
| Audit-Log mit Hash-Kette + Prüfjob | Manipulation einer Zeile wird erkannt |

### Sprint 7 · 18.01.–29.01.2027 · Versand, Zahlung, Rechnungen

| Lieferobjekt | Abnahmekriterium |
|---|---|
| Versandarten `weapon_personal`, `dhl_age_check`, `dealer_transfer`; DHL-Label-API, Tracking-Polling | Testsendungen, Tracking-Events landen in `shipment_events` |
| Waffenfreundlicher PSP (falls vertraglich gesichert) oder Vorkasse-nach-Freigabe-Flow | Zahlung erst nach Freigabe anforderbar |
| Rechnungen/Gutschriften/Storno GoBD-konform, DATEV-Export, XRechnung für Behörden | StB-Abnahme |
| Behörden- und Händlerkonten (Sichtbarkeit, Kauf auf Rechnung) | Rollen-Tests grün |

### Sprint 8 · 01.02.–12.02.2027 · Services, Newsletter, API

| Lieferobjekt | Abnahmekriterium |
|---|---|
| Verkaufsservice/Ankauf: Admin-Workflow, Beratungs-PDF, egun-Export | Durchlauf einer Kommission bis „ausgezahlt“ |
| Werkstatt-Kalender (Ressourcen, Drag & Drop), ICS | Doppelbuchung unmöglich (Unique-Constraint-Test) |
| Newsletter: Segmente, Kampagnen, Versand in Chunks, Double-Opt-in-Nachweis | Testkampagne an 500 Testadressen ohne Timeout |
| REST-API `/api/v1` (Katalog, Warenkorb, Konto, Bestellungen), OpenAPI-Doku | Contract-Tests grün |

### Sprint 9 · 15.02.–26.02.2027 · Härtung & Abnahme → Go-live 2

| Lieferobjekt | Abnahmekriterium |
|---|---|
| Externer Penetrationstest (Fokus: Upload, IDOR, Admin, Checkout-Manipulation) | Keine offenen kritischen/hohen Befunde |
| Performance: Lighthouse ≥ 95 mobil (Start, Kategorie, Produkt), Response-Cache, Cloudflare-Regeln | CI-Gate grün |
| Barrierefreiheit: axe + manueller Screenreader-Test | Keine Verstöße Stufe A/AA in Kernflows |
| DSFA abgeschlossen, Verarbeitungsverzeichnis, AV-Verträge | DSB-Freigabe |
| **Anwaltliche Prüfliste 100 % erledigt**, Rechtstexte Stufe 2 | Schriftliche Freigabe FA |
| Schulung Mitarbeiter (Compliance, Abholung, Admin) | Teilnahmeprotokoll |
| UAT mit 10 echten Kunden (Jäger, Sportschützen) | Kritische Rückmeldungen umgesetzt |
| **Go-live 2** am 02.03.2027 | Freigabe Geschäftsführung |

### Sprint 10 · 01.03.–12.03.2027 · Hypercare & Optimierung

Hypercare Go-live 2, IWA-Begleitung (Content, Newsletter), Auswertung Conversion/Funnels, Backlog für Phase 3 (Kassenanbindung, App, Meilisearch bei Umzug auf vServer, Bewertungen).

## 4. Meilensteine

| Datum | Meilenstein |
|---|---|
| 23.10.2026 | Review abgeschlossen, Hoster bestellt, Staging live |
| 06.11.2026 | Katalog vollständig auf Staging, Design abgenommen |
| **24.11.2026** | **Go-live 1** – WordPress abgelöst |
| 18.12.2026 | Compliance-Checkout auf Staging |
| 29.01.2027 | Feature-Freeze Stufe 2 |
| **02.03.2027** | **Go-live 2** – Online-Bestellung erlaubnispflichtiger Ware |

## 5. Definition of Done (für jedes Ticket)

- Code reviewed (zweite Person), Pint/Larastan/Pest grün, neue Logik mit Tests.
- Policies/Autorisierung für neue Endpunkte und Livewire-Aktionen geprüft.
- Keine N+1-Queries (Lazy-Loading-Schutz in Tests aktiv).
- Barrierefreiheit: Tastatur, Labels, Kontrast geprüft.
- Texte deutsch, rechtlich relevante Texte vom Fachbereich freigegeben.
- Auf Staging deployt und vom Product Owner abgenommen.
- Audit-Log-Einträge für Compliance-relevante Aktionen vorhanden.

## 6. Risiken

| Risiko | Wahrscheinlichkeit | Auswirkung | Gegenmaßnahme |
|---|---|---|---|
| Anwaltliche/behördliche Klärung verzögert sich | mittel | Go-live 2 verschiebt sich | Frühe Beauftragung (Sprint 1); Stufe 1 ist davon unabhängig |
| Kein waffenfreundlicher PSP verfügbar | hoch | Nur Vorkasse/Bar | Flow „Vorkasse nach Freigabe“ ist Standard; PSP optional |
| Versanddienstleister befördert keine Waffen/Munition | mittel | Nur Abholung/Händlerversand | Spezialspediteur recherchieren; Abholung ist Kernweg |
| Hoster-Limits (Upload-Größe, `max_execution_time`, Cron) | mittel | Fotos/PDF-Jobs scheitern | Limits in Sprint 1 verifizieren; Chunked Uploads; Wechsel auf Managed vServer vorbereitet ([14-skalierung.md](14-skalierung.md)) |
| Hoster-AGB schließen Waffenhandel aus | niedrig–mittel | Umzug | AGB-Prüfung vor Vertragsschluss |
| Go-live 1 zu knapp (6 Wochen) | mittel | Verschiebung auf Anfang Dezember | Scaffold reduziert Aufwand; Puffer: Go-live spätestens 08.12.2026, Content-Plan passt sich an |
| Produktdatenqualität (Bilder, Attribute) | hoch | SEO/Conversion | Import-Validierung, Redaktionsplan, Herstellerdaten |
| PHP-8.5-Inkompatibilität eines Pakets | niedrig | Verzögerung | CI-Matrix PHP 8.4/8.5 ab Sprint 1 |
