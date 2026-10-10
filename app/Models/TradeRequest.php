<?php

namespace App\Models;

use App\Enums\ItemCondition;
use App\Enums\TradeRequestStatus;
use App\Enums\TradeRequestType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Verkaufsservice (Kommission über egun) oder Ankaufanfrage. */
class TradeRequest extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'type' => TradeRequestType::class,
        'status' => TradeRequestStatus::class,
        'condition_code' => ItemCondition::class,
        'contact' => 'array',
        'privacy_accepted_at' => 'datetime',
    ];

    public function media(): HasMany
    {
        return $this->hasMany(TradeRequestMedia::class)->orderBy('sort_order');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function manufacturerLabel(): string
    {
        return $this->manufacturer?->name ?? (string) $this->manufacturer_name;
    }
}
