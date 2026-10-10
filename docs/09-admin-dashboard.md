# 09 – Admin-Dashboard (Filament 3, `/admin`)

Stand: 10.10.2026 · Panel-ID `admin`, Pfad `/admin` (zusätzlich per Cloudflare-WAF geschützt), Login nur für Mitarbeiterrollen mit Pflicht-2FA. Sichtbarkeit aller Menüpunkte, Aktionen und Felder richtet sich nach [07-rollen-rechte.md](07-rollen-rechte.md).

## 1. Panel-Grundeinstellungen

| Einstellung | Wert |
|---|---|
| Branding | Logo Armory Falter, Primärfarbe Gold `#caa44a`, Dark-Mode Standard |
| Sprache | Deutsch (`filament/*` de-Übersetzungen), Datumsformat `d.m.Y H:i`, Zeitzone Europe/Berlin |
| Globale Suche | Bestellnummer, Kundennummer, E-Mail, SKU, Seriennummer (über Blind Index, nur Compliance), Ankauf-Referenz |
| Benachrichtigungen | Datenbank-Notifications (Glocke) + Polling 60 s; Sammel-E-Mail täglich 07:30 |
| Performance | Tabellen mit Pagination 25/50, `->deferLoading()`, Eager Loading, keine Zählungen über Millionen-Tabellen ohne Cache |
| Audit | Jede Create/Update/Delete-Aktion über Observer → `audit_logs` |

## 2. Navigationsgruppen

| Gruppe | Einträge |
|---|---|
| **Übersicht** | Dashboard, Meine Aufgaben |
| **Verkauf** | Bestellungen, Reservierungen, Rechnungen, Zahlungen, Versand |
| **Compliance** | Dokumentenprüfung (Badge = wartend), Berechtigungen, Compliance-Freigaben (Badge), Seriennummern, Waffenhandelsbuch, NWR-Meldungen |
| **Katalog** | Produkte, Kategorien, Hersteller, Kaliber, Attribute, Rechtsklassen, SEO-Landingpages |
| **Kunden** | Kunden, Behörden & Händler, Merklisten-Statistik |
| **Services** | Verkaufsservice, Ankauf, Werkstatt-Termine, Werkstattleistungen, Öffnungszeiten |
| **Inhalte** | Seiten, Blog, Autoren, Kommentare, FAQ, Medien, Redirects |
| **Marketing** | Newsletter-Abonnenten, Segmente, Kampagnen, Aktionen/Gutscheine |
| **System** | Benutzer & Rollen, Einstellungen, Zahlarten, Versandarten, Audit-Log, Jobs/Fehler, Backups |

## 3. Dashboard-Widgets

| Widget | Inhalt | Rolle |
|---|---|---|
| KPI-Zeile (Stats) | Umsatz heute / 7 Tage / Monat (brutto), Bestellungen, Ø Warenkorb, Conversion (aus Analytics, falls angebunden) | management, accounting |
| **Offene Compliance-Prüfungen** | Bestellungen `pending_compliance` mit Alter (Ampel: > 2 Werktage gelb, > 5 rot), Link „Prüfen“ | compliance, management |
| **Dokumente wartend** | Anzahl `uploaded`/`precheck_*`, älteste zuerst, Vorprüfungs-Flags | compliance |
| Warten auf Original-Dokument | Bestellungen, bei denen die Original-WBK/Jagdschein noch nicht vorgelegt wurde | compliance, sales |
| NWR-Fristen | Waffenabgaben ohne NWR-Meldung, Tage bis Fristende (14 Tage) | compliance |
| **Niedriger Bestand** | Artikel unter Mindestbestand, Bestellvorschlag | warehouse, sales |
| Versand heute | Freigegebene, bezahlte Bestellungen zum Versand/Abholung | warehouse |
| **Offene Ankauf-/Verkaufsanfragen** | Neu, ohne Angebot > 48 h | sales |
| **Termine heute** | Werkstatt/Beratung/Abholung mit Uhrzeit, Kunde, Leistung, Status-Aktion „eingecheckt“ | workshop, sales |
| Ablaufende Berechtigungen | Kunden, deren Jagdschein/Voreintrag in 30 Tagen abläuft (für Service-Erinnerung) | compliance, sales |
| Kommentare zur Moderation | Anzahl `pending` | editor |
| Systemstatus | Letzter Cron-Lauf, Queue-Länge, fehlgeschlagene Jobs, letztes Backup, Audit-Kette ok | super_admin |

## 4. Module

### 4.1 Produkte

- **Liste:** Bild, SKU, Name, Hersteller, Kaliber, Rechtsklasse (Badge), Zustand, Preis, Sonderpreis, Bestand (verfügbar/reserviert), Verfügbarkeit, Status.
- **Filter:** Kategorie, Hersteller, Rechtsklasse, Produkttyp, Zustand, Status, Bestand < Mindestbestand, Aktion aktiv, ohne Bild, ohne Meta-Description.
- **Formular (Tabs):** Allgemein (Name, Slug, Hersteller, Kaliber, Typ, Waffenart, Kategorien + Primärkategorie) · Recht (Rechtsklasse – Vorbelegung aus Kategorie, Hinweistext, Behördensichtbarkeit) · Preise (Brutto, Steuersatz, Sonderpreis mit Zeitraum – Anzeige des automatisch berechneten 30-Tage-Tiefstpreises, UVP, Preisverlauf) · Bestand (Menge, Varianten, Lieferzeit, Einzelstück) · Medien (Upload mit Pflicht-Alt-Text, Sortierung, Video) · Technische Daten (Attribute) · Downloads · FAQ · SEO (Meta, OG-Bild, Vorschau-Snippet) · Zustand (bei Gebrauchtware: Bericht + Fotos).
- **Aktionen:** Duplizieren, Veröffentlichen/Verbergen, Preis ändern (mit Begründung), Seriennummern verwalten (nur Compliance), Vorschau.
- **Bulk:** Kategorie zuweisen, Status, Preisänderung in % (mit Vorschau), CSV-Export/-Import (Laravel Excel nicht nötig → Filament Import/Export Actions).

### 4.2 Kategorien

Baumansicht (Drag-&-Drop-Sortierung), Felder: Name, Slug (Pfad automatisch), Eltern, Bild, Beschreibung, SEO-Text unten, Standard-Rechtsklasse, aktiv. Aktion „Rechtsklasse auf alle Produkte übertragen“ (nur Compliance, mit Bestätigung und Audit).

### 4.3 Kunden

- **Liste:** Kundennr., Name, E-Mail, Typ (privat/Firma/Behörde/Händler), Alter verifiziert, aktive Berechtigungen (Icons), Bestellungen, Umsatz, Registriert, 2FA.
- **Filter:** Typ, verifiziert ja/nein, hat Berechtigung, Newsletter, Sperrvermerk, inaktiv > 24 Monate.
- **Detail (Relation Managers):** Adressen, Bestellungen, Dokumente (nur Compliance), Berechtigungen, Termine, Anfragen, Notizen (intern), Audit-Ereignisse.
- **Aktionen:** Passwort-Reset-Link senden, Konto sperren/entsperren, 2FA zurücksetzen (mit Identitätsprüfung, Audit), DSGVO-Export, Anonymisieren (prüft Aufbewahrungspflichten).

### 4.4 Bestellungen

- **Liste:** Nr., Datum, Kunde, Positionen (Icons Rechtsklasse), Summe, Zahlart, Zahlstatus, Versandart, Status, Compliance-Status, Alter.
- **Filter:** Status, Compliance-Status, Zahlart, Versandart, enthält Waffe/Munition, Zeitraum, offen > X Tage.
- **Detail:** Kopf, Positionen (inkl. zugeordneter Seriennummer), Compliance-Checks (automatisch/manuell, mit Ergebnis), Zahlungen, Sendungen + Tracking, Rechnungen, Statushistorie, interne Notizen, Kundenkommunikation.
- **Aktionen:** Status ändern (nur erlaubte Übergänge aus der StateMachine), Zahlung als eingegangen markieren, Rechnung erstellen/stornieren, Gutschrift, Sendung anlegen (DHL-Label über API oder manuelle Trackingnummer), Abholung bestätigen (mit Ausweis-/Erlaubnisprüfung-Checkliste), Kunden benachrichtigen, Stornieren.

### 4.5 Dokumentenprüfung (Kernmodul Compliance)

**Warteschlange:** Tabelle mit Kunde, Dokumenttyp, hochgeladen, Vorprüfungsergebnis (Ampel + Hinweise: „Name weicht ab“, „unscharf“, „Ablaufdatum in Vergangenheit“, „Dublette auf anderem Konto“), betroffene offene Bestellungen (Anzahl). Sortierung: Dokumente mit offenen Bestellungen zuerst.

**Prüfansicht (Seite, Side-by-Side):**

```
┌───────────────────────────────────────────────┬───────────────────────────────────────┐
│ DOKUMENT-VIEWER                               │ PRÜFUNG                               │
│ ▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒  │ Kunde: Max Muster, geb. 12.03.1985    │
│ ▒  PDF/Bild (pdf.js bzw. <img>), Zoom,     ▒  │ (Alter 41) · Kundennr. K-10234        │
│ ▒  Drehen, Seitenwechsel, Helligkeit       ▒  │ Konto-Adresse: …                      │
│ ▒  Wasserzeichen „Armory Falter – intern“  ▒  │ Kundenangaben: Nr. ••••12, gültig bis │
│ ▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒  │ 31.03.2027, Behörde LRA Esslingen     │
│ Seite 1/3  ◀ ▶  ⟲  ⊕ ⊖                        │ Vorprüfung: ✓ Format ✓ Größe ⚠ Name   │
│                                               │ Checkliste (Pflicht):                 │
│                                               │ [ ] Dokumenttyp korrekt               │
│                                               │ [ ] Name/Geburtsdatum = Konto          │
│                                               │ [ ] Gültigkeit geprüft                 │
│                                               │ [ ] Keine Hinweise auf Fälschung       │
│                                               │ Felder korrigieren: Nr., Gültig bis   │
│                                               │ ── Berechtigungen anlegen ──          │
│                                               │ + Typ [Voreintrag ▾] Waffenart [Kurz] │
│                                               │   Kaliber [9 mm Luger] Menge [1]      │
│                                               │   gültig bis [TT.MM.JJJJ]             │
│                                               │ [Freigeben] [Ablehnen ▾ Grund] [Rück- │
│                                               │ frage an Kunden]                      │
└───────────────────────────────────────────────┴───────────────────────────────────────┘
```

- Datei-Auslieferung nur über signierte, 5 Minuten gültige URL, Stream mit `Content-Disposition: inline`, `Cache-Control: no-store`; kein Download-Button (Download nur mit eigener Permission).
- **Freigeben** legt in einer Transaktion Status `approved`, `reviewed_by/at`, die erfassten `customer_entitlements` (Pflicht, wenn Typ WBK/Jagdschein/Munitionserwerbschein), ggf. `age_verified_at` an, setzt `delete_after` und feuert `DocumentApproved` → offene Bestellungen werden neu bewertet.
- Vorlagen je Dokumenttyp: Jagdschein → Berechtigung `hunting_license_longgun` (gültig bis Ende Jagdjahr laut Dokument) automatisch vorgeschlagen; grüne WBK → je Voreintrag eine Zeile; gelbe WBK → `wbk_yellow_sport`; Ausweis → nur Altersverifikation, Datei-Löschfrist 30 Tage.
- **Ablehnen** mit Pflichtgrund (Auswahl + Freitext), Kunde erhält E-Mail mit Grund.
- 4-Augen-Option: „Zur Zweitprüfung senden“ (siehe Dokument 07).

### 4.6 Compliance-Freigabe Bestellungen

Liste `pending_compliance` / `compliance_hold`; Detail mit allen automatischen Checks je Position, verwendeter Berechtigung, Kundendokumenten (Link zum Viewer), Checkliste „Originaldokument vorgelegt/eingegangen (Datum, Art)“, „Seriennummer zugeordnet“, „Lieferadresse = Meldeadresse“. Aktionen: **Freigeben** (verbraucht Kontingent `quantity_used`, setzt `compliance_status = approved`), **Anhalten** (`compliance_hold` mit Grund + Kundennachricht), **Ablehnen** (Storno + Erstattung anstoßen).

### 4.7 Hersteller

Liste: Logo, Name, Produkte, Featured, Sortierung. Formular: Logo (SVG/PNG), Hero-Bild, Land, Gründungsjahr, Intro, Langtext (Block-Editor), FAQ, SEO.

### 4.8 Inhalte (Seiten) & Blog

- **Seiten:** Block-Builder (Filament Builder): Hero (Bild/Video + CTAs), Text, Bild-Text, Kachelraster, Produktslider (nach Kategorie/Tag/manuell), FAQ, CTA-Band, Formular-Einbettung, Trust-Bar. Vorschau, Status, Veröffentlichungsdatum.
- **Blog:** Liste (Titel, Kategorie, Autor, Status, Veröffentlicht, Kommentare); Formular: Titel, Slug, Kategorie, Autor, Excerpt, Blocks, Titelbild (Alt-Text Pflicht), verknüpfte Produkte, FAQ, SEO, Kommentare an/aus, Status-Workflow `draft → review → scheduled → published`.
- **Kommentare:** Moderationsliste mit Spam-Score, Aktionen Freigeben/Spam/Ablehnen/Antworten.
- **Redirects:** Liste mit Treffern, CSV-Import (WordPress-Migration), 404-Log mit Aktion „Redirect anlegen“.

### 4.9 Rechnungen

Liste: Nr., Typ (Rechnung/Gutschrift/Storno), Bestellung, Kunde, Betrag, Datum. Aktionen: PDF ansehen, erneut senden, Storno erzeugen (keine Löschung/Änderung – GoBD), DATEV-CSV-Export je Zeitraum, XRechnung für Behörden.

### 4.10 Versand

Liste Sendungen: Bestellung, Versandart, Trackingnummer, Status, letzte Synchronisierung. Aktionen: Label erzeugen (DHL Parcel DE Shipping API; bei Waffenversand Service „Eigenhändig“/Ident-Check – Verfügbarkeit vertraglich mit DHL klären), Packliste/Lieferschein-PDF, Tracking jetzt synchronisieren, Abholung bestätigen. Versandarten-Verwaltung unter System.

### 4.11 Newsletter

Abonnenten (Status, Interessen, Quelle, Opt-in-Nachweis), Segmente (Regel-Builder: Interessen, Kauf in Kategorie, Kundentyp, Berechtigungstyp – nur aggregiert), Kampagnen (Editor mit Blöcken, Testversand, Zeitplanung, Versand in Chunks über Queue), Statistiken (nur mit Tracking-Einwilligung).

### 4.12 Verkaufsservice / Ankauf

- **Liste:** Referenz, Typ, Kunde, Waffe (Hersteller/Modell/Kaliber), Zustand, Wunschpreis, Angebotspreis, Status, zuständig, Alter.
- **Filter:** Typ, Status, zuständig, ohne Angebot > 48 h.
- **Detail:** Fotogalerie (bis 20, Lightbox), Kundenangaben, Notizen, Statusverlauf.
- **Aktionen:** Zuweisen, Angebot senden (E-Mail-Vorlage), Beratungs-PDF erzeugen (dompdf: Fotos, Daten, Bewertung, Konditionen), Ware eingegangen (→ Waffenbuch-Eingang durch Compliance), egun-Export (CSV/Text für manuelles Einstellen), Inseriert (egun-ID), Verkauft, Ausgezahlt.

### 4.13 Werkstatt

Kalenderansicht (Tag/Woche, Ressourcen = Mitarbeiter/Werkbank) und Liste; Termin anlegen/verschieben (Drag & Drop), Status (`requested → confirmed → checked_in → in_progress → done`, `no_show`), Annahmeprotokoll (Waffe, Seriennummer optional, Zustand, Auftrag), Abholbenachrichtigung. Verwaltung: Leistungen, Öffnungszeiten, Sperrtage.

### 4.14 Waffenhandelsbuch

- **Liste:** Datum, Richtung (Eingang/Ausgang), Waffe (Art, Hersteller, Modell, Kaliber, Seriennummer – entschlüsselt nur mit Permission), Gegenpartei (Name, Anschrift, Erlaubnis), Bestellung, NWR gemeldet am, gebucht von.
- **Filter:** Zeitraum, Richtung, NWR offen, Hersteller, Kaliber.
- **Aktionen:** Eingang buchen (aus Ankauf/Lieferant), Ausgang buchen (aus Bestellung/Ladenverkauf), Gegenbuchung (Korrektur, mit Begründung), NWR-Meldung als erledigt markieren (Referenz), **Export** (PDF/CSV für Behörde, Zeitraum). Keine Bearbeitung/Löschung vorhandener Einträge.
- Hinweis: Ob das Shop-Waffenbuch als führendes elektronisches Waffenhandelsbuch genügt oder eine zertifizierte Fachsoftware/NWR-Schnittstelle nötig ist, ist mit der Behörde zu klären (siehe Dokument 10).

### 4.15 Audit-Log

Nur lesen. Liste: Zeit, Akteur, Aktion, Objekt, IP; Filter nach Akteur, Aktion, Objekt, Zeitraum; Detail mit Diff alt/neu. Status-Anzeige „Hash-Kette geprüft am … ✓“. Export als signiertes JSON für Prüfungen.

### 4.16 Einstellungen

Shop-Stammdaten (Firma, Adresse, Bankverbindung für Vorkasse, Steuernummer/USt-IdNr.), Öffnungszeiten, Versandkostenfreigrenze, Reservierungsfrist, Vorkasse-Zahlungsfrist, Zahlarten (aktiv, Zuordnung zu Rechtsklassen nur lesend – Pflege über Rechtsklassen), Versandarten, Captcha-Schlüssel (nur Anzeige gesetzt/nicht gesetzt), Consent-Banner-Texte und -Version, Mail-Vorlagen, Wartungsmodus mit Bypass-Token, 4-Augen-Prinzip an/aus.

## 5. Technische Hinweise

- Ressourcen in `app/Filament/Resources/*`, Widgets in `app/Filament/Widgets/*`; Aktionen rufen Domain-Actions auf (`ApproveDocument::run(...)`), nie direkt Modelle ändern.
- Widgets mit Caching (60 s), damit das Dashboard auf Shared Hosting < 500 ms lädt.
- Filament-Assets werden mit `php artisan filament:assets` lokal/CI veröffentlicht und mit hochgeladen.
- Tests: Für jede Ressource Render- und Policy-Tests (`livewire(ListOrders::class)->assertCanSeeTableRecords(...)`).
