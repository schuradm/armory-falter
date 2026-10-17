<?php

use App\Http\Middleware\PageCache;
use App\Models\Product;
use Database\Seeders\DemoProductImageSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Database\Migrations\Migration;

/** Bestehende Demo-Installationen: CZ Scorpion EVO 3 S1 Carbine samt Bild ergänzen (frische Installationen legen sie per Seeder an). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Product::where('sku', 'GLO-G17-G5')->exists() || Product::where('sku', 'CZ-EVO3-S1-C')->exists()) {
            return; // kein Demo-Katalog oder bereits vorhanden
        }
        $seeder = new ProductSeeder;
        $seeder->only = ['CZ-EVO3-S1-C'];
        $seeder->run();
        (new DemoProductImageSeeder)->run();
        PageCache::flush();
    }

    public function down(): void {}
};
