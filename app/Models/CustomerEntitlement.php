<?php

namespace App\Models;

use App\Enums\EntitlementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerEntitlement extends Model
{
    protected $fillable = ['user_id', 'document_id', 'type', 'weapon_kind', 'caliber_id', 'quantity_total', 'quantity_used',
        'valid_from', 'valid_until', 'status', 'created_by'];

    protected $casts = [
        'type' => EntitlementType::class,
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', today()))
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhereDate('valid_from', '<=', today()));
    }

    public function remaining(): ?int
    {
        return $this->quantity_total === null ? null : max(0, $this->quantity_total - $this->quantity_used);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(CustomerDocument::class, 'document_id');
    }

    public function caliber(): BelongsTo
    {
        return $this->belongsTo(Caliber::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function describe(): string
    {
        $parts = [$this->type->label()];
        if ($this->weapon_kind && $this->weapon_kind !== 'any') {
            $parts[] = ucfirst($this->weapon_kind);
        }
        if ($this->caliber) {
            $parts[] = $this->caliber->name;
        }
        if ($this->quantity_total !== null) {
            $parts[] = "{$this->remaining()} von {$this->quantity_total} verfügbar";
        }
        if ($this->valid_until) {
            $parts[] = 'gültig bis '.$this->valid_until->format('d.m.Y');
        }

        return implode(' · ', $parts);
    }
}
