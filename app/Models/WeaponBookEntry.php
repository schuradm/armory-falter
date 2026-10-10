<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/** Eintrag im elektronischen Waffenhandelsbuch (§ 23 WaffG). Unveränderlich: Korrektur nur per Gegenbuchung. */
class WeaponBookEntry extends Model
{
    public $timestamps = false;

    protected $fillable = ['weapon_item_id', 'direction', 'counterparty', 'permit_reference', 'order_id', 'nwr_reported_at',
        'nwr_reference', 'booked_by', 'booked_at'];

    protected $casts = ['counterparty' => 'encrypted:array', 'booked_at' => 'datetime', 'nwr_reported_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(function (WeaponBookEntry $e) {
            if (array_diff(array_keys($e->getDirty()), ['nwr_reported_at', 'nwr_reference'])) {
                throw new LogicException('Waffenbuch-Einträge sind unveränderlich. Bitte Gegenbuchung anlegen.');
            }
        });
        static::deleting(fn () => throw new LogicException('Waffenbuch-Einträge können nicht gelöscht werden.'));
    }

    public function weaponItem(): BelongsTo
    {
        return $this->belongsTo(WeaponItem::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function booker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by');
    }
}
