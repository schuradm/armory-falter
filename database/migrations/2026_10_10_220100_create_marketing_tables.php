<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Upsell/Cross-Sell: „Passend dazu“ (z. B. Glock → Holster, Reinigungskit, Red Dot)
        Schema::create('product_relations', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('type', 20)->default('accessory'); // accessory | alternative
            $table->integer('sort_order')->default(0);
            $table->primary(['product_id', 'related_product_id', 'type']);
        });

        // Events: Shooting Day, Testtage, Jagdabend, Workshops, Wiederlade-Seminare
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 160)->unique();
            $table->string('title', 200);
            $table->string('teaser', 300)->nullable();
            $table->longText('description')->nullable();
            $table->string('location', 200)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->unsignedInteger('price_gross')->nullable(); // Cent, null = kostenlos
            $table->string('image_path')->nullable();
            $table->boolean('requires_entitlement')->default(false); // z. B. Schießstandtage
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->index(['is_published', 'starts_at']);
        });
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 160);
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->unsignedTinyInteger('persons')->default(1);
            $table->string('status', 20)->default('registered'); // registered | waitlist | cancelled
            $table->timestamps();
            $table->unique(['event_id', 'email']);
        });

        // Regionale Landingpages (/waffenhaendler/{ort}) – jeweils mit eigenem Inhalt, keine Textkopien
        Schema::create('local_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('city', 120);
            $table->string('title', 200);
            $table->string('meta_description', 300)->nullable();
            $table->string('headline', 200);
            $table->longText('body');
            $table->json('faqs')->nullable();
            $table->unsignedSmallInteger('distance_km')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        // Willkommensstrecke (Tag 1/3/7/14) – nur für bestätigte Newsletter-Abonnenten
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->unsignedTinyInteger('welcome_step')->default(0)->after('source');
        });

        // FAQ-Blöcke für Hersteller- und Kaliberseiten (Langtext steht bereits in „content“)
        Schema::table('manufacturers', fn (Blueprint $t) => $t->json('faqs')->nullable()->after('content'));
        Schema::table('calibers', fn (Blueprint $t) => $t->json('faqs')->nullable()->after('content'));
    }

    public function down(): void
    {
        Schema::dropIfExists('local_pages');
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('events');
        Schema::dropIfExists('product_relations');
        Schema::table('newsletter_subscribers', fn (Blueprint $t) => $t->dropColumn('welcome_step'));
        Schema::table('manufacturers', fn (Blueprint $t) => $t->dropColumn('faqs'));
        Schema::table('calibers', fn (Blueprint $t) => $t->dropColumn('faqs'));
    }
};
