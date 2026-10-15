<?php

namespace Database\Seeders;

use App\Enums\ShippingMode as M;
use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

/** Versandarten. Ein Warenkorb nimmt den strengsten Versandmodus seiner Artikel (ShippingMode::strictest). */
class ShippingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'code' => 'dhl_standard',
                'name' => 'DHL Paket',
                'description' => 'Zustellung in 1–3 Werktagen. Versandkostenfrei ab 150 €.',
                'carrier' => 'dhl',
                'allowed_shipping_modes' => [M::Standard->value],
                'price_gross' => 595,
                'free_from_gross' => (int) config('shop.free_shipping_from', 15000),
                'sort_order' => 10,
            ],
            [
                'code' => 'dhl_age_check',
                'name' => 'DHL Paket mit Alterssichtprüfung 18+',
                'description' => 'Übergabe nur an Personen ab 18 Jahren gegen Ausweis. Zustellung in 1–3 Werktagen.',
                'carrier' => 'dhl',
                'allowed_shipping_modes' => [M::AgeCheck->value],
                'price_gross' => 990,
                'free_from_gross' => null,
                'sort_order' => 20,
            ],
            [
                'code' => 'weapon_personal_overnight',
                'name' => 'Waffenversand mit persönlicher Übergabe',
                'description' => 'Versicherter Express-Versand, Übergabe nur persönlich an den Erwerber gegen Ausweis. Versand erst nach Freigabe durch unsere Waffensachbearbeitung.',
                'carrier' => 'dhl_express',
                'allowed_shipping_modes' => [M::WeaponPersonal->value],
                'price_gross' => 3900,
                'free_from_gross' => null,
                'sort_order' => 30,
            ],
            [
                'code' => 'pickup',
                'name' => 'Abholung in Wolfschlugen',
                'description' => 'Kostenlos. Wir melden uns, sobald Ihre Bestellung bereitliegt. Bitte Ausweis und ggf. Erwerbsberechtigung mitbringen.',
                'carrier' => null,
                'allowed_shipping_modes' => [M::Standard->value, M::AgeCheck->value, M::WeaponPersonal->value, M::PickupOnly->value],
                'price_gross' => 0,
                'free_from_gross' => null,
                'sort_order' => 40,
            ],
        ];

        foreach ($methods as $method) {
            ShippingMethod::updateOrCreate(['code' => $method['code']], $method + ['is_active' => true]);
        }
    }
}
