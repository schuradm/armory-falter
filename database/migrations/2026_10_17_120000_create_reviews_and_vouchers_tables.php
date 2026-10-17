<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Produktbewertungen (nur verifizierte Käufer), Rabattcodes und Geschenkgutscheine (Mehrzweck-Gutscheine).
 * Rein additiv: neue Tabellen, neue Spalten mit Standardwerten. Bestehende Bestellungen bleiben unverändert
 * (kein Rabatt, kein Gutschein, keine Bewertungseinladung, weil der Widerspruchshinweis damals fehlte).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete(); // Kaufnachweis
            $table->unsignedTinyInteger('rating'); // 1–5
            $table->string('title', 120)->nullable();
            $table->text('body')->nullable();
            $table->string('author_name', 80); // „Max T.“, beim Absenden festgehalten
            $table->string('status', 10)->default('pending'); // pending|approved|rejected
            $table->string('rejection_reason')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->text('reply')->nullable();
            $table->foreignId('replied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'user_id']); // eine Bewertung je Produkt und Kunde
            $table->index(['product_id', 'status', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        // Kennzahlen je Produkt (denormalisiert): Produktkarten zeigen Sterne ohne Zusatzabfrage
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('rating_avg', 3, 2)->nullable()->after('is_featured');
            $table->unsignedInteger('rating_count')->default(0)->after('rating_avg');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('review_requests_opted_out_at')->nullable(); // Widerspruch gegen Bewertungseinladungen
        });

        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique(); // normalisiert: Großbuchstaben, ohne Leer-/Bindestriche
            $table->string('description')->nullable(); // intern
            $table->string('type', 10); // percent|fixed
            $table->decimal('percent', 5, 2)->nullable();
            $table->unsignedInteger('amount_gross')->nullable(); // Cent
            $table->unsignedInteger('min_order_gross')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->unsignedInteger('times_used')->default(0); // aktive Einlösungen (Stornos zählen zurück)
            $table->boolean('applies_to_permit_items')->default(false); // erlaubnispflichtige Waffen/Munition
            $table->json('include_category_ids')->nullable();
            $table->json('exclude_category_ids')->nullable();
            $table->json('include_product_ids')->nullable();
            $table->json('exclude_product_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('gift_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique(); // normalisiert, 16 Zeichen
            $table->unsignedInteger('initial_value');
            $table->unsignedInteger('balance');
            $table->string('status', 12)->default('active'); // active|redeemed|cancelled
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete(); // Kauf
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchaser_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name', 100)->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('message', 300)->nullable();
            $table->date('expires_at');
            $table->string('pdf_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'expires_at']);
        });

        Schema::create('gift_voucher_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 10); // issue|redeem|release|adjust|cancel
            $table->integer('amount'); // Einlösung negativ
            $table->unsignedInteger('balance_after');
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['gift_voucher_id', 'id']);
        });

        Schema::create('discount_code_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->unsignedInteger('amount_gross');
            $table->string('status', 10)->default('active'); // active|released
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->unique(['discount_code_id', 'order_id']);
            $table->index(['discount_code_id', 'user_id', 'status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('discount_code', 40)->nullable()->after('discount_gross');
            $table->unsignedInteger('gift_voucher_gross')->default(0)->after('total_tax'); // per Gutschein bezahlt
            $table->boolean('review_invite_allowed')->default(false); // Hinweis nach § 7 Abs. 3 UWG gegeben, kein Widerspruch
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('review_requested_at')->nullable();
            $table->index(['status', 'delivered_at']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('item_type', 20)->default('product')->after('product_variant_id'); // product|gift_voucher
            $table->unsignedInteger('discount_gross')->default(0)->after('line_total_gross'); // anteiliger Rabatt
            $table->json('options')->nullable();
        });

        // Warenkorb: Konfiguration je Position (Gutscheinbetrag, Empfänger) und eingegebene Codes
        Schema::table('cart_items', function (Blueprint $table) {
            $table->json('options')->nullable();
            $table->string('options_key', 40)->default('');
            $table->index('cart_id'); // Fremdschlüssel braucht einen eigenen Index, bevor der Unique-Index fällt
        });
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'product_id', 'product_variant_id']);
            $table->unique(['cart_id', 'product_id', 'product_variant_id', 'options_key'], 'cart_items_line_unique'); // Standardname > 64 Zeichen
        });
        Schema::table('carts', function (Blueprint $table) {
            $table->string('discount_code', 40)->nullable();
            $table->json('gift_voucher_codes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('carts', fn (Blueprint $t) => $t->dropColumn(['discount_code', 'gift_voucher_codes']));
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_line_unique');
            $table->unique(['cart_id', 'product_id', 'product_variant_id']);
        });
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex(['cart_id']);
            $table->dropColumn(['options', 'options_key']);
        });
        Schema::table('order_items', fn (Blueprint $t) => $t->dropColumn(['item_type', 'discount_gross', 'options']));
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'delivered_at']);
            $table->dropColumn(['discount_code', 'gift_voucher_gross', 'review_invite_allowed', 'delivered_at', 'review_requested_at']);
        });
        Schema::dropIfExists('discount_code_redemptions');
        Schema::dropIfExists('gift_voucher_transactions');
        Schema::dropIfExists('gift_vouchers');
        Schema::dropIfExists('discount_codes');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('review_requests_opted_out_at'));
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn(['rating_avg', 'rating_count']));
        Schema::dropIfExists('product_reviews');
    }
};
