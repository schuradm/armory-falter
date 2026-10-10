<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocalPage extends Model
{
    protected $fillable = ['slug', 'city', 'title', 'meta_description', 'headline', 'body', 'faqs', 'distance_km', 'is_published'];

    protected $casts = ['faqs' => 'array', 'is_published' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
