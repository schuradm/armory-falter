<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Services\Cart\CartService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Feature\Concerns\ShopFixtures;
use Tests\TestCase;

/** Warenkorb: Mengen, Bestellbarkeit, Schnellkauf und Übernahme des Gast-Warenkorbs beim Login. */
class CartTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedShop();
    }

    private function product(string $sku): Product
    {
        return Product::where('sku', $sku)->firstOrFail();
    }

    public function test_gleicher_artikel_erhoeht_die_menge_bis_99(): void
    {
        $this->actingAs($this->customer());
        $cart = app(CartService::class);

        $cart->add($this->product('WAL-CLEAN-UNI'), 2);
        $cart->add($this->product('WAL-CLEAN-UNI'), 3);
        $this->assertSame(1, $cart->current()->items()->count());
        $this->assertSame(5, $cart->count());

        $cart->add($this->product('WAL-CLEAN-UNI'), 98);
        $this->assertSame(99, $cart->count());

        // Sonderpreis zählt, nicht der Listenpreis
        $cart->add($this->product('SAU-100-XT-3006'));
        $this->assertSame(99 * 4990 + 109900, $cart->subtotal());

        foreach ([0, 100] as $invalid) {
            try {
                $cart->add($this->product('WAL-CLEAN-UNI'), $invalid);
                $this->fail("Menge {$invalid} wurde angenommen");
            } catch (InvalidArgumentException $e) {
                $this->assertSame('Ungültige Menge.', $e->getMessage());
            }
        }
    }

    public function test_nicht_bestellbare_artikel_werden_abgewiesen(): void
    {
        $this->actingAs($this->customer());
        $cart = app(CartService::class);

        Product::where('sku', 'WAL-CLEAN-UNI')->update(['stock_quantity' => 0]); // „auf Lager“, aber nichts mehr da
        Product::where('sku', 'HK-CASE-PISTOL')->update(['status' => 'inactive']);
        foreach (['RL-PULVER-N140', 'WAL-CLEAN-UNI', 'HK-CASE-PISTOL'] as $sku) {
            try {
                $cart->add($this->product($sku));
                $this->fail("{$sku} ließ sich in den Warenkorb legen");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('nicht online bestellbar', $e->getMessage());
            }
        }

        // Bestellware ohne Bestand bleibt bestellbar
        $cart->add($this->product('CZ-SHADOW2'));
        $this->assertSame(1, $cart->count());
    }

    public function test_einzelstueck_bleibt_bei_menge_eins(): void
    {
        Product::where('sku', 'MER-HELIX-857')->update(['is_unique_item' => true]);
        $this->actingAs($this->customer());
        $cart = app(CartService::class);

        $item = $cart->add($this->product('MER-HELIX-857'), 3);
        $cart->add($this->product('MER-HELIX-857'));
        $cart->update($item->id, 5);

        $this->assertSame(1, $cart->count());
    }

    public function test_menge_aendern_und_fremde_positionen_sind_tabu(): void
    {
        $owner = $this->customer();
        $this->fillCart($owner, ['WAL-CLEAN-UNI' => 1, 'HK-CASE-PISTOL' => 1]);
        $items = Cart::where('user_id', $owner->id)->firstOrFail()->items;

        // Ein anderer Kunde kann die Position nicht ändern
        $this->actingAs($this->customer('Eve', 'Fremd'));
        $this->fillCart(auth()->user(), ['SIG-ROMEO5' => 1]);
        try {
            app(CartService::class)->update($items[0]->id, 50);
            $this->fail('Fremde Warenkorbposition geändert');
        } catch (ModelNotFoundException) {
        }
        app(CartService::class)->remove($items[1]->id);
        $this->assertSame(2, Cart::where('user_id', $owner->id)->sole()->items()->count());

        $this->actingAs($owner);
        $cart = app(CartService::class);
        $cart->update($items[0]->id, 4);
        $this->assertSame(5, $cart->count());
        $cart->update($items[0]->id, 0); // Menge 0 entfernt die Position
        $this->assertSame(1, $cart->count());
    }

    public function test_schnellkauf_als_gast_setzt_warenkorb_cookie(): void
    {
        $response = $this->postJson(route('cart.quick-add'), ['product_id' => $this->product('WAL-CLEAN-UNI')->id, 'quantity' => 2])
            ->assertOk()->assertJson(['count' => 2]);
        $token = $response->getCookie(CartService::COOKIE)?->getValue();
        $this->assertNotNull($token);
        $this->assertSame(2, (int) Cart::where('token', $token)->whereNull('user_id')->sole()->items()->sum('quantity'));

        $this->postJson(route('cart.quick-add'), ['product_id' => $this->product('RL-PULVER-N140')->id])
            ->assertStatus(422)->assertJsonFragment(['message' => 'Dieser Artikel ist derzeit nicht online bestellbar.']);

        // Behördenware ist für Privatkunden unsichtbar
        Product::where('sku', 'HK-CASE-PISTOL')->update(['authority_only' => true]);
        $this->postJson(route('cart.quick-add'), ['product_id' => $this->product('HK-CASE-PISTOL')->id])->assertNotFound();
    }

    public function test_gast_warenkorb_wird_beim_login_zusammengefuehrt(): void
    {
        $user = $this->customer();
        $this->fillCart($user, ['WAL-CLEAN-UNI' => 1]);
        $this->app['auth']->forgetGuards();

        $guest = Cart::create(['token' => 'gast-token-1', 'expires_at' => now()->addDay()]);
        $guest->items()->create(['product_id' => $this->product('WAL-CLEAN-UNI')->id, 'quantity' => 2, 'options_key' => '']);
        $guest->items()->create(['product_id' => $this->product('HK-CASE-PISTOL')->id, 'quantity' => 1, 'options_key' => '']);

        $this->withCookie(CartService::COOKIE, 'gast-token-1')
            ->post('/konto/anmelden', ['email' => $user->email, 'password' => 'password'])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertNull(Cart::find($guest->id), 'Gast-Warenkorb wurde nicht aufgelöst');
        $merged = Cart::where('user_id', $user->id)->sole()->items()->with('product')->get()->pluck('quantity', 'product.sku')->all();
        $this->assertEquals(['WAL-CLEAN-UNI' => 3, 'HK-CASE-PISTOL' => 1], $merged);
    }

    public function test_gast_warenkorb_wird_ohne_kundenwarenkorb_uebernommen(): void
    {
        $user = $this->customer();
        $guest = Cart::create(['token' => 'gast-token-2', 'expires_at' => now()->addDay()]);
        $guest->items()->create(['product_id' => $this->product('SIG-ROMEO5')->id, 'quantity' => 1, 'options_key' => '']);

        app(CartService::class)->mergeGuestCartInto($user, 'gast-token-2');
        app(CartService::class)->mergeGuestCartInto($user, 'unbekannt'); // kein Fehler

        $this->assertSame($user->id, $guest->fresh()->user_id);
        $this->assertSame(1, Cart::count());
    }
}
