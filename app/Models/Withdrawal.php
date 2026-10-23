<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Online erklärter Widerruf (Widerrufsbutton). */
class Withdrawal extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['declared_at' => 'datetime', 'processed_at' => 'datetime'];

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
