<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Im Backend (/kms-login → Einstellungen) pflegbare Werte: Startseite, Promo-Leiste, Firmendaten, Zahlarten
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        // Bewertungen (Social Proof auf Startseite/Produktseite). Nur moderiert sichtbar.
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name', 120);
            $table->string('author_meta', 120)->nullable(); // z. B. „Jäger aus Esslingen“
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('body');
            $table->string('source', 40)->nullable(); // google, shop, ...
            $table->boolean('is_published')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('settings');
    }
};
