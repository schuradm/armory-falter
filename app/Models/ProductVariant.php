<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'sku', 'options', 'price_gross', 'stock_quantity'];

    protected $casts = ['options' => 'array'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function label(): string
    {
        return collect($this->options)->map(fn ($v, $k) => ucfirst($k).': '.$v)->implode(', ');
    }
}
