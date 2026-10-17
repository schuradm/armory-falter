<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = ['author_name', 'author_meta', 'rating', 'body', 'source', 'is_published', 'sort_order'];

    protected $casts = ['is_published' => 'boolean'];
}
