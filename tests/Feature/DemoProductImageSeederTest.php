<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductMedia;
use App\Services\Media\ImageVariants;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DemoProductImageSeeder;
use Database\Seeders\LegalClassSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\TaxRateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Demo-Bilder: jedes Demo-Produkt bekommt ein Hauptbild mit WebP-Varianten, mehrfaches Ausführen erzeugt keine Dubletten. */
class DemoProductImageSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_demo_product_gets_images_with_variants_idempotently(): void
    {
        Storage::fake('public');
        $this->seed([LegalClassSeeder::class, TaxRateSeeder::class, CatalogSeeder::class, ProductSeeder::class]);

        $this->artisan('demo:images')->assertSuccessful();
        $this->artisan('demo:images')->assertSuccessful();

        $expected = collect(DemoProductImageSeeder::IMAGES)->flatten(1)->count();
        $this->assertSame($expected, ProductMedia::count());

        foreach (Product::whereIn('sku', array_keys(DemoProductImageSeeder::IMAGES))->with('media')->get() as $product) {
            $img = $product->primaryImage();
            $this->assertNotNull($img, $product->sku);
            $this->assertSame([2000, 1500], [$img->width, $img->height]);
            $this->assertNotEmpty($img->alt_text);
            $this->assertContains(480, $img->variant_widths);
            Storage::disk('public')->assertExists(ImageVariants::path($img->path, 480));
            // Karten bieten höchstens die 800er-Variante an
            $this->assertStringNotContainsString('-1200.webp', $img->srcset(800));
        }
    }
}
