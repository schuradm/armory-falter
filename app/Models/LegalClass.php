<?php

namespace App\Models;

use App\Enums\EntitlementType;
use App\Enums\PaymentMethod;
use App\Enums\ShippingMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rechtsklasse eines Produkts: Altersgrenze, nötige Erwerbsberechtigung, Versand- und Zahlungsregeln.
 * Die Werte werden im Admin gepflegt und sind vor Go-live anwaltlich zu bestätigen.
 */
class LegalClass extends Model
{
    protected $fillable = ['code', 'name', 'min_age', 'requires_entitlement', 'accepted_entitlements', 'shipping_mode',
        'allowed_payment_codes', 'requires_serial', 'notice_text'];

    protected $casts = [
        'requires_entitlement' => 'boolean',
        'requires_serial' => 'boolean',
        'accepted_entitlements' => 'array',
        'allowed_payment_codes' => 'array',
        'shipping_mode' => ShippingMode::class,
    ];

    /** @return list<EntitlementType> */
    public function acceptedEntitlementTypes(): array
    {
        return array_map(fn ($v) => EntitlementType::from($v), $this->accepted_entitlements ?? []);
    }

    /** @return list<PaymentMethod> */
    public function allowedPaymentMethods(): array
    {
        return array_values(array_filter(array_map(fn ($v) => PaymentMethod::tryFrom($v), $this->allowed_payment_codes ?? [])));
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
