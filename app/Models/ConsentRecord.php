<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsentRecord extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['consent_id', 'user_id', 'categories', 'banner_version', 'ip_hash'];

    protected $casts = ['categories' => 'array'];
}
