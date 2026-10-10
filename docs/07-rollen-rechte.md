# 07 – Benutzerrollen & Rechte

Stand: 10.10.2026 · Umsetzung: `spatie/laravel-permission` (Guard `web`), Policies je Modell, Filament-Ressourcen prüfen Permissions über `canViewAny/canCreate/…`.

## 1. Grundsätze

1. **Need-to-know:** Jede Rolle sieht nur, was sie für ihre Aufgabe braucht. Kundendokumente (Ausweis, WBK) sieht nur die Compliance-Rolle (und Geschäftsführung).
2. **Rechte an Rollen, nicht an Personen.** Direkte Einzelrechte nur befristet und protokolliert.
3. **Mitarbeiterkonten = persönliche Konten** (keine Sammelkonten), 2FA **Pflicht** (TOTP), Zugriff auf `/admin` zusätzlich über Cloudflare-WAF eingeschränkt (siehe [13-sicherheit.md](13-sicherheit.md)).
4. **Jede Compliance-Aktion** (Dokument freigeben/ablehnen, Berechtigung anlegen, Bestellung freigeben, Waffenbuch-Eintrag, NWR-Meldung markieren) wird im hash-verketteten Audit-Log protokolliert – mit Person, Zeit, IP, alten/neuen Werten.
5. **Keine Selbstfreigabe:** Mitarbeiter dürfen eigene Kundenkonten/Bestellungen nicht compliance-freigeben (Policy-Regel `reviewer_id ≠ user_id`).
6. Rollen sind kombinierbar (z. B. Inhaber = Geschäftsführung + Compliance).

## 2. Mitarbeiterrollen

| Rolle (Code) | Beschreibung | Typische Person |
|---|---|---|
| `super_admin` | Technische Vollverwaltung inkl. Rollen, Einstellungen, Logs. Kein Tagesgeschäft. | Entwicklungsdienstleister (befristet), max. 2 Konten |
| `management` | Geschäftsführung: alle fachlichen Bereiche, Berichte, Freigaben | Mark Falter |
| `compliance` | Waffen-Sachbearbeiter: Dokumentenprüfung, Berechtigungen, Bestellfreigabe, Seriennummern, Waffenhandelsbuch, NWR | Inhaber bzw. Mitarbeiter mit Fachkunde (Waffenhandelserlaubnis/verantwortliche Person) |
| `sales` | Verkauf/Beratung: Bestellungen bearbeiten, Kunden betreuen, Reservierungen, Ankauf-/Verkaufsanfragen | Verkäufer |
| `warehouse` | Lager/Versand: Kommissionierung, Sendungen, Etiketten, Bestand | Lager |
| `workshop` | Werkstatt: Termine, Aufträge, Leistungen | Büchsenmacher |
| `editor` | Redaktion: Seiten, Blog, FAQ, Hersteller-Texte, SEO-Meta, Kommentarmoderation | Redaktion/Agentur |
| `marketing` | Newsletter, Segmente, Kampagnen, Aktionen/Gutscheine, Redirects | Marketing |
| `accounting` | Buchhaltung: Rechnungen, Gutschriften, Zahlungen, Exporte | Steuerbüro/Buchhaltung |
| `support` | Kundenservice: Kundendaten lesen, Bestellstatus, Termine, Passwort-Reset-Mail auslösen | Aushilfe/Telefon |

## 3. Berechtigungsmatrix

Legende: **V** = verwalten (anlegen/ändern/löschen), **B** = bearbeiten (ändern, kein Löschen), **L** = lesen, **–** = kein Zugriff, **F** = freigeben (Compliance-Entscheidung).

| Bereich / Permission | super_admin | management | compliance | sales | warehouse | workshop | editor | marketing | accounting | support |
|---|---|---|---|---|---|---|---|---|---|---|
| Dashboard & Berichte | L | L | L (Compliance) | L (Verkauf) | L (Lager) | L (Termine) | – | L (Marketing) | L (Umsatz) | L |
| Produkte, Kategorien, Hersteller | V | V | B (Rechtsklasse) | B | B (Bestand) | – | B (Texte) | – | – | L |
| Preise & Aktionen | V | V | – | B | – | – | – | B (Aktionen) | L | – |
| Rechtsklassen (`legal_classes`) | V | B | V | L | L | – | – | – | – | – |
| Kunden (Stammdaten) | V | V | B | B | L (Adresse) | L | – | L (Opt-in-Status) | L | L |
| Kundendokumente ansehen | – ¹ | L | L | – | – | – | – | – | – | – |
| Dokumente freigeben/ablehnen | – | F | F | – | – | – | – | – | – | – |
| Berechtigungen anlegen/widerrufen | – | F | F | – | – | – | – | – | – | – |
| Bestellungen | V | V | B | B | B (Versandstatus) | – | – | – | L | L |
| Compliance-Freigabe Bestellung | – | F | F | – | – | – | – | – | – | – |
| Stornierung/Erstattung | V | V | B | B (bis 500 €) | – | – | – | – | B | – |
| Seriennummern (`weapon_items`) | – ¹ | L | V | L | B (Zuordnung scannen) | L | – | – | – | – |
| Waffenhandelsbuch | – ¹ | L | V (nur Buchungen, keine Löschung) | – | – | – | – | – | – | – |
| NWR-Meldungen | – | L | V | – | – | – | – | – | – | – |
| Versand & Etiketten | V | V | L | B | V | – | – | – | – | L |
| Rechnungen & Zahlungen | V | V | L | L | – | – | – | – | V | L |
| Ankauf/Verkaufsservice | V | V | B | V | – | L | – | – | L | L |
| Werkstatt & Termine | V | V | – | B | – | V | – | – | – | B |
| Seiten, Blog, FAQ, SEO | V | V | – | – | – | – | V | B (SEO) | – | – |
| Kommentare moderieren | V | V | – | – | – | – | V | – | – | – |
| Newsletter | V | V | – | – | – | – | B (Inhalte) | V | – | – |
| Redirects | V | V | – | – | – | – | B | V | – | – |
| Audit-Log | L | L | L (Compliance-Ereignisse) | – | – | – | – | – | – | – |
| Einstellungen, Zahl-/Versandarten | V | B | L | – | – | – | – | – | L | – |
| Benutzer & Rollen | V | V | – | – | – | – | – | – | – | – |
| DSGVO-Auskunft/Löschung | V | V | B | – | – | – | – | – | – | L (anstoßen) |

¹ `super_admin` hat technisch Vollzugriff, aber **keinen fachlichen** Zugriff auf Kundendokumente, Seriennummern und Waffenbuch (explizite Policy-Sperre). Notfallzugriff nur über „Break-Glass“-Verfahren: zeitlich befristete Rolle, die die Geschäftsführung erteilt, mit Audit-Eintrag und E-Mail-Benachrichtigung.

Permission-Namen folgen dem Schema `{bereich}.{aktion}`, z. B. `documents.view`, `documents.approve`, `entitlements.create`, `orders.release`, `weapon_book.create`, `products.update_price`, `refunds.create.limit_500`.

## 4. Vier-Augen-Prinzip (optional, empfohlen)

| Vorgang | Standard | Option „4-Augen“ (Einstellung) |
|---|---|---|
| Dokumentfreigabe Ausweis/Altersnachweis | 1 Person | – |
| Dokumentfreigabe WBK/Jagdschein + Anlage Berechtigung | 1 Person (Compliance) | 2. Person bestätigt vor Wirksamkeit |
| Compliance-Freigabe Bestellung mit Waffe/wesentlichem Teil | 1 Person | 2. Person bestätigt; Pflicht ab Bestellwert X oder bei Erstkunden |
| Stornierung Waffenbuch-Eintrag (Gegenbuchung) | 1 Person + Begründung | 2. Person |
| Erstattung > 1 000 € | Buchhaltung | Geschäftsführung bestätigt |

Empfehlung: Bei kleinem Team (1–2 Compliance-Berechtigte) zunächst **optional** aktivieren und für Erstkäufer von Kurzwaffen nutzen. Technisch: `approvals`-Datensatz mit `requested_by`, `approved_by` (≠ requested_by), Status; Filament-Aktion „Zur Zweitprüfung“.

## 5. Kunden-Stufen

Kundenstufen sind **keine Rollen** im Sinne der Mitarbeiterrechte, sondern werden aus Konto, Verifizierungen und Berechtigungen abgeleitet (`CustomerTier`-Service, gecacht je Request).

| Stufe | Voraussetzung | Darf |
|---|---|---|
| **Gast** | – | Katalog ansehen, Merkliste (Session), Warenkorb; kaufen nur `free_general` (Gastkonto implizit); Ankauf-/Verkaufsanfrage, Terminanfrage |
| **Registriert** | E-Mail bestätigt | Konto, Bestellhistorie, mehrere Merklisten, Dokumente hochladen, `free_general` kaufen |
| **Altersverifiziert 18+** | Geburtsdatum + geprüfter Ausweis (`age_verified_at`) oder Prüfung im Laden; alternativ Altersprüfung bei Zustellung (DHL Alterssichtprüfung 18+) | `free_18`-Artikel bestellen; `pickup_only` reservieren |
| **Altersverifiziert 21+** | wie oben, Alter ≥ 21 | Relevant für Sonderfälle (siehe Dokument 10); kein eigener Kaufumfang ohne Erlaubnis |
| **Erwerbsberechtigt** | mind. eine aktive `customer_entitlement` | Artikel der Klassen `wbk_*`, `ammo`, `essential_part` bestellen, **soweit die konkrete Berechtigung passt** (Typ, Waffenart, Kaliber, Menge, Gültigkeit) → Bestellung geht in `pending_compliance` |
| **Behörde** | `customer_type = authority`, geprüfte Behördenbescheinigung, Berechtigung `authority` | Sichtbarkeit `usage_defense_authority`-Artikel, Kauf auf Rechnung, E-Rechnung (XRechnung), Angebotsanfrage |
| **Händler** | `customer_type = dealer`, geprüfte Waffenhandelserlaubnis (§ 21 WaffG), Berechtigung `dealer` | Händlerpreise (Preisliste), Kauf auf Rechnung nach Freigabe, Waffenversand an Händler |

Zusatzmerkmale: „Stammkunde“ (Marketing-Segment), „Sperrvermerk“ (Compliance – z. B. bei Zweifel an Zuverlässigkeit, nur für Compliance sichtbar, mit Begründung).

## 6. Authentifizierung & Kontosicherheit

| Thema | Kunden | Mitarbeiter |
|---|---|---|
| Login | E-Mail + Passwort (Fortify) | E-Mail + Passwort + **TOTP-2FA Pflicht** (Erzwingung per Middleware, Einrichtung beim ersten Login) |
| 2FA | Optional, empfohlen; **Pflicht**, sobald Erwerbsberechtigungen hinterlegt sind | Pflicht, Recovery-Codes einmalig angezeigt |
| Passwort | min. 12 Zeichen, Prüfung gegen Have-I-Been-Pwned (k-Anonymity, `Password::uncompromised()`) | min. 14 Zeichen, gleiche Prüfung |
| Sperre | 5 Fehlversuche/15 Min. → Throttle; 20/Tag → Sperre + Mail | wie Kunden + Alarm an `super_admin` |
| Sitzung | 120 Min. Inaktivität, „angemeldet bleiben“ 30 Tage (nicht für Mitarbeiter) | 60 Min. Inaktivität, Admin-Session getrennt von Shop-Session |
| Sensible Aktionen | Passwortbestätigung (`password.confirm`) für Dokumente, E-Mail-Änderung, Konto löschen | Passwortbestätigung für Freigaben, Rollenänderungen, Exporte |
| Aktive Sitzungen | Einsehen/Beenden in `/konto/sicherheit` | Einsehen durch `super_admin` |

## 7. Umsetzung (Technik)

```php
// Seeder (Auszug)
Role::findOrCreate('compliance')->syncPermissions([
    'documents.view', 'documents.approve', 'documents.reject',
    'entitlements.create', 'entitlements.revoke',
    'orders.view', 'orders.release', 'orders.hold',
    'weapon_items.manage', 'weapon_book.create', 'weapon_book.export', 'nwr.manage',
    'audit.view.compliance',
]);

// Policy (Auszug)
public function approve(User $staff, CustomerDocument $doc): bool
{
    return $staff->can('documents.approve')
        && $staff->id !== $doc->user_id
        && $staff->hasConfirmedTwoFactor();
}
```

Tests: Für jede Rolle ein Pest-Datensatz (Dataset) „darf / darf nicht“ je Filament-Ressource und Policy-Methode; Regressionstest, dass `super_admin` keine Dokumentdatei abrufen kann.
