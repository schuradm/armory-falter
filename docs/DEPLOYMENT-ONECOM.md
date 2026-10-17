# Armory Falter auf One.com veröffentlichen

Anleitung für Karl-Martin Schur · Stand: 15.10.2026

Diese Anleitung führt Sie Schritt für Schritt vom fertigen Paket bis zum laufenden Shop auf One.com. Sie brauchen dafür keine Programmierkenntnisse, keine Kommandozeile und kein Git. Sie arbeiten nur im One.com-Kontrollpanel und mit dem Dateimanager (oder mit FileZilla).

Der Ablauf in Kürze:

1. Im One.com-Kontrollpanel PHP-Version, Datenbank, Subdomain `test.armory-falter.de` und E-Mail-Postfach einrichten.
2. Das Paket `armory-falter-onecom-JJJJMMTT.zip` in den Ordner der Subdomain hochladen und dort entpacken.
3. Die Datei `.env` mit Ihren Zugangsdaten anlegen.
4. Den Installer `https://test.armory-falter.de/install.php` einmal aufrufen und die Datei danach löschen.
5. Den Cron-Dienst einrichten, der jede Minute die Hintergrundaufgaben anstößt.
6. Alles in Ruhe testen und danach von WordPress auf den neuen Shop umstellen (Go-live).

---

## 0. Was im Paket steckt

Das ZIP wird mit `deploy/build-onecom.sh` erzeugt (siehe Abschnitt 11) und enthält den kompletten, fertig gebauten Shop:

| Inhalt | Bedeutung |
|---|---|
| `app/`, `config/`, `routes/`, `resources/`, `database/`, `bootstrap/` | Programmcode |
| `vendor/` | Programmbibliotheken (fertig installiert, kein Composer auf dem Server nötig) |
| `public/` | Alles, was Besucher direkt abrufen: `index.php`, Bilder, Schriften, `build/` (CSS/JS) |
| `public/install.php` | Einmal-Installer. **Wird nach der Installation gelöscht.** |
| `storage/` | Leeres Ordnergerüst für Uploads, Protokolle und Zwischenspeicher |
| `.htaccess` | Leitet jede Anfrage intern nach `public/` und sperrt alle anderen Ordner |
| `.env.onecom.example` | Vorlage für Ihre Zugangsdaten |

Im Paket ist **keine** `.env`. Ihre Passwörter stehen also nie im Paket.

Das Paket ist rund 32 MB groß und enthält etwa 15.500 Dateien. Der One.com-Dateimanager entpackt bis zu 40.000 Dateien, das passt also.

---

## 1. Vorbereitung im One.com-Kontrollpanel

Melden Sie sich unter <https://www.one.com/admin/> an.

### 1.1 Tarif prüfen

Der Shop braucht eine **eigene Datenbank**. Der WordPress-Auftritt belegt bereits eine.

- Der Tarif „Beginner“ enthält nur 1 Datenbank. Damit kommen Sie nicht aus.
- „Explorer“ enthält 5 Datenbanken, „Enthusiast“ 10.
- Empfehlung: **Enthusiast**. Er enthält zusätzlich SSH-Zugang und „Backup & Restore“.

### 1.2 Subdomain `test` anlegen

1. Öffnen Sie **Erweiterte Einstellungen → Subdomains** und klicken Sie auf **Subdomain hinzufügen**.
2. Geben Sie als Namen `test` ein und speichern Sie.
3. In der Spalte **Ordner** steht, in welchem Verzeichnis die Subdomain liegt. **Notieren Sie diesen Ordner.**
4. Warten Sie, bis `https://test.armory-falter.de` ohne Zertifikatswarnung aufrufbar ist. One.com stellt das SSL-Zertifikat automatisch aus, das kann einige Minuten bis Stunden dauern.

### 1.3 PHP-Version wählen

1. Öffnen Sie **Erweiterte Einstellungen → PHP (& Subdomains)**.
2. Klicken Sie bei `test.armory-falter.de` auf **Bearbeiten**.
3. Wählen Sie die neueste Version, die als **„Latest stable“** markiert ist (8.4 oder 8.5; mindestens 8.3).
4. Klicken Sie auf **Update** und dann auf **Got it**.

Die PHP-Version wird pro Domain und Subdomain getrennt eingestellt. Beim Go-live stellen Sie deshalb auch die Hauptdomain um (Abschnitt 8).

### 1.4 Datenbank anlegen

1. Öffnen Sie **Erweiterte Einstellungen → PHP- und Datenbankeinstellungen → MySQL**.
2. Legen Sie eine neue Datenbank an.
3. Notieren Sie diese vier Werte:
   - **Hostname**, meist in der Form `xxxxx.mysql.service.one.com`
   - **Datenbankname**
   - **Benutzername**
   - **Passwort**

Die Datenbank ist nur von One.com-Servern aus erreichbar. Von Ihrem PC aus können Sie sich nicht direkt verbinden, das ist normal.

### 1.5 E-Mail-Postfach für den Shop

1. Legen Sie unter **E-Mail** ein Postfach an, z. B. `shop@armory-falter.de`.
2. Über dieses Postfach verschickt der Shop Bestellbestätigungen. Der Server dafür ist `send.one.com`, Port 465 (SSL).

### 1.6 SFTP-Zugang (nur wenn Sie FileZilla nutzen oder Claude hochladen soll)

1. Öffnen Sie **SSH & SFTP** und schalten Sie den Zugang ein.
2. Klicken Sie auf **Senden**. Sie bekommen eine E-Mail, über die Sie ein Passwort setzen.
3. Hostname und Benutzername stehen ebenfalls auf dieser Seite. Notieren Sie beide.

---

## 2. Paket hochladen

### Variante A: One.com-Dateimanager (empfohlen, am schnellsten)

1. Öffnen Sie **Erweiterte Einstellungen → Subdomains** und dort beim Eintrag `test` den Link in der Spalte **Ordner**. Der Dateimanager öffnet sich direkt im Ordner der Subdomain.
2. Liegt dort schon etwas, z. B. eine `index.html` von One.com, löschen Sie es.
3. Laden Sie `armory-falter-onecom-JJJJMMTT.zip` mit **Dateien hochladen** hoch.
4. Klicken Sie die ZIP-Datei an und wählen Sie **Entpacken** (Extract). Entpacken Sie direkt in diesen Ordner, **nicht** in einen Unterordner.
5. Prüfen Sie das Ergebnis: Im Ordner liegen jetzt `app`, `public`, `vendor`, `artisan`, `.htaccess` usw.
6. Löschen Sie die ZIP-Datei.

### Variante B: FileZilla (SFTP)

1. Entpacken Sie das ZIP auf Ihrem PC.
2. Verbinden Sie FileZilla mit Server `sftp://<Hostname aus 1.6>`, Port 22, Benutzername und Passwort aus 1.6.
3. Aktivieren Sie **Server → Anzeige versteckter Dateien erzwingen**. Sonst übersehen Sie die wichtigen `.htaccess`-Dateien.
4. Wechseln Sie rechts in den Ordner der Subdomain.
5. Ziehen Sie den **Inhalt** des entpackten Ordners hinein, nicht den Ordner selbst.
6. Wegen der vielen kleinen Dateien dauert das 20 bis 60 Minuten. Brechen Sie nicht ab.

---

## 3. Die Datei `.env` anlegen

Die `.env` enthält alle Zugangsdaten. Sie liegt im selben Ordner wie `artisan`.

1. Kopieren Sie im Dateimanager `.env.onecom.example` und benennen Sie die Kopie in **`.env`** um.
2. Öffnen Sie die `.env` zum Bearbeiten und ersetzen Sie alle Werte in `<spitzen Klammern>`:

| Eintrag | Was Sie eintragen |
|---|---|
| `APP_URL` | vorerst `https://test.armory-falter.de` |
| `APP_KEY` | **leer lassen**, der Installer erzeugt den Schlüssel |
| `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | die vier Werte aus 1.4 |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | das Postfach aus 1.5 |
| `PAYPAL_…`, `KLARNA_…` | zum Testen die Sandbox- bzw. Playground-Zugänge (`…_SANDBOX=true`, `…_PLAYGROUND=true`) |
| `BLIND_INDEX_KEY` | 64 zufällige Zeichen. **Einmal setzen, nie wieder ändern.** |
| `CRON_TOKEN` | 40 zufällige Buchstaben und Ziffern (Abschnitt 6) |
| `INSTALL_TOKEN` | mindestens 32 zufällige Buchstaben und Ziffern |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | Ihr erster Zugang zum Backend |
| `SHOP_…` | Firmendaten für Impressum und Rechnungen |

So kommen Sie zu Zufallswerten: Lassen Sie in Ihrem Passwortmanager ein Passwort erzeugen, **nur aus Buchstaben und Ziffern**, in der gewünschten Länge.

Passwörter mit Sonderzeichen wie `#`, Leerzeichen oder `$` müssen in Anführungszeichen stehen, z. B. `DB_PASSWORD="ab#c$d"`. In der Vorlage sind die Anführungszeichen schon gesetzt.

**Bewahren Sie die fertige `.env` zusätzlich in Ihrem Passwortmanager auf.** Geht der `APP_KEY` verloren, sind verschlüsselte Kundendokumente und die 2FA-Einstellungen nicht mehr lesbar.

---

## 4. Installer ausführen

1. Rufen Sie `https://test.armory-falter.de/install.php` auf.
2. Füllen Sie das Formular aus:
   - Tragen Sie den **INSTALL_TOKEN** aus der `.env` ein.
   - Tippen Sie zur Bestätigung **INSTALLIEREN** ein.
   - Setzen Sie Haken bei **Migrationen**, **Grunddaten (Seeder)** (nur bei der ersten Installation!) und **Produktions-Caches**.
   - Die Grunddaten enthalten auch die Demo-Produktbilder (Quelle `resources/demo/products`, sie werden nach `storage/app/public/products/demo` kopiert und als WebP-Varianten erzeugt). Ist die Datenbank schon eingerichtet und fehlen nur die Bilder, setzen Sie stattdessen den Haken bei **Nur Demo-Produktbilder einspielen** (entspricht `php artisan demo:images`, beliebig oft wiederholbar). Voraussetzung: PHP-Erweiterung GD mit WebP-Unterstützung.
   - Lassen Sie den Haken bei **Symlink** weg. Auf One.com ist er nicht nötig, weil die `.htaccess` hochgeladene Bilder direkt ausliefert.
3. Klicken Sie auf **Jetzt ausführen**. Das dauert etwa 10 bis 60 Sekunden.
4. Am Ende sollte überall ein grüner Haken stehen.
   - Ein Hinweis „Optionale Erweiterungen fehlen“ ist unkritisch.
   - Steht irgendwo ein rotes ✘: Beheben Sie den Fehler, z. B. falsche Datenbank-Daten in der `.env`. Tragen Sie danach einen **neuen** `INSTALL_TOKEN` ein und rufen Sie den Installer erneut auf. Jeder Token funktioniert genau einmal.
5. **Danach sofort aufräumen:**
   - Löschen Sie im Dateimanager `public/install.php`.
   - Leeren Sie in der `.env` den Wert `INSTALL_TOKEN=`.

### Kurztest

| Aufruf | Erwartet |
|---|---|
| `https://test.armory-falter.de/` | Startseite |
| `https://test.armory-falter.de/kms-login` | Anmeldung zum Backend. Melden Sie sich mit ADMIN_EMAIL/ADMIN_PASSWORD an, richten Sie die 2FA ein und ändern Sie das Passwort. Danach leeren Sie `ADMIN_PASSWORD` in der `.env`. |
| `https://test.armory-falter.de/up` | Seite „Application up“ |
| `https://test.armory-falter.de/.env` | **403 Forbidden** (muss gesperrt sein!) |
| `https://test.armory-falter.de/vendor/autoload.php` | **403 Forbidden** |

---

## 5. Speicherorte und Rechte

Normalerweise müssen Sie hier nichts tun. Falls der Installer „nicht beschreibbar“ meldet, setzen Sie die Rechte in FileZilla per Rechtsklick → **Dateiberechtigungen**:

- Ordner `storage` und `bootstrap/cache` mit Unterordnern: `755`. Falls das nicht reicht, `775`.
- Datei `.env`: `600` oder `640`.

Ihre Uploads, also Produktbilder, landen in `storage/app/public`. Unter `https://…/storage/…` sind sie direkt abrufbar, ein Symlink ist nicht nötig. Kundendokumente liegen verschlüsselt in `storage/app/private` und sind von außen gesperrt.

---

## 6. Cron: Hintergrundaufgaben jede Minute

Der Shop muss jede Minute einmal angestoßen werden. Dabei verschickt er E-Mails, erzeugt Bildvarianten, versendet Newsletter-Folgemails und räumt auf.

Laut One.com-Hilfecenter gibt es im Webhosting **keine eigenen Cronjobs**. Prüfen Sie trotzdem kurz unter **Erweiterte Einstellungen**, ob dort „Geplante Aufgaben / Cron“ auftaucht. Falls ja, tragen Sie dort jede Minute diesen Befehl ein: `php <Ordner>/artisan schedule:run`. Andernfalls richten Sie den kostenlosen Dienst **cron-job.org** ein:

1. Legen Sie auf <https://cron-job.org> ein Konto an. Der Dienst ist kostenlos und hat seinen Sitz in Deutschland.
2. Klicken Sie auf **Cronjob erstellen**.
   - **URL:** `https://test.armory-falter.de/cron/<CRON_TOKEN aus der .env>`. Nach dem Go-live ändern Sie das in `https://www.armory-falter.de/cron/…`.
   - **Ausführung:** jede Minute
   - **Timeout:** so hoch wie möglich (30 s). Der Shop arbeitet auch nach dem Timeout im Hintergrund weiter.
   - **Benachrichtigung:** bei Fehlschlag per E-Mail
3. Speichern Sie und warten Sie eine Minute. Im Verlauf sollte jetzt **200** mit dem Text „OK“ stehen.

Den Cron-Link behandeln Sie wie ein Passwort und geben ihn nicht weiter. Ein falscher Token liefert absichtlich „404“.

---

## 7. Testen auf `test.armory-falter.de`

- Legen Sie Produkte, Inhalte und Einstellungen im Backend `/kms-login` an. Die Datenbank übernehmen Sie später für den Live-Betrieb.
- Machen Sie Testbestellungen mit PayPal-Sandbox und Klarna-Playground.
- Prüfen Sie, ob die Bestellbestätigungen per E-Mail ankommen. Ohne laufenden Cron (Abschnitt 6) werden keine Mails verschickt.
- Prüfen Sie die Seiten mit PageSpeed Insights (<https://pagespeed.web.dev>).

---

## 8. Go-live: von WordPress auf den neuen Shop umstellen

**Planen Sie dafür eine ruhige Zeit ein, etwa 1 bis 2 Stunden, z. B. am Sonntagabend.**

### 8.1 Vorher

1. **Sichern Sie WordPress.** Exportieren Sie in phpMyAdmin die WordPress-Datenbank (**Exportieren → SQL**). Laden Sie per FileZilla den kompletten Inhalt des Hauptordners (`httpd.www` bzw. `/www`) herunter.
2. **Erfassen Sie die alten Adressen.** Rufen Sie `https://www.armory-falter.de/wp-sitemap.xml` (bzw. `/sitemap_index.xml` bei Yoast) auf und notieren Sie alle wichtigen alten Seiten.
3. **Tragen Sie Weiterleitungen ein.** Legen Sie im neuen Backend unter **System → Weiterleitungen** für jede alte Adresse eine **301-Weiterleitung** auf die passende neue Seite an, z. B. `/shop/glock-17/` → `/produkt/glock-17`. So behalten Sie Ihre Google-Platzierungen. Ein abschließender Schrägstrich wird übrigens automatisch entfernt: `/produkt/glock-17/` landet ohne eigenen Eintrag auf `/produkt/glock-17`.
4. Entfernen Sie Testbestellungen und Testkunden im Backend.

### 8.2 Umschalten

1. Stellen Sie unter **PHP (& Subdomains)** die PHP-Version der **Hauptdomain** auf dieselbe Version wie bei `test` ein.
2. Verschieben Sie in FileZilla den gesamten WordPress-Inhalt von `httpd.www` in den privaten Ordner `httpd.private/wordpress-alt-JJJJMMTT`. Dieser Ordner ist von außen nicht erreichbar. Alternativ löschen Sie den Inhalt, nachdem die Sicherung aus 8.1 vollständig ist.
3. Laden Sie das Paket wie in Abschnitt 2 hoch und entpacken Sie es in **`httpd.www`**.
4. Kopieren Sie die `.env` von der Test-Subdomain nach `httpd.www` und ändern Sie darin:
   - `APP_URL=https://www.armory-falter.de`
   - Zahlungen auf live: `PAYPAL_SANDBOX=false`, `KLARNA_PLAYGROUND=false`, Live-Zugangsdaten eintragen, `PAYPAL_ENABLED=true` usw.
   - einen neuen `INSTALL_TOKEN`

   Die Datenbank bleibt dieselbe wie im Test. Alles, was Sie dort gepflegt haben, ist damit sofort live.
5. Kopieren Sie die bereits hochgeladenen Bilder von `storage/app/public` der Test-Subdomain in denselben Ordner unter `httpd.www`.
6. Rufen Sie `https://www.armory-falter.de/install.php` auf. Setzen Sie die Haken **nur** bei Migrationen und Caches, **keine** Grunddaten. Danach **löschen Sie `public/install.php`** und leeren `INSTALL_TOKEN`.
7. Stellen Sie bei cron-job.org die URL auf `https://www.armory-falter.de/cron/<CRON_TOKEN>` um.
8. Tragen Sie bei PayPal und Klarna die Webhook-Adressen auf `www.armory-falter.de` um (siehe Kommentare in der `.env`).

### 8.3 Danach prüfen

| Prüfung | Erwartet |
|---|---|
| `http://armory-falter.de` | Weiterleitung auf `https://www.armory-falter.de` |
| Alte WordPress-Adresse aufrufen | Weiterleitung (301) auf die neue Seite |
| `https://www.armory-falter.de/.env` | 403 |
| Testbestellung mit kleinem Betrag | kommt an, Mail wird verschickt |
| Google Search Console | `https://www.armory-falter.de/sitemap.xml` neu einreichen |

Die Test-Subdomain löschen Sie danach im Kontrollpanel. Alternativ behalten Sie sie für künftige Update-Tests, verwenden dann aber eine **eigene** Test-Datenbank.

**Wenn etwas schiefgeht:** Löschen Sie den Inhalt von `httpd.www` und schieben Sie WordPress aus `httpd.private/wordpress-alt-…` zurück. WordPress läuft dann sofort wieder.

---

## 9. Datensicherung

- **One.com Backup & Restore** (ab Tarif Enthusiast) sichert Dateien und Datenbank automatisch.
- **Zusätzlich einmal pro Woche selbst sichern:**
  1. phpMyAdmin → Shop-Datenbank → **Exportieren** (SQL)
  2. Per SFTP herunterladen: `.env` und den Ordner `storage/app` (Bilder und verschlüsselte Kundendokumente)
- Die `.env` mit dem `APP_KEY` gehört in den Passwortmanager. Ohne diesen Schlüssel ist die Sicherung der Kundendokumente wertlos.

---

## 10. Updates einspielen

1. Ein neues Paket wird gebaut (Abschnitt 11).
2. **Sichern Sie vorher die Datenbank** (phpMyAdmin → Exportieren).
3. Laden Sie das neue ZIP in denselben Ordner hoch und entpacken Sie es. Vorhandene Dateien werden überschrieben. Ihre `.env` und Ihre Uploads in `storage/` bleiben erhalten, denn sie sind nicht im Paket.
4. Tragen Sie in der `.env` einen neuen `INSTALL_TOKEN` ein.
5. Rufen Sie `/install.php` auf und setzen Sie die Haken bei **Wartungsmodus**, **Migrationen** und **Caches**, **nicht** bei Grunddaten.
6. Löschen Sie `public/install.php` und leeren Sie `INSTALL_TOKEN`.

Zwischen Schritt 3 und Schritt 5 kann der Shop kurz Fehler zeigen. Spielen Sie Updates deshalb in ruhigen Zeiten ein.

---

## 11. Für Entwickler und Claude: Paket bauen und per SFTP hochladen

### Paket bauen (auf einem Rechner mit PHP 8.3+, Composer, Node 20+, zip)

```bash
deploy/build-onecom.sh          # aktueller Arbeitsstand
deploy/build-onecom.sh --git    # genau der letzte Commit
# Ergebnis: dist/armory-falter-onecom-JJJJMMTT.zip (+ .sha256)
```

Das Skript erledigt Folgendes:

- Es kopiert den Code ohne `.git`, `node_modules`, `tests`, `.env` und Speicherinhalte.
- Es führt `composer install --no-dev --optimize-autoloader --classmap-authoritative` aus, außerdem `npm ci && npm run build`.
- Es entfernt Entwicklungsdateien.
- Es legt `public/install.php` und zusätzliche `.htaccess`-Sperren an.

`config:cache` läuft absichtlich **nicht** beim Bauen, sondern über den Installer auf dem Server.

### Automatischer Upload

1. Die Zugangsdaten stehen nur lokal in `deploy/.deploy.env`. Die Vorlage ist `deploy/.deploy.env.example`. Die Datei ist in `.gitignore` eingetragen und wird **nie committet** und nie ins ZIP gepackt.

   ```dotenv
   SFTP_HOST=<Hostname aus SSH & SFTP>
   SFTP_PORT=22
   SFTP_USER=<Benutzername>
   SFTP_PASS="<SFTP-Passwort>"
   REMOTE_DIR=<Ordner der Subdomain bzw. httpd.www>
   ```

2. Hochladen:
   - **Linux/macOS** (benötigt `lftp`):

     ```bash
     deploy/build-onecom.sh && deploy/upload-sftp.sh
     # Installer im Browser ausführen, dann:
     deploy/upload-sftp.sh --remove-installer
     ```

   - **Windows** (benötigt WinSCP): `deploy\upload-winscp.bat`, danach `deploy\upload-winscp.bat --remove-installer`.

Die Upload-Skripte löschen auf dem Server nichts und übertragen nie eine `.env`. Uploads, Protokolle und Sitzungen bleiben unberührt.

**Sicherheit:**

- Geben Sie Claude oder einem Dienstleister ein **eigenes** SFTP-Passwort. Ändern Sie es nach getaner Arbeit im Kontrollpanel unter „SSH & SFTP“.
- Die `.env` auf dem Server pflegen Sie selbst. Sie wird nie automatisch hochgeladen.

Mit Tarif Enthusiast/Guru und SSH können Migrationen statt über den Installer auch per `php artisan migrate --force && php artisan optimize` laufen.

---

## 12. Fehlerbehebung

| Problem | Ursache und Lösung |
|---|---|
| **500 Internal Server Error** direkt nach dem Hochladen | PHP-Version zu alt (Abschnitt 1.3) oder `.htaccess` fehlt bzw. wurde nicht mit hochgeladen (versteckte Dateien!). Details stehen in `storage/logs/laravel-JJJJ-MM-TT.log`. |
| **Weiterleitungsschleife** („zu oft umgeleitet“) | In `public/.htaccess` die drei Zeilen unter „HTTPS erzwingen“ mit `#` auskommentieren und One.com-Support zum SSL fragen. |
| Seite lädt ohne Design | `public/build/` fehlt. Paket neu entpacken. |
| „Verbindung zur Datenbank fehlgeschlagen“ | `DB_HOST` muss der Hostname aus dem Kontrollpanel sein, nicht `localhost`. Passwort in Anführungszeichen setzen. |
| „419 Page Expired“ beim Anmelden | Seite über `https://` aufrufen (`SESSION_SECURE_COOKIE=true` verlangt HTTPS). |
| Produktbilder fehlen | Liegen sie in `storage/app/public`? Rechte 755? `https://…/storage/<datei>.jpg` direkt testen. |
| Keine E-Mails | Läuft der Cron (Abschnitt 6)? Stimmen `MAIL_USERNAME`/`MAIL_PASSWORD`? Port 465 mit `MAIL_SCHEME=smtps`. |
| Änderungen an der `.env` wirken nicht | Die Einstellungen sind zwischengespeichert. Neuen `INSTALL_TOKEN` setzen und den Installer erneut ausführen. Falls die Datei schon gelöscht ist, die Datei `bootstrap/cache/config.php` löschen. |
| Installer: „Token wurde bereits verwendet“ | Gewollt. Einen neuen `INSTALL_TOKEN` in die `.env` schreiben. |

---

## Anhang: Technischer Hintergrund

- **Dokumentenwurzel:** One.com lässt die Dokumentenwurzel nicht auf `public/` zeigen. Deshalb liegt das ganze Projekt im Domain-Ordner. Die Root-`.htaccess` schreibt jede Anfrage intern nach `public/` um, Besucher sehen nie „/public“. Wer `/public/…` direkt aufruft, wird per 301 auf die saubere Adresse geleitet. Zusätzlich hat jeder Programmordner (`app`, `vendor`, `config`, `storage/app/private`, `storage/logs` …) eine eigene `.htaccess` mit `Require all denied`.
- **Uploads ohne Symlink:** `/storage/<pfad>` mit Medien-Endung (jpg, png, webp, avif, gif, ico, pdf, mp4, webm) wird intern auf `storage/app/public/<pfad>` umgeschrieben. Skripte sind dort per `.htaccess` gesperrt. `php artisan storage:link` ist damit überflüssig. Andere `/storage/…`-Adressen gehen an Laravel.
- **Header:** Sicherheits-Header für Seiten setzt `App\Http\Middleware\SecurityHeaders`. Die `.htaccess` setzt nur für statische Dateien `nosniff` und die Cache-Header: 1 Jahr `immutable` für Build-Dateien, Schriften und Bilder, 1 Stunde für txt/xml/json, 30 Tage für Uploads. So kommt kein Header doppelt an.
- **Kanonischer Host:** Die Weiterleitung von `armory-falter.de` auf `www.armory-falter.de` steht in `public/.htaccess` (markierte Zeilen). Subdomains sind davon ausgenommen.
- **Installer:** Er läuft nur mit `INSTALL_TOKEN` von mindestens 32 Zeichen, nur per POST, mit `hash_equals` und Bestätigungswort. Jeder Token funktioniert einmal (Sperrdatei `storage/app/install.lock` mit dem SHA-256 des Tokens). Er gibt keine Geheimnisse aus und erzeugt `APP_KEY` nur, wenn das Feld leer ist.
- **Sicherere Alternative für Fortgeschrittene:** Das Projekt liegt in `httpd.private/armory-falter`. Nach `httpd.www` kommt nur der Inhalt von `public/`, und die Pfade in `index.php` werden angepasst. Das ist mehr Handarbeit bei jedem Update und deshalb nicht der Standard.
