# 14 – Skalierungskonzept

Stand: 10.10.2026 · Leitgedanke: **Einfach starten, messen, erst bei belegtem Bedarf ausbauen – ohne Code-Neuschreibung.** Alle Ausbaustufen nutzen dieselbe Laravel-Codebasis; der Wechsel erfolgt über `.env`-Treiber und Infrastruktur.

## 1. Lastannahmen (Jahr 1)

| Kennzahl | Annahme normal | Spitze (Aktion, Newsletter, IWA, Black Friday, Drückjagdsaison) |
|---|---|---|
| Besuche/Monat | 15 000–30 000 | – |
| Seitenaufrufe/Tag | 2 000–5 000 | 20 000 |
| Gleichzeitige Besucher | 10–30 | 150–250 (Newsletter an 5 000 Empfänger in 30 Min.) |
| Requests/s an den Origin | < 2 | 10–20 (ohne Cache), < 3 mit Cache |
| Bestellungen/Tag | 5–20 | 80 |
| Produkte | 3 000–8 000 (inkl. Varianten), Ziel 20 000 | |
| Dokument-Uploads/Tag | 5–30 | 100 |
| Datenbankgröße | < 2 GB (ohne Dateien) | |
| Dateien | 20–50 GB (Produktbilder in mehreren Formaten, Dokumente, Trade-Fotos) | |

Die Anwendung ist leseintensiv (Katalog, Blog) und schreibarm. Der größte Hebel ist daher **Caching vor PHP**.

## 2. Stufe 1: Shared Hosting (Start)

### 2.1 Typische Grenzen

| Ressource | Typischer Wert Business-Tarif | Auswirkung |
|---|---|---|
| PHP-Prozesse (FPM-Worker) parallel | 10–40 | Bei langsamen Requests (> 500 ms) Warteschlange |
| `memory_limit` | 256–1024 MB | PDF/Bildverarbeitung in Jobs |
| `max_execution_time` | 60–300 s | Jobs in Chunks |
| CPU | geteilt, Fair-Use | Schwankende TTFB |
| Cron | 1 Min. kleinstes Intervall | Queue-Latenz bis ~60 s |
| MySQL-Verbindungen | 25–100 | Response-Cache entlastet |
| Inodes / Speicher | 250 000–1 Mio. Dateien, 50–250 GB | Bildvarianten und alte Releases aufräumen |
| Kein Redis/Memcached | – | Cache/Session/Queue in DB bzw. Dateisystem |

### 2.2 Caching-Strategie

| Ebene | Was | TTL / Invalidierung |
|---|---|---|
| **Cloudflare Edge** | Statische Assets (`/build`, Fonts, Bilder): `immutable`, 1 Jahr (Dateinamen mit Hash) | Neue Dateinamen bei Build |
| **Cloudflare Edge (HTML)** | Anonyme Seiten (Start, Kategorie, Produkt, Hersteller, Blog) | 10 Min. Edge-TTL, Bypass bei Login-/Warenkorb-Cookie; Purge by URL bei Änderungen |
| **Response-Cache (Laravel)** | Vollständige HTML-Antworten anonymer Besucher im Dateisystem (`spatie/laravel-responsecache`) | 60 Min., Tag-basiertes Leeren bei Produkt/Kategorie/Inhalt-Änderung |
| **Fragment-/Query-Cache** | Kategoriebaum, Mega-Menü, Facettenzählungen je Kategorie, Herstellerliste, Settings | `Cache::remember` 10–60 Min., Event-basiertes Vergessen |
| **Personalisierte Teile** | Mini-Cart-Zähler, Merkliste, „Ihr Status“ auf Produktseite | Nachladen per Livewire `lazy`/kleiner JSON-Endpunkt – nie im gecachten HTML |
| **OPcache** | PHP-Bytecode | Standard des Hosters, `validate_timestamps` laut Hoster |
| **Laravel-Optimierungen** | `config/route/view/event:cache` | bei jedem Deploy |
| **Bilder** | Vorab generierte AVIF/WebP-Varianten, keine On-the-fly-Skalierung im Request | bei Upload (Queue) |
| **Datenbank** | Indizes laut [02-datenbankmodell.md](02-datenbankmodell.md#5-indizes-über-das-ddl-hinaus), Facettenzählungen gecacht, Pagination mit Limits | – |

Ziel: ≥ 80 % der anonymen HTML-Aufrufe aus Cloudflare oder Response-Cache, Origin-TTFB für ungecachte Seiten < 400 ms.

### 2.3 Lastspitzen planen

- Newsletter in Chunks (200 Mails/Min.) → gleichmäßiger Besucherstrom.
- Cache-Warm-up nach Deploy und vor Aktionen (`cache:warm` crawlt Top-500-URLs).
- Aktionen nicht zur vollen Stunde gleichzeitig mit Newsletter-Versand.

## 3. Wann wechseln? (Schwellenwerte)

Wechsel auf Stufe 2 vorbereiten, sobald **zwei** der folgenden Kriterien über **4 Wochen** erfüllt sind (Messung über Uptime-Monitor, Server-Timing, Admin-Systemstatus, Cloudflare Analytics):

| Kriterium | Schwellenwert |
|---|---|
| Origin-TTFB p95 (ungecachte Seiten) | > 800 ms |
| Queue-Latenz p95 (Job erstellt → erledigt) | > 3 Min. |
| Fehlerrate 5xx | > 0,5 % der Requests oder wiederholte 503/508 (Ressourcenlimit) |
| Hoster-Warnungen | CPU-/Prozesslimit-Hinweise, Drosselung |
| Gleichzeitige Nutzer in Spitzen | > 300 regelmäßig |
| Bestellungen/Tag | > 100 regelmäßig |
| Produkte | > 20 000 oder Suche mit FULLTEXT unzureichend (Relevanz, Tippfehlertoleranz) |
| Funktionsbedarf | Redis-basierte Features, ClamAV-Virenscan, OCR, Echtzeit-Queue (z. B. für Kassenanbindung) |

## 4. Stufe 2: Managed vServer (Laravel Forge / Ploi)

| Komponente | Umsetzung |
|---|---|
| Server | 1 vServer in DE (z. B. 4 vCPU, 8–16 GB RAM, NVMe), verwaltet über **Laravel Forge** oder **Ploi** (Provisionierung, Deploy, SSL, Firewall) |
| Webserver | NGINX + PHP-FPM 8.5, OPcache mit `validate_timestamps=0` |
| Cache/Session/Queue | **Redis** (`CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`) |
| Queue-Worker | **Laravel Horizon** (Supervisor), getrennte Queues, Dashboard (nur Admin) |
| Suche | **Meilisearch** (selbst gehostet) über Laravel Scout – Tippfehlertoleranz, Synonyme (Kaliber-Aliase), Facetten |
| Datenbank | MySQL 8 lokal oder Managed DB des Anbieters |
| Sicherheit | ClamAV für Uploads, Fail2ban, UFW (nur Cloudflare-IPs auf 443), unattended-upgrades |
| Deploy | Zero-Downtime-Deploys (Forge/Envoyer-Prinzip, identisch zur Release-Struktur aus Stufe 1) |
| Backups | wie Stufe 1 + Server-Snapshots |

Migration (1–2 Tage): Server provisionieren → Code deployen → DB-Dump + `storage/` übertragen → `.env`-Treiber umstellen → Scout-Index aufbauen (`scout:import`) → Cloudflare-A-Record ändern (proxied, sofort wirksam) → Shared Hosting 2 Wochen als Fallback behalten. **Keine Codeänderung**, nur Konfiguration (Suchtreiber ist über `ProductSearch`-Interface bereits abstrahiert).

## 5. Stufe 3: Verteilte Architektur

Nur bei deutlichem Wachstum (z. B. > 1 000 gleichzeitige Nutzer, mehrere Vertriebskanäle, App mit hoher API-Last):

| Komponente | Umsetzung |
|---|---|
| Datenbank | Separater Managed-MySQL-Server (EU), Read-Replica für Reporting/Suche-Indexierung (`read`/`write`-Verbindungen in `config/database.php`) |
| Dateien | **S3-kompatibler Objektspeicher in der EU** (z. B. Hetzner, IONOS, OVH, Wasabi EU) für Medien; private Dokumente in separatem Bucket mit serverseitiger + clientseitiger Verschlüsselung; Auslieferung öffentlicher Medien über Cloudflare |
| App-Server | 2+ identische App-Server hinter **Load Balancer** (Cloudflare Load Balancing oder Anbieter-LB), Sessions in Redis, keine lokalen Dateien |
| Redis | Eigener Server/Managed Redis |
| Worker | Eigener Worker-Server für Horizon (Bildverarbeitung, PDF, Newsletter) |
| Suche | Meilisearch auf eigenem Knoten |
| Beobachtbarkeit | Zentrales Logging (z. B. Grafana Loki), Metriken, Tracing |

Voraussetzungen, die schon ab Stufe 1 im Code eingehalten werden: Dateizugriffe nur über `Storage`-Disks (kein `file_put_contents` auf lokale Pfade), keine Zustände im Prozessspeicher, Jobs idempotent, Scheduler-Aufgaben mit `onOneServer()`, Uploads streamingfähig.

## 6. Übersicht

| | Stufe 1 Shared Hosting | Stufe 2 Managed vServer | Stufe 3 Verteilt |
|---|---|---|---|
| Kosten/Monat (Größenordnung) | 15–50 € + Cloudflare Free/Pro | 40–120 € + Forge/Ploi ~10–20 € | 300 €+ |
| Queue | DB + Cron (Latenz ≤ 1 Min.) | Redis + Horizon (Sekunden) | Redis + Worker-Server |
| Cache | DB/Datei + Response-Cache + Cloudflare | Redis + Response-Cache + Cloudflare | Redis-Cluster |
| Suche | MySQL FULLTEXT | Meilisearch | Meilisearch-Knoten |
| Uploads/Scan | Re-Encoding, kein ClamAV | + ClamAV | + Objektspeicher |
| Codeänderungen | – | keine (Treiber) | minimal (Disks/DB-Verbindungen konfigurieren) |
