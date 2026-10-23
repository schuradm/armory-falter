<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Abgesagte Termine geben ihren Slot frei: Eindeutigkeit über slot_key, der bei Absage leer wird. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('slot_key', 90)->nullable()->after('resource');
        });

        DB::table('appointments')->where('status', '!=', 'cancelled')->orderBy('id')->each(function ($a) {
            DB::table('appointments')->where('id', $a->id)
                ->update(['slot_key' => $a->resource.'|'.substr((string) $a->starts_at, 0, 16)]);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique(['resource', 'starts_at']);
            $table->unique('slot_key');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique(['slot_key']);
            $table->dropColumn('slot_key');
            $table->unique(['resource', 'starts_at']);
        });
    }
};
