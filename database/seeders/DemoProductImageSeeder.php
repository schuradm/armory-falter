<?php

namespace Database\Seeders;

use App\Http\Middleware\PageCache;
use App\Jobs\GenerateImageVariants;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Services\Media\ImageVariants;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Demo-Produktbilder (Studio-Illustrationen, 2000×1500 WebP) aus resources/demo/products
 * auf die public-Disk kopieren, ProductMedia anlegen/aktualisieren und die WebP-Varianten
 * sofort erzeugen (kein Queue-Worker nötig). Idempotent, auch einzeln aufrufbar:
 *   php artisan demo:images
 * Quellen der Bilder: resources/demo/generator (SVG-Generator, siehe build.mjs).
 */
class DemoProductImageSeeder extends Seeder
{
    /** SKU => [[Datei ohne .webp, Alternativtext], …]; das erste Bild ist das Hauptbild. */
    public const IMAGES = [
        'GLO-G17-G5' => [
            ['glock-17-gen5-fs', 'Glock 17 Gen5 FS, Pistole im Kaliber 9 mm Luger, linke Seite mit Front- und Heckriffelung'],
            ['glock-17-gen5-fs-detail', 'Glock 17 Gen5 FS, Detailansicht von Abzug, Schlittenfanghebel und texturiertem Griff'],
        ],
        'HK-SFP9-SF' => [['heckler-koch-sfp9-sf', 'Heckler & Koch SFP9 SF, Pistole 9 mm Luger mit Griffrücken und Picatinny-Schiene, Seitenansicht']],
        'WAL-PDP-FS45' => [
            ['walther-pdp-full-size-45', 'Walther PDP Full Size 4,5 Zoll, Optics-Ready-Pistole 9 mm Luger, Seitenansicht'],
            ['walther-pdp-full-size-45-detail', 'Walther PDP Full Size, Detail von Optics-Ready-Schlitten und SuperTerrain-Riffelung'],
        ],
        'SIG-P320-X5' => [['sig-sauer-p320-x-five', 'Sig Sauer P320 X-Five, Wettkampfpistole mit silbernem Schlitten, Magwell und rotem Leuchtkorn']],
        'CZ-SHADOW2' => [['cz-shadow-2', 'CZ Shadow 2, Ganzstahl-Wettkampfpistole mit Hahn und Griffschalen, Seitenansicht']],
        'CZ-EVO3-S1-C' => [['cz-scorpion-evo-3-s1', 'CZ Scorpion EVO 3 S1, halbautomatischer Selbstlader 9 mm Luger mit Klappschaft und Picatinny-Schiene, Seitenansicht']],
        'BLA-R8-PRO-308' => [
            ['blaser-r8-professional-success', 'Blaser R8 Professional Success, Geradezugrepetierer mit Lochschaft und Zielfernrohr'],
            ['blaser-r8-professional-success-detail', 'Blaser R8 Professional Success, Detail von Geradezugverschluss, Handspannung und Montage'],
        ],
        'SAU-100-XT-3006' => [['sauer-100-classic-xt', 'Sauer 100 Classic XT, Repetierbüchse mit schwarzem Kunststoffschaft und Zielfernrohr']],
        'MER-HELIX-857' => [['merkel-helix-explorer', 'Merkel Helix Explorer, kompakter Geradezugrepetierer mit Drückjagdglas']],
        'BER-694-12' => [['beretta-694-sporting', 'Beretta 694 Sporting, Bockdoppelflinte mit Nussbaumschaft und silberner Basküle']],
        'CZ-457-VAR-22' => [['cz-457-varmint', 'CZ 457 Varmint, Kleinkaliber-Repetierer mit schwerem Lauf und Buchenschaft']],
        'ZEI-V4-312-56' => [
            ['zeiss-conquest-v4-3-12x56', 'Zeiss Conquest V4 3–12x56, Zielfernrohr mit 56-mm-Objektiv, Seitenansicht'],
            ['zeiss-conquest-v4-3-12x56-detail', 'Zeiss Conquest V4 3–12x56, Detail von Absehenverstellung und Objektivglocke'],
        ],
        'SWA-Z8I-216-50' => [['swarovski-z8i-2-16x50-p-l', 'Swarovski Z8i 2–16x50 P L, Zielfernrohr mit Innenschiene, Seitenansicht']],
        'SIG-ROMEO5' => [['sig-sauer-romeo5-red-dot', 'Sig Sauer Romeo5, kompaktes Leuchtpunktvisier mit Picatinny-Klemmmontage']],
        'SIG-AMMO-9-124' => [['sig-sauer-elite-ball-9mm-luger-124-gr-fmj-50-stuck', 'Schachtel 9 mm Luger 124 gr FMJ mit 50 Patronen, davor drei lose Patronen']],
        'BLA-CDC-308' => [['blaser-cdc-308-win-110-g-20-stuck', 'Schachtel .308 Win mit 20 bleifreien Patronen und Kupfergeschossen']],
        'WAL-BWK2' => [['walther-bwk-2-jagdmesser', 'Walther BWK 2, feststehendes Jagdmesser mit Lederscheide']],
        'WAL-CLEAN-UNI' => [['walther-universal-reinigungsset', 'Universal-Reinigungsset im geöffneten Koffer mit Putzstock, Bürsten, Öl und Pflastern']],
        'HK-CASE-PISTOL' => [['heckler-koch-pistolenkoffer', 'Abschließbarer Pistolenkoffer aus Hartschale mit Tragegriff und Schlossösen']],
        'RL-PULVER-N140' => [['treibladungspulver-n140-500-g', 'Dose Treibladungspulver, 500 g, mit Gefahrstoffkennzeichnung']],
        'BLA-VINTAGE-JKT' => [['blaser-vintage-jacke-herren', 'Blaser Vintage Jacke Herren in Oliv, Vorderansicht mit Brust- und Pattentaschen']],
    ];

    public const SOURCE_DIR = 'resources/demo/products';

    public const TARGET_DIR = 'products/demo';

    public function run(): void
    {
        $disk = Storage::disk('public');
        $variants = app(ImageVariants::class);
        $products = Product::whereIn('sku', array_keys(self::IMAGES))->pluck('id', 'sku');
        $done = 0;

        foreach (self::IMAGES as $sku => $images) {
            if (! isset($products[$sku])) {
                $this->command?->warn("Produkt {$sku} fehlt, übersprungen (zuerst ProductSeeder ausführen).");

                continue;
            }
            foreach ($images as $i => [$file, $alt]) {
                $source = base_path(self::SOURCE_DIR."/{$file}.webp");
                if (! is_file($source)) {
                    $this->command?->warn("Bild fehlt: {$source}");

                    continue;
                }
                $path = self::TARGET_DIR."/{$file}.webp";
                $changed = ! $disk->exists($path) || md5_file($source) !== md5($disk->get($path));
                if ($changed) {
                    $disk->put($path, file_get_contents($source), 'public');
                }

                $media = ProductMedia::withoutEvents(fn () => ProductMedia::updateOrCreate(
                    ['product_id' => $products[$sku], 'path' => $path],
                    ['type' => 'image', 'alt_text' => $alt, 'sort_order' => $i],
                ));

                // Varianten synchron erzeugen (gleiche Logik wie der Job, ohne Warteschlange)
                $missing = collect($media->variant_widths ?? [])->contains(fn ($w) => ! $disk->exists(ImageVariants::path($path, $w)));
                if ($changed || empty($media->variant_widths) || $missing) {
                    (new GenerateImageVariants($media->id))->handle($variants);
                }
                $done++;
            }
        }

        PageCache::flush();
        $this->command?->info("{$done} Demo-Produktbilder bereit (public/storage/".self::TARGET_DIR.').');
    }
}
