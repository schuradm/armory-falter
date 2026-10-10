<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CartService
{
    public const COOKIE = 'af_cart';

    private ?Cart $cart = null;

    public function current(bool $create = false): ?Cart
    {
        if ($this->cart) {
            return $this->cart;
        }

        $user = Auth::user();
        $token = request()->cookie(self::COOKIE) ?? session(self::COOKIE);

        $cart = $user
            ? Cart::where('user_id', $user->id)->latest('id')->first()
            : ($token ? Cart::where('token', $token)->whereNull('user_id')->first() : null);

        if (! $cart && $create) {
            $cart = Cart::create([
                'token' => (string) Str::uuid(),
                'user_id' => $user?->id,
                'expires_at' => now()->addDays(30),
            ]);
            session([self::COOKIE => $cart->token]);
            Cookie::queue(self::COOKIE, $cart->token, 60 * 24 * 30, null, null, null, true, false, 'lax');
        }

        return $this->cart = $cart;
    }

    public function add(Product $product, int $quantity = 1, ?int $variantId = null): CartItem
    {
        if ($quantity < 1 || $quantity > 99) {
            throw new InvalidArgumentException('Ungültige Menge.');
        }
        if (! $product->isPurchasable()) {
            throw new InvalidArgumentException('Dieser Artikel ist derzeit nicht online bestellbar.');
        }
        if ($product->is_unique_item) {
            $quantity = 1;
        }

        $cart = $this->current(create: true);
        $item = $cart->items()->firstOrNew(['product_id' => $product->id, 'product_variant_id' => $variantId]);
        $item->quantity = $product->is_unique_item ? 1 : min(99, ($item->exists ? $item->quantity : 0) + $quantity);
        $item->save();
        $cart->touch();
        $this->cart = $cart->fresh();

        return $item;
    }

    public function update(int $itemId, int $quantity): void
    {
        $item = $this->current()?->items()->findOrFail($itemId);
        if ($quantity < 1) {
            $item->delete();

            return;
        }
        $item->update(['quantity' => $item->product->is_unique_item ? 1 : min(99, $quantity)]);
        $this->cart = null;
    }

    public function remove(int $itemId): void
    {
        $this->current()?->items()->whereKey($itemId)->delete();
        $this->cart = null;
    }

    public function count(): int
    {
        return (int) $this->current()?->items()->sum('quantity');
    }

    public function subtotal(?Cart $cart = null): int
    {
        $cart ??= $this->current();
        if (! $cart) {
            return 0;
        }
        $cart->loadMissing('items.product', 'items.variant');

        return $cart->items->sum(fn (CartItem $i) => $i->lineTotal());
    }

    /** Gast-Warenkorb nach dem Login in das Kundenkonto übernehmen. */
    public function mergeGuestCartInto(User $user, ?string $token): void
    {
        if (! $token) {
            return;
        }
        $guest = Cart::where('token', $token)->whereNull('user_id')->with('items')->first();
        if (! $guest) {
            return;
        }
        $target = Cart::where('user_id', $user->id)->latest('id')->first();
        if (! $target) {
            $guest->update(['user_id' => $user->id]);

            return;
        }
        foreach ($guest->items as $item) {
            $existing = $target->items()->firstOrNew(['product_id' => $item->product_id, 'product_variant_id' => $item->product_variant_id]);
            $existing->quantity = min(99, ($existing->exists ? $existing->quantity : 0) + $item->quantity);
            $existing->save();
        }
        $guest->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $this->cart = null;
    }
}
