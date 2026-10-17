<?php

namespace App\Models;

use App\Enums\ComplianceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'review_invite_allowed' => 'boolean',
        'delivered_at' => 'datetime',
        'review_requested_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<OrderComplianceCheck, $this> */
    public function complianceChecks(): HasMany
    {
        return $this->hasMany(OrderComplianceCheck::class);
    }

    /** @return HasMany<OrderStatusHistory, $this> */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<Shipment, $this> */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /** @return BelongsTo<ShippingMethod, $this> */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_code', 'code');
    }

    /** @return HasOne<DiscountCodeRedemption, $this> */
    public function discountRedemption(): HasOne
    {
        return $this->hasOne(DiscountCodeRedemption::class)->latestOfMany();
    }

    /** Buchungen auf Geschenkgutscheinen, mit denen diese Bestellung (teil-)bezahlt wurde. */
    public function giftVoucherTransactions(): HasMany
    {
        return $this->hasMany(GiftVoucherTransaction::class);
    }

    /** Mit dieser Bestellung gekaufte Geschenkgutscheine. */
    public function issuedGiftVouchers(): HasMany
    {
        return $this->hasMany(GiftVoucher::class);
    }

    /** Noch zu zahlen: Gesamtbetrag abzüglich eingelöster Geschenkgutscheine (die wie eine Zahlung wirken). */
    public function amountDue(): int
    {
        return max(0, $this->total_gross - $this->gift_voucher_gross);
    }

    /**
     * USt-Ausweis je Steuersatz: Bruttobetrag nach Rabatt, Netto und enthaltene Steuer. Versand mit Regelsteuersatz.
     * Gekaufte Geschenkgutscheine (Mehrzweck-Gutscheine) sind beim Verkauf nicht steuerbar und fehlen hier.
     * Eingelöste Gutscheine sind Zahlungen und mindern die Bemessungsgrundlage nicht.
     *
     * @return array<string, array{gross:int, net:int, tax:int}> Schlüssel = Steuersatz („19“, „7“)
     */
    public function vatBreakdown(): array
    {
        $gross = [];
        foreach ($this->items as $item) {
            if ($item->isGiftVoucher()) {
                continue;
            }
            $rate = (string) (float) $item->tax_rate;
            $gross[$rate] = ($gross[$rate] ?? 0) + $item->line_total_gross - $item->discount_gross;
        }
        if ($this->shipping_gross > 0) {
            $gross['19'] = ($gross['19'] ?? 0) + $this->shipping_gross;
        }
        ksort($gross);

        $result = [];
        foreach ($gross as $rate => $g) {
            $net = (int) round($g / (1 + ((float) $rate) / 100));
            $result[(string) $rate] = ['gross' => $g, 'net' => $net, 'tax' => $g - $net];
        }

        return $result;
    }

    public function requiresCompliance(): bool
    {
        return $this->compliance_status !== ComplianceStatus::NotRequired;
    }
}
