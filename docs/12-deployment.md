# 12 – Deployment-Anleitung (Shared Hosting, PHP 8.5)

Stand: 10.10.2026 · Domain `www.armory-falter.de` (Apex `armory-falter.de` → 301 auf `www`)

## 1. Hoster-Anforderungen (Checkliste vor Vertragsschluss)

| Anforderung | Muss/Soll | Prüfung |
|---|---|---|
| PHP **8.5** (CLI und Web gleiche Version), pro Verzeichnis/Domain wählbar | Muss | `php -v` per SSH, `phpinfo()` |
| Erweiterungen: `bcmath, ctype, curl, dom, fileinfo, gd` oder `imagick, intl, mbstring, openssl, pdo_mysql, sodium, tokenizer, xml, zip` (+ `opcache`, `exif`) | Muss | `php -m` |
| `memory_limit` ≥ 256 MB, `upload_max_filesize` ≥ 20 MB, `post_max_size` ≥ 64 MB, `max_execution_time` ≥ 60 s, `max_file_uploads` ≥ 25 | Muss | eigene `php.ini`/`.user.ini` |
| **MySQL 8** (bzw. MariaDB ≥ 10.11 nur nach Test von JSON/FULLTEXT) | Muss | |
| **SSH-Zugang** + Composer, Git | Soll (stark empfohlen) | ohne SSH: FTP-Fallback (Abschnitt 6.3) |
| **Cron jede Minute** | Muss | Kundenmenü |
| Dokumentenwurzel frei auf Unterverzeichnis setzbar (`…/public`) | Soll | sonst Root-`.htaccess`-Rewrite |
| Symlinks erlaubt (`storage:link`) | Soll | sonst Alternative (Abschnitt 5) |
| Let's-Encrypt-SSL | Muss | bzw. Cloudflare Origin Certificate |
| Serverstandort **Deutschland**, AV-Vertrag nach Art. 28 DSGVO | Muss | |
| Tägliche Hoster-Backups (Dateien + DB), 14 Tage | Soll | |
| `exec`/`proc_open` nicht erforderlich | – | App ist so gebaut |
| **AGB erlauben Shop mit Waffen/Munition** | Muss | schriftlich bestätigen lassen |
| E-Mail-Postfächer/SMTP | Soll | alternativ EU-Mailanbieter |

Beispiele für geeignete Tarife sind Business-/„Managed“-Webhosting-Pakete deutscher Anbieter mit SSH und Cron; konkrete Auswahl in Sprint 1 anhand dieser Liste.

## 2. Verzeichnisstruktur auf dem Server

```
/home/<account>/
├── armory-falter/                 ← Anwendung (NICHT im Webroot)
│   ├── releases/
│   │   ├── 20261124-0930/         ← vollständiges Release inkl. vendor/ und public/build/
│   │   └── 20261201-1415/
│   ├── shared/
│   │   ├── .env                   ← chmod 600
│   │   └── storage/               ← app/private, app/public, framework, logs (persistent)
│   └── current -> releases/20261201-1415   ← Symlink (atomarer Wechsel)
└── public_html/  bzw. Docroot     ← zeigt auf armory-falter/current/public
```

Varianten:

1. **Docroot frei wählbar (empfohlen):** Docroot der Domain = `/home/<account>/armory-falter/current/public`.
2. **Docroot fix `public_html`, Symlinks erlaubt:** `public_html` löschen und als Symlink auf `armory-falter/current/public` anlegen.
3. **Docroot fix, keine Symlinks:** Release direkt in `armory-falter/app` (ohne `releases/`), Inhalt von `public/` nach `public_html/` kopieren und in `public_html/index.php` die Pfade anpassen:
   ```php
   require __DIR__.'/../armory-falter/app/vendor/autoload.php';
   $app = require_once __DIR__.'/../armory-falter/app/bootstrap/app.php';
   $app->usePublicPath(__DIR__);
   ```
4. **Notlösung (Anwendung liegt im Webroot):** Root-`.htaccess`, die alles nach `public/` umschreibt und den Zugriff auf alle anderen Verzeichnisse sperrt:
   ```apache
   RewriteEngine On
   RewriteRule ^(\.env|composer\.(json|lock)|artisan|storage|vendor|bootstrap|config|database|routes|resources|tests)(/|$) - [F,L]
   RewriteCond %{REQUEST_URI} !^/public/
   RewriteRule ^(.*)$ public/$1 [L]
   ```
   Nur verwenden, wenn 1–3 unmöglich sind; nach jedem Deploy mit `curl -I https://www.armory-falter.de/.env` (erwartet 403/404) prüfen.

## 3. `.env` Produktion (Auszug)

```dotenv
APP_NAME="Armory Falter"
APP_ENV=production
APP_KEY=base64:…                      # einmalig, sicher verwahren (Passwortmanager + Offline)
APP_DEBUG=false
APP_URL=https://www.armory-falter.de
ASSET_URL=https://www.armory-falter.de

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=…
DB_USERNAME=…
DB_PASSWORD=…

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=www.armory-falter.de
CACHE_STORE=database
QUEUE_CONNECTION=database
RESPONSE_CACHE_ENABLED=true

MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com        # oder SMTP des Hosters
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@armory-falter.de
MAIL_FROM_NAME="Armory Falter"

CAPTCHA_DRIVER=turnstile
TURNSTILE_SITE_KEY=…
TURNSTILE_SECRET_KEY=…
BLIND_INDEX_KEY=base64:…
CLOUDFLARE_ZONE_ID=…
CLOUDFLARE_API_TOKEN=…                # nur Cache-Purge-Recht

BACKUP_DISK=s3_backup
AWS_ACCESS_KEY_ID=… (EU-Objektspeicher)
AWS_DEFAULT_REGION=eu-central-1
AWS_ENDPOINT=https://…
AWS_BUCKET=af-backups

SENTRY_LARAVEL_DSN=                    # optional, EU-Region
```

## 4. Build (lokal oder CI)

```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction
npm ci
npm run build                           # erzeugt public/build (Vite-Manifest)
php artisan filament:assets             # Filament-CSS/JS nach public/
# Paket erstellen (ohne node_modules, tests, .git, .env)
tar --exclude=node_modules --exclude=.git --exclude=tests --exclude=.env -czf release.tgz .
```

Wichtig: Build mit **derselben PHP-Version** (8.5) wie auf dem Server, damit Composer-Plattformprüfungen passen (`config.platform.php` in `composer.json` auf `8.5.0` setzen).

## 5. Deploy-Schritte (SSH)

```bash
cd ~/armory-falter
REL=releases/$(date +%Y%m%d-%H%M)
mkdir -p $REL && tar -xzf ~/upload/release.tgz -C $REL
rm -rf $REL/storage && ln -s ~/armory-falter/shared/storage $REL/storage
ln -s ~/armory-falter/shared/.env $REL/.env

cd $REL
php artisan down --render="errors::503" --retry=30 --secret="<bypass-token>"   # nur bei Migrationen nötig
php artisan migrate --force
php artisan storage:link --relative || true
php artisan optimize                   # config, route, view, event cache
php artisan filament:optimize
cd ~/armory-falter && ln -sfn $REL current   # atomarer Wechsel
php current/artisan queue:restart       # laufender Worker beendet sich nach aktuellem Job
php current/artisan responsecache:clear
php current/artisan up
# Alte Releases aufräumen (5 behalten)
ls -1dt releases/* | tail -n +6 | xargs rm -rf
```

**Ohne Symlink-Erlaubnis für `storage:link`:** öffentliche Dateien auf eine Disk legen, deren `root` direkt `public_path('media')` ist (`config/filesystems.php`, Disk `public_media`), oder eine Route `/storage/{path}` mit Cache-Headern ausliefern (langsamer, durch Cloudflare-Cache abgefedert). Private Dokumente liegen ohnehin in `shared/storage/app/private` und werden nur über Controller ausgeliefert.

**OPcache:** Nach dem Wechsel des `current`-Symlinks verwendet OPcache u. U. alte Pfade (realpath-Cache). Falls der Hoster keinen OPcache-Reset per Panel bietet: kleines, durch Token geschütztes Skript `opcache_reset()` aufrufen oder Docroot auf den Release-Pfad (nicht Symlink) zeigen lassen und Docroot je Release umstellen (Variante 3).

## 6. Cron, Queue, Scheduler

Ein einziger Cron-Eintrag im Hoster-Panel:

```
* * * * * /usr/bin/php8.5 /home/<account>/armory-falter/current/artisan schedule:run >> /dev/null 2>&1
```

Der Scheduler startet jede Minute `queue:work --stop-when-empty --max-time=50` (mit `withoutOverlapping()`), alle weiteren Jobs siehe [03-backend-laravel.md](03-backend-laravel.md#5-scheduler-routesconsolephp). Kontrolle: Admin-Widget „Systemstatus“ zeigt letzten Scheduler-Lauf (Heartbeat in `cache`), Alarm per Mail, wenn > 5 Min. alt.

## 7. GitHub Actions

### 7.1 Deploy per SSH/rsync (Standard)

```yaml
# .github/workflows/deploy.yml
name: deploy
on:
  push:
    branches: [main]
  workflow_dispatch:

concurrency: production

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.4
        env: { MYSQL_DATABASE: testing, MYSQL_ROOT_PASSWORD: root }
        ports: ['3306:3306']
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-retries=5
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.5', extensions: 'bcmath, intl, gd, sodium, zip, pdo_mysql', coverage: none }
      - run: composer install --no-interaction --prefer-dist
      - run: vendor/bin/pint --test && vendor/bin/phpstan analyse --memory-limit=1G
      - run: php artisan test --parallel
        env: { DB_HOST: 127.0.0.1, DB_DATABASE: testing, DB_USERNAME: root, DB_PASSWORD: root }

  deploy:
    needs: test
    runs-on: ubuntu-latest
    environment: production
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.5', extensions: 'bcmath, intl, gd, sodium, zip' }
      - uses: actions/setup-node@v4
        with: { node-version: '22', cache: 'npm' }
      - run: composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction
      - run: npm ci && npm run build
      - run: php artisan filament:assets
      - run: tar --exclude=node_modules --exclude=.git --exclude=tests -czf release.tgz .
      - name: SSH-Key
        run: |
          mkdir -p ~/.ssh && echo "${{ secrets.SSH_KEY }}" > ~/.ssh/id_ed25519 && chmod 600 ~/.ssh/id_ed25519
          echo "${{ secrets.SSH_KNOWN_HOSTS }}" > ~/.ssh/known_hosts
      - run: rsync -az release.tgz ${{ secrets.SSH_USER }}@${{ secrets.SSH_HOST }}:~/upload/
      - run: rsync -az deploy/remote-deploy.sh ${{ secrets.SSH_USER }}@${{ secrets.SSH_HOST }}:~/upload/
      - run: ssh ${{ secrets.SSH_USER }}@${{ secrets.SSH_HOST }} 'bash ~/upload/remote-deploy.sh'
      - name: Smoke-Test
        run: |
          curl -fsS -o /dev/null https://www.armory-falter.de/
          curl -fsS -o /dev/null https://www.armory-falter.de/shop
          test "$(curl -s -o /dev/null -w '%{http_code}' https://www.armory-falter.de/.env)" != "200"
```

`deploy/remote-deploy.sh` enthält die Schritte aus Abschnitt 5. Die GitHub-Umgebung `production` verlangt manuelle Freigabe (Required Reviewer).

### 7.2 FTP-Fallback (ohne SSH)

```yaml
      - name: FTP-Deploy
        uses: SamKirkland/FTP-Deploy-Action@v4.3.5
        with:
          server: ${{ secrets.FTP_HOST }}
          username: ${{ secrets.FTP_USER }}
          password: ${{ secrets.FTP_PASSWORD }}
          protocol: ftps
          local-dir: ./
          server-dir: /armory-falter/app/
          exclude: |
            **/.git*
            **/node_modules/**
            **/tests/**
            .env
            storage/**
```

Ohne SSH fehlen `migrate`/`optimize`: Dafür eine **geschützte Wartungsroute** `POST /_deploy/{token}` (Token aus `.env`, nur Cloudflare-IP + IP-Allowlist von GitHub Actions bzw. manuell), die `migrate --force`, `optimize`, `queue:restart` ausführt und protokolliert. Nach dem Deploy per `curl` aufrufen. FTP-Deploys sind nicht atomar → vorher `php artisan down` über dieselbe Route. Empfehlung: Hoster mit SSH wählen.

## 8. Cloudflare-Konfiguration (`armory-falter.de`)

| Bereich | Einstellung |
|---|---|
| DNS | `www` und Apex als **Proxied** (orange), MX/SPF/DKIM/DMARC **DNS only**; Origin-IP nicht öffentlich in anderen Records |
| SSL/TLS | Modus **Full (strict)**; Origin: Let's Encrypt des Hosters oder Cloudflare Origin Certificate; „Always Use HTTPS“, HSTS (nach 4 Wochen stabilem Betrieb: `max-age=31536000; includeSubDomains; preload`), Minimum TLS 1.2, TLS 1.3 an |
| Redirect Rule | `armory-falter.de/*` → `https://www.armory-falter.de/$1` (301) |
| Cache Rules | (1) `/build/*`, `/fonts/*`, `/storage/*`, `/media/*` → Cache Everything, Edge TTL 1 Jahr, Browser TTL respektieren; (2) HTML anonymer Seiten (`/`, `/shop*`, `/produkt/*`, `/hersteller/*`, `/blog*`) → Cache Everything, Edge TTL 10 Min., **Bypass** wenn Cookie `af_logged_in` oder `af_cart` gesetzt; (3) Bypass für `/admin*`, `/konto*`, `/kasse*`, `/warenkorb*`, `/livewire/*`, `/api/*`, `/webhooks/*` |
| Purge | App ruft bei Produkt-/Inhaltsänderung die Cloudflare-API (Purge by URL) über Queue-Job auf |
| WAF Custom Rules | (1) `/admin*`: Block außer Länder DE/AT/CH **und** (optional) IP-Allowlist Geschäft, zusätzlich **Cloudflare Access** (E-Mail-OTP) vor dem Filament-Login; (2) `/login`, `/konto/anmelden`, `/register`: Managed Challenge bei Bot-Score < 30; (3) Block für `/wp-login.php`, `/xmlrpc.php`, `/wp-admin*` nach Umstellung (statt 301); (4) Block von Pfaden `/.env`, `/.git`, `/vendor` |
| Rate Limiting Rules | `/konto/anmelden` POST 10/Min./IP; `/api/v1/auth/*` 20/Min./IP; `/suche` 60/Min./IP |
| Bot Fight Mode | an (Achtung: Ausnahme für Webhook-Pfade PayPal/Klarna/PSP und Uptime-Monitor per Skip-Rule) |
| Turnstile | Site anlegen für `www.armory-falter.de` (und `staging.…`), Modus „Managed“, Keys in `.env` |
| Sonstiges | Brotli an, Early Hints an, HTTP/3 an, Rocket Loader **aus** (stört Livewire/Alpine), Auto Minify aus (Vite minifiziert), E-Mail-Obfuscation aus (CSP), „Server-Side Excludes“ aus |
| Origin-Schutz | Am Hoster nur Cloudflare-IPs zulassen, falls möglich (`.htaccess` mit `Require ip …` der Cloudflare-Ranges) |

## 9. Umstellung WordPress → Shop

| Zeitpunkt | Schritt |
|---|---|
| T−28 Tage | Crawl der WP-Seite, URL-Inventar, Redirect-Map ([06-seo.md](06-seo.md#10-migration-von-wordpress-wwwarmory-falterde)) |
| T−14 | DNS-Zone zu Cloudflare umziehen (Nameserver beim Registrar ändern), WP läuft unverändert hinter Cloudflare weiter; TTLs prüfen |
| T−7 | Vollständiges **Backup der WordPress-Installation** (Dateien + DB-Dump) an zwei Orte (Hoster + Off-site), Export der WP-Inhalte (XML), Screenshots wichtiger Seiten; E-Mail-Postfächer/MX unverändert lassen |
| T−3 | Content-Freeze in WordPress; Shop auf Produktivhosting unter temporärer Subdomain (`neu.armory-falter.de`, Cloudflare Access) vollständig vorbereitet, Redirect-Test aller Alt-URLs |
| T−1 | Letzte Datenübernahme (Blog/Kommentare), Rechtstexte final, Zahlungsanbieter auf Live-Modus, Turnstile-Keys Produktion |
| **T (Di, 9:00)** | Docroot/DNS von `www` auf den Shop umstellen (bei gleichem Hoster: Docroot ändern; bei neuem Hoster: Cloudflare-A-Record ändern – wirkt sofort, da proxied); WP-Installation in `~/wp-archiv` verschieben (nicht öffentlich) |
| T+0,5 h | Smoke-Tests: Startseite, Kategorie, Produkt, Testkauf, Formular, Mails; 50 Alt-URLs stichprobenartig; `robots.txt`, Sitemap |
| T+1 h | Search Console: Sitemap einreichen, Domain-Property prüfen; Bing |
| T+1 bis T+56 Tage | 404-Log täglich (Woche 1), dann wöchentlich; fehlende Redirects nachtragen |
| Rollback | Bis T+48 h: Docroot/DNS zurück auf WP (Backup bereit), danach nur Fix-forward |

## 10. Backups

| Was | Wie | Frequenz | Aufbewahrung | Ziel |
|---|---|---|---|---|
| Datenbank | `spatie/laravel-backup` (`mysqldump` über PHP – falls `exec` gesperrt: `--only-db` mit PHP-Dumper `spatie/db-dumper` Alternative prüfen bzw. Hoster-Dump) | täglich 01:00 + vor jedem Deploy | 30 tägliche, 12 monatliche | EU-Objektspeicher (S3-kompatibel, Rechenzentrum DE/EU), **verschlüsselt** (Backup-Passwort) |
| Private Dateien (Dokumente, Rechnungen, Trade-Fotos) | laravel-backup Dateien-Backup `storage/app/private` | täglich | 30 Tage | wie oben |
| Öffentliche Medien | wöchentlich | 4 Wochen | wie oben |
| Hoster-Backup | automatisch durch Hoster | täglich | laut Tarif (≥ 14 Tage) | Hoster |
| `.env` + `APP_KEY` + `BLIND_INDEX_KEY` | manuell | bei Änderung | dauerhaft | Passwortmanager + Offline-Kopie (ohne Schlüssel sind Dokumente/Backups unlesbar) |

Restore-Test **quartalsweise** auf Staging (Dauer, Vollständigkeit, Entschlüsselbarkeit) mit Protokoll. `backup:monitor` meldet fehlende/zu alte Backups per Mail.

## 11. Monitoring

| Bereich | Werkzeug |
|---|---|
| Uptime | Externer Monitor (z. B. Uptime Kuma auf eigenem Server, Better Stack, HetrixTools – EU), Prüfung `/up` (Laravel Health-Route, prüft DB + Cache) alle 1 Min., SSL-Ablauf |
| Scheduler/Queue | Heartbeat-URL (Healthchecks.io o. ä.) aus dem Scheduler jede 5 Min.; Admin-Systemstatus |
| Fehler | Laravel-Log `daily` (14 Tage), Mail-Alarm bei `critical`; optional **Sentry** (EU-Region, PII-Scrubbing, keine Request-Bodies) |
| Performance | Lighthouse CI, Search Console CWV, Cloudflare Analytics |
| Sicherheit | Cloudflare-Security-Events wöchentlich prüfen, `composer audit` + `npm audit` in CI (wöchentlich geplant), Dependabot |
| Business | Dashboard-Widgets, tägliche Sammelmail an Geschäftsführung |

## 12. Rollback

1. **Code:** `ln -sfn releases/<vorheriges> current` + `php current/artisan optimize` + `queue:restart` (Sekunden).
2. **Migrationen:** Sind vorwärtskompatibel (Expand/Contract), daher meist kein DB-Rollback nötig. Falls doch: `migrate:rollback --step=1` nur, wenn die Migration eine `down()` ohne Datenverlust hat; sonst Restore des Pre-Deploy-Dumps (Bestellungen seit Deploy vorher exportieren!).
3. **FTP-Variante:** vorheriges Release-ZIP erneut hochladen.
4. Nach jedem Rollback: Ursache im Ticket dokumentieren, Hotfix über normalen Pipeline-Weg.
