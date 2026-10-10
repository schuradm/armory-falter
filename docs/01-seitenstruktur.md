# 01 – Seitenstruktur, URL-Schema und Navigation

## 1. Sitemap

```
/                                   Startseite
├── /shop                           Alle Produkte (Listing mit Live-Filter)
│   ├── /shop/kurzwaffen
│   │   ├── /shop/kurzwaffen/pistolen
│   │   └── /shop/kurzwaffen/revolver
│   ├── /shop/langwaffen
│   │   ├── /shop/langwaffen/repetierbuechsen
│   │   ├── /shop/langwaffen/selbstladebuechsen
│   │   ├── /shop/langwaffen/flinten
│   │   └── /shop/langwaffen/kombinierte-waffen
│   ├── /shop/jagdwaffen
│   ├── /shop/sportwaffen
│   ├── /shop/optiken
│   │   ├── /shop/optiken/zielfernrohre
│   │   ├── /shop/optiken/fernglaeser
│   │   ├── /shop/optiken/waermebild
│   │   ├── /shop/optiken/rotpunktvisiere
│   │   └── /shop/optiken/montagen
│   ├── /shop/munition
│   │   ├── /shop/munition/buechsenpatronen
│   │   ├── /shop/munition/kurzwaffenpatronen
│   │   ├── /shop/munition/schrotpatronen
│   │   └── /shop/munition/kleinkaliber
│   ├── /shop/messer
│   ├── /shop/waffenzubehoer
│   │   ├── /shop/waffenzubehoer/waffenpflege
│   │   ├── /shop/waffenzubehoer/magazine
│   │   ├── /shop/waffenzubehoer/holster
│   │   ├── /shop/waffenzubehoer/schalldaempfer
│   │   └── /shop/waffenzubehoer/waffenschraenke
│   ├── /shop/wiederladen
│   └── /shop/bekleidung
├── /produkt/{slug}                 Produktdetailseite (flach, kategorieunabhängig → keine Duplicate URLs)
├── /hersteller                     Herstellerübersicht A–Z
│   └── /hersteller/{slug}          Hersteller-Landingpage (glock, cz, sig-sauer, heckler-koch, walther,
│                                   beretta, mauser, blaser, steiner, zeiss, leica)
├── /kaliber/{slug}                 Kaliber-Landingpages (z. B. /kaliber/308-win, /kaliber/9mm-luger)
├── /verkaufsservice                Egun-Verkaufsservice (Kommissionsverkauf)
│   └── /verkaufsservice/anfrage    Formular (mehrstufig)
├── /waffen-ankauf                  „Wir kaufen Waffen“
│   └── /waffen-ankauf/anfrage
├── /werkstatt                      Leistungsübersicht
│   ├── /werkstatt/{leistung}       waffenreinigung, waffenpflege, optikmontage, einschiessen,
│   │                               reparaturen, cerakote
│   └── /werkstatt/termin           Online-Terminbuchung
├── /beratung                       Beratungstermin buchen (CTA „Beratung buchen“)
├── /ueber-uns
├── /blog
│   ├── /blog/kategorie/{slug}      jagd, sportschiessen, waffenrecht, produktvorstellungen, optiken, munition
│   ├── /blog/autor/{slug}
│   └── /blog/{slug}
├── /faq                            Mit Kategorien (Bestellung, Versand, WBK, Munition, Werkstatt …)
├── /kontakt
├── /vergleich                      Produktvergleich (noindex)
├── /merkliste                      Wunschliste Gäste/Kunden (noindex)
├── /suche?q=                       Suchergebnisseite (noindex, follow)
├── /warenkorb                      (noindex)
├── /kasse                          Checkout, mehrstufig (noindex)
│   ├── /kasse/adresse
│   ├── /kasse/berechtigung         Nur bei erlaubnispflichtigen Artikeln
│   ├── /kasse/versand
│   ├── /kasse/zahlung
│   └── /kasse/pruefen
├── /konto                          Kundenkonto (noindex)
│   ├── /konto/anmelden
│   ├── /konto/registrieren
│   ├── /konto/passwort-vergessen
│   ├── /konto/2fa
│   ├── /konto/bestellungen
│   ├── /konto/bestellungen/{nr}    inkl. Versandstatus/Tracking
│   ├── /konto/rechnungen
│   ├── /konto/dokumente            WBK/Jagdschein/Ausweis hochladen, Status
│   ├── /konto/downloads            Anleitungen gekaufter Produkte
│   ├── /konto/favoriten
│   ├── /konto/merklisten           Mehrere benannte Listen
│   ├── /konto/termine              Werkstatt-/Beratungstermine
│   ├── /konto/anfragen             Verkaufs-/Ankaufanfragen mit Status
│   ├── /konto/sicherheit           Passwort, 2FA, aktive Sitzungen
│   └── /konto/datenschutz          Datenexport, Konto löschen, Newsletter
├── /newsletter/bestaetigen/{token}
├── /versandinformationen
├── /zahlungsarten
├── /impressum
├── /datenschutz
├── /agb
├── /widerruf
├── /barrierefreiheit               Erklärung nach BFSG (gilt seit 28.06.2025 für Online-Shops)
└── /cookie-einstellungen
```

## 2. URL-Regeln

| Regel | Beispiel |
|---|---|
| Nur Kleinbuchstaben, Bindestriche, Umlaute transliteriert (ä→ae, ß→ss) | `/shop/waffenzubehoer` |
| Produkt-URLs flach, ohne Kategoriepfad; Slug = Hersteller + Modell + Kaliber | `/produkt/sig-sauer-p320-x5-9mm-luger` |
| Gebrauchtwaffen erhalten Suffix mit Artikelnummer (Einzelstücke) | `/produkt/blaser-r8-professional-308-win-gebraucht-af10234` |
| Filter als Query-Parameter, Standard `noindex` | `/shop/kurzwaffen?hersteller=glock&kaliber=9mm-luger` |
| Ausgewählte Filterkombinationen als eigene, indexierbare Landingpage | `/shop/kurzwaffen/glock-9mm` (gepflegt im Admin, „SEO-Landingpage“) |
| Pagination `?seite=2`, self-canonical je Seite | |
| Slug-Änderungen erzeugen automatisch 301-Weiterleitung (Tabelle `redirects`) | |
| Abschließender Slash: nein, 301 auf ohne Slash | |

## 3. Hauptnavigation (Sticky Header)

```
[Logo]  Shop ▾  Hersteller ▾  Verkaufen & Ankauf ▾  Werkstatt  Blog  Über uns     [🔍 Suche]  [♡]  [⇄]  [👤]  [🛒 2]
```

- Header wird beim Scrollen auf 64 px verkleinert, Hintergrund #111111 mit 90 % Deckkraft + Backdrop-Blur.
- Top-Bar darüber (ausblendbar beim Scrollen): „Versand nur an Berechtigte · Abholung in [Ort] · Tel. …“.

### Mega-Menü „Shop“

| Spalte 1 | Spalte 2 | Spalte 3 | Spalte 4 (Teaser) |
|---|---|---|---|
| **Waffen**: Kurzwaffen, Langwaffen, Jagdwaffen, Sportwaffen, Gebrauchtwaffen | **Optik**: Zielfernrohre, Wärmebild, Ferngläser, Rotpunkt, Montagen | **Munition & Zubehör**: Munition, Wiederladen, Waffenpflege, Magazine, Holster, Waffenschränke | Aktion des Monats (Bild + Preis), „Neu eingetroffen“ |
| **Messer**, **Bekleidung** | Top-Marken Optik: Zeiss, Leica, Steiner, Swarovski | Schalldämpfer (Hinweis: erlaubnispflichtig) | |

### Mega-Menü „Hersteller“
Logo-Raster der 11 Hauptmarken + Link „Alle Hersteller A–Z“.

### Mobile Navigation
Bottom-Sheet-Menü (Off-Canvas von links), Kategorien als Akkordeon, darunter Konto, Merkliste, Kontakt. Unten fixierte Leiste auf Produktseiten: Preis + „In den Warenkorb“.

## 4. Footer

- Spalte 1: Logo, Kurztext, Adresse, Öffnungszeiten, Telefon, Google-Maps-Link (kein eingebettetes Maps ohne Consent)
- Spalte 2: Shop (Hauptkategorien)
- Spalte 3: Service (Verkaufsservice, Ankauf, Werkstatt, Beratung, FAQ, Versandinformationen, Zahlungsarten)
- Spalte 4: Rechtliches (Impressum, Datenschutz, AGB, Widerruf, Barrierefreiheit, Cookie-Einstellungen)
- Newsletter-Anmeldung (Double Opt-in), Zahlungs- und Versandlogos, Social Links
- Pflichthinweis: „Abgabe erlaubnispflichtiger Waffen und Munition nur an Berechtigte gegen Vorlage der Erwerbserlaubnis. Alle Preise inkl. MwSt.“

## 5. Seiteninventar mit Zweck und Template

| Seite | Template | Indexierung | Primäres Ziel |
|---|---|---|---|
| Startseite | `home` | index | Orientierung, Vertrauen, Einstieg in Kategorien |
| Kategorie | `category` | index | Produkt finden (Filter) |
| Produkt | `product` | index | Kauf / Reservierung / Anfrage |
| Hersteller | `brand` | index | SEO-Traffic „{Marke} kaufen“, Markenvertrauen |
| Kaliber | `caliber` | index | SEO-Traffic „{Kaliber} Munition / Waffen“ |
| Verkaufsservice | `landing` + `form` | index | Leads Kommissionsverkauf |
| Waffen-Ankauf | `landing` + `form` | index | Leads Ankauf |
| Werkstatt | `landing` + `booking` | index | Termine |
| Blog | `blog-list` / `blog-post` | index | Reichweite, Expertise (E-E-A-T) |
| FAQ | `faq` | index | Support-Entlastung, FAQ-Rich-Results |
| Konto/Kasse | `account` / `checkout` | noindex | Transaktion |
| Rechtstexte | `legal` | index (Impressum, AGB) / noindex optional | Pflicht |
