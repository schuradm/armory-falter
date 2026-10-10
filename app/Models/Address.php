<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = ['type', 'is_default', 'company_name', 'first_name', 'last_name', 'street', 'house_number',
        'address_extra', 'postal_code', 'city', 'country_code'];

    protected $casts = ['is_default' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toSnapshot(): array
    {
        return $this->only($this->fillable);
    }

    public function oneLine(): string
    {
        return "{$this->first_name} {$this->last_name}, {$this->street} {$this->house_number}, {$this->postal_code} {$this->city}";
    }
}
