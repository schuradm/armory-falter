<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Faq extends Model
{
    protected $fillable = ['faqable_type', 'faqable_id', 'group_name', 'question', 'answer', 'sort_order'];

    /** @return MorphTo<\Illuminate\Database\Eloquent\Model, $this> */
    public function faqable(): MorphTo
    {
        return $this->morphTo();
    }
}
