<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasSeo;

    protected $fillable = ['slug', 'title', 'body', 'template', 'status', 'published_at'];

    protected $casts = ['published_at' => 'datetime'];
}
