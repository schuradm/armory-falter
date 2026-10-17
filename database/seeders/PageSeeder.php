<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Rechtstexte und Informationsseiten (Slugs siehe PageController::SLUGS).
 * Alle Stellen mit [PLATZHALTER …] muss der Anwalt bzw. der Inhaber vor Go-live ersetzen.
 * Bereits im Backend bearbeitete Seiten werden nicht überschrieben (nur angelegt, wenn sie fehlen).
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $c = config('shop.company');
        $address = e($c['name']).'<br>Inhaber: '.e($c['owner']).'<br>'.e($c['street']).'<br>'.e($c['postal_code'].' '.$c['city']);
        $contact = 'Telefon: '.e($c['phone']).'<br>E-Mail: <a href="mailto:'.e($c['email']).'">'.e($c['email']).'</a>';
        $todo = fn (string $text) => '<mark>[PLATZHALTER: '.$text.']</mark>';

        $pages = [
            'impressum' => ['Impressum', <<<HTML
<h2>Angaben gemäß § 5 DDG</h2>
<p>{$address}</p>
<h2>Kontakt</h2>
<p>{$contact}</p>
<h2>Umsatzsteuer</h2>
<p>Umsatzsteuer-Identifikationsnummer gemäß § 27a UStG: {$todo('USt-IdNr. eintragen')}</p>
<h2>Waffenrechtliche Erlaubnis</h2>
<p>Waffenhandelserlaubnis nach § 21 WaffG, erteilt durch: {$todo('Behörde, Aktenzeichen und Datum der Erlaubnis')}</p>
<p>Zuständige Aufsichtsbehörde: {$todo('z. B. Landratsamt Esslingen, Waffenbehörde, Anschrift')}</p>
<h2>Berufsrechtliche Angaben</h2>
<p>{$todo('ggf. Handwerkskammer, Meistertitel Büchsenmacher, Eintragung Handwerksrolle')}</p>
<h2>Verbraucherstreitbeilegung</h2>
<p>Wir sind nicht bereit und nicht verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen. {$todo('anwaltlich bestätigen')}</p>
<h2>Verantwortlich für den Inhalt</h2>
<p>{$c['owner']}, Anschrift wie oben.</p>
HTML],
            'datenschutz' => ['Datenschutzerklärung', <<<HTML
<p>{$todo('Vollständige Datenschutzerklärung durch Anwalt/Datenschutzbeauftragten erstellen lassen. Die folgenden Abschnitte sind eine Gliederung mit den im Shop tatsächlich eingesetzten Verarbeitungen.')}</p>
<h2>1. Verantwortlicher</h2>
<p>{$address}<br>{$contact}</p>
<h2>2. Hosting und Server-Logfiles</h2>
<p>{$todo('Hoster (One.com Group AB), Auftragsverarbeitungsvertrag, Speicherdauer der Logfiles')}</p>
<h2>3. Kundenkonto und Bestellungen</h2>
<p>Wir verarbeiten Ihre Daten zur Vertragserfüllung (Art. 6 Abs. 1 lit. b DSGVO). {$todo('Aufbewahrungsfristen HGB/AO')}</p>
<h2>4. Erwerbsberechtigungen und Ausweisdokumente</h2>
<p>Für den Verkauf erlaubnispflichtiger Waffen und Munition sind wir gesetzlich verpflichtet, die Erwerbsberechtigung zu prüfen und zu dokumentieren (§ 34 WaffG, Art. 6 Abs. 1 lit. c DSGVO). Hochgeladene Dokumente werden verschlüsselt gespeichert und nur von berechtigten Mitarbeitern eingesehen. {$todo('Löschfristen, Waffenhandelsbuch, Nationales Waffenregister')}</p>
<h2>5. Zahlungsdienstleister</h2>
<p>PayPal (Europe) S.à r.l. et Cie, S.C.A. und Klarna Bank AB (publ) für frei verkäufliche Ware. {$todo('Angaben zur Datenübermittlung und Rechtsgrundlage')}</p>
<h2>6. Versand</h2>
<p>{$todo('DHL / Deutsche Post, Übermittlung von Name, Anschrift, ggf. E-Mail für Sendungsverfolgung')}</p>
<h2>7. Newsletter und Waffenratgeber (Double-Opt-in)</h2>
HTML.\App\Support\LegalTexts::newsletter($todo).<<<HTML
<h2>8. Cookies und Einwilligung</h2>
<p>{$todo('Technisch notwendige Cookies, Cookie-Banner, ggf. Statistik')}</p>
<h2>9. Spamschutz</h2>
<p>{$todo('Cloudflare Turnstile oder Google reCAPTCHA, je nach Konfiguration')}</p>
<h2>10. Ihre Rechte</h2>
<p>Auskunft, Berichtigung, Löschung, Einschränkung, Datenübertragbarkeit, Widerspruch und Beschwerde bei einer Aufsichtsbehörde (z. B. LfDI Baden-Württemberg).</p>
HTML],
            'agb' => ['Allgemeine Geschäftsbedingungen', <<<HTML
<p>{$todo('AGB durch Fachanwalt erstellen lassen. Gliederung als Vorlage.')}</p>
<h2>§ 1 Geltungsbereich</h2>
<p>Diese AGB gelten für alle Bestellungen über den Online-Shop von {$c['name']}, {$c['street']}, {$c['postal_code']} {$c['city']}.</p>
<h2>§ 2 Vertragsschluss</h2>
<p>{$todo('Angebot/Annahme, Bestellbestätigung, Vertragsschluss bei erlaubnispflichtiger Ware erst nach Prüfung der Erwerbsberechtigung')}</p>
<h2>§ 3 Erlaubnispflichtige Waren</h2>
<p>Waffen und Munition, deren Erwerb einer Erlaubnis bedarf, werden nur an Personen abgegeben, die ihre Berechtigung nachgewiesen haben. Wir sind berechtigt, die Bestellung abzulehnen, wenn die Berechtigung nicht nachgewiesen wird. {$todo('Regelung zu bereits geleisteten Zahlungen')}</p>
<h2>§ 4 Preise und Zahlung</h2>
<p>Alle Preise inkl. gesetzlicher Umsatzsteuer, zzgl. Versandkosten. {$todo('Zahlungsarten, Fälligkeit, Vorkasse-Frist 7 Tage')}</p>
<h2>§ 5 Lieferung und Übergabe</h2>
<p>{$todo('Persönliche Übergabe, Altersprüfung, Abholung')}</p>
<h2>§ 6 Eigentumsvorbehalt</h2>
<p>{$todo('')}</p>
<h2>§ 7 Gewährleistung</h2>
<p>{$todo('Gesetzliche Mängelhaftung, Gebrauchtwaffen 1 Jahr bei Verbrauchern')}</p>
<h2>§ 8 Schlussbestimmungen</h2>
<p>{$todo('Rechtswahl, Gerichtsstand für Kaufleute')}</p>
HTML],
            'widerruf' => ['Widerrufsbelehrung', <<<HTML
<p>{$todo('Muster-Widerrufsbelehrung nach Anlage 1 zu Art. 246a § 1 Abs. 2 EGBGB einsetzen und anwaltlich prüfen lassen, insbesondere zu Waffen mit Seriennummer und Ausschlüssen nach § 312g BGB.')}</p>
<h2>Widerrufsrecht</h2>
<p>Sie haben das Recht, binnen vierzehn Tagen ohne Angabe von Gründen diesen Vertrag zu widerrufen. {$todo('vollständiger Mustertext')}</p>
<h2>Folgen des Widerrufs</h2>
<p>{$todo('Mustertext, Hinweis: Rücksendung erlaubnispflichtiger Waffen nur unter Beachtung des WaffG (keine Rücksendung per Standardpaket)')}</p>
<h2>Muster-Widerrufsformular</h2>
<p>An {$c['name']}, {$c['street']}, {$c['postal_code']} {$c['city']}, E-Mail: {$c['email']}<br>
Hiermit widerrufe(n) ich/wir den von mir/uns abgeschlossenen Vertrag über den Kauf der folgenden Waren: …<br>
Bestellt am / erhalten am: …<br>Name, Anschrift: …<br>Datum, Unterschrift (nur bei Mitteilung auf Papier)</p>
HTML],
            'versandinformationen' => ['Versand & Lieferung', <<<HTML
<h2>Versandarten und Kosten</h2>
<table>
<thead><tr><th>Versandart</th><th>Für</th><th>Kosten</th></tr></thead>
<tbody>
<tr><td>DHL Paket</td><td>Frei verkäufliche Artikel</td><td>5,95 €, ab 150 € versandkostenfrei</td></tr>
<tr><td>DHL mit Alterssichtprüfung 18+</td><td>Artikel ab 18 (z. B. Messer)</td><td>9,90 €</td></tr>
<tr><td>Waffenversand mit persönlicher Übergabe</td><td>Erlaubnispflichtige Waffen und Munition</td><td>39,00 €</td></tr>
<tr><td>Abholung in {$c['city']}</td><td>Alle Artikel</td><td>kostenlos</td></tr>
</tbody>
</table>
<h2>Erlaubnispflichtige Ware</h2>
<p>Waffen und Munition versenden wir erst, nachdem unsere Waffensachbearbeitung Ihre Erwerbsberechtigung geprüft und die Bestellung freigegeben hat. Die Zustellung erfolgt ausschließlich persönlich an Sie gegen Vorlage des Ausweises.</p>
<h2>Treibladungspulver</h2>
<p>Treibladungspulver und Anzündhütchen geben wir nur im Geschäft ab.</p>
<h2>Lieferzeiten</h2>
<p>Lagerware: 1–3 Werktage nach Zahlungseingang bzw. Freigabe. {$todo('Lieferung ins Ausland: ja/nein')}</p>
HTML],
            'zahlungsarten' => ['Zahlungsarten', <<<HTML
<h2>Frei verkäufliche Artikel</h2>
<p>Vorkasse per Überweisung, PayPal, Sofortüberweisung (Klarna), Klarna Rechnung/Ratenkauf, Kreditkarte (sofern aktiviert) und Barzahlung bei Abholung.</p>
<h2>Waffen, Munition und Artikel ab 18</h2>
<p>PayPal, Klarna und viele Kartenanbieter schließen Waffen und Munition aus. Für diese Artikel bieten wir Vorkasse per Überweisung, Barzahlung bzw. EC-Karte bei Abholung und, sofern aktiviert, Kartenzahlung über einen waffenfreundlichen Zahlungsanbieter an.</p>
<p>Bitte überweisen Sie bei erlaubnispflichtiger Ware erst nach unserer Freigabe. Sie erhalten dazu eine E-Mail.</p>
<h2>Bankverbindung</h2>
<p>{$todo('Kontoinhaber, IBAN, BIC, Bank')}</p>
HTML],
            'altersverifikation' => ['Altersprüfung & Erwerbsberechtigung', <<<HTML
<p>Wir sind gesetzlich verpflichtet, vor der Abgabe altersbeschränkter oder erlaubnispflichtiger Ware das Alter und die Berechtigung des Käufers festzustellen (§ 34 WaffG).</p>
<h2>So funktioniert es</h2>
<ol>
<li>Kundenkonto anlegen und Geburtsdatum hinterlegen.</li>
<li>Unter <em>Konto → Dokumente</em> Personalausweis oder Reisepass sowie Ihre Erwerbsberechtigung (WBK, Jagdschein, Munitionserwerbsberechtigung) hochladen.</li>
<li>Unsere Waffensachbearbeitung prüft die Unterlagen, in der Regel innerhalb eines Werktags.</li>
<li>Nach der Freigabe können Sie berechtigte Artikel bestellen. Jede Bestellung wird vor dem Versand zusätzlich geprüft.</li>
</ol>
<h2>Sicherheit Ihrer Dokumente</h2>
<p>Ihre Dateien werden verschlüsselt außerhalb des öffentlichen Webservers gespeichert und nur von berechtigten Mitarbeitern eingesehen. Nach Ablauf der Aufbewahrungsfrist werden sie automatisch gelöscht. {$todo('Löschfristen anwaltlich bestätigen')}</p>
<h2>Altersgrenzen</h2>
<ul><li>Messer und Artikel mit Altersfreigabe: ab 18 Jahren, Versand mit DHL-Alterssichtprüfung</li>
<li>Erlaubnispflichtige Waffen und Munition: ab 18 Jahren mit gültiger Erlaubnis; Großkaliber für Sportschützen ab 21 Jahren (§ 14 WaffG)</li></ul>
HTML],
            'barrierefreiheit' => ['Erklärung zur Barrierefreiheit', <<<HTML
<p>Wir möchten unseren Online-Shop für alle Menschen zugänglich machen und orientieren uns an den Anforderungen des Barrierefreiheitsstärkungsgesetzes (BFSG) und der WCAG 2.1 Stufe AA.</p>
<h2>Stand der Vereinbarkeit</h2>
<p>{$todo('Ergebnis der Prüfung, bekannte Einschränkungen')}</p>
<h2>Feedback und Kontakt</h2>
<p>Sind Ihnen Barrieren aufgefallen? Schreiben Sie uns: <a href="mailto:{$c['email']}">{$c['email']}</a>, Telefon {$c['phone']}.</p>
<h2>Marktüberwachungsbehörde</h2>
<p>{$todo('zuständige Marktüberwachungsbehörde der Länder für das BFSG')}</p>
HTML],
            'ueber-uns' => ['Über uns', <<<HTML
<p>Armory Falter ist Ihr Waffenfachhandel in {$c['city']} bei Stuttgart. Inhaber {$c['owner']} und sein Team beraten Jäger, Sportschützen, Sammler und Behörden persönlich, fachkundig und ohne Verkaufsdruck.</p>
<h2>Was uns ausmacht</h2>
<ul><li>Fester Ansprechpartner für Ihre Anliegen, vor Ort, am Telefon oder per Video</li>
<li>Eigene Werkstatt für Optikmontage, Einschießen, Reinigung und Reparatur</li>
<li>Verkaufsservice und Ankauf Ihrer gebrauchten Waffen</li>
<li>Elf Premium-Marken von Blaser bis Swarovski Optik</li></ul>
<p>{$todo('Geschichte, Teamfotos, Qualifikationen')}</p>
HTML],
        ];

        foreach ($pages as $slug => [$title, $body]) {
            Page::firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'body' => $body,
                'template' => 'default',
                'status' => 'published',
                'published_at' => now(),
            ]);
        }
    }
}
