<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Buchung auf einem Geschenkgutschein (Ausgabe, Einlösung, Rückbuchung bei Storno, Korrektur). */
class GiftVoucherTransaction extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = ['issue' => 'Ausgabe', 'redeem' => 'Einlösung', 'release' => 'Rückbuchung (Storno)', 'adjust' => 'Korrektur', 'cancel' => 'Sperrung'];

    protected $fillable = ['gift_voucher_id', 'order_id', 'type', 'amount', 'balance_after', 'note', 'created_by'];

    public function giftVoucher(): BelongsTo
    {
        return $this->belongsTo(GiftVoucher::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
