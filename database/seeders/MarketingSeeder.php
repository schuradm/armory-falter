<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\LocalPage;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/** Kundenstimmen, ein Demo-Event und eine Regionalseite. */
class MarketingSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            ['Thomas K.', 'Jäger aus Esslingen', 5, 'Hervorragende Beratung beim Kauf meiner Blaser R8. Zielfernrohr montiert, eingeschossen und alles perfekt erklärt. So stelle ich mir einen Fachhändler vor.', 'google'],
            ['Sandra M.', 'Sportschützin aus Stuttgart', 5, 'Die Abwicklung mit WBK-Voreintrag lief online völlig unkompliziert. Dokumente hochgeladen, am nächsten Tag freigegeben, zwei Tage später war die Pistole da.', 'google'],
            ['Michael B.', 'Sammler aus Reutlingen', 5, 'Meine alte Doppelflinte über den Verkaufsservice angeboten: faire Einschätzung, gute Fotos, zügiger Verkauf. Gern wieder.', 'shop'],
        ];
        foreach ($testimonials as $i => [$name, $meta, $rating, $body, $source]) {
            Testimonial::updateOrCreate(['author_name' => $name, 'author_meta' => $meta], [
                'rating' => $rating, 'body' => $body, 'source' => $source, 'is_published' => true, 'sort_order' => ($i + 1) * 10,
            ]);
        }

        // Demo-Event; das Datum wird nur beim ersten Anlegen gesetzt, damit Änderungen im Backend erhalten bleiben
        $start = now()->addWeeks(6)->next('Saturday')->setTime(10, 0);
        Event::firstOrCreate(['slug' => 'testtag-jagd-und-optik'], [
            'title' => 'Testtag Jagd & Optik',
            'teaser' => 'Blaser, Sauer, Zeiss und Swarovski zum Anfassen: Neuheiten testen, Optiken vergleichen, Fachgespräche mit unseren Beratern.',
            'description' => '<p>Erleben Sie die aktuellen Jagdwaffen und Optiken unserer Premium-Partner. Unsere Berater zeigen Ihnen die Unterschiede zwischen Geradezug- und Kammerstängelrepetierern, erklären Absehen und Leuchtpunkt und beraten Sie zur passenden Kombination für Ihr Revier.</p><p>Für Speisen und Getränke ist gesorgt. Die Teilnahme ist kostenlos, wir bitten um Anmeldung.</p>',
            'location' => 'Armory Falter, '.config('shop.company.street').', '.config('shop.company.postal_code').' '.config('shop.company.city'),
            'starts_at' => $start,
            'ends_at' => $start->copy()->setTime(16, 0),
            'capacity' => 40,
            'price_gross' => null,
            'requires_entitlement' => false,
            'is_published' => true,
        ]);

        LocalPage::updateOrCreate(['slug' => 'stuttgart'], [
            'city' => 'Stuttgart',
            'title' => 'Waffenhändler für Stuttgart – Armory Falter',
            'meta_description' => 'Waffenfachhandel nahe Stuttgart: Jagd- und Sportwaffen, Optiken, Munition, eigene Werkstatt und persönliche Beratung in Wolfschlugen, 20 Minuten von Stuttgart.',
            'headline' => 'Ihr Waffenfachhändler für Stuttgart und die Region',
            'body' => '<p>Von der Stuttgarter Innenstadt sind Sie in rund 20 Minuten bei uns in Wolfschlugen, über die B 27 oder die A 8 mit Abfahrt Esslingen. Parkplätze finden Sie direkt vor dem Geschäft.</p>'
                .'<h2>Für Jäger aus Stuttgart und dem Schurwald</h2><p>Ob Drückjagd im Schönbuch oder Ansitz auf den Fildern: Wir beraten Sie zur passenden Büchse, zum Kaliber und zur Optik und montieren und schießen Ihre Waffe in unserer Werkstatt ein.</p>'
                .'<h2>Für Sportschützen</h2><p>Viele Stuttgarter Vereine schießen BDS, DSB und IPSC. Wir führen Wettkampfpistolen von Glock, Walther, Sig Sauer und CZ und kümmern uns um den Voreintrag in Ihre WBK.</p>'
                .'<h2>Online bestellen, persönlich abholen</h2><p>Bestellen Sie online, laden Sie Ihre Erwerbsberechtigung sicher hoch und holen Sie Ihre Waffe nach der Freigabe bei uns ab, inklusive Einweisung.</p>',
            'faqs' => [
                ['question' => 'Wie weit ist Armory Falter von Stuttgart entfernt?', 'answer' => 'Rund 20 km bzw. 20 Minuten mit dem Auto vom Stuttgarter Zentrum.'],
                ['question' => 'Kann ich online bestellen und in Wolfschlugen abholen?', 'answer' => 'Ja. Wählen Sie im Checkout „Abholung in Wolfschlugen“. Die Abholung ist kostenlos.'],
            ],
            'distance_km' => 20,
            'is_published' => true,
        ]);
    }
}
