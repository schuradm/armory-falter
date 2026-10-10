<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeRequestMedia extends Model
{
    public $timestamps = false;

    protected $table = 'trade_request_media';

    protected $fillable = ['trade_request_id', 'path', 'sort_order'];
}
