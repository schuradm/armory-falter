<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopService extends Model
{
    protected $fillable = ['slug', 'name', 'teaser', 'description', 'price_from_gross', 'duration_minutes', 'is_bookable', 'sort_order'];

    protected $casts = ['is_bookable' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
