<?php

namespace Database\Seeders;

use App\Models\WorkshopService;
use Illuminate\Database\Seeder;

class WorkshopServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['optikmontage', 'Optikmontage', 'Zielfernrohr oder Red Dot fachgerecht montiert, ausgerichtet und mit Drehmoment angezogen.', 4900, 60,
                '<p>Wir montieren Ihr Zielfernrohr spannungsfrei auf Schiene, Schwenk- oder Blaser-Sattelmontage, richten das Absehen lotrecht aus und stellen den Augenabstand auf Ihren Anschlag ein. Auf Wunsch liefern wir die passende Montage direkt mit.</p>'],
            ['einschiessen', 'Einschießen', 'Ihre Waffe auf dem Stand eingeschossen, auf Ihre Munition und Ihre Wunschentfernung.', 6900, 90,
                '<p>Nach der Montage schießen wir Ihre Waffe auf 100 m (oder Ihre Wunschentfernung) mit Ihrer Munition ein. Sie erhalten ein Schussbild-Protokoll. Munition bitte mitbringen oder bei uns erwerben.</p>'],
            ['reinigung', 'Waffenreinigung', 'Gründliche Grundreinigung inkl. Laufreinigung, Entkupferung und Funktionskontrolle.', 3900, 60,
                '<p>Zerlegen, Reinigen, Entkupfern, Konservieren und Funktionsprüfung. Ideal nach der Jagdsaison oder vor der Einlagerung.</p>'],
            ['reparatur', 'Reparatur & Instandsetzung', 'Fehlersuche, Ersatzteiltausch und Instandsetzung durch den Büchsenmacher.', 5900, 60,
                '<p>Zuführstörungen, Abzugsprobleme, verschlissene Federn: Wir prüfen Ihre Waffe, erstellen einen Kostenvoranschlag und setzen sie fachgerecht instand. Preis je angefangene Stunde zzgl. Material.</p>'],
            ['cerakote', 'Cerakote-Beschichtung', 'Keramikbeschichtung für Verschluss, System und Lauf, in vielen Farben.', 24900, 30,
                '<p>Cerakote schützt vor Korrosion und Abrieb und sieht dabei hervorragend aus. Wir beraten Sie zu Farbe und Muster und übernehmen Demontage, Strahlen und Beschichtung.</p>'],
            ['schaftanpassung', 'Schaftanpassung', 'Schaftverkürzung, Schaftkappe und Backe passend zu Ihrem Anschlag.', 8900, 60,
                '<p>Eine Waffe, die passt, schießt besser. Wir vermessen Ihren Anschlag und passen Länge, Senkung und Schaftbacke an.</p>'],
        ];

        foreach ($services as $i => [$slug, $name, $teaser, $price, $minutes, $description]) {
            WorkshopService::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'teaser' => $teaser,
                'description' => $description,
                'price_from_gross' => $price,
                'duration_minutes' => $minutes,
                'is_bookable' => true,
                'sort_order' => ($i + 1) * 10,
            ]);
        }
    }
}
