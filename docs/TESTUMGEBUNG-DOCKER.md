# Testumgebung mit Docker (HOME-SERVER)

So läuft der Shop auf dem HOME-SERVER, und Sie können ihn vom Laptop im selben Netzwerk aus aufrufen. Die Umgebung verhält sich wie später bei One.com: PHP 8.4, Apache, MariaDB, dieselben `.htaccess`-Regeln. Mails werden nicht verschickt, sondern landen in einem Testpostfach.

## 1. Einmalig starten

Auf dem HOME-SERVER in PowerShell:

```powershell
cd "C:\Claude Programmierung\armory-falter"
git pull
docker compose up -d --build
```

Der erste Start dauert einige Minuten: Das Image wird gebaut, die Datenbank angelegt und mit Beispieldaten gefüllt.

Das Passwort für das Backend steht danach im Protokoll:

```powershell
docker compose logs app | Select-String "Passwort"
```

Benutzer: `admin@armory-falter.de`. Beim ersten Login richten Sie die Zwei-Faktor-Anmeldung mit einer Authenticator-App ein (Pflicht für Mitarbeiter).

## 2. IP-Adresse des Servers herausfinden

Auf dem HOME-SERVER:

```powershell
ipconfig
```

Gesucht ist die **IPv4-Adresse** des aktiven Netzwerkadapters (Ethernet oder WLAN), zum Beispiel `192.168.178.50`.

## 3. Vom Laptop aufrufen

Der Laptop muss im selben Netzwerk sein (gleiches WLAN oder gleicher Router).

| Was | Adresse |
|---|---|
| Shop | `http://192.168.178.50:8080` |
| Backend | `http://192.168.178.50:8080/kms-login` |
| Test-Postfach (alle Mails) | `http://192.168.178.50:8025` |

Ersetzen Sie `192.168.178.50` durch die IP aus Schritt 2.

**Wenn die Seite vom Laptop nicht lädt, auf dem Server aber schon** (`http://localhost:8080`), blockiert meist die Windows-Firewall. Einmalig in PowerShell **als Administrator**:

```powershell
New-NetFirewallRule -DisplayName "Armory Falter Test" -Direction Inbound -Protocol TCP -LocalPort 8080,8025 -Action Allow -Profile Private
```

Das Netzwerk muss in Windows als „Privat“ eingestuft sein, nicht als „Öffentlich“.

## 4. Dem Kunden zeigen (außerhalb Ihres Netzwerks)

Die IP-Adresse funktioniert nur im eigenen Netzwerk. Für den Kunden gibt es zwei Wege:

1. **Empfohlen: Test-Subdomain bei One.com.** Sie legen `test.armory-falter.de` an und laden das ZIP dort hoch (siehe `docs/DEPLOYMENT-ONECOM.md`). Das ist zugleich die Generalprobe für den Livegang, und Ihr Heimnetz bleibt geschlossen. Mit einem Passwortschutz für die Subdomain im One.com-Panel sieht nur der Kunde die Seite.
2. **HOME-SERVER öffnen:** Portfreigabe für Port 8080 im Router, eine DynDNS-Adresse (z. B. MyFRITZ!) und eine Subdomain als CNAME darauf. Das öffnet Ihr Heimnetz nach außen und ist ohne HTTPS unverschlüsselt. Nur kurzzeitig und mit Passwortschutz nutzen.

## 5. Alltag

| Aufgabe | Befehl |
|---|---|
| Stoppen | `docker compose stop` |
| Wieder starten | `docker compose start` |
| Neue Version einspielen | `git pull` und `docker compose up -d --build` |
| Protokoll ansehen | `docker compose logs -f app` |
| Alles zurücksetzen (Datenbank leer, neue Beispieldaten) | `docker compose down -v` und `docker compose up -d --build` |

Andere Ports, falls 8080 schon belegt ist: vor dem Start `$env:ARMORY_PORT=8090` setzen.

## Technik in Kürze

- `app`: PHP 8.4 + Apache. Document-Root ist wie bei One.com der Projektordner, die Root-`.htaccess` leitet nach `public/`. Der HTTPS-Zwang aus `public/.htaccess` ist hier abgeschaltet (`Define AF_LOCAL`).
- `scheduler`: ersetzt den Cronjob (Warteschlange, Newsletter-Strecke, Aufräumen).
- `db`: MariaDB 10.11, Daten im Docker-Volume `db`.
- `mailpit`: fängt alle Mails ab.
- Die Einstellungen liegen nach dem ersten Start im Volume unter `storage/app/.env.docker` (Vorlage: `docker/env.docker`).
