<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['tax_rate' => 'decimal:2', 'options' => 'array'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isGiftVoucher(): bool
    {
        return $this->item_type === 'gift_voucher';
    }

    /** Positionssumme nach anteiligem Rabatt (Bemessungsgrundlage für die USt.). */
    public function netOfDiscount(): int
    {
        return $this->line_total_gross - $this->discount_gross;
    }

    public function weaponItem(): BelongsTo
    {
        return $this->belongsTo(WeaponItem::class);
    }
}
