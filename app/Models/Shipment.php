<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['shipped_at' => 'datetime', 'delivered_at' => 'datetime', 'last_tracking_sync_at' => 'datetime'];

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<ShippingMethod, $this> */
    public function method(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    /** @return HasMany<ShipmentEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class)->latest('occurred_at');
    }

    public function trackingUrl(): ?string
    {
        if (! $this->tracking_number) {
            return null;
        }

        return match ($this->carrier) {
            'dhl' => 'https://www.dhl.de/de/privatkunden/pakete-empfangen/verfolgen.html?piececode='.urlencode($this->tracking_number),
            default => null,
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'created' => 'Versand vorbereitet',
            'label_printed' => 'Paketschein erstellt',
            'handed_over' => 'An Versanddienstleister übergeben',
            'in_transit' => 'Unterwegs',
            'out_for_delivery' => 'In Zustellung',
            'delivered' => 'Zugestellt',
            'failed_attempt' => 'Zustellversuch fehlgeschlagen',
            'returned' => 'Rücksendung',
            'picked_up' => 'Abgeholt',
            default => $this->status,
        };
    }
}
