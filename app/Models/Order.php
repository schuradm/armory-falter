<?php

namespace App\Models;

use App\Enums\ComplianceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status' => OrderStatus::class,
        'compliance_status' => ComplianceStatus::class,
        'payment_method_code' => PaymentMethod::class,
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'terms_accepted_at' => 'datetime',
        'legal_declaration_accepted_at' => 'datetime',
        'placed_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function complianceChecks(): HasMany
    {
        return $this->hasMany(OrderComplianceCheck::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_code', 'code');
    }

    public function requiresCompliance(): bool
    {
        return $this->compliance_status !== ComplianceStatus::NotRequired;
    }
}
