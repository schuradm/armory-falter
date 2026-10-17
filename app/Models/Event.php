<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = ['slug', 'title', 'teaser', 'description', 'location', 'starts_at', 'ends_at', 'capacity', 'price_gross', 'image_path', 'requires_entitlement', 'is_published'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'requires_entitlement' => 'boolean', 'is_published' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<EventRegistration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('is_published', true)->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at');
    }

    public function seatsLeft(): ?int
    {
        if (! $this->capacity) {
            return null;
        }

        return max(0, $this->capacity - (int) $this->registrations()->where('status', 'registered')->sum('persons'));
    }
}
