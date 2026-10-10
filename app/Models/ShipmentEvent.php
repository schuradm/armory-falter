<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['occurred_at' => 'datetime'];
}
