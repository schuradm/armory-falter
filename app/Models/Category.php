<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasSeo;

    protected $fillable = ['parent_id', 'name', 'slug', 'path', 'icon', 'description', 'content_bottom', 'image_path',
        'default_legal_class_id', 'is_active', 'show_on_home', 'sort_order'];

    protected $casts = ['is_active' => 'boolean', 'show_on_home' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (Category $c) {
            $c->path = $c->parent ? $c->parent->path.'/'.$c->slug : $c->slug;
        });
    }

    /** @return BelongsTo<Category, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /** @return HasMany<Category, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->where('is_active', true)->orderBy('sort_order');
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('is_primary');
    }

    /** @return BelongsTo<LegalClass, $this> */
    public function defaultLegalClass(): BelongsTo
    {
        return $this->belongsTo(LegalClass::class, 'default_legal_class_id');
    }

    /** IDs dieser Kategorie und aller Unterkategorien (über den materialisierten Pfad). */
    public function descendantIdsWithSelf(): array
    {
        return static::where('path', $this->path)->orWhere('path', 'like', $this->path.'/%')->pluck('id')->all();
    }

    /** @return list<Category> von der Wurzel bis zu dieser Kategorie */
    public function ancestry(): array
    {
        $chain = [];
        $node = $this;
        while ($node) {
            array_unshift($chain, $node);
            $node = $node->parent;
        }

        return $chain;
    }

    public function url(): string
    {
        return route('shop.category', ['path' => $this->path]);
    }
}
