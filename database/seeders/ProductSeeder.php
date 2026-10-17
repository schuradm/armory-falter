<?php

namespace Database\Seeders;

use App\Models\Caliber;
use App\Models\Category;
use App\Models\LegalClass;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\TaxRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demo-Produkte quer durch den Katalog (Preise in Cent, brutto). Bilder werden im Backend ergänzt.
 * Waffen/Munition sind erlaubnispflichtig, Optiken/Zubehör/Bekleidung frei verkäuflich.
 */
class ProductSeeder extends Seeder
{
    /** Nur diese SKUs anlegen, ohne „Passend dazu“ (für Datenmigrationen in bestehende Installationen). */
    public ?array $only = null;

    public function run(): void
    {
        $m = Manufacturer::pluck('id', 'slug');
        $cal = Caliber::pluck('id', 'slug');
        $legal = LegalClass::pluck('id', 'code');
        $cat = Category::pluck('id', 'path');
        $tax = TaxRate::where('rate', 19)->value('id');

        $products = [
            // ---- Kurzwaffen ----
            ['sku' => 'GLO-G17-G5', 'name' => 'Glock 17 Gen5 FS', 'manufacturer' => 'glock', 'caliber' => '9mm-luger', 'legal' => 'firearm_permit',
                'type' => 'weapon', 'kind' => 'kurzwaffe', 'price' => 79900, 'uvp' => 84900, 'stock' => 4, 'sport' => true, 'featured' => true,
                'cats' => ['kurzwaffen/pistolen', 'kurzwaffen', 'sportwaffen'],
                'short' => 'Die Referenz unter den Dienst- und Sportpistolen: 17 Schuss, Front Serrations, Marksman-Lauf und beidseitiger Verschlussfanghebel.',
                'desc' => '<p>Die Glock 17 der fünften Generation ist die konsequente Weiterentwicklung der weltweit meistverkauften Pistole. Der Glock Marksman Barrel (GMB) mit überarbeitetem Polygonprofil sorgt für spürbar engere Streukreise, der nDLC-Überzug schützt Lauf und Schlitten dauerhaft vor Korrosion.</p><ul><li>Kaliber 9mm Luger, Magazinkapazität 17 Patronen</li><li>Lauflänge 114 mm, Gewicht 705 g (ohne Magazin)</li><li>Griff ohne Fingerrillen, wechselbare Griffrücken</li><li>Beidseitiger Schlittenfanghebel, umsteckbarer Magazinhalter</li></ul><p>Ideal für IPSC, BDS-Disziplinen und als zuverlässige Allround-Pistole.</p>'],
            ['sku' => 'HK-SFP9-SF', 'name' => 'Heckler & Koch SFP9 SF', 'manufacturer' => 'heckler-koch', 'caliber' => '9mm-luger', 'legal' => 'firearm_permit',
                'type' => 'weapon', 'kind' => 'kurzwaffe', 'price' => 89900, 'stock' => 2, 'sport' => true,
                'cats' => ['kurzwaffen/pistolen', 'kurzwaffen'],
                'short' => 'Ergonomie-Wunder aus Oberndorf: individuell anpassbares Griffstück, präziser Striker-Abzug und hervorragende Verarbeitung.',
                'desc' => '<p>Die SFP9 ist die Antwort von Heckler & Koch auf die Anforderungen moderner Behörden und Sportschützen. Durch austauschbare Griffschalen und Rückenteile lässt sich der Griff in 27 Kombinationen an jede Hand anpassen.</p><ul><li>Kaliber 9mm Luger, Magazin 15 Schuss</li><li>Lauflänge 104 mm, kaltgehämmerter Polygonlauf</li><li>Picatinny-Schiene für Lampe oder Laser</li></ul>'],
            ['sku' => 'WAL-PDP-FS45', 'name' => 'Walther PDP Full Size 4,5"', 'manufacturer' => 'walther', 'caliber' => '9mm-luger', 'legal' => 'firearm_permit',
                'type' => 'weapon', 'kind' => 'kurzwaffe', 'price' => 74900, 'stock' => 3, 'sport' => true, 'featured' => true,
                'cats' => ['kurzwaffen/pistolen', 'kurzwaffen', 'sportwaffen'],
                'short' => 'Optics Ready ab Werk, Performance Duty Trigger und SuperTerrain-Schlittenriffelung: die moderne Walther für Sport und Dienst.',
                'desc' => '<p>Die Walther PDP (Performance Duty Pistol) ist für den Einsatz mit Leuchtpunktvisier konzipiert. Der Schlitten ist ab Werk für gängige Red Dots vorbereitet, der Abzug bricht trocken und mit kurzem Rücksetzpunkt.</p><ul><li>Kaliber 9mm Luger, Magazin 18 Schuss</li><li>Lauflänge 4,5 Zoll (114 mm)</li><li>Optics-Ready-Schlitten, Montageplatten im Lieferumfang</li></ul>'],
            ['sku' => 'SIG-P320-X5', 'name' => 'Sig Sauer P320 X-Five', 'manufacturer' => 'sig-sauer', 'caliber' => '9mm-luger', 'legal' => 'firearm_permit',
                'type' => 'weapon', 'kind' => 'kurzwaffe', 'price' => 139900, 'stock' => 1, 'sport' => true,
                'cats' => ['kurzwaffen/pistolen', 'sportwaffen'],
                'short' => 'Wettkampfpistole mit 5"-Bull-Barrel, Edelstahl-Griffmodul und Flat-Trigger für die Production Optics und Standard Division.',
                'desc' => '<p>Die P320 X-Five ist für IPSC und Präzisionsschießen gebaut: schwerer Bull-Barrel, beschwertes Griffmodul, Magwell und ein Abzug mit sehr kurzem Weg. Durch das modulare FCU-Konzept lässt sich die Waffe später auf andere Griffgrößen umbauen.</p>'],
            ['sku' => 'CZ-SHADOW2', 'name' => 'CZ Shadow 2', 'manufacturer' => 'cz', 'caliber' => '9mm-luger', 'legal' => 'firearm_permit',
                'type' => 'weapon', 'kind' => 'kurzwaffe', 'price' => 129900, 'stock' => 0, 'availability' => 'on_order', 'delivery' => '2–4 Wochen', 'sport' => true,
                'cats' => ['kurzwaffen/pistolen', 'sportwaffen'],
                'short' => 'Ganzstahl-Wettkampfpistole mit hervorragendem SA/DA-Abzug, die Standardwaffe vieler IPSC-Production-Schützen.',
                'desc' => '<p>Mit 1.300 g Gewicht liegt die Shadow 2 extrem ruhig in der Hand. Der Stahlrahmen, der lange Schlitten und der feine Abzug machen sie zur Wettkampfwaffe ab Werk.</p>'],

            // ---- Langwaffen / Jagd ----
            ['sku' => 'CZ-EVO3-S1-C', 'name' => 'CZ Scorpion EVO 3 S1 Carbine', 'manufacturer' => 'cz', 'caliber' => '9mm-luger', 'legal' => 'firearm_permit',
                'type' => 'weapon', 'kind' => 'langwaffe', 'price' => 164900, 'stock' => 2, 'sport' => true, 'featured' => true,
                'cats' => ['langwaffen/selbstlader', 'langwaffen', 'sportwaffen'],
                'short' => 'Halbautomatischer Selbstlader im Kaliber 9 mm Luger: kompakt, leicht und rückstoßarm. Die beliebte Pistol Caliber Carbine für das sportliche Schießen.',
                'desc' => '<p>Die Scorpion EVO 3 S1 von Česká zbrojovka ist die zivile, halbautomatische Version der bekannten Dienstwaffe. Als Carbine mit langem Lauf ist sie eine Langwaffe und in Deutschland für Sportschützen mit entsprechendem Voreintrag erwerbbar. Durch das Kaliber 9 mm Luger schießt sie sich besonders weich und günstig, ideal für PCC-Disziplinen und das dynamische Schießen.</p><ul><li>Kaliber 9 mm Luger, Selbstlader (Halbautomat)</li><li>Rückstoßlader mit Feder-Masse-Verschluss</li><li>Klapp- und längenverstellbarer Schaft</li><li>Durchgehende Picatinny-Schiene für Optik, seitliche Schienen für Zubehör</li><li>Beidseitige Bedienelemente, verstellbare Diopter-Visierung</li><li>Lieferung mit Magazin für 10 Patronen (gesetzliche Höchstkapazität für halbautomatische Langwaffen)</li></ul><p>Erwerb nur mit grüner WBK und Voreintrag für eine halbautomatische Langwaffe. Mit der gelben WBK ist der Erwerb nicht möglich. Gern beraten wir Sie zur Bedürfnisbescheinigung und montieren Ihnen eine passende Leuchtpunktoptik.</p>'],

            ['sku' => 'BLA-R8-PRO-308', 'name' => 'Blaser R8 Professional Success', 'manufacturer' => 'blaser', 'caliber' => '308-win', 'legal' => 'firearm_permit',
                'type' => 'weapon', 'kind' => 'langwaffe', 'price' => 439000, 'uvp' => 459000, 'stock' => 2, 'hunting' => true, 'featured' => true,
                'cats' => ['langwaffen/repetierbuechsen', 'langwaffen', 'jagdwaffen'],
                'short' => 'Der Geradezugrepetierer für jede Jagdart: Wechsellauf-System, Handspannung und Lochschaft für schnelle, sichere Schüsse.',
                'desc' => '<p>Die R8 Professional Success verbindet die Vorzüge des R8-Systems mit einem ergonomischen Lochschaft aus robustem Kunststoff mit Soft-Touch-Einlagen. Der Geradezugverschluss ermöglicht schnelle Folgeschüsse, ohne die Waffe aus dem Anschlag zu nehmen.</p><ul><li>Kaliber .308 Win (weitere über Wechsellauf)</li><li>Handspannung: Waffe ist nur gespannt, wenn Sie es wollen</li><li>Blaser-Sattelmontage: Optik wiederkehrgenau abnehmbar</li><li>Abzugsgewicht ca. 750 g, Direktabzug</li></ul><p>Tipp: Lassen Sie Ihr Zielfernrohr gleich in unserer Werkstatt montieren und einschießen.</p>'],
            ['sku' => 'SAU-100-XT-3006', 'name' => 'Sauer 100 Classic XT', 'manufacturer' => 'sauer-sohn', 'caliber' => '30-06', 'legal' => 'firearm_permit',
                'type' => 'weapon', 'kind' => 'langwaffe', 'price' => 119900, 'special' => 109900, 'stock' => 5, 'hunting' => true,
                'cats' => ['langwaffen/repetierbuechsen', 'jagdwaffen'],
                'short' => 'Deutsche Präzision zum fairen Preis: 3-Stellungs-Sicherung, Kaltgehämmerter Lauf und Sub-MOA-Garantie.',
                'desc' => '<p>Die Sauer 100 ist der ideale Einstieg in die Welt der Repetierbüchsen aus Isny. Der kaltgehämmerte Lauf und der einstellbare Abzug liefern Präzision unter einem MOA, der robuste XT-Kunststoffschaft verzeiht auch Regen und Schlamm.</p><ul><li>Kaliber .30-06, Magazin 5 Patronen</li><li>Lauflänge 56 cm, Gewinde M15x1</li><li>Abzugsgewicht 1.000–2.000 g einstellbar</li></ul>'],
            ['sku' => 'MER-HELIX-857', 'name' => 'Merkel Helix Explorer', 'manufacturer' => 'merkel', 'caliber' => '8x57-is', 'legal' => 'firearm_permit',
                'type' => 'weapon', 'kind' => 'langwaffe', 'price' => 249000, 'stock' => 1, 'hunting' => true,
                'cats' => ['langwaffen/repetierbuechsen', 'jagdwaffen'],
                'short' => 'Geradezugrepetierer aus Suhl mit rotierendem Drehkopfverschluss: blitzschnell, kurz und führig auf der Drückjagd.',
                'desc' => '<p>Der Helix Explorer ist mit 99 cm Gesamtlänge besonders kompakt. Der Verschluss läuft im geschlossenen Gehäuse und lässt sich ohne Kraftaufwand repetieren, ideal für schnelle Doubletten auf der Bewegungsjagd.</p>'],
            ['sku' => 'BER-694-12', 'name' => 'Beretta 694 Sporting', 'manufacturer' => 'beretta', 'caliber' => '12-70', 'legal' => 'firearm_permit',
                'type' => 'weapon', 'kind' => 'langwaffe', 'price' => 349000, 'stock' => 1, 'sport' => true, 'hunting' => true,
                'cats' => ['langwaffen/flinten', 'sportwaffen'],
                'short' => 'Bockdoppelflinte für Trap, Skeet und Sporting mit Steelium-Läufen, B-Fast-Schaftrücken und Optima-HP-Chokes.',
                'desc' => '<p>Die 694 wurde gemeinsam mit Weltmeistern entwickelt. Der tiefere Systemkasten bringt die Läufe näher an die Hand, das spürbar geringere Hochschlagen erleichtert den zweiten Schuss.</p><ul><li>Kaliber 12/76, Lauflänge 76 cm</li><li>Verstellbare Schaftbacke, B-Fast-System</li><li>5 Wechselchokes im Lieferumfang</li></ul>'],
            ['sku' => 'CZ-457-VAR-22', 'name' => 'CZ 457 Varmint', 'manufacturer' => 'cz', 'caliber' => '22-lfb', 'legal' => 'firearm_permit',
                'type' => 'weapon', 'kind' => 'langwaffe', 'price' => 69900, 'stock' => 6, 'sport' => true,
                'cats' => ['langwaffen/repetierbuechsen', 'sportwaffen'],
                'short' => 'Kleinkaliber-Repetierer mit schwerem Lauf und Buchenschaft: präzise, leise, günstig im Unterhalt.',
                'desc' => '<p>Die CZ 457 Varmint ist der ideale Einstieg ins Präzisionsschießen und perfekt für das Training. Der 60°-Kammerstängel erlaubt eine niedrige Montage, der Abzug ist einstellbar.</p>'],

            // ---- Optiken (frei verkäuflich) ----
            ['sku' => 'ZEI-V4-312-56', 'name' => 'Zeiss Conquest V4 3–12x56', 'manufacturer' => 'zeiss', 'legal' => 'free',
                'type' => 'optic', 'price' => 109900, 'uvp' => 119900, 'stock' => 3, 'hunting' => true, 'featured' => true,
                'cats' => ['optiken/zielfernrohre', 'optiken'],
                'short' => 'Das Ansitzglas mit 4-fach-Zoom, Leuchtabsehen 60 und 90 % Transmission. Zuverlässig bis in die späte Dämmerung.',
                'desc' => '<p>Das Conquest V4 3–12x56 deckt vom Ansitz bis zur Pirsch fast jede Jagdsituation ab. Das beleuchtete Absehen 60 im zweiten Bildebene bleibt in jeder Vergrößerung gleich fein.</p><ul><li>Vergrößerung 3–12-fach, Objektiv 56 mm</li><li>Mittelrohr 30 mm, Schiene oder glatt</li><li>Wasserdicht bis 400 mbar, stickstoffgefüllt</li></ul>'],
            ['sku' => 'SWA-Z8I-216-50', 'name' => 'Swarovski Z8i 2–16x50 P L', 'manufacturer' => 'swarovski-optik', 'legal' => 'free',
                'type' => 'optic', 'price' => 289000, 'stock' => 1, 'hunting' => true,
                'cats' => ['optiken/zielfernrohre', 'optiken'],
                'short' => 'Achtfach-Zoom, Flexchange-Absehen und brillante Optik: das Universalglas für Drückjagd und Ansitz.',
                'desc' => '<p>Das Z8i 2–16x50 ist eines der vielseitigsten Zielfernrohre am Markt. Bei 2-facher Vergrößerung bietet es ein riesiges Sehfeld für die Drückjagd, bei 16-fach präzise Schüsse auf große Distanz.</p>'],
            ['sku' => 'SIG-ROMEO5', 'name' => 'Sig Sauer Romeo5 Red Dot', 'manufacturer' => 'sig-sauer', 'legal' => 'free',
                'type' => 'optic', 'price' => 17900, 'stock' => 12, 'sport' => true,
                'cats' => ['optiken/leuchtpunktvisiere', 'optiken'],
                'short' => 'Robustes Leuchtpunktvisier mit 2-MOA-Punkt, MOTAC-Bewegungssensor und bis zu 40.000 Stunden Batterielaufzeit.',
                'desc' => '<p>Das Romeo5 schaltet sich bei Bewegung automatisch ein und nach Ruhe ab. Ideal für Selbstlader, PCC und das schnelle Schießen im Parcours.</p>'],

            // ---- Munition ----
            ['sku' => 'SIG-AMMO-9-124', 'name' => 'Sig Sauer Elite Ball 9mm Luger 124 gr FMJ (50 Stück)', 'manufacturer' => 'sig-sauer', 'caliber' => '9mm-luger', 'legal' => 'ammo_permit',
                'type' => 'ammo', 'price' => 2490, 'stock' => 80, 'sport' => true,
                'cats' => ['munition'],
                'short' => 'Saubere, präzise Trainingsmunition mit Vollmantelgeschoss für Sport und Übung.',
                'desc' => '<p>Gleichmäßige Laborierung, saubere Verbrennung und zuverlässige Funktion in allen gängigen Pistolen. Inhalt: 50 Patronen.</p>'],
            ['sku' => 'BLA-CDC-308', 'name' => 'Blaser CDC .308 Win 11,0 g (20 Stück)', 'manufacturer' => 'blaser', 'caliber' => '308-win', 'legal' => 'ammo_permit',
                'type' => 'ammo', 'price' => 5990, 'stock' => 30, 'hunting' => true,
                'cats' => ['munition'],
                'short' => 'Bleifreies Jagdgeschoss mit kontrollierter Deformation: hohe Restmasse, sichere Wirkung, wildbretschonend.',
                'desc' => '<p>Das CDC-Geschoss (Controlled Deformation Copper) ist für die Jagd auf mittleres und starkes Schalenwild entwickelt und auf Blaser-Waffen abgestimmt. Inhalt: 20 Patronen.</p>'],

            // ---- Messer (ab 18) ----
            ['sku' => 'WAL-BWK2', 'name' => 'Walther BWK 2 Jagdmesser', 'manufacturer' => 'walther', 'legal' => 'age_18',
                'type' => 'knife', 'price' => 6990, 'stock' => 8, 'hunting' => true,
                'cats' => ['messer'],
                'short' => 'Feststehendes Jagdmesser mit 10-cm-Klinge aus 440C-Stahl und rutschfestem Griff, inklusive Lederscheide.',
                'desc' => '<p>Das BWK 2 ist der zuverlässige Begleiter beim Aufbrechen und Zerwirken. Die Klinge hält die Schärfe lange und lässt sich einfach nachschärfen.</p><p>Abgabe nur an Personen ab 18 Jahren. Bitte beachten Sie das Führverbot nach § 42a WaffG.</p>'],

            // ---- Zubehör, Wiederladen, Bekleidung (frei verkäuflich) ----
            ['sku' => 'WAL-CLEAN-UNI', 'name' => 'Walther Universal-Reinigungsset', 'manufacturer' => 'walther', 'legal' => 'free',
                'type' => 'accessory', 'price' => 4990, 'stock' => 20,
                'cats' => ['waffenzubehoer'],
                'short' => 'Komplettes Reinigungsset für Kurz- und Langwaffen von .22 bis 12/70 im praktischen Koffer.',
                'desc' => '<p>Mit Messing-Putzstock, Bürsten, Wischern und Adaptern für die gängigen Kaliber. Der Koffer hält alles ordentlich zusammen.</p>'],
            ['sku' => 'HK-CASE-PISTOL', 'name' => 'Heckler & Koch Pistolenkoffer', 'manufacturer' => 'heckler-koch', 'legal' => 'free',
                'type' => 'accessory', 'price' => 5900, 'stock' => 10,
                'cats' => ['waffenzubehoer'],
                'short' => 'Abschließbarer Hartschalenkoffer mit Würfelschaum, für den sicheren Transport zum Stand (§ 12 WaffG).',
                'desc' => '<p>Stabil, staubdicht und abschließbar. Der Würfelschaum lässt sich auf jede Pistole und zwei Ersatzmagazine anpassen.</p>'],
            ['sku' => 'RL-PULVER-N140', 'name' => 'Treibladungspulver N140 (500 g)', 'manufacturer' => null, 'legal' => 'pickup_only',
                'type' => 'reloading', 'price' => 6490, 'stock' => 12, 'availability' => 'in_store_only',
                'cats' => ['wiederladen'],
                'short' => 'Universelles Büchsenpulver für .308 Win, .30-06 und 6,5 Creedmoor. Nur Abholung im Geschäft.',
                'desc' => '<p>Nur Abgabe im Geschäft gegen Vorlage der Erlaubnis nach dem Sprengstoffgesetz. Ladedaten beachten.</p>'],
            ['sku' => 'BLA-VINTAGE-JKT', 'name' => 'Blaser Vintage Jacke Herren', 'manufacturer' => 'blaser', 'legal' => 'free',
                'type' => 'apparel', 'price' => 29900, 'stock' => 6, 'hunting' => true,
                'cats' => ['bekleidung'],
                'short' => 'Leise, wasserdichte Jagdjacke im klassischen Look mit Membran und vielen Taschen.',
                'desc' => '<p>Das Obermaterial aus gebürstetem Fleece raschelt nicht, die Membran hält Wind und Regen ab. Mit Patronentaschen und Handwärmertaschen.</p>'],
        ];

        $ids = [];
        foreach ($products as $p) {
            if ($this->only !== null && ! in_array($p['sku'], $this->only, true)) {
                continue;
            }
            $product = Product::updateOrCreate(['sku' => $p['sku']], [
                'name' => $p['name'],
                'slug' => Str::slug($p['name']),
                'manufacturer_id' => $p['manufacturer'] ? $m[$p['manufacturer']] : null,
                'caliber_id' => isset($p['caliber']) ? $cal[$p['caliber']] : null,
                'legal_class_id' => $legal[$p['legal']],
                'tax_rate_id' => $tax,
                'product_type' => $p['type'],
                'weapon_kind' => $p['kind'] ?? null,
                'condition_code' => 'new',
                'usage_hunting' => $p['hunting'] ?? false,
                'usage_sport' => $p['sport'] ?? false,
                'price_gross' => $p['price'],
                'special_price_gross' => $p['special'] ?? null,
                'lowest_price_30d' => isset($p['special']) ? $p['price'] : null,
                'uvp_gross' => $p['uvp'] ?? null,
                'stock_quantity' => $p['stock'],
                'availability' => $p['availability'] ?? ($p['stock'] > 0 ? ($p['stock'] <= 2 ? 'low_stock' : 'in_stock') : 'out_of_stock'),
                'delivery_time' => $p['delivery'] ?? ($p['stock'] > 0 ? '1–3 Werktage' : null),
                'short_description' => $p['short'],
                'description' => $p['desc'],
                'is_featured' => $p['featured'] ?? false,
                'status' => 'active',
                'published_at' => now(),
            ]);
            $ids[$p['sku']] = $product->id;

            $sync = [];
            foreach ($p['cats'] as $i => $path) {
                $sync[$cat[$path]] = ['is_primary' => $i === 0];
            }
            $product->categories()->sync($sync);
        }

        if ($this->only !== null) {
            return;
        }

        // „Passend dazu“ (Upsell): Zubehör und Optik zu den Waffen
        $relations = [
            'GLO-G17-G5' => ['SIG-ROMEO5', 'HK-CASE-PISTOL', 'WAL-CLEAN-UNI', 'SIG-AMMO-9-124'],
            'WAL-PDP-FS45' => ['SIG-ROMEO5', 'HK-CASE-PISTOL', 'SIG-AMMO-9-124'],
            'HK-SFP9-SF' => ['HK-CASE-PISTOL', 'WAL-CLEAN-UNI', 'SIG-AMMO-9-124'],
            'BLA-R8-PRO-308' => ['ZEI-V4-312-56', 'SWA-Z8I-216-50', 'BLA-CDC-308', 'BLA-VINTAGE-JKT'],
            'SAU-100-XT-3006' => ['ZEI-V4-312-56', 'WAL-CLEAN-UNI', 'WAL-BWK2'],
            'MER-HELIX-857' => ['SWA-Z8I-216-50', 'WAL-BWK2'],
        ];
        foreach ($relations as $sku => $related) {
            foreach ($related as $i => $relSku) {
                DB::table('product_relations')->updateOrInsert(
                    ['product_id' => $ids[$sku], 'related_product_id' => $ids[$relSku], 'type' => 'accessory'],
                    ['sort_order' => $i * 10],
                );
            }
        }
    }
}
