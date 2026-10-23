<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['contact' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];

    protected static function booted(): void
    {
        // Eindeutig je Ressource und Startzeit, aber nur solange der Termin nicht abgesagt ist
        static::saving(function (Appointment $appointment) {
            $appointment->slot_key = $appointment->status === 'cancelled'
                ? null
                : $appointment->resource.'|'.$appointment->starts_at->format('Y-m-d H:i');
        });
    }

    /** @return BelongsTo<WorkshopService, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(WorkshopService::class, 'workshop_service_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'workshop' => 'Werkstatt',
            'consultation' => 'Beratung',
            'pickup' => 'Abholung',
            default => $this->type,
        };
    }
}
