<?php

namespace Database\Seeders;

use App\Enums\ComplianceStatus;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\Reviews\ReviewService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo-Daten: Rabattcode WILLKOMMEN10 (10 % auf Waffenzubehör) und freigegebene Bewertungen.
 * Auch die Demo-Bewertungen stammen von „Käufern“ mit zugestellter Bestellung, damit der Hinweis
 * „nur verifizierte Käufer“ auch für die Demo stimmt. Idempotent, einzeln aufrufbar:
 *   php artisan db:seed --class=ReviewVoucherDemoSeeder
 * Vor dem Livegang Demo-Kunden (@example.invalid) samt Bestellungen und Bewertungen löschen.
 */
class ReviewVoucherDemoSeeder extends Seeder
{
    /** SKU => [[Kunde, Sterne, Titel, Text], …] */
    private const REVIEWS = [
        'ZEI-V4-312-56' => [
            [0, 5, 'Klares Bild bis in die Dämmerung', 'Am Ansitz deutlich mehr Reserven als mein altes 2,5–10x50. Absehen 60 fein genug für den Schuss auf 150 m, Leuchtpunkt stufenlos. Montage hat die Werkstatt direkt mit eingeschossen.'],
            [1, 5, 'Top Beratung, top Glas', 'Hatte zwischen Zeiss und Swarovski geschwankt, im Laden durften wir beide in der Dämmerung vergleichen. Preis-Leistung beim V4 für mich unschlagbar.'],
            [2, 4, 'Sehr gut, Deckel könnten besser sein', 'Optisch einwandfrei und verstellt sauber. Einen Stern Abzug für die mitgelieferten Schutzdeckel, die habe ich gleich gegen Klappdeckel getauscht.'],
            [3, 5, null, 'Schnelle Lieferung, sauber verpackt. Läuft auf der Sauer 100 ohne Probleme.'],
        ],
        'WAL-CLEAN-UNI' => [
            [1, 5, 'Alles drin, was man braucht', 'Passt für 9 mm und .308 gleichermaßen, der Koffer ist stabil. Für den Preis sehr ordentlich.'],
            [3, 4, 'Gutes Set', 'Putzstock aus Messing, Bürsten in brauchbarer Qualität. Ein Adapter für 12/70 fehlte, der wurde ohne Diskussion nachgeschickt.'],
        ],
        'HK-CASE-PISTOL' => [
            [0, 5, 'Solider Koffer', 'Schaum lässt sich gut anpassen, beide Ersatzmagazine passen rein. Schlösser stabil.'],
        ],
        'SIG-ROMEO5' => [
            [2, 4, 'Für den Preis klasse', 'Auf der Flinte für Bewegungsjagd montiert. MOTAC funktioniert zuverlässig, Punkt bei Sonne gut sichtbar.'],
            [0, 3, 'Ok für den Einstieg', 'Funktioniert, aber die Batterieklappe sitzt etwas locker. Für den Stand in Ordnung.'],
        ],
    ];

    private const CUSTOMERS = [
        ['Lukas', 'Maier', 'demo-lukas@example.invalid'],
        ['Sabine', 'Krüger', 'demo-sabine@example.invalid'],
        ['Thomas', 'Weber', 'demo-thomas@example.invalid'],
        ['Andreas', 'Schmid', 'demo-andreas@example.invalid'],
    ];

    public function run(): void
    {
        $accessories = Category::where('path', 'waffenzubehoer')->value('id');
        DiscountCode::firstOrCreate(['code' => 'WILLKOMMEN10'], [
            'description' => 'Demo: 10 % auf Waffenzubehör, einmal je Kunde',
            'type' => 'percent',
            'percent' => 10,
            'usage_limit_per_customer' => 1,
            'applies_to_permit_items' => false,
            'include_category_ids' => $accessories ? [$accessories] : null,
            'is_active' => true,
        ]);

        $products = Product::whereIn('sku', array_keys(self::REVIEWS))->get()->keyBy('sku');
        if ($products->isEmpty()) {
            return; // Demo-Katalog nicht vorhanden
        }

        $customers = collect(self::CUSTOMERS)->map(function (array $c) {
            $user = User::withTrashed()->firstOrNew(['email' => $c[2]]);
            $user->forceFill([
                'customer_type' => 'private', 'first_name' => $c[0], 'last_name' => $c[1],
                'password' => $user->password ?? Str::random(40), 'is_active' => true, 'email_verified_at' => now(),
                'customer_number' => $user->customer_number ?? 'K'.now()->format('y').str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            ])->save();

            return $user;
        });

        $daysAgo = 120;
        foreach (self::REVIEWS as $sku => $reviews) {
            $product = $products[$sku] ?? null;
            if (! $product) {
                continue;
            }
            foreach ($reviews as [$customer, $rating, $title, $body]) {
                $user = $customers[$customer];
                $item = $this->deliveredOrderItem($user, $product, $daysAgo -= 7);
                ProductReview::updateOrCreate(['product_id' => $product->id, 'user_id' => $user->id], [
                    'order_item_id' => $item->id,
                    'rating' => $rating,
                    'title' => $title,
                    'body' => $body,
                    'author_name' => ProductReview::displayName($user),
                    'status' => 'approved',
                    'moderated_at' => now(),
                ])->forceFill(['created_at' => now()->subDays(max(1, $daysAgo - 10))])->save();
            }
            app(ReviewService::class)->refreshProductRating($product->id);
        }
    }

    /** Abgeschlossene Demo-Bestellung des Kunden mit dem Artikel (Kaufnachweis der Bewertung). */
    private function deliveredOrderItem(User $user, Product $product, int $daysAgo): OrderItem
    {
        $number = 'DEMO-'.$user->id.'-'.$product->id;
        $placed = now()->subDays($daysAgo);
        $order = Order::firstOrCreate(['order_number' => $number], [
            'user_id' => $user->id,
            'status' => OrderStatus::Completed,
            'compliance_status' => ComplianceStatus::NotRequired,
            'billing_address' => ['first_name' => $user->first_name, 'last_name' => $user->last_name, 'street' => 'Musterweg', 'house_number' => '1',
                'postal_code' => '72649', 'city' => 'Wolfschlugen', 'country_code' => 'DE'],
            'shipping_method_code' => 'dhl_standard',
            'payment_method_code' => 'prepayment',
            'subtotal_gross' => $product->price_gross,
            'shipping_gross' => 0,
            'total_gross' => $product->price_gross,
            'total_tax' => (int) round($product->price_gross - $product->price_gross / 1.19),
            'terms_accepted_at' => $placed,
            'idempotency_key' => (string) Str::uuid(),
            'placed_at' => $placed,
            'delivered_at' => $placed->copy()->addDays(3),
            'review_requested_at' => $placed->copy()->addDays(10), // keine echte Einladung an Demo-Adressen
        ]);

        return $order->items()->firstOrCreate(['product_id' => $product->id], [
            'sku' => $product->sku, 'name' => $product->name, 'legal_class_code' => 'free', 'quantity' => 1,
            'unit_price_gross' => $product->price_gross, 'tax_rate' => 19, 'line_total_gross' => $product->price_gross,
        ]);
    }
}
