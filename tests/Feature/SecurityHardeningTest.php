<?php

namespace Tests\Feature;

use App\Enums\EntitlementType;
use App\Filament\Resources\AuditLogResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\WeaponBookEntryResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Compliance\ComplianceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Feature\Concerns\ShopFixtures;
use Tests\TestCase;

/** Befunde aus der Sicherheitsprüfung: jede Korrektur bleibt durch einen Test abgesichert. */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedShop();
    }

    private function freeProduct(): Product
    {
        return Product::where('status', 'active')->whereHas('legalClass', fn ($q) => $q->where('requires_entitlement', false)->where('min_age', 0))
            ->get()->first(fn (Product $p) => ! $p->isGiftVoucher() && $p->isPurchasable()) ?? throw new \RuntimeException('Kein freies Testprodukt');
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_staff' => true, 'is_active' => true, 'two_factor_confirmed_at' => now()])->save();
        $user->assignRole($role);

        return $user;
    }

    public function test_variant_of_another_product_is_rejected(): void
    {
        $product = $this->freeProduct();
        $other = Product::whereKeyNot($product->id)->firstOrFail();
        $foreign = ProductVariant::create(['product_id' => $other->id, 'sku' => 'FREMD-1', 'options' => ['Ausführung' => 'Test'], 'price_gross' => 100, 'stock_quantity' => 5]);

        $this->actingAs($this->customer());
        $this->expectException(InvalidArgumentException::class);
        app(CartService::class)->add($product, 1, $foreign->id);
    }

    public function test_own_variant_is_accepted_and_priced(): void
    {
        $product = $this->freeProduct();
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'EIGEN-1', 'options' => ['Ausführung' => 'Test'], 'price_gross' => 4321, 'stock_quantity' => 5]);

        $this->actingAs($this->customer());
        $item = app(CartService::class)->add($product, 1, $variant->id);

        $this->assertSame($variant->id, $item->product_variant_id);
        $this->assertSame(4321, $item->unitPrice());
    }

    public function test_backend_resources_follow_role_permissions(): void
    {
        $this->actingAs($this->staff('shop-manager'));
        $this->get(ProductResource::getUrl('index'))->assertOk();
        $this->get(OrderResource::getUrl('index'))->assertOk();
        $this->get(WeaponBookEntryResource::getUrl('index'))->assertForbidden();
        $this->get(AuditLogResource::getUrl('index'))->assertForbidden();

        $this->actingAs($this->staff('compliance'));
        $this->get(WeaponBookEntryResource::getUrl('index'))->assertOk();
        $this->get(AuditLogResource::getUrl('index'))->assertOk();
        $this->get(ProductResource::getUrl('index'))->assertForbidden();
    }

    public function test_self_declared_dealer_does_not_unlock_authority_products(): void
    {
        $product = $this->freeProduct();
        $product->update(['authority_only' => true]);
        $dealer = $this->customer();
        $dealer->forceFill(['customer_type' => 'dealer'])->save();

        $this->assertFalse(Product::visible($dealer)->whereKey($product->id)->exists());
        $this->assertFalse(app(ComplianceService::class)->evaluate($product, $dealer)->allowed);

        $this->grantEntitlement($dealer, EntitlementType::Dealer);
        $verified = $dealer->fresh();

        $this->assertTrue(Product::visible($verified)->whereKey($product->id)->exists());
        $this->assertTrue(app(ComplianceService::class)->evaluate($product, $verified)->allowed);
    }

    public function test_shared_wishlist_is_readable_by_token_only(): void
    {
        $product = $this->freeProduct();
        $owner = $this->customer();
        $list = $owner->wishlists()->create(['name' => 'Drückjagd', 'is_default' => false, 'share_token' => str_repeat('a', 32)]);
        $list->products()->attach($product->id);

        $this->get(route('wishlist.shared', str_repeat('a', 32)))->assertOk()->assertSee('Drückjagd')->assertSee($product->name)
            ->assertDontSee($owner->email);
        $this->get(route('wishlist.shared', str_repeat('b', 32)))->assertNotFound();
    }
}
