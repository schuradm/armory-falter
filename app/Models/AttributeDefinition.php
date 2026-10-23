<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property-read Pivot&object{value_text: string|null, value_number: string|null} $pivot
 */
class AttributeDefinition extends Model
{
    public $timestamps = false;

    protected $fillable = ['code', 'name', 'unit', 'data_type', 'is_filterable', 'group_name', 'sort_order'];

    protected $casts = ['is_filterable' => 'boolean'];
}
