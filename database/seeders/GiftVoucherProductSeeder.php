<?php

namespace Database\Seeders;

use App\Models\LegalClass;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\TaxRate;
use Illuminate\Database\Seeder;

/**
 * Produkt „Geschenkgutschein“ und Versandart „per E-Mail“. firstOrCreate: Im Backend geänderte Texte oder Preise
 * bleiben erhalten, deshalb auch auf bestehenden Installationen gefahrlos (läuft zusätzlich als Datenmigration).
 */
class GiftVoucherProductSeeder extends Seeder
{
    public function run(): void
    {
        $free = LegalClass::where('code', 'free')->value('id');
        $tax = TaxRate::where('rate', 19)->value('id') ?? TaxRate::value('id');
        if (! $free || ! $tax) {
            return; // Grunddaten fehlen noch (frische Installation vor dem Seeden)
        }

        ShippingMethod::firstOrCreate(['code' => ShippingMethod::DIGITAL], [
            'name' => 'Versand per E-Mail',
            'description' => 'Geschenkgutscheine erhalten Sie nach Zahlungseingang als PDF per E-Mail. Kostenlos.',
            'carrier' => null,
            'allowed_shipping_modes' => [], // nur für reine Gutscheinbestellungen, siehe CheckoutService::shippingMethodsFor
            'price_gross' => 0,
            'free_from_gross' => null,
            'is_active' => true,
            'sort_order' => 5,
        ]);

        Product::firstOrCreate(['sku' => 'GUTSCHEIN'], [
            'name' => 'Geschenkgutschein',
            'slug' => 'geschenkgutschein',
            'legal_class_id' => $free,
            'tax_rate_id' => $tax, // wird beim Verkauf nicht angewendet (Mehrzweck-Gutschein)
            'product_type' => 'voucher',
            'condition_code' => 'new',
            'price_gross' => 5000,
            'stock_quantity' => 0,
            'availability' => 'in_stock',
            'delivery_time' => 'per E-Mail nach Zahlungseingang',
            'short_description' => 'Das passende Geschenk für Jäger und Sportschützen: Betrag frei wählbar, einlösbar online und im Geschäft, auch in Teilbeträgen.',
            'description' => '<p>Mit dem Geschenkgutschein von Armory Falter verschenken Sie freie Wahl: Optik, Zubehör, Bekleidung, Munition oder die nächste Waffe. Sie erhalten den Gutschein nach Zahlungseingang als PDF im Armory-Falter-Design per E-Mail, auf Wunsch direkt an den Beschenkten.</p>'
                .'<ul><li>Beträge von 25 € bis 250 € oder Wunschbetrag</li><li>Gültig drei Jahre zum Jahresende</li><li>Teileinlösung möglich, das Restguthaben bleibt erhalten</li><li>Einlösbar im Onlineshop und im Geschäft in Wolfschlugen</li></ul>'
                .'<p>Hinweis: Der Gutschein ersetzt keine Erwerbsberechtigung. Erlaubnispflichtige Waffen und Munition geben wir auch bei Zahlung per Gutschein nur an Berechtigte ab.</p>',
            'status' => 'active',
            'published_at' => now(),
        ]);
    }
}
