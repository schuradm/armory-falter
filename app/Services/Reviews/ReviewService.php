<?php

namespace App\Services\Reviews;

use App\Enums\OrderStatus;
use App\Http\Middleware\PageCache;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Produktbewertungen nur von verifizierten Käufern (§ 5b Abs. 3 UWG: Wer Bewertungen zugänglich macht, muss angeben,
 * ob und wie er sicherstellt, dass sie von Kunden stammen, die das Produkt tatsächlich erworben haben).
 * Nachweis: eigene Bestellung mit dem Artikel im Status „zugestellt“ oder „abgeschlossen“. Keine Gegenleistung
 * für Bewertungen, keine gekauften oder selbst verfassten Bewertungen (Nr. 23b/23c Anhang zu § 3 Abs. 3 UWG).
 */
class ReviewService
{
    public const DELIVERED = [OrderStatus::Delivered, OrderStatus::Completed];

    public function __construct(private AuditLogger $audit) {}

    /** Bestellposition, die den Kauf belegt (jüngste zugestellte), oder null. */
    public function proofOfPurchase(User $user, Product $product): ?OrderItem
    {
        return OrderItem::query()
            ->where('product_id', $product->id)
            ->where('item_type', 'product')
            ->whereHas('order', fn ($q) => $q->where('user_id', $user->id)->whereIn('status', self::DELIVERED))
            ->latest('id')
            ->first();
    }

    public function canReview(User $user, Product $product): bool
    {
        return ! $product->isGiftVoucher()
            && ! ProductReview::where('product_id', $product->id)->where('user_id', $user->id)->exists()
            && $this->proofOfPurchase($user, $product) !== null;
    }

    /** Gekaufte, zugestellte und noch nicht bewertete Produkte des Kunden. */
    public function reviewableProducts(User $user): Collection
    {
        return Product::query()
            ->whereIn('id', OrderItem::query()->select('product_id')->where('item_type', 'product')
                ->whereHas('order', fn ($q) => $q->where('user_id', $user->id)->whereIn('status', self::DELIVERED)))
            ->whereNotIn('id', ProductReview::query()->select('product_id')->where('user_id', $user->id))
            ->where('product_type', '!=', 'voucher')
            ->with('media')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array{rating:int, title?:?string, body?:?string}  $data
     *
     * @throws InvalidArgumentException
     */
    public function submit(User $user, Product $product, array $data): ProductReview
    {
        $rating = (int) ($data['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Bitte vergeben Sie 1 bis 5 Sterne.');
        }

        return DB::transaction(function () use ($user, $product, $data, $rating) {
            if (ProductReview::where('product_id', $product->id)->where('user_id', $user->id)->lockForUpdate()->exists()) {
                throw new InvalidArgumentException('Sie haben diesen Artikel bereits bewertet.');
            }
            $proof = $this->proofOfPurchase($user, $product);
            if (! $proof || $product->isGiftVoucher()) {
                throw new InvalidArgumentException('Bewerten können nur Kunden, die den Artikel bei uns gekauft und erhalten haben.');
            }

            $review = ProductReview::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'order_item_id' => $proof->id,
                'rating' => $rating,
                'title' => self::clean($data['title'] ?? null, 120),
                'body' => self::clean($data['body'] ?? null, 3000),
                'author_name' => ProductReview::displayName($user),
                'status' => 'pending',
            ]);
            $this->audit->log('review.submitted', $review, new: ['product_id' => $product->id, 'rating' => $rating]);

            return $review;
        });
    }

    /** Freigabe. Alle Bewertungen, die nicht gegen Recht oder Nutzungsregeln verstoßen, werden veröffentlicht – auch negative. */
    public function approve(ProductReview $review, User $by): void
    {
        $review->update(['status' => 'approved', 'rejection_reason' => null, 'moderated_by' => $by->id, 'moderated_at' => now()]);
        $this->audit->log('review.approved', $review);
        $this->refreshProductRating($review->product_id);
    }

    /** Ablehnung nur mit Grund (z. B. Beleidigung, personenbezogene Daten Dritter, kein Produktbezug). */
    public function reject(ProductReview $review, User $by, string $reason): void
    {
        $review->update(['status' => 'rejected', 'rejection_reason' => mb_substr(trim($reason), 0, 255), 'moderated_by' => $by->id, 'moderated_at' => now()]);
        $this->audit->log('review.rejected', $review, new: ['reason' => $reason]);
        $this->refreshProductRating($review->product_id);
    }

    /** Öffentliche Antwort des Shops (leer = Antwort entfernen). */
    public function reply(ProductReview $review, User $by, ?string $text): void
    {
        $text = self::clean($text, 2000);
        $review->update(['reply' => $text, 'replied_by' => $text ? $by->id : null, 'replied_at' => $text ? now() : null]);
        if ($review->status === 'approved') {
            PageCache::flush();
        }
    }

    public function delete(ProductReview $review): void
    {
        $productId = $review->product_id;
        $review->delete();
        $this->refreshProductRating($productId);
    }

    /** Durchschnitt und Anzahl der freigegebenen Bewertungen am Produkt speichern, Seiten-Cache leeren. */
    public function refreshProductRating(int $productId): void
    {
        $stats = ProductReview::approved()->where('product_id', $productId)
            ->selectRaw('COUNT(*) AS c, AVG(rating) AS a')->toBase()->first();
        // Direkt per Query: Produkt-Events (Seiten-Cache) laufen unten ohnehin, updated_at bleibt unverändert
        Product::whereKey($productId)->toBase()->update([
            'rating_count' => (int) $stats->c,
            'rating_avg' => $stats->c ? round((float) $stats->a, 2) : null,
        ]);
        PageCache::flush();
        Cache::forget('home.v2'); // Produktkarten der Startseite
    }

    /** @return array<int,int> Sterne (5…1) => Anzahl freigegebener Bewertungen */
    public function distribution(Product $product): array
    {
        $counts = ProductReview::approved()->where('product_id', $product->id)
            ->selectRaw('rating, COUNT(*) AS c')->groupBy('rating')->pluck('c', 'rating');

        return collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($s) => [$s => (int) ($counts[$s] ?? 0)])->all();
    }

    private static function clean(?string $text, int $max): ?string
    {
        $text = trim(strip_tags((string) $text));

        return $text === '' ? null : mb_substr($text, 0, $max);
    }
}
