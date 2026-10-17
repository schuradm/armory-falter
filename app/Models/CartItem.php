<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_id', 'product_variant_id', 'quantity', 'options', 'options_key'];

    protected $casts = ['options' => 'array'];

    /** @return BelongsTo<Cart, $this> */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function isGiftVoucher(): bool
    {
        return $this->product->product_type === ProductType::Voucher;
    }

    /** Geschenkgutscheine: frei gewählter Betrag aus den Optionen, sonst Varianten- bzw. Produktpreis. */
    public function unitPrice(): int
    {
        if ($this->isGiftVoucher() && isset($this->options['amount'])) {
            return (int) $this->options['amount'];
        }

        return $this->variant?->price_gross ?? $this->product->currentPrice();
    }

    /** Anzeigename inkl. Gutscheinbetrag, z. B. „Geschenkgutschein 50,00 €“. */
    public function displayName(): string
    {
        return $this->isGiftVoucher() ? 'Geschenkgutschein '.Money::format($this->unitPrice()) : $this->product->name;
    }

    public function lineTotal(): int
    {
        return $this->unitPrice() * $this->quantity;
    }
}
