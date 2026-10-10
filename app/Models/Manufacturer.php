<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Manufacturer extends Model
{
    use HasSeo;

    protected $fillable = ['name', 'slug', 'logo_path', 'hero_image_path', 'country_code', 'founded_year', 'intro', 'content', 'faqs',
        'is_featured', 'sort_order'];

    protected $casts = ['is_featured' => 'boolean', 'faqs' => 'array'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'faqable')->orderBy('sort_order');
    }
}
