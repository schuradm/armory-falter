<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Author extends Model
{
    protected $fillable = ['user_id', 'name', 'slug', 'role_title', 'bio', 'avatar_path', 'same_as'];

    protected $casts = ['same_as' => 'array'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }
}
