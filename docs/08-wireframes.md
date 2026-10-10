# 08 – Wireframes (textuell)

Stand: 10.10.2026 · Low-Fidelity-Wireframes als Grundlage für das UI-Design (Figma) und die Blade-Templates. Desktop-Raster 12 Spalten / 1440 px, mobil 4 Spalten / 375 px. Farben und Tokens siehe [05-frontend.md](05-frontend.md).

Legende: `[Button]` · `(•)` Radio · `[x]` Checkbox · `▾` Dropdown · `▒▒` Bild/Video · `{…}` dynamischer Inhalt

## 1. Globaler Rahmen

```
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│ TOP-BAR (#111): Abgabe nur an Berechtigte · Abholung in Wolfschlugen · ☎ 0151 29107670  │ ← blendet beim Scrollen aus
├──────────────────────────────────────────────────────────────────────────────────────────┤
│ [LOGO]  Shop▾  Hersteller▾  Verkaufen & Ankauf▾  Werkstatt  Blog  Über uns               │
│                                            [🔍 Suche …………………]  ☾  ♡ 3  ⇄ 2  👤  🛒 2     │ ← sticky, 80→64 px
└──────────────────────────────────────────────────────────────────────────────────────────┘
  … Seiteninhalt …
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│ FOOTER: Logo/Adresse/Öffnungszeiten │ Shop │ Service │ Rechtliches │ Newsletter-Anmeldung │
│ Pflichthinweis: Abgabe erlaubnispflichtiger Waffen und Munition nur an Berechtigte …     │
│ Zahlarten-Logos (nur zulässige) · Versandlogos · Social · © Armory Falter                │
└──────────────────────────────────────────────────────────────────────────────────────────┘
```

## 2. Startseite (Desktop)

```
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│ HERO (100vh, max. 900 px)                                                                │
│ ▒▒▒▒▒▒▒▒▒▒ Fullscreen-Video (startet nach LCP) · Poster-Bild = LCP ▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒ │
│ ▒  Overline (Gold): WAFFENHANDEL · JAGD · SPORT                                         ▒ │
│ ▒  H1: Präzision, die man spürt.                                                        ▒ │
│ ▒  Lead: Fachhändler für Jagd- und Sportwaffen, Optik und Munition bei Stuttgart.       ▒ │
│ ▒  [Jetzt shoppen]  [Waffen ansehen]  [Beratung buchen]                    [❚❚ Pause]   ▒ │
│ ▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒ │
├──────────────────────────────────────────────────────────────────────────────────────────┤
│ TRUST-BAR: ✓ Fachhändler mit Werkstatt  ✓ Persönliche Beratung  ✓ Ankauf & Kommission    │
│            ✓ Diskreter Versand an Berechtigte                                            │
├──────────────────────────────────────────────────────────────────────────────────────────┤
│ KATEGORIEN – H2 „Sortiment“ (Raster 5×2, Kacheln mit Bild + Titel + Pfeil)               │
│ [Kurzwaffen] [Langwaffen] [Jagdwaffen] [Sportwaffen] [Optiken]                           │
│ [Munition]   [Messer]     [Zubehör]    [Wiederladen] [Bekleidung]                        │
├──────────────────────────────────────────────────────────────────────────────────────────┤
│ MARKEN-LEISTE (Logos, graustufig, horizontal scrollbar)                                 │
│ GLOCK · CZ · SIG SAUER · H&K · WALTHER · BERETTA · MAUSER · BLASER · STEINER · ZEISS · LEICA │
├──────────────────────────────────────────────────────────────────────────────────────────┤
│ H2 „Highlights“   Tabs: [Neu eingetroffen] [Aktionen] [Gebrauchtwaffen]   [Alle ansehen →] │
│ ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐                                             │
│ │ ▒▒▒▒▒▒ │ │ ▒▒▒▒▒▒ │ │ ▒▒▒▒▒▒ │ │ ▒▒▒▒▒▒ │   Produktkarte: Bild, Hersteller, Name,     │
│ │ Blaser │ │ Zeiss  │ │ Glock  │ │ Leica  │   Kaliber, Preis, Badge (Neu/Aktion/        │
│ │ 3.299€ │ │ 1.999€ │ │  749€  │ │ 2.390€ │   Gebraucht), ♡ ⇄ 👁 QuickView               │
│ └────────┘ └────────┘ └────────┘ └────────┘                                             │
├──────────────────────────────────────────────────────────────────────────────────────────┤
│ SERVICES (3 Spalten, Bild oben)                                                         │
│ ┌ Werkstatt ───────┐ ┌ Waffen-Ankauf ───┐ ┌ Verkaufsservice ─┐                          │
│ │ Reinigung, Optik-│ │ Faire Bewertung, │ │ Wir verkaufen    │                          │
│ │ montage, Einschie│ │ sofortige Abwick-│ │ Ihre Waffe auf   │                          │
│ │ ßen, Cerakote    │ │ lung             │ │ egun – Kommission│                          │
│ │ [Termin buchen]  │ │ [Angebot anfordern]│ [Mehr erfahren]  │                          │
│ └──────────────────┘ └──────────────────┘ └──────────────────┘                          │
├──────────────────────────────────────────────────────────────────────────────────────────┤
│ BLOG-TEASER: H2 „Wissen vom Fachhändler“  [1 großer Artikel | 2 kleine]  [Zum Blog →]    │
├──────────────────────────────────────────────────────────────────────────────────────────┤
│ ÜBER UNS-KURZ: Bild Laden/Team + Text + Öffnungszeiten + [Anfahrt] (Maps-Link, kein Embed)│
├──────────────────────────────────────────────────────────────────────────────────────────┤
│ NEWSLETTER (Gold-Fläche): „Neuheiten & Aktionen zuerst“ [E-Mail……] Interessen [x]Jagd    │
│ [x]Sport [ ]Optik [ ]Sammler  [Anmelden]  · Double-Opt-in-Hinweis · Datenschutz-Link     │
└──────────────────────────────────────────────────────────────────────────────────────────┘
```

**Mobil (375 px):** Hero 85vh mit Standbild (kein Video), CTAs untereinander (volle Breite, „Jetzt shoppen“ primär Gold). Kategorien 2-spaltig, Marken als Swipe-Leiste, Highlights als horizontaler Slider (Scroll-Snap, ohne JS-Bibliothek), Services als Akkordeon-Karten.

## 3. Kategorie / Listing

```
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│ Breadcrumb: Shop › Langwaffen › Repetierbüchsen                                          │
│ H1 Repetierbüchsen   · Intro (2 Zeilen, „mehr“)                                          │
├───────────────┬──────────────────────────────────────────────────────────────────────────┤
│ FILTER (Side) │ Aktive Filter: [Blaser ×] [.308 Win. ×]  Alle zurücksetzen               │
│               │ 48 Artikel        Sortieren: [Beliebtheit ▾]   Ansicht: ▦ ☰              │
│ Hersteller    │ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐                                      │
│ [x] Blaser(12)│ │ ▒▒▒▒ │ │ ▒▒▒▒ │ │ ▒▒▒▒ │ │ ▒▒▒▒ │                                      │
│ [ ] Mauser (9)│ │Blaser│ │Mauser│ │ CZ   │ │Sauer │                                      │
│ [ ] CZ (7) …  │ │ R8   │ │ M18  │ │ 600  │ │ 100  │                                      │
│ Kaliber       │ │.308  │ │.308  │ │.308  │ │.308  │                                      │
│ [x] .308 Win. │ │3.299€│ │ 799€ │ │1.049€│ │ 899€ │                                      │
│ [ ] 8x57 IS … │ │[Erwerbsberechtigung] Badge                                          │
│ Preis         │ └──────┘ └──────┘ └──────┘ └──────┘                                      │
│ [500]──●──●──[5000] │ … (24 je Seite)                                                    │
│ Verfügbarkeit │                                                                          │
│ [x] Sofort lieferbar │  ‹ 1 2 3 › (Links, crawlbar)                                      │
│ Zustand       │                                                                          │
│ [ ] Neu [ ] Gebraucht │                                                                  │
│ Einsatz       │                                                                          │
│ [ ] Jagd [ ] Sport │                                                                     │
│ + Lauflänge, Magazin… │                                                                  │
├───────────────┴──────────────────────────────────────────────────────────────────────────┤
│ SEO-Text unten (H2-Abschnitte), FAQ zur Kategorie, Links zu Herstellern/Kalibern          │
└──────────────────────────────────────────────────────────────────────────────────────────┘
⇄ Vergleichsleiste (erscheint ab 1 Produkt): [Bild][Bild][+][+]  [Vergleichen (2)]  ✕
```

**Mobil:** Leiste oben `[Filter (2)]  [Sortieren ▾]  48 Artikel`; Filter als Vollbild-Drawer von unten mit `[48 Artikel anzeigen]`; Produktraster 2-spaltig.

## 4. Produktdetail

```
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│ Breadcrumb: Shop › Langwaffen › Repetierbüchsen › Blaser R8 Professional                 │
├─────────────────────────────────────────────┬────────────────────────────────────────────┤
│ GALERIE                                     │ BLASER (Link Herstellerseite)               │
│ ▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒  │ H1 Blaser R8 Professional .308 Win.          │
│ ▒          Hauptbild (Zoom/Lightbox)     ▒  │ Art.-Nr. AF-10021 · ★ (nur echte Bewertungen)│
│ ▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒  │ ┌────────────────────────────────────────┐ │
│ [▒][▒][▒][▒][▶ Video][360°]                 │ │ ⚠ Erwerbsberechtigung erforderlich      │ │
│                                             │ │ Abgabe nur gegen WBK-Voreintrag, gelbe  │ │
│                                             │ │ WBK oder gültigen Jagdschein. [Mehr]    │ │
│                                             │ └────────────────────────────────────────┘ │
│                                             │ ~~3.590,00 €~~  3.299,00 €  (Gold)          │
│                                             │ Niedrigster Preis der letzten 30 Tage:      │
│                                             │ 3.590,00 € · inkl. 19 % MwSt., zzgl. Versand│
│                                             │ UVP 3.749,00 €                              │
│                                             │ Lauflänge: (•) 52 cm ( ) 58 cm              │
│                                             │ ● Sofort lieferbar · Abholung möglich       │
│                                             │ Menge [1]  [In den Warenkorb]               │
│                                             │ [♡ Merken] [⇄ Vergleichen] [Beratung buchen]│
│                                             │ Ihr Status: ✓ Jagdschein geprüft (gültig    │
│                                             │ bis 31.03.2027) – Kauf möglich              │
│                                             │   oder: „Noch keine Berechtigung hinterlegt │
│                                             │   → [Dokument hochladen]“                   │
│                                             │ Versand: persönliche Übergabe / Abholung    │
│                                             │ Zahlung: Vorkasse, Barzahlung bei Abholung  │
├─────────────────────────────────────────────┴────────────────────────────────────────────┤
│ TABS/ANKER: Beschreibung | Technische Daten | Downloads | FAQ | Werkstatt-Service          │
│ Technische Daten (gruppiert):                                                            │
│ ┌ Lauf ─────────────┬──────────┐ ┌ System ───────────┬──────────────┐                   │
│ │ Lauflänge         │ 58 cm    │ │ Verschluss        │ Geradezug    │                   │
│ │ Laufprofil        │ …        │ │ Magazinkapazität  │ 3+1          │                   │
│ └───────────────────┴──────────┘ └───────────────────┴──────────────┘                   │
│ Downloads: 📄 Bedienungsanleitung (PDF, 4 MB) · 📄 Datenblatt                            │
│ FAQ (Akkordeon): Kann ich das Zielfernrohr montieren lassen? …                            │
├──────────────────────────────────────────────────────────────────────────────────────────┤
│ PASSENDES ZUBEHÖR: Montagen · Munition .308 Win. · Pflege   (Produktslider)              │
│ ÄHNLICHE PRODUKTE · ZULETZT ANGESEHEN · Ratgeber-Artikel zum Produkt                     │
└──────────────────────────────────────────────────────────────────────────────────────────┘
```

Varianten des Hinweiskastens: `free_18` → „Abgabe ab 18 Jahren – Altersprüfung bei Zustellung“; `pickup_only` → „Nur Abholung im Geschäft – online reservieren“ (Button „Reservieren“). Gebrauchtwaffe: Zustandsbericht (Text + Fotos), Badge „Einzelstück“, Schussbild/Werkstattprotokoll als Download.

**Mobil:** Galerie als Swipe (Punkte-Indikator), danach Titel/Preis/Hinweis; fixierte Kaufleiste unten `3.299,00 € [In den Warenkorb]`; Tabs als Akkordeons.

## 5. Herstellerseite (z. B. /hersteller/zeiss)

```
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│ HERO: Markenbild ▒▒▒ + Logo + H1 „Zeiss – Jagdoptik aus Wetzlar“ + 2-Satz-Intro          │
│ Fakten: Gegründet · Land · Schwerpunkte · [Alle 64 Produkte]                             │
├──────────────────────────────────────────────────────────────────────────────────────────┤
│ KATEGORIEN DER MARKE: [Zielfernrohre (28)] [Ferngläser (14)] [Wärmebild (6)] …           │
│ TOP-PRODUKTE (Raster 4)                                                                  │
│ RATGEBERTEXT (SEO, H2/H3): Produktlinien, Absehen, Einsatzempfehlung                      │
│ WERKSTATT-CTA: „Zeiss-Optik montiert & eingeschossen“ [Termin buchen]                    │
│ FAQ zur Marke · Blogartikel zur Marke                                                    │
└──────────────────────────────────────────────────────────────────────────────────────────┘
```

## 6. Warenkorb

```
┌──────────────────────────────────────────────────────────────┬───────────────────────────┐
│ H1 Warenkorb (3 Artikel)                                     │ ZUSAMMENFASSUNG           │
│ ┌──────────────────────────────────────────────────────────┐ │ Zwischensumme  3.508,90 € │
│ │ ▒ Blaser R8 .308 Win.     [1]   3.299,00 €   🗑          │ │ Versand (vorauss.) 29,00 €│
│ │   ⚠ Erwerbsberechtigung: ✓ Jagdschein geprüft            │ │ Gesamt         3.537,90 € │
│ ├──────────────────────────────────────────────────────────┤ │ inkl. MwSt. 564,87 €      │
│ │ ▒ RWS Evolution .308 (20 Stk.) [2] 2× 69,95 €   🗑       │ │ [Zur Kasse]               │
│ │   ⚠ Munition: ✓ Jagdschein (Langwaffenmunition)          │ │                           │
│ ├──────────────────────────────────────────────────────────┤ │ Zahlarten für diesen      │
│ │ ▒ Ballistol Universalöl     [1]   …                      │ │ Warenkorb: Vorkasse,      │
│ └──────────────────────────────────────────────────────────┘ │ Barzahlung bei Abholung   │
│ ℹ PayPal/Klarna sind für Waffen und Munition nicht verfügbar. │ Gutscheincode [……] [OK]   │
│   [Zubehör separat bestellen] (teilt Warenkorb)              │                           │
└──────────────────────────────────────────────────────────────┴───────────────────────────┘
```

Blockierte Position: roter Hinweis „Für diesen Artikel liegt keine passende Berechtigung vor“ + `[Dokument hochladen]` + `[Als Reservierung zur Abholung]`; „Zur Kasse“ bleibt für restliche Positionen möglich, sobald die blockierte entfernt oder in Reservierung umgewandelt wurde.

## 7. Checkout (Schritte)

```
Fortschritt: ① Adresse ─ ② Berechtigung ─ ③ Versand ─ ④ Zahlung ─ ⑤ Prüfen & Bestellen
             (Schritt ② nur bei erlaubnispflichtigen Artikeln; Layout „checkout“: Header nur Logo + ☎)

① ADRESSE         Login/Registrierung (Gast nur bei frei verkäuflicher Ware)
                  Rechnungsadresse · [x] Lieferadresse = Rechnungsadresse
                  Hinweis: Bei Waffen/Munition muss die Lieferadresse der Meldeadresse laut
                  Erlaubnis entsprechen.
② BERECHTIGUNG    Je Position: Artikel → verwendete Berechtigung (Auswahl, falls mehrere)
                  ┌ Blaser R8 .308 Win. ─ (•) Jagdschein Nr. ••••12 (gültig bis 31.03.2027) ┐
                  └ RWS .308            ─ (•) Jagdschein (Langwaffenmunition)              ┘
                  Geburtsdatum (falls fehlt) · [x] Erklärung: „Ich bin zum Erwerb berechtigt,
                  die Angaben sind wahr; mir ist bekannt, dass die Ware erst nach Prüfung und
                  Vorlage der Originaldokumente übergeben wird.“
                  Info-Box: Ablauf nach der Bestellung (Prüfung → Original-WBK/Jagdschein →
                  Übergabe/Versand)
③ VERSAND         (•) Abholung in Wolfschlugen (kostenlos)  ( ) Waffenversand mit persönlicher
                  Übergabe (DHL, Zustellung nur an Empfänger persönlich)  ( ) Versand an Fachhändler
④ ZAHLUNG         (•) Vorkasse (Überweisung nach Freigabe)  ( ) Barzahlung bei Abholung
                  [PayPal/Klarna ausgegraut mit Erklärung]
⑤ PRÜFEN          Übersicht, Änderungslinks, [x] AGB/Widerruf, Datenschutzhinweis,
                  [Zahlungspflichtig bestellen]
Danke-Seite       Bestellnummer, Status „Wartet auf Prüfung“, nächste Schritte als Timeline,
                  Kontakt, Hinweis auf Konto-Bereich
```

## 8. Kundenkonto – Dashboard

```
┌───────────────┬──────────────────────────────────────────────────────────────────────────┐
│ Übersicht     │ Hallo Herr Muster  · Kundennr. K-10234                                    │
│ Bestellungen  │ ┌ Status-Kacheln ──────────────────────────────────────────────────────┐ │
│ Rechnungen    │ │ Offene Bestellungen 1 │ Dokumente: 1 in Prüfung │ Nächster Termin 12.11│ │
│ Dokumente  •1 │ └──────────────────────────────────────────────────────────────────────┘ │
│ Berechtigungen│ ⚠ Ihr Jagdschein läuft am 31.03.2027 ab – [Neuen Jagdschein hochladen]   │
│ Merklisten    │ Letzte Bestellung AF-2026-000123 · Wartet auf Original-Jagdschein [Details]│
│ Termine       │ Meine Berechtigungen: Jagdschein (Langwaffen + Munition) ✓ bis 31.03.2027│
│ Anfragen      │                       WBK-Voreintrag Kurzwaffe 9 mm (1 Stk.) ✓ bis 14.08.27│
│ Downloads     │ Merkliste (3) · Zuletzt angesehen                                        │
│ Sicherheit    │                                                                          │
│ Datenschutz   │                                                                          │
│ Abmelden      │                                                                          │
└───────────────┴──────────────────────────────────────────────────────────────────────────┘
```

## 9. Dokumente-Upload (/konto/dokumente)

```
H1 Meine Dokumente
Info: Wofür brauchen wir Ihre Dokumente? Wie lange speichern wir sie? (Link Datenschutz)

[+ Dokument hinzufügen]
┌ Upload-Dialog ───────────────────────────────────────────────────────────────┐
│ Dokumenttyp [Jagdschein ▾]  (WBK grün/gelb/rot, Jagdschein, Ausweis, Reisepass,│
│                              Munitionserwerbschein, Händlererlaubnis, Behörde) │
│ Dokumentnummer [……]   Ausstellende Behörde [……]   Gültig bis [TT.MM.JJJJ]      │
│ Datei(en): ┌─────────────────────────────────────┐  PDF, JPG, PNG · max. 10 MB │
│            │  Datei hierher ziehen oder wählen   │  Vorder- und Rückseite;      │
│            └─────────────────────────────────────┘  bei WBK alle Seiten mit     │
│ Fortschritt ███████░░░ 70 %                           Voreinträgen               │
│ [x] Ich bestätige, dass das Dokument mir gehört und gültig ist.                 │
│ [Hochladen]                                                                    │
└────────────────────────────────────────────────────────────────────────────────┘
Liste:  Typ          | Hochgeladen | Gültig bis | Status
        Jagdschein   | 02.11.2026  | 31.03.2027 | ✓ Freigegeben
        WBK grün     | 05.11.2026  | –          | ⏳ In Prüfung (Vorprüfung: Bild unscharf ⚠)
        Personalausw.| 02.11.2026  | 2031       | ✓ Altersnachweis erbracht (Datei gelöscht)
```

## 10. Verkaufsservice-Formular (/verkaufsservice/anfrage)

```
Schritte: ① Waffe ② Fotos ③ Preis & Zustand ④ Kontakt ⑤ Absenden
① Waffenart [Langwaffe ▾] Hersteller [Blaser ▾ / Freitext] Modell [……] Kaliber [▾/Freitext]
   Seriennummer (optional, wird verschlüsselt) · Zubehör (Optik, Montage, Koffer) [……]
② Fotos (bis 20): ┌──┐┌──┐┌──┐┌──┐┌──┐ [+]  Tipps: Gesamtansicht beidseitig, Lauf, Verschluss,
                   └──┘└──┘└──┘└──┘└──┘      Schaft, Seriennummer, Beschuss, Mängel
   (Drag-&-Drop-Sortierung, Vorschau, Löschen, clientseitige Verkleinerung)
③ Zustand (•) neuwertig ( ) sehr gut ( ) gut ( ) gebraucht ( ) defekt · Wunschpreis [……] €
   Beschreibung [……]   Wie soll verkauft werden? (•) egun-Kommission ( ) Direktankauf prüfen
④ Name, Anschrift, Telefon, E-Mail · [x] Datenschutz · Turnstile
⑤ Bestätigung: Referenz VS-2026-0012, nächste Schritte (Bewertung in 2 Werktagen,
   Beratungs-PDF per Mail, Übergabe der Waffe im Geschäft – Hinweis: Transport nur durch Berechtigte)
```

## 11. Ankauf-Landingpage (/waffen-ankauf)

```
HERO: „Wir kaufen Ihre Waffe – fair, schnell, rechtssicher“ [Angebot anfordern]
3 Schritte: ① Anfrage mit Fotos ② Angebot in 48 h ③ Übergabe im Geschäft & Bezahlung
Was wir ankaufen: Jagdwaffen, Sportwaffen, Optiken, Sammlerwaffen, Nachlässe
Nachlass/Erbe: Hinweis-Box (Erbenregelung, Blockiersystem – Link Ratgeber; anwaltlich geprüft)
Vergleich Ankauf vs. Verkaufsservice (Tabelle: Tempo, Erlös, Aufwand)
FAQ · Bewertungen · CTA-Band [Angebot anfordern] [Anrufen]
```

## 12. Werkstatt + Terminbuchung

```
/werkstatt: Leistungskacheln (Waffenreinigung, Waffenpflege, Optikmontage, Einschießen,
            Reparaturen, Cerakote) mit „ab … €“ · Büchsenmacher-Vorstellung · Galerie vorher/nachher
/werkstatt/termin:
 ① Leistung (•) Optikmontage (30 Min. Annahme)
 ② Kalender  Nov 2026  ◀ ▶      Freie Zeiten am Do 12.11.:
    Mo Di Mi Do Fr Sa            [10:00] [10:30] [14:00] [15:30]
    .. .. ●  ●  ●  ●
 ③ Waffe/Optik beschreiben [……]  ④ Kontakt / Login  ⑤ [Termin verbindlich anfragen]
 Bestätigung: „Termin bestätigt“ + [In Kalender (.ics)] · Hinweis: Waffe ungeladen, verschlossenes
 Behältnis, WBK mitbringen
```

## 13. Blogpost

```
Breadcrumb · Kategorie-Badge (Jagd) · H1 · Autor (Foto, Name, „Büchsenmachermeister“) · Datum ·
Lesezeit 7 Min. · Titelbild ▒▒▒
Inhaltsverzeichnis (sticky rechts ab lg) | Artikeltext mit H2/H3, Bildern, Info-Boxen, Produkt-
karten inline („Im Artikel erwähnt“) · FAQ-Block · Autor-Box · Teilen-Links (ohne Tracking-Skripte)
Verwandte Artikel (3) · Kommentare (moderiert; Formular mit Turnstile)
```

## 14. Mobile-Varianten (Übersicht)

| Seite | Mobile Besonderheit |
|---|---|
| Header | 56 px, Logo zentriert, links ☰, rechts 🔍 🛒; Topbar nur 1 Zeile, scrollt weg |
| Start | Standbild statt Video, CTAs gestapelt, Slider mit Scroll-Snap |
| Listing | Filter-Drawer, 2-spaltig, „Mehr laden“-Button **und** Paginierungslinks |
| Produkt | Swipe-Galerie, fixe Kaufleiste, Akkordeons |
| Warenkorb | Zusammenfassung unter Positionen, fixer „Zur Kasse“-Button |
| Checkout | Ein Schritt pro Bildschirm, Fortschritt als „Schritt 2 von 5“ |
| Konto | Navigation als Liste/Select oben |
| Upload | Kamera-Aufnahme direkt (`accept="image/*,application/pdf" capture`), Hinweis auf gute Ausleuchtung |
| Terminbuchung | Tagesliste statt Monatskalender |
