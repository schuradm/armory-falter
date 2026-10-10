<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('logo_path')->nullable();
            $table->string('hero_image_path')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->smallInteger('founded_year')->nullable();
            $table->text('intro')->nullable();
            $table->longText('content')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories');
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->string('path', 500)->unique();
            $table->string('icon', 40)->nullable();
            $table->text('description')->nullable();
            $table->longText('content_bottom')->nullable();
            $table->string('image_path')->nullable();
            $table->foreignId('default_legal_class_id')->nullable()->constrained('legal_classes');
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_home')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['parent_id', 'slug']);
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->decimal('rate', 5, 2);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 40)->unique();
            $table->string('ean', 14)->nullable();
            $table->string('manufacturer_part_number', 60)->nullable();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->foreignId('manufacturer_id')->nullable()->constrained();
            $table->foreignId('caliber_id')->nullable()->constrained();
            $table->foreignId('legal_class_id')->constrained();
            $table->foreignId('tax_rate_id')->constrained();
            $table->string('product_type', 20); // weapon|ammo|optic|knife|accessory|reloading|apparel|service|voucher
            $table->string('weapon_kind', 20)->nullable(); // kurzwaffe|langwaffe
            $table->string('condition_code', 20)->default('new'); // new|used|demo|like_new
            $table->text('condition_notes')->nullable();
            $table->boolean('is_unique_item')->default(false);
            $table->boolean('usage_hunting')->default(false);
            $table->boolean('usage_sport')->default(false);
            $table->boolean('authority_only')->default(false);
            $table->unsignedInteger('price_gross'); // Cent
            $table->unsignedInteger('special_price_gross')->nullable();
            $table->dateTime('special_from')->nullable();
            $table->dateTime('special_to')->nullable();
            $table->unsignedInteger('lowest_price_30d')->nullable();
            $table->unsignedInteger('uvp_gross')->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->integer('stock_reserved')->default(0);
            $table->string('availability', 20)->default('in_stock');
            $table->string('delivery_time', 40)->nullable();
            $table->unsignedInteger('weight_grams')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('video_url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'product_type', 'availability']);
            $table->index(['manufacturer_id', 'status']);
            $table->index(['caliber_id', 'status']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('products', fn (Blueprint $table) => $table->fullText(['name', 'short_description']));
        }

        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->primary(['product_id', 'category_id']);
            $table->index('category_id');
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 50)->unique();
            $table->json('options');
            $table->unsignedInteger('price_gross')->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10)->default('image');
            $table->string('path');
            $table->string('alt_text');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->json('variant_widths')->nullable(); // erzeugte WebP-Breiten, siehe ImageVariants
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'sort_order']);
        });

        Schema::create('product_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // manual|datasheet|certificate|exploded_view|other
            $table->string('title', 200);
            $table->string('path');
            $table->char('language', 2)->default('de');
            $table->string('visibility', 20)->default('public'); // public|customers|buyers
            $table->timestamps();
        });

        Schema::create('attribute_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 120);
            $table->string('unit', 20)->nullable();
            $table->string('data_type', 10)->default('text');
            $table->boolean('is_filterable')->default(false);
            $table->string('group_name', 80)->nullable();
            $table->integer('sort_order')->default(0);
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_definition_id')->constrained();
            $table->string('value_text')->nullable();
            $table->decimal('value_number', 12, 3)->nullable();
            $table->primary(['product_id', 'attribute_definition_id']);
            $table->index(['attribute_definition_id', 'value_number'], 'pav_attr_num_idx');
        });

        Schema::create('weapon_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->text('serial_number');              // encrypted
            $table->char('serial_bidx', 64)->unique();  // HMAC blind index
            $table->string('nwr_id', 40)->nullable();
            $table->string('status', 20)->default('in_stock');
            $table->string('acquired_from')->nullable();
            $table->date('acquired_at')->nullable();
            $table->timestamps();
        });

        Schema::create('weapon_book_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weapon_item_id')->constrained();
            $table->string('direction', 3); // in|out
            $table->text('counterparty');    // encrypted JSON
            $table->string('permit_reference', 120)->nullable();
            $table->foreignId('order_id')->nullable();
            $table->timestamp('nwr_reported_at')->nullable();
            $table->string('nwr_reference', 80)->nullable();
            $table->foreignId('booked_by')->constrained('users');
            $table->timestamp('booked_at');
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100)->default('Favoriten');
            $table->boolean('is_default')->default(false);
            $table->char('share_token', 32)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->foreignId('wishlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->boolean('notify_price_drop')->default(false);
            $table->boolean('notify_back_in_stock')->default(false);
            $table->timestamp('added_at')->useCurrent();
            $table->primary(['wishlist_id', 'product_id']);
        });
    }

    public function down(): void
    {
        foreach (['wishlist_items', 'wishlists', 'weapon_book_entries', 'weapon_items', 'product_attribute_values', 'attribute_definitions',
            'product_documents', 'product_media', 'product_variants', 'category_product', 'products', 'tax_rates', 'categories', 'manufacturers'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
