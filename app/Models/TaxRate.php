<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'rate'];

    protected $casts = ['rate' => 'decimal:2'];
}
