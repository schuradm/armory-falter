<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsletterCampaign extends Model
{
    protected $fillable = ['name', 'subject', 'preheader', 'newsletter_segment_id', 'body', 'status', 'scheduled_at', 'sent_at'];

    protected $casts = ['scheduled_at' => 'datetime', 'sent_at' => 'datetime'];

    /** @return BelongsTo<NewsletterSegment, $this> */
    public function segment(): BelongsTo
    {
        return $this->belongsTo(NewsletterSegment::class, 'newsletter_segment_id');
    }

    /** @return HasMany<NewsletterEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(NewsletterEvent::class);
    }
}
