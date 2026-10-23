<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
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

    /** @param  array{amount?:int, recipient_name?:?string, recipient_email?:?string, message?:?string}  $options  nur Geschenkgutscheine */
    public function add(Product $product, int $quantity = 1, ?int $variantId = null, array $options = []): CartItem
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
        // Die Variante kommt vom Client: nur Varianten genau dieses Artikels zulassen (sonst falscher Preis)
        if ($variantId !== null && ! $product->variants()->whereKey($variantId)->exists()) {
            throw new InvalidArgumentException('Diese Variante gehört nicht zum Artikel.');
        }

        $options = $product->isGiftVoucher() ? $this->giftVoucherOptions($product, $options) : [];
        $key = $options ? sha1(json_encode($options)) : '';

        $cart = $this->current(create: true);
        $item = $cart->items()->firstOrNew(['product_id' => $product->id, 'product_variant_id' => $variantId, 'options_key' => $key]);
        $item->options = $options ?: null;
        $item->quantity = $product->is_unique_item ? 1 : min(99, ($item->exists ? $item->quantity : 0) + $quantity);
        $item->save();
        $cart->touch();
        $this->cart = $cart->fresh();

        return $item;
    }

    /**
     * Geschenkgutschein: Betrag (vorgegeben oder frei in ganzen Euro) und optionale Empfängerangaben.
     *
     * @throws InvalidArgumentException
     */
    private function giftVoucherOptions(Product $product, array $options): array
    {
        $amount = (int) ($options['amount'] ?? $product->currentPrice());
        $min = (int) config('shop.gift_vouchers.min', 1000);
        $max = (int) config('shop.gift_vouchers.max', 100000);
        if ($amount < $min || $amount > $max || $amount % 100 !== 0) {
            throw new InvalidArgumentException('Bitte wählen Sie einen Gutscheinbetrag in ganzen Euro zwischen '
                .Money::format($min).' und '.Money::format($max).'.');
        }
        $email = trim((string) ($options['recipient_email'] ?? ''));
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Bitte prüfen Sie die E-Mail-Adresse des Beschenkten.');
        }

        return array_filter([
            'amount' => $amount,
            'recipient_name' => mb_substr(trim(strip_tags((string) ($options['recipient_name'] ?? ''))), 0, 100) ?: null,
            'recipient_email' => $email ?: null,
            'message' => mb_substr(trim(strip_tags((string) ($options['message'] ?? ''))), 0, 300) ?: null,
        ], fn ($v) => $v !== null);
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
            $existing = $target->items()->firstOrNew(['product_id' => $item->product_id, 'product_variant_id' => $item->product_variant_id, 'options_key' => $item->options_key]);
            $existing->options = $item->options;
            $existing->quantity = min(99, ($existing->exists ? $existing->quantity : 0) + $item->quantity);
            $existing->save();
        }
        $guest->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update(['discount_code' => null, 'gift_voucher_codes' => null]);
        $this->cart = null;
    }
}
