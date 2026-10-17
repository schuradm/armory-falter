<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountCodeRedemption extends Model
{
    protected $fillable = ['discount_code_id', 'order_id', 'user_id', 'amount_gross', 'status', 'released_at'];

    protected $casts = ['released_at' => 'datetime'];

    /** @return BelongsTo<DiscountCode, $this> */
    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
