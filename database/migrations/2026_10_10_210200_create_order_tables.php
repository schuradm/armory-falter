<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['cart_id', 'product_id', 'product_variant_id']);
        });

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->string('description')->nullable();
            $table->string('carrier', 40)->nullable();
            $table->json('allowed_shipping_modes');
            $table->unsignedInteger('price_gross');
            $table->unsignedInteger('free_from_gross')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->foreignId('user_id')->constrained();
            $table->string('status', 30);
            $table->string('compliance_status', 20);
            $table->json('billing_address');
            $table->json('shipping_address')->nullable();
            $table->string('shipping_method_code', 40);
            $table->string('payment_method_code', 40);
            $table->unsignedInteger('subtotal_gross');
            $table->unsignedInteger('shipping_gross');
            $table->unsignedInteger('discount_gross')->default(0);
            $table->unsignedInteger('total_gross');
            $table->unsignedInteger('total_tax');
            $table->char('currency', 3)->default('EUR');
            $table->text('customer_note')->nullable();
            $table->timestamp('terms_accepted_at');
            $table->timestamp('legal_declaration_accepted_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->timestamp('placed_at');
            $table->timestamps();
            $table->index(['user_id', 'placed_at']);
            $table->index(['status', 'placed_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('product_variant_id')->nullable()->constrained();
            $table->foreignId('weapon_item_id')->nullable()->constrained();
            $table->string('sku', 50);
            $table->string('name', 200);
            $table->string('legal_class_code', 40);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price_gross');
            $table->decimal('tax_rate', 5, 2);
            $table->unsignedInteger('line_total_gross');
            $table->timestamps();
        });

        Schema::create('order_compliance_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('check_type', 30);
            $table->foreignId('customer_entitlement_id')->nullable()->constrained();
            $table->string('result', 10); // passed|failed|pending
            $table->json('details')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users');
            $table->timestamp('checked_at');
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('comment')->nullable();
            $table->boolean('notify_customer')->default(false);
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->string('provider', 40);
            $table->string('provider_reference', 120)->nullable();
            $table->string('status', 20);
            $table->unsignedInteger('amount');
            $table->json('raw_response')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_reference']);
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->foreignId('shipping_method_id')->constrained();
            $table->string('carrier', 40)->nullable();
            $table->string('tracking_number', 60)->nullable()->index();
            $table->string('label_path')->nullable();
            $table->string('status', 20)->default('created');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('last_tracking_sync_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('status', 60);
            $table->string('description')->nullable();
            $table->string('location', 120)->nullable();
            $table->timestamp('occurred_at');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->string('invoice_number', 30)->unique();
            $table->string('type', 20)->default('invoice');
            $table->string('pdf_path');
            $table->integer('total_gross');
            $table->timestamp('issued_at');
            $table->timestamps();
        });

        Schema::create('number_sequences', function (Blueprint $table) {
            $table->string('key', 40)->primary(); // order:2026, invoice:2026 …
            $table->unsignedBigInteger('value')->default(0);
        });
    }

    public function down(): void
    {
        foreach (['number_sequences', 'invoices', 'shipment_events', 'shipments', 'payments', 'order_status_history',
            'order_compliance_checks', 'order_items', 'orders', 'shipping_methods', 'cart_items', 'carts'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
