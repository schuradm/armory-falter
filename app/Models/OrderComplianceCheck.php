<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderComplianceCheck extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['details' => 'array', 'checked_at' => 'datetime'];

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<OrderItem, $this> */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /** @return BelongsTo<CustomerEntitlement, $this> */
    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(CustomerEntitlement::class, 'customer_entitlement_id');
    }

    /** @return BelongsTo<User, $this> */
    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
