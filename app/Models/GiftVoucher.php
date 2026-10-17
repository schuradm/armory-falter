<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Geschenkgutschein als Mehrzweck-Gutschein (§ 3 Abs. 15 UStG): beim Verkauf keine Umsatzsteuer,
 * sie entsteht erst bei Einlösung auf die gelieferte Ware. Restguthaben bleibt bis zum Ablauf erhalten.
 */
class GiftVoucher extends Model
{
    public const STATUSES = ['active' => 'Aktiv', 'redeemed' => 'Eingelöst', 'cancelled' => 'Gesperrt'];

    protected $fillable = ['code', 'initial_value', 'balance', 'status', 'order_id', 'order_item_id', 'purchaser_id',
        'recipient_name', 'recipient_email', 'message', 'expires_at', 'pdf_path', 'sent_at'];

    protected $casts = ['expires_at' => 'date', 'sent_at' => 'datetime'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchaser_id')->withTrashed();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(GiftVoucherTransaction::class)->orderByDesc('id');
    }

    public static function normalize(?string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code));
    }

    /** Lesbare Schreibweise in Vierergruppen: ABCD-EFGH-JKLM-NPQR. */
    public function formattedCode(): string
    {
        return implode('-', str_split($this->code, 4));
    }

    public function isExpired(): bool
    {
        return $this->expires_at->endOfDay()->isPast();
    }

    public function isRedeemable(): bool
    {
        return $this->status === 'active' && $this->balance > 0 && ! $this->isExpired();
    }

    public function statusLabel(): string
    {
        if ($this->status === 'active' && $this->isExpired()) {
            return 'Abgelaufen';
        }

        return self::STATUSES[$this->status] ?? $this->status;
    }
}
