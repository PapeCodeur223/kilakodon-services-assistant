<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demandes de droits (PRV-02) : précisions libres du demandeur (rectification, contexte) et
 * empreinte HMAC de l'IP du formulaire public (traçabilité, jamais l'IP en clair).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('privacy_requests', function (Blueprint $table) {
            $table->text('details')->nullable()->after('type');
            $table->char('ip_hash', 64)->nullable()->after('details');
        });
    }

    public function down(): void
    {
        Schema::table('privacy_requests', function (Blueprint $table) {
            $table->dropColumn(['details', 'ip_hash']);
        });
    }
};
