<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10); // billing|shipping
            $table->boolean('is_default')->default(false);
            $table->string('company_name', 200)->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('street', 200);
            $table->string('house_number', 20);
            $table->string('address_extra', 200)->nullable();
            $table->string('postal_code', 10);
            $table->string('city', 100);
            $table->char('country_code', 2)->default('DE');
            $table->timestamps();
            $table->index(['user_id', 'type']);
        });

        Schema::create('legal_classes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->unsignedTinyInteger('min_age')->default(0);
            $table->boolean('requires_entitlement')->default(false);
            $table->json('accepted_entitlements')->nullable();
            $table->string('shipping_mode', 30); // standard|age_check|weapon_personal|pickup_only
            $table->json('allowed_payment_codes');
            $table->boolean('requires_serial')->default(false);
            $table->text('notice_text')->nullable();
            $table->timestamps();
        });

        Schema::create('calibers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->json('aliases')->nullable();
            $table->string('kind', 20); // rifle|handgun|shotgun|rimfire|airgun|other
            $table->boolean('is_large_bore')->default(false);
            $table->longText('content')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->text('document_number')->nullable();          // encrypted cast
            $table->char('document_number_bidx', 64)->nullable(); // HMAC blind index
            $table->string('issuing_authority', 200)->nullable();
            $table->date('issued_at')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('storage_path');
            $table->string('original_name');
            $table->string('mime_type', 60);
            $table->unsignedInteger('file_size');
            $table->char('sha256', 64);
            $table->string('status', 20)->default('uploaded');
            $table->json('precheck_result')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->date('delete_after')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'type', 'status']);
        });

        Schema::create('customer_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('customer_documents');
            $table->string('type', 40);
            $table->string('weapon_kind', 20)->nullable(); // kurzwaffe|langwaffe|any
            $table->foreignId('caliber_id')->nullable()->constrained();
            $table->unsignedSmallInteger('quantity_total')->nullable();
            $table->unsignedSmallInteger('quantity_used')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index(['user_id', 'status', 'type', 'valid_until']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable();
            $table->string('actor_type', 10);
            $table->string('action', 100);
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->char('prev_hash', 64)->nullable();
            $table->char('hash', 64);
            $table->timestamp('created_at', 3)->useCurrent();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });

        Schema::create('consent_records', function (Blueprint $table) {
            $table->id();
            $table->string('consent_id', 64)->index();
            $table->foreignId('user_id')->nullable();
            $table->json('categories');
            $table->string('banner_version', 20);
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['consent_records', 'audit_logs', 'customer_entitlements', 'customer_documents', 'calibers', 'legal_classes', 'addresses'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
