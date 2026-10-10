<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BlogPost extends Model
{
    use HasSeo;

    protected $fillable = ['blog_category_id', 'author_id', 'title', 'slug', 'excerpt', 'body', 'cover_path', 'reading_minutes',
        'comments_enabled', 'status', 'published_at'];

    protected $casts = ['comments_enabled' => 'boolean', 'published_at' => 'datetime'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')->where('published_at', '<=', now());
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->comments()->where('status', 'approved')->whereNull('parent_id')->with('replies')->oldest();
    }

    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'faqable')->orderBy('sort_order');
    }
}
