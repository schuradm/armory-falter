<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rabattcode (Prozent oder fester Betrag). Die Gültigkeitsregeln prüft DiscountCodeService,
 * die Verteilung auf Positionen und Steuersätze TotalsCalculator.
 */
class DiscountCode extends Model
{
    protected $fillable = ['code', 'description', 'type', 'percent', 'amount_gross', 'min_order_gross', 'starts_at', 'ends_at',
        'usage_limit', 'usage_limit_per_customer', 'applies_to_permit_items', 'include_category_ids', 'exclude_category_ids',
        'include_product_ids', 'exclude_product_ids', 'is_active'];

    protected $casts = [
        'percent' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'applies_to_permit_items' => 'boolean',
        'include_category_ids' => 'array',
        'exclude_category_ids' => 'array',
        'include_product_ids' => 'array',
        'exclude_product_ids' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(fn (DiscountCode $c) => $c->code = self::normalize($c->code));
    }

    /** Groß, ohne Leerzeichen und Bindestriche: „willkommen-10“ = „WILLKOMMEN10“. */
    public static function normalize(?string $code): string
    {
        return mb_strtoupper(preg_replace('/[\s\-_]+/u', '', (string) $code));
    }

    /** @return HasMany<DiscountCodeRedemption, $this> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(DiscountCodeRedemption::class);
    }

    public function label(): string
    {
        return $this->type === 'percent'
            ? rtrim(rtrim(number_format((float) $this->percent, 2, ',', ''), '0'), ',').' %'
            : Money::format((int) $this->amount_gross);
    }
}
