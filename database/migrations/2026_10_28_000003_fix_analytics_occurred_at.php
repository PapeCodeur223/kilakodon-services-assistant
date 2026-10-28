<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MariaDB (explicit_defaults_for_timestamp=OFF) ajoute ON UPDATE CURRENT_TIMESTAMP à la première
 * colonne TIMESTAMP NOT NULL d'une table : `occurred_at` aurait été réécrite à chaque mise à jour.
 * On la passe en DATETIME (voir DECISIONS.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dateTime('occurred_at')->change();
        });
    }

    public function down(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->timestamp('occurred_at')->change();
        });
    }
};
