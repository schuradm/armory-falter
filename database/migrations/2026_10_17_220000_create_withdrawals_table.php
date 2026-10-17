<?php

use App\Models\Page;
use App\Support\LegalTexts;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Widerrufsbutton (Art. 11a Verbraucherrechte-Richtlinie i. d. F. der RL (EU) 2023/2673, seit 19.06.2026):
 * online erklärte Widerrufe mit Zeitstempel. Außerdem: noch unveränderte Rechtstexte durch die überarbeiteten Fassungen ersetzen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 200);
            $table->string('email');
            $table->string('order_number', 40);
            $table->text('items')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('received'); // received | processed
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('declared_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'declared_at']);
        });

        // Nur Seiten aktualisieren, die nach dem Anlegen nie im Backend bearbeitet wurden
        $todo = fn (string $text) => '<mark>[PLATZHALTER: '.$text.']</mark>';
        foreach (['impressum' => 'impressum', 'agb' => 'agb', 'widerruf' => 'widerruf', 'datenschutz' => 'datenschutz'] as $slug => $method) {
            $page = Page::where('slug', $slug)->first();
            if ($page && $page->updated_at?->equalTo($page->created_at)) {
                $page->body = LegalTexts::$method($todo);
                $page->timestamps = false;
                $page->save();
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
