<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Support\LegalTexts;
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
        $todo = fn (string $text) => '<mark>[PLATZHALTER: '.$text.']</mark>';

        $pages = [
            'impressum' => ['Impressum', LegalTexts::impressum($todo)],
            'datenschutz' => ['Datenschutzerklärung', LegalTexts::datenschutz($todo)],
            'agb' => ['Allgemeine Geschäftsbedingungen', LegalTexts::agb($todo)],
            'widerruf' => ['Widerrufsbelehrung', LegalTexts::widerruf($todo)],
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
