<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kundenbewertung eines Produkts. Nur verifizierte Käufer (zugestellte/abgeschlossene Bestellung mit dem Artikel),
 * eine Bewertung je Produkt und Kunde. Öffentlich erst nach Freigabe; Text und Sterne ändert das Personal nicht.
 */
class ProductReview extends Model
{
    public const STATUSES = ['pending' => 'Wartet auf Prüfung', 'approved' => 'Veröffentlicht', 'rejected' => 'Abgelehnt'];

    protected $fillable = ['product_id', 'user_id', 'order_item_id', 'rating', 'title', 'body', 'author_name', 'status',
        'rejection_reason', 'moderated_by', 'moderated_at', 'reply', 'replied_by', 'replied_at'];

    protected $casts = ['rating' => 'integer', 'moderated_at' => 'datetime', 'replied_at' => 'datetime'];

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Anzeigename: Vorname und Initiale des Nachnamens („Max T.“). */
    public static function displayName(User $user): string
    {
        $first = trim((string) $user->first_name) ?: 'Kunde';
        $initial = mb_strtoupper(mb_substr(trim((string) $user->last_name), 0, 1));

        return mb_substr($first, 0, 60).($initial !== '' ? " {$initial}." : '');
    }
}
