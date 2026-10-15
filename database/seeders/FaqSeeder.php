<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/** Allgemeine FAQ (/faq), gruppiert. Produkt-/herstellerbezogene FAQ werden im Backend gepflegt. */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'Bestellung & Waffenerwerb' => [
                ['Kann ich Waffen online bestellen?', 'Ja. Sie legen die Waffe in den Warenkorb, laden Ihre Erwerbsberechtigung (WBK mit Voreintrag, gelbe WBK oder Jagdschein) im Kundenkonto hoch und bestellen. Unsere Waffensachbearbeitung prüft Ihre Unterlagen und gibt die Bestellung frei, bevor wir versenden.'],
                ['Welche Unterlagen brauche ich?', 'Für alle altersbeschränkten Artikel einen Personalausweis oder Reisepass. Für erlaubnispflichtige Waffen zusätzlich die passende Waffenbesitzkarte mit Voreintrag, die gelbe WBK oder einen gültigen Jahresjagdschein (für Langwaffen). Für Munition die Munitionserwerbsberechtigung oder den Jagdschein.'],
                ['Wie lange dauert die Prüfung meiner Dokumente?', 'In der Regel innerhalb eines Werktags. Sie erhalten eine E-Mail, sobald Ihre Unterlagen geprüft sind.'],
                ['Kann ich eine Waffe reservieren?', 'Ja. Rufen Sie uns an oder schreiben Sie uns über das Kontaktformular. Wir reservieren die Waffe gern für einige Tage, bis Ihr Voreintrag vorliegt.'],
            ],
            'Versand & Zahlung' => [
                ['Wie werden Waffen versendet?', 'Erlaubnispflichtige Waffen und Munition versenden wir versichert mit persönlicher Übergabe an Sie gegen Vorlage Ihres Ausweises. Alternativ holen Sie Ihre Bestellung in Wolfschlugen ab.'],
                ['Warum kann ich Waffen nicht mit PayPal bezahlen?', 'PayPal, Klarna und viele Kartenanbieter schließen Waffen und Munition in ihren Nutzungsbedingungen aus. Für diese Artikel bieten wir Vorkasse, Barzahlung bei Abholung und, sofern verfügbar, einen waffenfreundlichen Kartenanbieter an.'],
                ['Ab wann ist der Versand kostenlos?', 'Frei verkäufliche Artikel versenden wir ab 150 € Bestellwert versandkostenfrei innerhalb Deutschlands.'],
                ['Kann ich Treibladungspulver bestellen?', 'Treibladungspulver und Anzündhütchen geben wir nur im Geschäft ab. Sie können online bestellen und bei Abholung bezahlen; bitte bringen Sie Ihre Erlaubnis nach dem Sprengstoffgesetz mit.'],
            ],
            'Werkstatt & Service' => [
                ['Montieren Sie mein Zielfernrohr?', 'Ja. Wir montieren Zielfernrohre und Leuchtpunktvisiere fachgerecht und schießen die Waffe auf Wunsch auch gleich ein. Buchen Sie dazu einfach einen Werkstatttermin.'],
                ['Kaufen Sie gebrauchte Waffen an?', 'Ja. Über unseren Ankauf machen wir Ihnen ein faires Angebot. Alternativ verkaufen wir Ihre Waffe in Kommission über egun, Sie erhalten den Erlös abzüglich unserer Provision.'],
                ['Wie überlasse ich eine Waffe an Sie?', 'Die Überlassung an uns als Waffenhändler erfolgt persönlich im Geschäft oder per Waffenversand. Wir übernehmen die Meldung an die Behörde über das Nationale Waffenregister.'],
            ],
            'Kundenkonto & Datenschutz' => [
                ['Wie sicher sind meine hochgeladenen Dokumente?', 'Ihre Dokumente werden verschlüsselt außerhalb des öffentlichen Webservers gespeichert. Nur berechtigte Mitarbeiter der Waffensachbearbeitung können sie einsehen. Nach Ablauf der Aufbewahrungsfrist löschen wir sie automatisch.'],
                ['Kann ich mein Kundenkonto löschen?', 'Ja, unter Konto → Datenschutz. Daten, die wir aus gesetzlichen Gründen aufbewahren müssen (z. B. Rechnungen, Waffenhandelsbuch), werden bis zum Ablauf der Frist gesperrt und danach gelöscht.'],
            ],
        ];

        $sort = 0;
        foreach ($groups as $group => $faqs) {
            foreach ($faqs as [$question, $answer]) {
                $sort += 10;
                Faq::updateOrCreate(
                    ['faqable_type' => null, 'faqable_id' => null, 'question' => $question],
                    ['group_name' => $group, 'answer' => $answer, 'sort_order' => $sort],
                );
            }
        }
    }
}
