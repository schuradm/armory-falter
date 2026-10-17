<?php

namespace Database\Seeders;

use App\Http\Middleware\PageCache;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Grunddaten und Demo-Katalog. Alle Seeder sind idempotent (updateOrCreate) und dürfen mehrfach laufen:
 *   php artisan db:seed
 * Model-Events sind stummgeschaltet (kein Seiten-Cache-Flush je Datensatz); abgeleitete Werte
 * (Kategoriepfad, Kundennummer) setzen die Seeder deshalb selbst.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            LegalClassSeeder::class,
            TaxRateSeeder::class,
            ShippingMethodSeeder::class,
            CatalogSeeder::class,
            WorkshopServiceSeeder::class,
            PageSeeder::class,
            FaqSeeder::class,
            ProductSeeder::class,
            DemoProductImageSeeder::class,
            MarketingSeeder::class,
            AdminUserSeeder::class,
        ]);

        // Seiten-, Navigations- und Einstellungs-Cache verwerfen, damit die neuen Daten sofort sichtbar sind
        Cache::forget('settings.v1');
        Cache::forget('sitemap.xml');
        PageCache::flush();
    }
}
