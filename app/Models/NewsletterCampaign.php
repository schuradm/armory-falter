<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsletterCampaign extends Model
{
    protected $fillable = ['name', 'subject', 'preheader', 'newsletter_segment_id', 'body', 'status', 'scheduled_at', 'sent_at'];

    protected $casts = ['scheduled_at' => 'datetime', 'sent_at' => 'datetime'];

    public function segment(): BelongsTo
    {
        return $this->belongsTo(NewsletterSegment::class, 'newsletter_segment_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(NewsletterEvent::class);
    }
}
