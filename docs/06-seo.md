# 06 – SEO-Konzept

Stand: 10.10.2026 · Domain: `https://www.armory-falter.de` (bisher WordPress-Website, wird durch den Shop ersetzt)

## 1. Rahmenbedingungen

- **Keine bezahlte Suchwerbung für Waffen/Munition:** Google Ads und Google Shopping/Merchant Center lassen Waffen, Munition und viele Waffenteile nicht zu (Richtlinie „Gefährliche Produkte und Dienstleistungen“). Optiken, Bekleidung, Messer-/Pflegezubehör sind teilweise zulässig – vor Einreichung eines Feeds Richtlinie je Warengruppe prüfen. Ein Merchant-Center-Feed enthält deshalb **nur** freie Warengruppen; Waffen/Munition werden ausgeschlossen. Organische Suche ist der wichtigste Kanal.
- Ein-Sprachen-Shop (Deutsch, Zielmarkt Deutschland) → **kein hreflang** erforderlich.
- Technik: serverseitiges Rendering (Blade) → vollständiges HTML für Crawler, keine JS-Abhängigkeit.

## 2. URL-Schema (Kurzfassung, Details in 01)

| Typ | Muster | Beispiel |
|---|---|---|
| Kategorie | `/shop/{pfad}` | `/shop/optiken/zielfernrohre` |
| Produkt | `/produkt/{hersteller}-{modell}-{kaliber}` | `/produkt/glock-17-gen5-9mm-luger` |
| Gebrauchtwaffe | `…-gebraucht-{artikelnr}` | `/produkt/blaser-r8-professional-308-win-gebraucht-af10234` |
| Hersteller | `/hersteller/{slug}` | `/hersteller/zeiss` |
| Kaliber | `/kaliber/{slug}` | `/kaliber/308-win` |
| SEO-Landingpage (Filterkombination) | `/shop/{kategorie}/{thema}` | `/shop/kurzwaffen/glock-9mm` |
| Blog | `/blog/{slug}` | `/blog/drueckjagd-vorbereitung-checkliste` |
| Service | `/werkstatt/{leistung}` | `/werkstatt/optikmontage` |

Kleinbuchstaben, Umlaute transliteriert, kein Trailing Slash (301), Slug-Änderung erzeugt automatisch einen Eintrag in `redirects`.

## 3. Meta-Titel und -Beschreibungen

Automatische Muster (`MetaBuilder`), im Admin je Datensatz überschreibbar (`seo_meta`). Titel ≤ 60 Zeichen, Description 140–160 Zeichen. Markenname am Ende: „| Armory Falter“.

| Template | Titel-Muster | Description-Muster |
|---|---|---|
| Startseite | `Waffenhandel & Jagdausrüstung bei Stuttgart \| Armory Falter` | `Fachhändler für Jagd- und Sportwaffen, Optik, Munition & Zubehör in Wolfschlugen. Werkstatt, Ankauf & Verkaufsservice. Jetzt online entdecken.` |
| Kategorie | `{Kategorie} kaufen – {Top-Marken} \| Armory Falter` | `{Anzahl} {Kategorie} von {Marke1}, {Marke2} & {Marke3}. {USP}. Beratung vom Fachhändler bei Stuttgart.` |
| Produkt neu | `{Hersteller} {Modell} {Kaliber} kaufen \| Armory Falter` | `{Hersteller} {Modell} in {Kaliber}: {Kernmerkmal}. {Preis-Hinweis}. {Erwerbshinweis}. Beratung & Werkstatt vom Fachhändler.` |
| Produkt gebraucht | `{Hersteller} {Modell} {Kaliber} gebraucht \| Armory Falter` | `Gebrauchte {Hersteller} {Modell}, Zustand {Zustand}, geprüft durch unsere Werkstatt. Einzelstück – Abgabe nur an Berechtigte.` |
| Hersteller | `{Hersteller} kaufen – {Produktgruppen} \| Armory Falter` | `{Hersteller} beim Fachhändler: {Produktgruppen}. Große Auswahl, Beratung, Montage & Service in Wolfschlugen bei Stuttgart.` |
| Kaliber | `Kaliber {Kaliber}: Waffen & Munition \| Armory Falter` | |
| Blogpost | `{Titel}` (ohne Marke, falls > 60 Zeichen) | Excerpt |
| Werkstatt-Leistung | `{Leistung} vom Büchsenmacher bei Stuttgart \| Armory Falter` | |

Beispiele:

| Seite | Titel | Description |
|---|---|---|
| Glock 17 Gen5 | `Glock 17 Gen5 9mm Luger kaufen \| Armory Falter` (47) | `Glock 17 Gen5 in 9 mm Luger: Marksman-Lauf, nDFS-Beschichtung, 17 Schuss. Abgabe gegen WBK-Voreintrag. Beratung & Service vom Fachhändler.` |
| Blaser R8 Professional | `Blaser R8 Professional .308 Win. kaufen \| Armory Falter` | `Geradezugrepetierer Blaser R8 Professional in .308 Win.: Wechsellaufsystem, Handspannung. Für Jäger mit gültigem Jagdschein. Montage & Einschießen im Haus.` |
| Zeiss V8 1,8-14x50 | `Zeiss V8 1,8-14x50 Zielfernrohr kaufen \| Armory Falter` | `Zeiss V8 1,8-14x50 mit Leuchtabsehen 60: Drückjagd bis Ansitz in einem Glas. Montage auf Ihre Waffe durch unsere Werkstatt. Jetzt bestellen.` |
| Hersteller Zeiss | `Zeiss Zielfernrohre & Ferngläser kaufen \| Armory Falter` | |
| Kategorie Kurzwaffen | `Kurzwaffen kaufen – Glock, Walther, SIG Sauer \| Armory Falter` | |

Technische Produktmerkmale in Beispielen werden vor Veröffentlichung gegen Herstellerdaten geprüft.

## 4. Open Graph / Twitter Cards

```html
<meta property="og:type" content="product">            <!-- website | article | product -->
<meta property="og:site_name" content="Armory Falter">
<meta property="og:locale" content="de_DE">
<meta property="og:title" content="Blaser R8 Professional .308 Win.">
<meta property="og:description" content="…">
<meta property="og:url" content="https://www.armory-falter.de/produkt/blaser-r8-professional-308-win">
<meta property="og:image" content="https://www.armory-falter.de/storage/og/blaser-r8-professional-1200x630.jpg">
<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Blaser R8 Professional Repetierbüchse, Seitenansicht">
<meta name="twitter:card" content="summary_large_image">
```

OG-Bilder werden automatisch 1200×630 erzeugt (Produktbild auf dunklem Hintergrund mit Logo). Preise werden bewusst **nicht** als `product:price` ausgegeben, um Plattform-Richtlinien für Waffen beim Teilen nicht zu triggern (optional abschaltbar).

## 5. Strukturierte Daten (JSON-LD)

Ausgabe über `App\Domain\Seo\Services\JsonLd`, ein `@graph` je Seite, mit `@id`-Referenzen.

### 5.1 Organization + Store (global, Startseite und Kontakt)

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": ["Organization"],
      "@id": "https://www.armory-falter.de/#organization",
      "name": "Armory Falter",
      "url": "https://www.armory-falter.de/",
      "logo": "https://www.armory-falter.de/images/logo-armory-falter.png",
      "email": "info@armory-falter.de",
      "telephone": "+49 151 29107670",
      "founder": { "@type": "Person", "name": "Mark Falter" },
      "sameAs": ["https://www.instagram.com/…", "https://www.youtube.com/@…"]
    },
    {
      "@type": "Store",
      "@id": "https://www.armory-falter.de/#store",
      "name": "Armory Falter – Waffenhandel Wolfschlugen",
      "parentOrganization": { "@id": "https://www.armory-falter.de/#organization" },
      "image": "https://www.armory-falter.de/images/geschaeft-aussen.jpg",
      "telephone": "+49 151 29107670",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Stuttgarter Straße 2/1",
        "postalCode": "72649",
        "addressLocality": "Wolfschlugen",
        "addressRegion": "Baden-Württemberg",
        "addressCountry": "DE"
      },
      "geo": { "@type": "GeoCoordinates", "latitude": 48.65, "longitude": 9.29 },
      "areaServed": ["Wolfschlugen", "Nürtingen", "Esslingen", "Filderstadt", "Stuttgart"],
      "openingHoursSpecification": [
        { "@type": "OpeningHoursSpecification", "dayOfWeek": ["Tuesday","Wednesday","Thursday","Friday"], "opens": "10:00", "closes": "18:00" }
      ],
      "priceRange": "€€–€€€"
    },
    {
      "@type": "WebSite",
      "@id": "https://www.armory-falter.de/#website",
      "url": "https://www.armory-falter.de/",
      "name": "Armory Falter",
      "publisher": { "@id": "https://www.armory-falter.de/#organization" },
      "potentialAction": {
        "@type": "SearchAction",
        "target": { "@type": "EntryPoint", "urlTemplate": "https://www.armory-falter.de/suche?q={search_term_string}" },
        "query-input": "required name=search_term_string"
      }
    }
  ]
}
```

Geokoordinaten und Öffnungszeiten sind **Platzhalter** und vor Launch aus dem Google-Unternehmensprofil zu übernehmen (Werte müssen exakt übereinstimmen).

### 5.2 Product + Offer (Gebrauchtwaffe)

```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "@id": "https://www.armory-falter.de/produkt/blaser-r8-professional-308-win-gebraucht-af10234#product",
  "name": "Blaser R8 Professional .308 Win. (gebraucht)",
  "sku": "AF10234",
  "brand": { "@type": "Brand", "name": "Blaser" },
  "image": ["https://www.armory-falter.de/storage/products/af10234-1.jpg"],
  "description": "Gebrauchter Geradezugrepetierer, Lauflänge 58 cm, sehr guter Zustand, Werkstatt-geprüft.",
  "additionalProperty": [
    { "@type": "PropertyValue", "name": "Kaliber", "value": ".308 Win." },
    { "@type": "PropertyValue", "name": "Lauflänge", "value": "58", "unitCode": "CMT" }
  ],
  "offers": {
    "@type": "Offer",
    "url": "https://www.armory-falter.de/produkt/blaser-r8-professional-308-win-gebraucht-af10234",
    "priceCurrency": "EUR",
    "price": "2890.00",
    "itemCondition": "https://schema.org/UsedCondition",
    "availability": "https://schema.org/InStock",
    "seller": { "@id": "https://www.armory-falter.de/#organization" },
    "eligibleCustomerType": "https://schema.org/Enduser",
    "hasMerchantReturnPolicy": { "@id": "https://www.armory-falter.de/#returnpolicy" }
  }
}
```

Regeln: `itemCondition` = `NewCondition` / `UsedCondition` / `RefurbishedCondition` (Vorführwaffe → `UsedCondition` + Hinweis). `availability`: `InStock`, `LimitedAvailability`, `BackOrder`, `PreOrder`, `OutOfStock`, `InStoreOnly` (für `pickup_only`). Bewertungen (`aggregateRating`) nur, wenn echte Kundenbewertungen im Shop existieren – keine importierten oder erfundenen Werte. Rückgabebedingungen (`MerchantReturnPolicy`) einmal global, Inhalt aus Widerrufsbelehrung (anwaltlich geprüft).

Hinweis: Google zeigt für Waffen/Munition erfahrungsgemäß eingeschränkte oder keine Produkt-Rich-Results; das Markup bleibt trotzdem korrekt und nützt anderen Suchmaschinen und KI-Assistenten.

### 5.3 BreadcrumbList

```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": "Shop", "item": "https://www.armory-falter.de/shop" },
    { "@type": "ListItem", "position": 2, "name": "Optiken", "item": "https://www.armory-falter.de/shop/optiken" },
    { "@type": "ListItem", "position": 3, "name": "Zielfernrohre", "item": "https://www.armory-falter.de/shop/optiken/zielfernrohre" },
    { "@type": "ListItem", "position": 4, "name": "Zeiss V8 1,8-14x50" }
  ]
}
```

### 5.4 FAQPage

Auf `/faq`, Produkt-, Kategorie- und Herstellerseiten mit sichtbaren FAQ-Blöcken. Google zeigt FAQ-Rich-Results seit 2023 nur noch für ausgewählte Websites; das Markup bleibt sinnvoll für Verständnis und KI-Antworten.

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [{
    "@type": "Question",
    "name": "Kann ich eine Langwaffe mit meinem Jagdschein online bestellen?",
    "acceptedAnswer": { "@type": "Answer", "text": "Ja. Laden Sie Ihren gültigen Jahresjagdschein im Kundenkonto hoch. Nach Prüfung durch unser Team wird die Bestellung freigegeben; die Übergabe erfolgt persönlich im Geschäft oder per Waffenversand mit persönlicher Zustellung." }
  }]
}
```

### 5.5 Article + Person (Blog, E-E-A-T)

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "Drückjagd-Vorbereitung: Die Checkliste vom Büchsenmacher",
  "datePublished": "2026-11-03T08:00:00+01:00",
  "dateModified": "2026-11-10T12:00:00+01:00",
  "image": ["https://www.armory-falter.de/storage/blog/drueckjagd-1200x675.jpg"],
  "author": {
    "@type": "Person",
    "name": "Mark Falter",
    "url": "https://www.armory-falter.de/blog/autor/mark-falter",
    "jobTitle": "Inhaber, Waffenhändler",
    "worksFor": { "@id": "https://www.armory-falter.de/#organization" }
  },
  "publisher": { "@id": "https://www.armory-falter.de/#organization" },
  "mainEntityOfPage": "https://www.armory-falter.de/blog/drueckjagd-vorbereitung-checkliste"
}
```

Validierung: Rich Results Test + Schema.org Validator in der Abnahme; automatisierter Test, dass jede Template-Seite valides JSON-LD ausgibt.

## 6. Sitemaps & robots.txt

Sitemap-Index `/sitemap.xml` (täglich via Scheduler generiert, statisch unter `public/sitemaps/`):

| Datei | Inhalt | `lastmod` |
|---|---|---|
| `sitemap-pages.xml` | Startseite, Services, Landingpages, Rechtstexte (Impressum, AGB) | `updated_at` |
| `sitemap-categories.xml` | Kategorien + gepflegte SEO-Landingpages | letzte Produktänderung |
| `sitemap-products-{n}.xml` | Aktive Produkte, je 10 000 URLs, inkl. `<image:image>` | `updated_at` |
| `sitemap-brands.xml` | Hersteller | |
| `sitemap-calibers.xml` | Kaliber mit ≥ 3 Produkten | |
| `sitemap-blog.xml` | Blogposts, Kategorien, Autoren | `dateModified` |

Ausverkaufte Gebrauchtwaffen (Einzelstück verkauft): Seite bleibt 90 Tage mit „Verkauft“ + Alternativen (200), danach 301 auf Kategorie/Modell-Seite bzw. 410, wenn keine sinnvolle Alternative existiert.

```
User-agent: *
Disallow: /admin
Disallow: /konto
Disallow: /kasse
Disallow: /warenkorb
Disallow: /livewire/
Disallow: /suche
Disallow: /*?*sort=
Allow: /build/
Allow: /storage/

Sitemap: https://www.armory-falter.de/sitemap.xml
```

Filter-URLs werden **nicht** per robots.txt gesperrt (sonst sieht Google das `noindex` nicht), sondern per Meta-Robots gesteuert.

## 7. Canonical & facettierte Navigation

| Situation | Regel |
|---|---|
| Produkt in mehreren Kategorien | Eine URL `/produkt/{slug}`, Breadcrumb über Primärkategorie |
| Kategorie mit Pagination `?seite=2` | `index, follow`, self-canonical je Seite, Titelzusatz „– Seite 2“ |
| Kategorie mit **einem** Filter aus Whitelist (Hersteller) | Wenn eine SEO-Landingpage existiert → 301 auf diese, sonst `noindex, follow` + Canonical auf Kategorie |
| Mehrere Filter / Preis / Sortierung | `noindex, follow`, Canonical auf Kategorie |
| Varianten (Größe/Farbe) | Keine eigenen URLs |
| Suche `/suche` | `noindex, follow` |
| Tracking-Parameter (`utm_*`, `gclid`) | Canonical ohne Parameter |
| Interne Links | Immer auf kanonische URLs; Filterlinks als `<a>` (für Nutzer), aber nicht in Sitemaps |

SEO-Landingpages (`seo_landing_pages`) werden im Admin angelegt, wenn Keyword-Recherche Suchvolumen zeigt (z. B. „Glock 9mm kaufen“, „Drückjagd Zielfernrohr“, „Gebrauchtwaffen Stuttgart“), mit eigener H1, Einleitung und FAQ.

## 8. Interne Verlinkung

- Mega-Menü und Footer verlinken alle Hauptkategorien und Top-Hersteller.
- Produktseite → Hersteller, Kaliber-Seite, Primärkategorie, passendes Zubehör (Montagen, Munition im Kaliber, Pflege), relevante Blogposts (`blog_post_product`).
- Blogpost → 2–5 Produkte/Kategorien + 1 Service-Seite (Werkstatt/Ankauf) + verwandte Posts.
- Hersteller → Top-Produkte, Kategorien des Herstellers, Ratgeber.
- Kaliber → Waffen und Munition im Kaliber, Ballistik-Ratgeber.
- Breadcrumbs überall; „Zuletzt angesehen“ per Livewire (nicht crawlbar, nur UX).
- Ankertexte beschreibend („Zeiss V8 Zielfernrohre“), nie „hier klicken“.

## 9. Local SEO

| Maßnahme | Details |
|---|---|
| **Google-Unternehmensprofil** | Kategorie „Waffengeschäft“ (Haupt), „Jagdbedarfsgeschäft“, „Büchsenmacher“ (falls zutreffend); Öffnungszeiten, Fotos (Laden, Werkstatt, Team), Leistungen (Werkstatt, Ankauf, Verkaufsservice, Optikmontage); Beiträge 2×/Monat (Events, Neuheiten ohne Waffenwerbung im Sinne der Google-Richtlinien); Fragen & Antworten pflegen |
| NAP-Konsistenz | „Armory Falter, Stuttgarter Straße 2/1, 72649 Wolfschlugen, +49 151 29107670“ identisch in Website-Footer, Impressum, Profil, Branchenbüchern (Gelbe Seiten, Das Örtliche, 11880, Apple Maps, Bing Places) |
| Bewertungen | Nach Abholung/Werkstattauftrag Einladung per E-Mail (kein Anreiz, keine Filterung negativer Bewertungen); Antworten innerhalb 48 h |
| Lokale Landingpages | `/waffenhandel-stuttgart`, `/waffenhandel-esslingen`, `/buechsenmacher-nuertingen` nur mit echtem lokalem Inhalt (Anfahrt, Einzugsgebiet, Referenzen, Schießstände/Vereine der Region) – keine Doorway-Pages |
| Lokale Links | Jägervereinigungen/Kreisjägervereinigungen Esslingen/Nürtingen, Schützenvereine, Hegeringe, Gemeinde Wolfschlugen, regionale Presse |
| Einzugsgebiet-Keywords | „Waffengeschäft Esslingen“, „Waffenhändler Stuttgart“, „Büchsenmacher Nürtingen“, „Zielfernrohr montieren Stuttgart“, „Waffen verkaufen Stuttgart“ |

## 10. Migration von WordPress (www.armory-falter.de)

1. **Bestandsaufnahme (4 Wochen vor Launch):** Vollständiger Crawl der Altseite (Screaming Frog), Export aller URLs aus WordPress-Sitemap, Search Console (Seiten mit Impressionen der letzten 16 Monate) und Backlink-Daten (Search Console „Links“). Bilder-URLs mit Traffic separat erfassen.
2. **Redirect-Map:** Tabelle `alte URL → neue URL → Typ` (Seite, Beitrag, Kategorie, Bild, Feed). Typische WP-Muster: `/?p=123`, `/{jahr}/{monat}/{slug}/`, `/category/{x}/`, `/tag/{x}/`, `/feed/`, `/wp-content/uploads/…`, `/shop/…` (falls WooCommerce). Ziel: jede URL mit Traffic oder Backlink → thematisch passende neue URL (nicht pauschal auf Startseite). Rest: 410 für Spam/Testseiten.
3. **Import** der Map in Tabelle `redirects` (CSV-Import im Admin); Middleware `HandleRedirects` greift vor dem Routing; Muster-Regeln (z. B. `/category/*` → `/blog/kategorie/*`) zusätzlich in `.htaccess`.
4. **Inhalte übernehmen:** Wertvolle Texte/Beiträge in Blog bzw. CMS migrieren (Autor, Datum erhalten).
5. **Test auf Staging:** Alle Alt-URLs automatisiert gegen Staging prüfen (Statuscode 301, Ziel 200, keine Ketten).
6. **Launch-Tag:** DNS/Docroot umstellen, Sitemap in Search Console einreichen, Stichproben-Crawl, `site:`-Abfrage.
7. **Nachbetreuung 8 Wochen:** 404-Log im Admin (Treffer), fehlende Redirects nachtragen, Search Console „Seiten“-Bericht und Ranking-Monitoring wöchentlich. Redirects mindestens 12 Monate, wichtige dauerhaft bestehen lassen.

Domain-Property in Search Console (`armory-falter.de`, DNS-Verifizierung über Cloudflare) und Bing Webmaster Tools anlegen.

## 11. Core Web Vitals

| Metrik | Ziel (75. Perzentil, mobil) | Messung |
|---|---|---|
| LCP | ≤ 2,0 s (Google-Grenze 2,5 s) | CrUX/Search Console, Lighthouse CI, `web-vitals`-Snippet in Matomo/Plausible (nach Consent bzw. cookielos) |
| INP | ≤ 150 ms (Grenze 200 ms) | Feldmessung; Livewire-Interaktionen < 100 ms Serverzeit |
| CLS | ≤ 0,05 (Grenze 0,1) | feste Bildmaße, reservierte Höhen für Lazy-Komponenten |
| TTFB | ≤ 400 ms (gecachte Seiten ≤ 100 ms über Cloudflare) | Server-Timing-Header, Uptime-Monitor |
| Lighthouse Performance | ≥ 95 mobil (Start, Kategorie, Produkt, Blog) | Lighthouse CI je Pull Request |

Maßnahmen siehe [05-frontend.md](05-frontend.md#10-performance-plan-ziel-lighthouse--95-mobil-auf-start-kategorie-produkt).

## 12. Monitoring & KPIs

Search Console (Impressionen, Klicks, CTR je Template), Rankings für 150 Fokus-Keywords (Hersteller, Kategorien, lokal), organische Umsätze/Leads (Matomo-Ziele: Bestellung, Ankaufanfrage, Terminbuchung), Indexierungsquote Produkte (≥ 90 % der aktiven Produkte indexiert), 404-Rate (< 0,5 % der Aufrufe).
