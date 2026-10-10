<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caliber extends Model
{
    protected $fillable = ['name', 'slug', 'aliases', 'kind', 'is_large_bore', 'content', 'faqs'];

    protected $casts = ['aliases' => 'array', 'is_large_bore' => 'boolean', 'faqs' => 'array'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function isLonggunCaliber(): bool
    {
        return in_array($this->kind, ['rifle', 'shotgun', 'rimfire'], true);
    }
}
