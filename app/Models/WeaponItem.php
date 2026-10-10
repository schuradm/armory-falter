<?php

namespace App\Models;

use App\Support\BlindIndex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Einzelne Waffe mit Seriennummer (Grundlage für Waffenhandelsbuch und NWR-Anzeigen). */
class WeaponItem extends Model
{
    protected $fillable = ['product_id', 'serial_number', 'nwr_id', 'status', 'acquired_from', 'acquired_at'];

    protected $casts = ['serial_number' => 'encrypted', 'acquired_at' => 'date'];

    protected static function booted(): void
    {
        static::saving(function (WeaponItem $item) {
            if ($item->isDirty('serial_number')) {
                $item->serial_bidx = BlindIndex::make($item->serial_number);
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bookEntries(): HasMany
    {
        return $this->hasMany(WeaponBookEntry::class);
    }
}
