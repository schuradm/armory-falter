<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Die Vorprüfung sucht bei jedem Upload nach Dubletten (Datei-Hash, Dokumentnummer). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_documents', function (Blueprint $table) {
            $table->index('sha256');
            $table->index('document_number_bidx');
        });
    }

    public function down(): void
    {
        Schema::table('customer_documents', function (Blueprint $table) {
            $table->dropIndex(['sha256']);
            $table->dropIndex(['document_number_bidx']);
        });
    }
};
