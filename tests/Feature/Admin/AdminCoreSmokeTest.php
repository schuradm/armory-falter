<?php

namespace Tests\Feature\Admin;

use App\Enums\ComplianceStatus;
use App\Enums\DocumentStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\CaliberResource;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\CustomerDocumentResource;
use App\Filament\Resources\CustomerDocumentResource\Pages\ViewCustomerDocument;
use App\Filament\Resources\LegalClassResource;
use App\Filament\Resources\ManufacturerResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\TradeRequestResource\Pages\ListTradeRequests;
use App\Filament\Resources\TradeRequestResource;
use App\Filament\Resources\UserResource;
use App\Filament\Support\Euro;
use App\Models\Caliber;
use App\Models\Category;
use App\Models\CustomerDocument;
use App\Models\LegalClass;
use App\Models\Manufacturer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\TaxRate;
use App\Models\TradeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Rauchtest für die Kern-Ressourcen im Backend (/kms-login).
 * Ausführen gegen MySQL, z. B.:
 *   DB_CONNECTION=mysql DB_DATABASE=armory_admin1 php artisan test --filter=AdminCoreSmokeTest
 */
class AdminCoreSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private Product $product;

    private Order $order;

    private CustomerDocument $document;

    private TradeRequest $tradeRequest;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('documents');
        Storage::fake('public');
        config(['shop.documents.disk' => 'documents']);

        Permission::findOrCreate('compliance.release', 'web');
        Role::findOrCreate('super-admin', 'web');
        Role::findOrCreate('shop-manager', 'web');

        $this->admin = $this->makeUser('admin@example.test', staff: true);
        $this->admin->assignRole('super-admin');
        $this->customer = $this->makeUser('kunde@example.test');

        $free = LegalClass::create([
            'code' => 'free', 'name' => 'Frei verkäuflich', 'min_age' => 0, 'requires_entitlement' => false,
            'shipping_mode' => 'standard', 'allowed_payment_codes' => ['prepayment', 'paypal', 'sofort'],
        ]);
        $wbk = LegalClass::create([
            'code' => 'wbk', 'name' => 'Erlaubnispflichtig (WBK)', 'min_age' => 18, 'requires_entitlement' => true,
            'accepted_entitlements' => ['wbk_voreintrag'], 'shipping_mode' => 'weapon_personal',
            'allowed_payment_codes' => ['prepayment', 'cash_pickup'], 'requires_serial' => true,
        ]);
        $tax = TaxRate::create(['name' => '19 %', 'rate' => 19]);
        $manufacturer = Manufacturer::create(['name' => 'Sauer', 'slug' => 'sauer', 'faqs' => [['question' => 'Wo?', 'answer' => 'Isny']]]);
        $caliber = Caliber::create(['name' => '.308 Win', 'slug' => '308-win', 'kind' => 'rifle', 'faqs' => []]);
        $category = Category::create(['name' => 'Langwaffen', 'slug' => 'langwaffen', 'default_legal_class_id' => $wbk->id]);

        $this->product = Product::create([
            'sku' => 'S404-308', 'name' => 'Sauer 404 Classic', 'slug' => 'sauer-404-classic', 'manufacturer_id' => $manufacturer->id,
            'caliber_id' => $caliber->id, 'legal_class_id' => $wbk->id, 'tax_rate_id' => $tax->id, 'product_type' => 'weapon',
            'weapon_kind' => 'langwaffe', 'price_gross' => 399000, 'stock_quantity' => 3, 'status' => 'active',
        ]);
        $this->product->categories()->attach($category->id, ['is_primary' => true]);
        Product::create([
            'sku' => 'ZB-1', 'name' => 'Gewehrriemen', 'slug' => 'gewehrriemen', 'legal_class_id' => $free->id,
            'tax_rate_id' => $tax->id, 'product_type' => 'accessory', 'price_gross' => 4990, 'stock_quantity' => 10, 'status' => 'active',
        ]);

        ShippingMethod::create([
            'code' => 'weapon_personal_overnight', 'name' => 'Waffenversand persönlich', 'carrier' => 'dhl',
            'allowed_shipping_modes' => ['weapon_personal'], 'price_gross' => 2990,
        ]);

        $this->order = Order::create([
            'order_number' => 'AF-2026-000001', 'user_id' => $this->customer->id,
            'status' => OrderStatus::PendingCompliance, 'compliance_status' => ComplianceStatus::Pending,
            'billing_address' => ['first_name' => 'Max', 'last_name' => 'Muster', 'street' => 'Hauptstr.', 'house_number' => '1', 'postal_code' => '72649', 'city' => 'Wolfschlugen', 'country_code' => 'DE'],
            'shipping_method_code' => 'weapon_personal_overnight', 'payment_method_code' => 'prepayment',
            'subtotal_gross' => 399000, 'shipping_gross' => 2990, 'total_gross' => 401990, 'total_tax' => 64183,
            'terms_accepted_at' => now(), 'legal_declaration_accepted_at' => now(), 'idempotency_key' => (string) Str::uuid(), 'placed_at' => now(),
        ]);
        $this->order->items()->create([
            'product_id' => $this->product->id, 'sku' => $this->product->sku, 'name' => $this->product->name,
            'legal_class_code' => 'wbk', 'quantity' => 1, 'unit_price_gross' => 399000, 'tax_rate' => 19, 'line_total_gross' => 399000,
        ]);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        Storage::disk('documents')->put('test/wbk.bin', encrypt($png));
        $this->document = new CustomerDocument([
            'type' => 'wbk_green', 'document_number' => 'WBK-123', 'valid_until' => now()->addYear(),
            'storage_path' => 'test/wbk.bin', 'original_name' => 'wbk.png', 'mime_type' => 'image/png',
            'file_size' => strlen($png), 'sha256' => hash('sha256', $png), 'status' => DocumentStatus::PrecheckFlagged,
            'precheck_result' => ['flags' => ['Auflösung gering'], 'info' => ['dimensions' => '1×1']],
        ]);
        $this->document->user()->associate($this->customer);
        $this->document->save();

        $this->tradeRequest = TradeRequest::create([
            'reference' => 'VS-2026-0001', 'type' => 'consignment', 'contact' => ['name' => 'Erika Muster', 'email' => 'erika@example.test'],
            'model' => 'K98', 'caliber' => '8x57 IS', 'condition_code' => 'good', 'desired_price' => 80000,
            'status' => 'new', 'privacy_accepted_at' => now(),
        ]);

        $this->actingAs($this->admin);
    }

    private function makeUser(string $email, bool $staff = false): User
    {
        $user = new User(['first_name' => $staff ? 'Ada' : 'Max', 'last_name' => $staff ? 'Admin' : 'Muster', 'email' => $email, 'password' => 'Geheim-Passwort-123']);
        $user->forceFill([
            'is_staff' => $staff, 'is_active' => true, 'email_verified_at' => now(),
            'two_factor_confirmed_at' => $staff ? now() : null,
            'birth_date' => '1980-05-01',
        ])->save();

        return $user;
    }

    public function test_index_pages_load(): void
    {
        foreach ([ProductResource::class, OrderResource::class, CustomerDocumentResource::class, TradeRequestResource::class,
            UserResource::class, CategoryResource::class, ManufacturerResource::class, CaliberResource::class, LegalClassResource::class] as $resource) {
            $this->get($resource::getUrl('index'))->assertOk();
        }
    }

    public function test_create_pages_load(): void
    {
        foreach ([ProductResource::class, TradeRequestResource::class, UserResource::class, CategoryResource::class,
            ManufacturerResource::class, CaliberResource::class, LegalClassResource::class] as $resource) {
            $this->get($resource::getUrl('create'))->assertOk();
        }
    }

    public function test_edit_and_view_pages_load(): void
    {
        $this->get(ProductResource::getUrl('edit', ['record' => $this->product]))->assertOk()->assertSee('3990,00', false);
        $this->get(OrderResource::getUrl('view', ['record' => $this->order]))->assertOk()->assertSee('AF-2026-000001')->assertSee('Wolfschlugen');
        $this->get(CustomerDocumentResource::getUrl('view', ['record' => $this->document]))->assertOk()->assertSee('data:image/png;base64', false);
        $this->get(CustomerDocumentResource::getUrl('edit', ['record' => $this->document]))->assertOk();
        $this->get(TradeRequestResource::getUrl('edit', ['record' => $this->tradeRequest]))->assertOk();
        $this->get(UserResource::getUrl('edit', ['record' => $this->customer]))->assertOk();
        $this->get(CategoryResource::getUrl('edit', ['record' => Category::first()]))->assertOk();
        $this->get(ManufacturerResource::getUrl('edit', ['record' => Manufacturer::first()]))->assertOk();
        $this->get(CaliberResource::getUrl('edit', ['record' => Caliber::first()]))->assertOk();
        $this->get(LegalClassResource::getUrl('edit', ['record' => LegalClass::first()]))->assertOk();
    }

    public function test_orders_have_no_create_page(): void
    {
        $this->assertFalse(OrderResource::canCreate());
        $this->assertArrayNotHasKey('create', OrderResource::getPages());
    }

    public function test_customer_without_staff_flag_cannot_enter_panel(): void
    {
        $this->actingAs($this->customer)->get(ProductResource::getUrl('index'))->assertForbidden();
    }

    public function test_staff_without_compliance_permission_cannot_see_documents(): void
    {
        $manager = $this->makeUser('manager@example.test', staff: true);
        $manager->assignRole('shop-manager');
        $this->actingAs($manager)->get(CustomerDocumentResource::getUrl('index'))->assertForbidden();
    }

    public function test_euro_conversion(): void
    {
        $this->assertSame(129900, Euro::toCents('1.299,00'));
        $this->assertSame(129950, Euro::toCents('1299,5'));
        $this->assertSame(129950, Euro::toCents('1299.50'));
        $this->assertSame(500, Euro::toCents('5'));
        $this->assertNull(Euro::toCents(''));
        $this->assertSame('1299,00', Euro::fromCents(129900));
    }

    public function test_product_can_be_created_with_euro_price_and_accessory(): void
    {
        $accessory = Product::where('sku', 'ZB-1')->first();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Testbüchse', 'slug' => 'testbuechse', 'sku' => 'TB-1', 'product_type' => 'weapon', 'weapon_kind' => 'langwaffe',
                'legal_class_id' => LegalClass::where('code', 'wbk')->value('id'), 'tax_rate_id' => TaxRate::value('id'),
                'condition_code' => 'new', 'status' => 'draft', 'price_gross' => '1.499,90', 'stock_quantity' => 1, 'availability' => 'in_stock',
                'categories' => [Category::value('id')],
                'accessory_ids' => [['related_product_id' => $accessory->id]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('sku', 'TB-1')->firstOrFail();
        $this->assertSame(149990, $product->price_gross);
        $this->assertSame([$accessory->id], $product->accessories()->pluck('products.id')->all());
        $this->assertTrue((bool) $product->categories()->first()->pivot->is_primary);
    }

    public function test_compliance_release_action(): void
    {
        Livewire::test(ViewOrder::class, ['record' => $this->order->getRouteKey()])
            ->callAction('releaseCompliance', ['note' => 'WBK geprüft', 'original_document_seen' => true])
            ->assertHasNoActionErrors();

        $this->order->refresh();
        $this->assertSame(ComplianceStatus::Approved, $this->order->compliance_status);
        $this->assertSame(OrderStatus::PendingPayment, $this->order->status);
    }

    public function test_compliance_reject_cancels_order(): void
    {
        Livewire::test(ViewOrder::class, ['record' => $this->order->getRouteKey()])
            ->callAction('rejectCompliance', ['reason' => 'Kein Voreintrag'])
            ->assertHasNoActionErrors();

        $this->order->refresh();
        $this->assertSame(ComplianceStatus::Rejected, $this->order->compliance_status);
        $this->assertSame(OrderStatus::Cancelled, $this->order->status);
        $this->assertSame(4, $this->product->fresh()->stock_quantity);
    }

    public function test_document_reject_and_approve(): void
    {
        Livewire::test(ViewCustomerDocument::class, ['record' => $this->document->getKey()])
            ->callAction('approve', [
                'entitlements' => [['type' => 'wbk_voreintrag', 'weapon_kind' => 'langwaffe', 'quantity_total' => 1]],
                'confirm_birth_date' => true, 'birth_date' => '1980-05-01',
            ])
            ->assertHasNoActionErrors();

        $this->document->refresh();
        $this->assertSame(DocumentStatus::Approved, $this->document->status);
        $this->assertTrue($this->document->entitlements()->where('type', 'wbk_voreintrag')->where('weapon_kind', 'langwaffe')->exists());
        $this->assertNotNull($this->customer->fresh()->age_verified_at);

        $second = $this->document->replicate(['document_number_bidx']);
        $second->status = DocumentStatus::Uploaded;
        $second->save();

        Livewire::test(ViewCustomerDocument::class, ['record' => $second->getKey()])
            ->callAction('reject', ['reason' => 'Nicht lesbar'])
            ->assertHasNoActionErrors();
        $this->assertSame(DocumentStatus::Rejected, $second->fresh()->status);
        $this->assertSame('Nicht lesbar', $second->fresh()->rejection_reason);
    }

    public function test_document_download_streams_decrypted_file(): void
    {
        Livewire::test(ViewCustomerDocument::class, ['record' => $this->document->getKey()])
            ->callAction('download')
            ->assertFileDownloaded('dokument-'.$this->document->id.'-wbk_green.png');
    }
    public function test_product_edit_keeps_price_and_accessories(): void
    {
        $accessory = Product::where('sku', 'ZB-1')->first();
        $this->product->accessories()->attach($accessory->id, ['type' => 'accessory', 'sort_order' => 0]);

        Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
            ->assertFormSet(['price_gross' => '3990,00'])
            ->fillForm(['stock_quantity' => 7])
            ->call('save')
            ->assertHasNoFormErrors();

        $product = $this->product->fresh();
        $this->assertSame(399000, $product->price_gross);
        $this->assertSame(7, $product->stock_quantity);
        $this->assertSame([$accessory->id], $product->accessories()->pluck('products.id')->all());
    }

    public function test_egun_csv_bulk_export(): void
    {
        Livewire::test(ListTradeRequests::class)
            ->callTableBulkAction('egunCsv', [$this->tradeRequest])
            ->assertFileDownloaded();
    }
}
