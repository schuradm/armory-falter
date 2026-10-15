<?php

namespace Database\Seeders;

use App\Models\Caliber;
use App\Models\Category;
use App\Models\LegalClass;
use App\Models\Manufacturer;
use Illuminate\Database\Seeder;

/** Kategorien (inkl. Unterkategorien), Hersteller und Kaliber. */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->categories();
        $this->manufacturers();
        $this->calibers();
    }

    private function categories(): void
    {
        $legal = LegalClass::pluck('id', 'code');

        // slug => [Name, Icon, Rechtsklasse, Startseite, Beschreibung, Unterkategorien]
        $tree = [
            'kurzwaffen' => ['Kurzwaffen', 'pistol', 'firearm_permit', true, 'Pistolen und Revolver für Sportschützen, Jäger und Behörden. Erwerb nur mit WBK-Voreintrag oder gelber WBK.', [
                'pistolen' => 'Pistolen', 'revolver' => 'Revolver',
            ]],
            'langwaffen' => ['Langwaffen', 'rifle', 'firearm_permit', true, 'Repetierbüchsen, Selbstlader, Flinten und kombinierte Waffen namhafter Hersteller.', [
                'repetierbuechsen' => 'Repetierbüchsen', 'selbstlader' => 'Selbstladebüchsen', 'flinten' => 'Flinten', 'kipplaufwaffen' => 'Kipplaufwaffen',
            ]],
            'jagdwaffen' => ['Jagdwaffen', 'tree', 'firearm_permit', true, 'Jagdbüchsen, Drückjagdwaffen und kombinierte Waffen für Ansitz, Pirsch und Bewegungsjagd.', []],
            'sportwaffen' => ['Sportwaffen', 'target', 'firearm_permit', true, 'Wettkampfwaffen für IPSC, BDS, DSB und Präzisionsschießen.', []],
            'optiken' => ['Optiken', 'scope', 'free', true, 'Zielfernrohre, Leuchtpunktvisiere, Ferngläser und Wärmebildtechnik.', [
                'zielfernrohre' => 'Zielfernrohre', 'leuchtpunktvisiere' => 'Leuchtpunktvisiere', 'fernglaeser' => 'Ferngläser',
            ]],
            'munition' => ['Munition', 'box', 'ammo_permit', true, 'Büchsen-, Kurzwaffen- und Flintenmunition. Abgabe nur gegen Nachweis der Erwerbsberechtigung.', []],
            'messer' => ['Messer', 'knife', 'age_18', false, 'Jagdmesser, Taschenmesser und Outdoor-Messer. Abgabe ab 18 Jahren.', []],
            'waffenzubehoer' => ['Waffenzubehör', 'wrench', 'free', false, 'Reinigung, Holster, Koffer, Montagen und Ersatzteile.', []],
            'wiederladen' => ['Wiederladen', 'flask', 'free', false, 'Pressen, Matrizen, Hülsenbearbeitung und Messwerkzeug. Treibladungspulver nur bei Abholung.', []],
            'bekleidung' => ['Bekleidung', 'shirt', 'free', false, 'Jagd- und Schießbekleidung, Gehör- und Augenschutz.', []],
        ];

        $sort = 0;
        foreach ($tree as $slug => [$name, $icon, $legalCode, $home, $description, $children]) {
            $sort += 10;
            $parent = Category::updateOrCreate(['parent_id' => null, 'slug' => $slug], [
                'name' => $name,
                'path' => $slug,
                'icon' => $icon,
                'description' => $description,
                'default_legal_class_id' => $legal[$legalCode] ?? null,
                'is_active' => true,
                'show_on_home' => $home,
                'sort_order' => $sort,
            ]);

            $childSort = 0;
            foreach ($children as $childSlug => $childName) {
                $childSort += 10;
                Category::updateOrCreate(['parent_id' => $parent->id, 'slug' => $childSlug], [
                    'name' => $childName,
                    'path' => $slug.'/'.$childSlug,
                    'icon' => $icon,
                    'default_legal_class_id' => $legal[$legalCode] ?? null,
                    'is_active' => true,
                    'show_on_home' => false,
                    'sort_order' => $childSort,
                ]);
            }
        }
    }

    private function manufacturers(): void
    {
        $list = [
            ['Heckler & Koch', 'heckler-koch', 'DE', 1949, 'Der Oberndorfer Hersteller steht für robuste Pistolen und Gewehre im Behörden-, Sport- und Jagdbereich.'],
            ['Walther', 'walther', 'DE', 1886, 'Carl Walther aus Ulm baut seit über 130 Jahren Sport-, Dienst- und Jagdwaffen, darunter die PDP und die Q-Serie.'],
            ['Sig Sauer', 'sig-sauer', 'DE', 1853, 'Sig Sauer verbindet Schweizer und deutsche Waffenbautradition mit modernen modularen Systemen wie der P320.'],
            ['Glock', 'glock', 'AT', 1963, 'Die Polymerpistolen aus Deutsch-Wagram gelten weltweit als Maßstab für Zuverlässigkeit und einfache Handhabung.'],
            ['Blaser', 'blaser', 'DE', 1957, 'Blaser aus Isny im Allgäu ist bekannt für den Geradezugrepetierer R8 und kombinierte Waffen höchster Präzision.'],
            ['Sauer & Sohn', 'sauer-sohn', 'DE', 1751, 'J. P. Sauer & Sohn ist Deutschlands ältester Waffenhersteller. Die Sauer 100 und 404 sind Klassiker auf der Jagd.'],
            ['Merkel', 'merkel', 'DE', 1898, 'Merkel aus Suhl fertigt Kipplaufwaffen, Doppelbüchsen und den Geradezugrepetierer Helix in Handarbeit.'],
            ['Beretta', 'beretta', 'IT', 1526, 'Beretta ist das älteste Industrieunternehmen der Welt und Synonym für edle Flinten aus Gardone Val Trompia.'],
            ['CZ', 'cz', 'CZ', 1936, 'Česká zbrojovka steht für präzise und preiswerte Kleinkaliber-, Sport- und Dienstwaffen aus Uherský Brod.'],
            ['Zeiss', 'zeiss', 'DE', 1846, 'Zeiss Jagdoptik aus Wetzlar: Zielfernrohre, Ferngläser und Entfernungsmesser mit legendärer Glasqualität.'],
            ['Swarovski Optik', 'swarovski-optik', 'AT', 1949, 'Swarovski Optik aus Absam in Tirol fertigt Premium-Zielfernrohre und Ferngläser für höchste Ansprüche.'],
        ];

        foreach ($list as $i => [$name, $slug, $country, $founded, $intro]) {
            Manufacturer::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'country_code' => $country,
                'founded_year' => $founded,
                'intro' => $intro,
                'is_featured' => true,
                'sort_order' => ($i + 1) * 10,
            ]);
        }
    }

    private function calibers(): void
    {
        // [Name, slug, Art, Großkaliber (Sport, § 14 WaffG), Aliase, Kurztext]
        $list = [
            ['9mm Luger', '9mm-luger', 'handgun', true, ['9x19', '9mm Para', '9mm Parabellum'], 'Die meistverbreitete Pistolenpatrone der Welt: moderater Rückstoß, große Auswahl an Laborierungen, Standard im IPSC- und Behördenbereich.'],
            ['.45 ACP', '45-acp', 'handgun', true, ['.45 Auto', '11,43x23'], 'Schwere, langsame Pistolenpatrone mit angenehm schiebendem Rückstoß. Beliebt im Großkaliber-Sport und bei 1911-Fans.'],
            ['.22 lfB', '22-lfb', 'rimfire', false, ['.22 LR', '.22 Long Rifle', '5,6 mm lfB'], 'Randfeuerpatrone für Training, Präzisionssport und die Jagd auf Raubwild im Nahbereich. Günstig und leise.'],
            ['.223 Rem', '223-rem', 'rifle', true, ['5,56x45', '5.56 NATO'], 'Rasante Patrone für Raubwild, Varmint und den sportlichen Einsatz in Selbstladebüchsen.'],
            ['.308 Win', '308-win', 'rifle', true, ['7,62x51', '7.62 NATO'], 'Der Allrounder für Jagd und Sport: präzise, weltweit verfügbar und für nahezu alles Schalenwild in Europa geeignet.'],
            ['6,5 Creedmoor', '65-creedmoor', 'rifle', true, ['6.5 CM', '6.5 Creedmoor'], 'Moderne Präzisionspatrone mit hohem ballistischem Koeffizienten, wenig Rückstoß und hervorragender Weitschussleistung.'],
            ['.30-06', '30-06', 'rifle', true, ['.30-06 Springfield', '7,62x63'], 'Klassische Jagdpatrone mit großer Geschossauswahl, stark genug für Sau, Rotwild und Auslandsjagden.'],
            ['8x57 IS', '8x57-is', 'rifle', true, ['8x57 JS', '8 mm Mauser'], 'Die deutsche Traditionspatrone für Drückjagd und Ansitz: hohe Wirkung auf Schwarzwild bei überschaubarem Rückstoß.'],
            ['7x64', '7x64', 'rifle', true, ['7x64 Brenneke'], 'Wilhelm Brennekes Klassiker: gestreckte Flugbahn, universell auf Reh-, Gams- und Rotwild.'],
            ['12/70', '12-70', 'shotgun', false, ['12/70', 'Kaliber 12'], 'Das Standardkaliber für Flinten auf Wurfscheibe, Niederwild und mit Flintenlaufgeschossen auch auf Schwarzwild.'],
        ];

        foreach ($list as [$name, $slug, $kind, $large, $aliases, $content]) {
            Caliber::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'kind' => $kind,
                'is_large_bore' => $large,
                'aliases' => $aliases,
                'content' => '<p>'.$content.'</p>',
            ]);
        }
    }
}
