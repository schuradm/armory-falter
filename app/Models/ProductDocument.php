<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDocument extends Model
{
    protected $fillable = ['product_id', 'type', 'title', 'path', 'language', 'visibility'];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'manual' => 'Bedienungsanleitung',
            'datasheet' => 'Datenblatt',
            'certificate' => 'Zertifikat',
            'exploded_view' => 'Explosionszeichnung',
            default => 'Dokument',
        };
    }
}
