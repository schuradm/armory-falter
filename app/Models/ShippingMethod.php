<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    public $timestamps = false;

    protected $fillable = ['code', 'name', 'description', 'carrier', 'allowed_shipping_modes', 'price_gross', 'free_from_gross', 'is_active', 'sort_order'];

    protected $casts = ['allowed_shipping_modes' => 'array', 'is_active' => 'boolean'];

    public function priceFor(int $subtotal): int
    {
        return $this->free_from_gross !== null && $subtotal >= $this->free_from_gross ? 0 : $this->price_gross;
    }

    public function isPickup(): bool
    {
        return $this->code === 'pickup';
    }
}
