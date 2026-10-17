<?php

use Database\Seeders\GiftVoucherProductSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Datenmigration für bestehende Installationen: Produkt „Geschenkgutschein“ und Versandart „per E-Mail“ anlegen.
 * Idempotent (firstOrCreate), überspringt sich auf frischen Datenbanken ohne Rechtsklassen; dort legt sie der Seeder an.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new GiftVoucherProductSeeder)->run();
    }

    public function down(): void
    {
        // Bewusst leer: Produkt kann bereits in Bestellungen stehen
    }
};
