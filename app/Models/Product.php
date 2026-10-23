<?php

namespace App\Models;

use App\Enums\Availability;
use App\Enums\Condition;
use App\Enums\ProductType;
use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    use HasSeo;

    protected $fillable = ['sku', 'ean', 'manufacturer_part_number', 'name', 'slug', 'manufacturer_id', 'caliber_id', 'legal_class_id',
        'tax_rate_id', 'product_type', 'weapon_kind', 'condition_code', 'condition_notes', 'is_unique_item', 'usage_hunting',
        'usage_sport', 'authority_only', 'price_gross', 'special_price_gross', 'special_from', 'special_to', 'lowest_price_30d',
        'uvp_gross', 'stock_quantity', 'availability', 'delivery_time', 'weight_grams', 'short_description', 'description',
        'video_url', 'is_featured', 'status', 'published_at'];

    // rating_avg/rating_count pflegt ausschließlich ReviewService::refreshProductRating() (nicht fillable)

    protected $casts = [
        'product_type' => ProductType::class,
        'condition_code' => Condition::class,
        'availability' => Availability::class,
        'is_unique_item' => 'boolean',
        'usage_hunting' => 'boolean',
        'usage_sport' => 'boolean',
        'authority_only' => 'boolean',
        'is_featured' => 'boolean',
        'special_from' => 'datetime',
        'special_to' => 'datetime',
        'published_at' => 'datetime',
        'rating_avg' => 'float',
        'rating_count' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ---- Scopes -------------------------------------------------------

    public function scopeVisible(Builder $query, ?User $viewer = null): Builder
    {
        $query->where('status', 'active');
        if (! $viewer?->isAuthorityOrDealer()) {
            $query->where('authority_only', false);
        }

        return $query;
    }

    // ---- Preise (alle Werte in Cent, brutto) --------------------------

    public function hasActiveSpecialPrice(): bool
    {
        return $this->special_price_gross !== null
            && $this->special_price_gross < $this->price_gross
            && ($this->special_from === null || $this->special_from->isPast())
            && ($this->special_to === null || $this->special_to->isFuture());
    }

    public function currentPrice(): int
    {
        return $this->hasActiveSpecialPrice() ? $this->special_price_gross : $this->price_gross;
    }

    /** Referenzpreis für die Streichpreis-Angabe nach § 11 PAngV (niedrigster Preis der letzten 30 Tage). */
    public function referencePrice(): int
    {
        return $this->lowest_price_30d ?? $this->price_gross;
    }

    public function taxAmount(int $gross): int
    {
        $rate = (float) $this->taxRate->rate;

        return (int) round($gross - $gross / (1 + $rate / 100));
    }

    public function availableStock(): int
    {
        return max(0, $this->stock_quantity - $this->stock_reserved);
    }

    /** Geschenkgutscheine haben keinen Lagerbestand (digital, Code nach Zahlung). */
    public function isGiftVoucher(): bool
    {
        return $this->product_type === ProductType::Voucher;
    }

    public function tracksStock(): bool
    {
        return ! $this->isGiftVoucher() && in_array($this->availability, [Availability::InStock, Availability::LowStock], true);
    }

    public function isPurchasable(): bool
    {
        if ($this->isGiftVoucher()) {
            return $this->status === 'active';
        }

        return $this->status === 'active' && $this->availability->purchasable()
            && ($this->availability !== Availability::InStock && $this->availability !== Availability::LowStock || $this->availableStock() > 0);
    }

    public function primaryImage(): ?ProductMedia
    {
        return $this->media->firstWhere('type', 'image');
    }

    public function primaryCategory(): ?Category
    {
        return $this->categories->firstWhere('pivot.is_primary', true) ?? $this->categories->first();
    }

    public function url(): string
    {
        return route('product.show', $this);
    }

    // ---- Relationen ----------------------------------------------------

    /** @return BelongsTo<Manufacturer, $this> */
    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /** @return BelongsTo<Caliber, $this> */
    public function caliber(): BelongsTo
    {
        return $this->belongsTo(Caliber::class);
    }

    /** @return BelongsTo<LegalClass, $this> */
    public function legalClass(): BelongsTo
    {
        return $this->belongsTo(LegalClass::class);
    }

    /** @return BelongsTo<TaxRate, $this> */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('is_primary');
    }

    /**
     * „Passend dazu“: Zubehör-Empfehlungen (Upsell), im Backend je Produkt gepflegt.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function accessories(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_relations', 'product_id', 'related_product_id')
            ->wherePivot('type', 'accessory')->withPivot('sort_order')->orderByPivot('sort_order');
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /** @return HasMany<ProductMedia, $this> */
    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order');
    }

    /** @return HasMany<ProductDocument, $this> */
    public function downloads(): HasMany
    {
        return $this->hasMany(ProductDocument::class);
    }

    /** @return BelongsToMany<AttributeDefinition, $this> */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(AttributeDefinition::class, 'product_attribute_values')
            ->withPivot(['value_text', 'value_number'])
            ->orderBy('sort_order');
    }

    /** @return HasMany<WeaponItem, $this> */
    public function weaponItems(): HasMany
    {
        return $this->hasMany(WeaponItem::class);
    }

    /** @return HasMany<ProductReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /** @return MorphMany<Faq, $this> */
    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'faqable')->orderBy('sort_order');
    }
}
