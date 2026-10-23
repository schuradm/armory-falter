<?php

namespace App\Services\Vouchers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\DiscountCode;
use App\Models\DiscountCodeRedemption;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Rabattcodes: Gültigkeit prüfen, berechtigte Positionen bestimmen, Einlösung beim Bestellen zählen
 * und bei Storno wieder freigeben. Gezählt wird erst mit der Bestellung, nicht beim Eingeben im Warenkorb.
 */
class DiscountCodeService
{
    public function find(string $input): ?DiscountCode
    {
        $code = DiscountCode::normalize($input);

        return $code === '' ? null : DiscountCode::where('code', $code)->first();
    }

    /**
     * Prüft, ob der Code für diesen Warenkorb und Kunden gilt. Ohne Kunde (Gast im Warenkorb) entfällt die
     * Prüfung je Kunde; sie folgt spätestens beim Bestellen.
     *
     * @throws VoucherException
     */
    public function validate(DiscountCode $code, Cart $cart, ?User $user): void
    {
        if (! $code->is_active) {
            throw new VoucherException('Dieser Rabattcode ist nicht mehr gültig.');
        }
        if ($code->starts_at?->isFuture()) {
            throw new VoucherException('Dieser Rabattcode gilt erst ab '.$code->starts_at->format('d.m.Y').'.');
        }
        if ($code->ends_at?->isPast()) {
            throw new VoucherException('Dieser Rabattcode ist am '.$code->ends_at->format('d.m.Y').' abgelaufen.');
        }
        if ($code->usage_limit !== null && $code->times_used >= $code->usage_limit) {
            throw new VoucherException('Dieser Rabattcode wurde bereits so oft eingelöst wie vorgesehen.');
        }
        if ($user && $code->usage_limit_per_customer !== null && $this->usedBy($code, $user) >= $code->usage_limit_per_customer) {
            throw new VoucherException($code->usage_limit_per_customer === 1
                ? 'Sie haben diesen Rabattcode bereits eingelöst.'
                : "Sie haben diesen Rabattcode bereits {$code->usage_limit_per_customer}-mal eingelöst.");
        }

        $cart->loadMissing('items.product.legalClass', 'items.product.categories', 'items.variant');
        // Mindestbestellwert: Warenwert ohne Versand und ohne gekaufte Geschenkgutscheine
        $goods = $cart->items->reject(fn (CartItem $i) => $i->isGiftVoucher())->sum(fn (CartItem $i) => $i->lineTotal());
        if ($code->min_order_gross && $goods < $code->min_order_gross) {
            throw new VoucherException('Dieser Rabattcode gilt ab einem Warenwert von '.Money::format($code->min_order_gross)
                .'. Es fehlen noch '.Money::format($code->min_order_gross - $goods).'.');
        }

        if ($this->eligibleItems($code, $cart)->isEmpty()) {
            $permitOnly = ! $code->applies_to_permit_items
                && $cart->items->contains(fn (CartItem $i) => $i->product->legalClass->requires_entitlement);
            throw new VoucherException($permitOnly
                ? 'Dieser Rabattcode gilt nicht für erlaubnispflichtige Waffen und Munition und für keinen anderen Artikel in Ihrem Warenkorb.'
                : 'Dieser Rabattcode gilt für keinen Artikel in Ihrem Warenkorb.');
        }
    }

    /** Positionen, auf die der Code wirkt. Geschenkgutscheine sind nie rabattfähig. */
    public function eligibleItems(DiscountCode $code, Cart $cart): Collection
    {
        $include = $this->expandCategories($code->include_category_ids ?? []);
        $exclude = $this->expandCategories($code->exclude_category_ids ?? []);
        $includeProducts = array_map('intval', $code->include_product_ids ?? []);
        $excludeProducts = array_map('intval', $code->exclude_product_ids ?? []);

        return $cart->items->filter(function (CartItem $item) use ($code, $include, $exclude, $includeProducts, $excludeProducts) {
            $product = $item->product;
            if ($item->isGiftVoucher()) {
                return false;
            }
            // Standard: kein Rabatt auf erlaubnispflichtige Waffen und Munition (je Code umschaltbar)
            if (! $code->applies_to_permit_items && $product->legalClass->requires_entitlement) {
                return false;
            }
            $categoryIds = $product->categories->pluck('id')->all();
            if (in_array($product->id, $excludeProducts, true) || array_intersect($categoryIds, $exclude)) {
                return false;
            }
            if ($include === [] && $includeProducts === []) {
                return true;
            }

            return in_array($product->id, $includeProducts, true) || array_intersect($categoryIds, $include) !== [];
        })->values();
    }

    /** Kategorie-IDs inklusive aller Unterkategorien (Code für „Zubehör“ gilt auch für dessen Unterkategorien). */
    private function expandCategories(array $ids): array
    {
        $ids = array_map('intval', array_filter($ids));
        if ($ids === []) {
            return [];
        }
        $paths = Category::whereKey($ids)->pluck('path');

        return Category::query()
            ->where(function ($q) use ($paths) {
                foreach ($paths as $path) {
                    $q->orWhere('path', $path)->orWhere('path', 'like', $path.'/%');
                }
            })
            ->pluck('id')->all();
    }

    public function usedBy(DiscountCode $code, User $user): int
    {
        return $code->redemptions()->where('user_id', $user->id)->where('status', 'active')->count();
    }

    /**
     * Einlösung beim Bestellen (innerhalb der Bestell-Transaktion, Code ist gesperrt).
     *
     * @throws VoucherException
     */
    public function redeem(DiscountCode $code, Order $order, User $user, int $amount): DiscountCodeRedemption
    {
        $locked = DiscountCode::lockForUpdate()->findOrFail($code->id);
        if ($locked->usage_limit !== null && $locked->times_used >= $locked->usage_limit) {
            throw new VoucherException('Dieser Rabattcode wurde inzwischen vollständig eingelöst.');
        }
        // Kundenlimit ebenfalls unter Sperre prüfen (zwei parallele Bestellungen desselben Kunden)
        if ($locked->usage_limit_per_customer !== null && $this->usedBy($locked, $user) >= $locked->usage_limit_per_customer) {
            throw new VoucherException('Sie haben diesen Rabattcode bereits eingelöst.');
        }
        $locked->increment('times_used');

        return $locked->redemptions()->create(['order_id' => $order->id, 'user_id' => $user->id, 'amount_gross' => $amount]);
    }

    /** Storno: Einlösung freigeben, damit Gesamt- und Kundenlimit wieder stimmen. Mehrfachaufruf ist harmlos. */
    public function release(Order $order): void
    {
        foreach (DiscountCodeRedemption::where('order_id', $order->id)->where('status', 'active')->lockForUpdate()->get() as $redemption) {
            $redemption->update(['status' => 'released', 'released_at' => now()]);
            DiscountCode::whereKey($redemption->discount_code_id)->where('times_used', '>', 0)->decrement('times_used');
        }
    }
}
