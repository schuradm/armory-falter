# 13 – Sicherheits- und Datenschutzkonzept

Stand: 10.10.2026 · Gilt für Shop, Admin, API und Betrieb auf Shared Hosting hinter Cloudflare.

## 1. Schutzbedarf

| Datenkategorie | Beispiele | Schutzbedarf | Begründung |
|---|---|---|---|
| Erlaubnis- und Ausweisdaten | WBK-, Jagdschein-, Ausweiskopien, Dokumentnummern, Berechtigungen | **sehr hoch** | Identitätsmissbrauch, Rückschluss auf Waffenbesitz (Diebstahlsrisiko), Fälschungsgefahr |
| Waffenhandelsbuch, Seriennummern | Erwerber, Anschrift, Waffe | **sehr hoch** | „Wo liegen Waffen?“ – Einbruchsrisiko |
| Kunden- und Bestelldaten | Name, Adresse, Bestellungen | hoch | Kaufverhalten zu Waffen/Munition ist sensibel |
| Zahlungsdaten | Zahlstatus, Referenzen (keine Kartendaten im Shop) | hoch | |
| Katalog, Inhalte | Produkte, Blog | normal | Integrität (Preise!) |

## 2. DSGVO

### 2.1 Verarbeitungsverzeichnis (Art. 30) – Verarbeitungstätigkeiten

| Nr. | Tätigkeit | Zweck | Rechtsgrundlage | Empfänger/Auftragsverarbeiter |
|---|---|---|---|---|
| 1 | Kundenkonto | Vertragsdurchführung | Art. 6 (1) b | Hoster |
| 2 | Bestellabwicklung | Vertrag, Rechnung | Art. 6 (1) b, c | Hoster, Zahlungsanbieter, Versanddienstleister, Steuerberater |
| 3 | Erwerbsberechtigungsprüfung | Waffenrechtliche Pflichten | Art. 6 (1) c i. V. m. WaffG; Art. 6 (1) b | Hoster (verschlüsselt), ggf. Behörde |
| 4 | Altersverifikation | Jugendschutz/WaffG | Art. 6 (1) c | Hoster, Versanddienstleister (Alterssichtprüfung) |
| 5 | Waffenhandelsbuch, NWR | gesetzliche Pflicht | Art. 6 (1) c | Behörde |
| 6 | Ankauf/Verkaufsservice | Vertragsanbahnung | Art. 6 (1) b | Hoster, egun (bei Inserat, nur Waffendaten) |
| 7 | Werkstatt-/Beratungstermine | Vertragsanbahnung | Art. 6 (1) b | Hoster, Mailanbieter |
| 8 | Newsletter | Werbung | Art. 6 (1) a (Double-Opt-in); Bestandskunden § 7 Abs. 3 UWG | Mailanbieter (EU) |
| 9 | Blog-Kommentare | Kommunikation | Art. 6 (1) a/f | Hoster |
| 10 | Webanalyse | Optimierung | Art. 6 (1) a i. V. m. § 25 TDDDG (bei Cookies/Fingerprinting) bzw. cookielos nach Prüfung | Matomo (self-hosted) oder Plausible (EU) |
| 11 | Sicherheit (Logs, WAF, Captcha) | Schutz | Art. 6 (1) f, § 25 Abs. 2 TDDDG (unbedingt erforderlich) | Cloudflare (DPA, SCC/DPF) |
| 12 | Mitarbeiterkonten, Audit-Log | Nachvollziehbarkeit | Art. 6 (1) c, f; § 26 BDSG | Hoster |

### 2.2 Datenschutz-Folgenabschätzung (Art. 35)

Pflicht nach unserer Einschätzung für **Tätigkeiten 3–5** (systematische Verarbeitung von Ausweis-/Erlaubnisdokumenten, Rückschluss auf Waffenbesitz). Inhalt: Beschreibung, Notwendigkeit/Verhältnismäßigkeit (Alternativen: Prüfung nur im Laden), Risiken (Datenleck → Einbruch, Identitätsdiebstahl), Maßnahmen (dieses Dokument), Restrisiko, Freigabe durch Geschäftsführung, Stellungnahme DSB. Wiederholung bei wesentlichen Änderungen (z. B. Einführung eines OCR-Dienstes).

### 2.3 Auftragsverarbeitung (Art. 28)

AV-Verträge mit: Hoster, Cloudflare, Mail-Versand (SMTP/Newsletter), Backup-Speicher, Sentry (falls genutzt), Analytics (Plausible, falls genutzt), Entwicklungsdienstleister (Wartungszugriff). Zahlungsanbieter und Versanddienstleister sind i. d. R. eigene Verantwortliche. Drittlandtransfers (Cloudflare): EU-US Data Privacy Framework / SCC dokumentieren, Cloudflare „Data Localization Suite“ optional.

### 2.4 Löschkonzept

| Daten | Frist | Auslöser | Umsetzung |
|---|---|---|---|
| Ausweiskopie | 30 Tage nach Prüfung | Freigabe/Ablehnung | `documents:purge` (Datei + Feld), Metadaten bleiben |
| WBK/Jagdschein ohne Waffengeschäft | Ablauf + 90 Tage bzw. max. 3 Jahre | Ablauf/Widerruf | `documents:purge` |
| Nachweise zu Waffengeschäften | Planwert 10 Jahre (anwaltlich zu bestätigen) | Übergabe | `delete_after` gesetzt bei Übergabe |
| Abgelehnte Dokumente | 90 Tage | Ablehnung | `documents:purge` |
| Waffenhandelsbuch | nach AWaffV | – | kein Löschpfad |
| Bestellungen/Rechnungen | 8 bzw. 10 Jahre (AO/HGB, StB bestätigt) | Jahresende | Archiv-Flag, danach Anonymisierung |
| Kundenkonto ohne Bestellung | 24 Monate Inaktivität (nach Ankündigungsmail) | Inaktivität | Anonymisierung |
| Gastwarenkorb | 30 Tage | Ablauf | `model:prune` |
| Ankauf-/Verkaufsanfragen ohne Abschluss | 12 Monate | Status closed/declined | `model:prune` inkl. Fotos |
| Termine | 24 Monate | Termin | `model:prune` |
| Newsletter-Abmeldung | Opt-in-Nachweis 3 Jahre nach Abmeldung, E-Mail in Sperrliste (Hash) | Abmeldung | `model:prune` |
| Consent-Nachweis | 3 Jahre | Erteilung | `model:prune` |
| Server-/Laravel-Logs | 14 Tage | – | Log-Rotation |
| Audit-Log | 10 Jahre (Compliance) / 1 Jahr (Rest) | – | Archiv-Export, dann Löschung |
| Backups | 30 Tage / 12 Monate | – | Rotation; gelöschte Daten verschwinden spätestens mit Backup-Ablauf |

### 2.5 Betroffenenrechte über das Konto

`/konto/datenschutz`: Datenexport (JSON + PDF, asynchron per Mail-Link, 24 h gültig), Daten berichtigen, Newsletter-Einstellungen, Dokument zurückziehen (sofern nicht aufbewahrungspflichtig), **Konto löschen** (Prüfung auf Aufbewahrungspflichten → Anonymisierung bzw. Einschränkung nach Art. 18 mit Erklärung). Anfragen per E-Mail werden im Admin als Vorgang mit Frist (1 Monat) erfasst.

## 3. Cookie-Consent (eigenes Banner, Version 2)

- Kategorien: **Notwendig** (Session, CSRF, Warenkorb, Consent, Turnstile, Theme-Präferenz in `localStorage`), **Statistik** (Matomo mit Cookies / GA4 – nur falls gewünscht), **Marketing** (YouTube-Einbettungen, Meta-/Pinterest-Pixel – derzeit nicht geplant).
- Gleichwertige Buttons „Alle akzeptieren“ / „Nur notwendige“ / „Einstellungen“ auf erster Ebene; kein Pre-Ticking; Widerruf jederzeit über `/cookie-einstellungen` und Footer-Link.
- Speicherung: Cookie `af_consent` (pseudonyme ID, Kategorien, Version) + `consent_records` (Nachweis); neue Banner-Version → erneute Abfrage.
- **Google Consent Mode v2** nur, wenn GA4 genutzt wird: Default `ad_storage`, `ad_user_data`, `ad_personalization`, `analytics_storage` = `denied`; Update nach Einwilligung.
- **Empfehlung:** Matomo self-hosted (cookielos konfiguriert, IP-Anonymisierung, kein Fingerprinting – Einwilligungsfreiheit mit DSB prüfen) oder Plausible (EU) → kein Statistik-Opt-in nötig, bessere Datenqualität.
- Externe Inhalte (YouTube, Google Maps) nur per Zwei-Klick-Lösung.

## 4. Netz- und Anwendungsschutz

### 4.1 Cloudflare

WAF Managed Rules (OWASP Core Ruleset, Cloudflare Managed) aktiv; Custom Rules siehe [12-deployment.md](12-deployment.md#8-cloudflare-konfiguration-armory-falterde): `/admin*` hinter Cloudflare Access + Geo-Einschränkung, Login-Pfade mit Managed Challenge bei niedrigem Bot-Score, Blockierung typischer WordPress-Angriffspfade, Bot Fight Mode, DDoS-Schutz. Origin akzeptiert nach Möglichkeit nur Cloudflare-IPs.

### 4.2 Rate Limiting (Laravel `RateLimiter` + Cloudflare)

| Endpunkt | Limit | Schlüssel | Reaktion |
|---|---|---|---|
| Login (Shop, Admin, API) | 5/Min., 20/Std. | E-Mail + IP | 429, nach 20 Fehlversuchen/Tag Kontosperre + Mail |
| Registrierung | 3/Std. | IP | 429 + Turnstile |
| Passwort vergessen | 3/Std. | E-Mail + IP | immer gleiche Antwort (kein Account-Enumeration) |
| 2FA-Code | 5/Min. | Session | |
| Kontaktformular, Ankauf/Verkauf, Termin | 5/Std. | IP | |
| Kommentar | 3/10 Min. | IP | |
| Newsletter | 5/Std. | IP | |
| Suche/Autocomplete | 60/Min. | IP | |
| Dokument-Upload | 10/Std. | Benutzer | |
| Bestellung absenden | 10/Std. | Benutzer | |
| API | siehe [04-api.md](04-api.md#6-rate-limits-laravel-ratelimiter-zusätzlich-cloudflare) | | |

### 4.3 Spam- und Bot-Schutz

- **Cloudflare Turnstile** (Standard, Modus „Managed“) auf Registrierung, Login nach 2 Fehlversuchen, Kontakt-, Ankauf-, Verkaufs-, Termin-, Kommentar- und Newsletterformularen; serverseitige Token-Prüfung (`siteverify`) mit Hostname-Abgleich.
- **reCAPTCHA v3** nur optional und nur nach Einwilligung (Kategorie Marketing/Statistik), über dieselbe `Captcha`-Abstraktion.
- **Honeypot-Feld** (unsichtbar, `aria-hidden`, `tabindex=-1`) + **Zeitfalle** (Formular < 3 s abgesendet → verwerfen, signierter Zeitstempel).
- Kommentare: immer Moderation beim ersten Kommentar, Linkanzahl ≤ 2, Spam-Score (Heuristik: Links, Stoppwörter, Wiederholungen), IP-Hash-Sperrliste.

## 5. Authentifizierung & Autorisierung

- Fortify mit Argon2id (`HASH_DRIVER=argon2id`, falls Hoster-PHP mit Argon2 kompiliert, sonst bcrypt cost 12).
- **Passwortrichtlinie:** Kunden ≥ 12 Zeichen, Mitarbeiter ≥ 14 Zeichen, keine Komplexitätszwänge, Prüfung gegen bekannte Leaks (`Password::uncompromised()`), kein periodischer Zwangswechsel (NIST SP 800-63B).
- **2FA:** TOTP Pflicht für alle Mitarbeiter und für Kunden mit hinterlegten Erwerbsberechtigungen; Recovery-Codes; 2FA-Reset nur nach Identitätsprüfung (Rückruf) mit Audit.
- Session-Fixation-Schutz (Regeneration bei Login), `SameSite=Lax`, `Secure`, `HttpOnly`; Admin-Sessions 60 Min.
- Rollen/Rechte und Need-to-know siehe [07-rollen-rechte.md](07-rollen-rechte.md); Policies für jede Ressource, Tests gegen IDOR.
- Livewire: `#[Locked]` für IDs, Autorisierung in jeder Action, keine sensiblen Daten in öffentlichen Properties.

## 6. Verschlüsselung

| Ebene | Maßnahme |
|---|---|
| Transport | TLS 1.2+/1.3 Browser↔Cloudflare und Cloudflare↔Origin (Full strict), HSTS |
| Felder | `encrypted`-Casts (Dokumentnummern, Seriennummern, Waffenbuch-Gegenpartei, 2FA-Secrets), Blind Index (HMAC-SHA256, eigener Schlüssel) für Suche |
| Dateien (Dokumente) | Verschlüsselung vor dem Schreiben (libsodium `secretstream`, Schlüssel aus `APP_KEY`-abgeleitetem Unterschlüssel), Ablage auf **privater Disk** `storage/app/private/documents/{ulid}` **außerhalb des Webroots**; Dateiname zufällig, Originalname nur in DB |
| Auslieferung an Mitarbeiter | Nur über `DocumentDownloadController` mit Policy, **signierte temporäre URL (5 Min.)**, `Cache-Control: no-store`, `X-Content-Type-Options: nosniff`, `Content-Disposition: inline`, jeder Abruf im Audit-Log |
| Kunden | Sehen eigene Dokumente nur als Metadaten (kein erneuter Download nötig) |
| Backups | Verschlüsselte Archive (AES-256, Passwort getrennt verwahrt) |
| Schlüsselverwaltung | `APP_KEY`, `BLIND_INDEX_KEY`, Backup-Passwort im Passwortmanager der Geschäftsführung + versiegelte Offline-Kopie; Rotation dokumentiert (`APP_PREVIOUS_KEYS`) |

## 7. Security-Header (Middleware `SecurityHeaders`)

```
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload   (nach Stabilisierungsphase)
Content-Security-Policy: default-src 'self';
  script-src 'self' 'nonce-{n}' https://challenges.cloudflare.com;
  style-src 'self' 'unsafe-inline';
  img-src 'self' data: blob:;
  font-src 'self';
  connect-src 'self';
  frame-src https://challenges.cloudflare.com https://www.youtube-nocookie.com;
  media-src 'self';
  object-src 'none'; base-uri 'self'; form-action 'self' https://www.paypal.com https://*.klarna.com;
  frame-ancestors 'none'; upgrade-insecure-requests
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(self), microphone=(), geolocation=(), payment=(self)
Cross-Origin-Opener-Policy: same-origin
X-Frame-Options: DENY
```

Hinweise: Alpine.js benötigt ohne CSP-Build `unsafe-eval` → **Alpine-CSP-Build** (`@alpinejs/csp`) verwenden bzw. Livewire-Kompatibilität in Sprint 1 testen; falls nicht machbar, `unsafe-eval` nur dokumentiert und befristet. Filament-Admin erhält eine eigene, ggf. lockerere CSP. CSP zuerst im `Report-Only`-Modus mit Report-Endpunkt betreiben.

## 8. Datei-Upload-Härtung

| Maßnahme | Details |
|---|---|
| Whitelist | Dokumente: PDF, JPEG, PNG; Trade-Fotos: JPEG, PNG, WebP, HEIC (serverseitig zu JPEG konvertiert, falls Imagick mit HEIC-Support, sonst clientseitig konvertieren) |
| MIME-Sniffing | `finfo` (Magic Bytes) statt Endung/Client-MIME; Endung wird aus erkanntem Typ abgeleitet |
| Größe | Dokument ≤ 10 MB, Foto ≤ 15 MB vor Verkleinerung, max. 20 Fotos je Anfrage; Bildmaße ≤ 12 000 px (Decompression-Bomb-Schutz) |
| Re-Encoding | Bilder werden mit Intervention Image neu kodiert (EXIF/GPS entfernt, eingebettete Payloads verworfen); PDFs: Prüfung auf JavaScript/eingebettete Dateien/Verschlüsselung → `precheck_flagged`; Anzeige im Admin nur über pdf.js im isolierten Viewer |
| Speicherung | Zufällige Dateinamen, private Disk, kein Zugriff über Webserver |
| Keine Ausführung | `storage/` liegt außerhalb des Docroots; zusätzlich `.htaccess` in öffentlichen Upload-Verzeichnissen: `php_flag engine off` (falls erlaubt) bzw. `<FilesMatch "\.(php|phtml|phar)$"> Require all denied </FilesMatch>`, `Options -ExecCGI -Indexes` |
| Virenscan | ClamAV ist auf Shared Hosting meist nicht verfügbar → optional über EU-Scan-API nach DSFA; Phase 2 auf vServer mit ClamAV |
| Livewire-Temp-Uploads | `livewire.temporary_file_upload.disk = local` (privat), Regeln `max`, `mimes`, Bereinigung nach 24 h |

## 9. Audit-Logs

- Tabelle `audit_logs` mit **Hash-Kette**: `hash = SHA-256(prev_hash ‖ actor ‖ action ‖ subject ‖ old ‖ new ‖ created_at)`; Schreiben serialisiert über Cache-Lock.
- Erfasst: Logins (erfolgreich/fehlgeschlagen) von Mitarbeitern, Rollen-/Rechteänderungen, alle Compliance-Aktionen, Dokumentabrufe, Preisänderungen, Status- und Zahlungsänderungen, Exporte, Einstellungen, DSGVO-Vorgänge.
- Täglicher Prüfjob `audit:verify-chain`; wöchentlicher **Anker** (letzter Hash) per Mail an Geschäftsführung → externe Manipulation erkennbar.
- DB-Rechte: Falls der Hoster mehrere DB-Benutzer erlaubt, erhält der App-User auf `audit_logs` nur `INSERT, SELECT`.

## 10. Abhängigkeiten & Updates

- Dependabot (Composer, npm, GitHub Actions) wöchentlich; `composer audit` und `npm audit --omit=dev` als CI-Gate (high/critical blockieren).
- Laravel-/Filament-/Livewire-Sicherheitsupdates innerhalb von 72 h einspielen; monatlicher Patch-Tag.
- PHP-Version: Hoster-Upgrades verfolgen; CI-Matrix mit nächster PHP-Version.
- Keine aufgegebenen Pakete; jährlicher Paket-Review.

## 11. Backups

Siehe [12-deployment.md](12-deployment.md#10-backups): täglich verschlüsselt off-site (EU), Restore-Test quartalsweise, Schlüssel getrennt verwahrt. Backups enthalten Dokumente nur verschlüsselt (doppelte Verschlüsselung).

## 12. Incident Response

| Phase | Maßnahmen |
|---|---|
| Erkennen | Alarme (Uptime, Fehlerraten, WAF-Spitzen, Audit-Kettenbruch, ungewöhnliche Dokumentabrufe > 20/Std. je Mitarbeiter) |
| Eindämmen | Wartungsmodus, betroffene Konten sperren, Sessions invalidieren (`sessions`-Tabelle leeren), API-Tokens widerrufen, Cloudflare „I'm Under Attack“, Schlüssel rotieren |
| Bewerten | Umfang (welche Daten?), Logs sichern (Laravel, Cloudflare, Hoster), Zeitlinie |
| Melden | **Aufsichtsbehörde (LfDI Baden-Württemberg) binnen 72 h** (Art. 33), Betroffene bei hohem Risiko (Art. 34) – bei Abfluss von WBK-/Waffendaten i. d. R. gegeben; ggf. Waffenbehörde/Polizei informieren |
| Beheben | Patch, Neuaufsetzen aus sauberem Release, Passwörter/Keys rotieren |
| Lernen | Post-Mortem binnen 14 Tagen, Maßnahmenliste |

Kontaktliste (Geschäftsführung, Entwickler, Hoster-Support, DSB, Anwalt, Cloudflare) in einem offline verfügbaren Notfallblatt.

## 13. Shared-Hosting-spezifische Risiken

| Risiko | Mitigation |
|---|---|
| Andere Kunden auf demselben Server (Nachbarn) | Hoster mit Account-Isolation (CageFS/chroot, eigene PHP-FPM-Pools); Dateirechte 640/750; `.env` 600; sensible Dateien verschlüsselt |
| Kein Root, keine eigene Firewall | Cloudflare als vorgelagerte WAF, Origin-Zugriff nur über Cloudflare-IPs (`.htaccess`) |
| PHP-Konfiguration nicht voll kontrollierbar | `.user.ini`: `expose_php=Off`, `display_errors=Off`, `session.cookie_secure=1`; Laufzeitprüfung `/admin/system` |
| Kein Supervisor → Queue-Verzögerung | Scheduler-basierter Worker; zeitkritische Mails (Passwort-Reset) mit `sendNow` |
| `exec` gesperrt → kein `mysqldump` | Hoster-Backups + PHP-basierter Dump + Export-Test in Sprint 1 |
| Geteilte IP für ausgehende Mails | Externer SMTP (Brevo/Mailjet EU) mit SPF/DKIM/DMARC |
| Hoster-Panel-Zugang als Angriffsfläche | Starke Passwörter, 2FA im Hoster-Panel, SSH nur mit Key, FTP deaktivieren (wenn SSH vorhanden) oder nur FTPS |
| Webroot-Fehlkonfiguration (App im Docroot) | Docroot auf `public`; Smoke-Test `/.env` → 403/404 in jeder Pipeline |
| Begrenzte Logs/Forensik | Eigene Applikationslogs + Cloudflare-Logs (Security Events) aufbewahren |
| Hoster-Ausfall/Kündigung (AGB Waffen) | Schriftliche Bestätigung, Off-site-Backups, dokumentierter Umzug auf Managed vServer ([14-skalierung.md](14-skalierung.md)) |
