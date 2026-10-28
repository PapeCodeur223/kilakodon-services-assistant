<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brief et versions figées (BACKEND_SCHEMA §5). Ajoute la clé étrangère `leads.brief_id`
 * laissée en attente au lot 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('briefs', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 16)->unique();
            $table->foreignUlid('diagnostic_session_id')->nullable()->unique()->constrained('diagnostic_sessions')->nullOnDelete();
            $table->ulid('conversation_id')->nullable()->index();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('draft')->index();
            $table->unsignedInteger('current_version')->default(1);
            $table->char('access_token_hash', 64)->unique();
            $table->dateTime('access_expires_at'); // DATETIME : un TIMESTAMP NOT NULL serait réécrit à chaque mise à jour (MariaDB).
            $table->timestamp('access_revoked_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });

        Schema::create('brief_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brief_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('declared');
            $table->json('recommended');
            $table->text('summary');
            $table->string('summary_source', 16);
            $table->text('prospect_comment')->nullable();
            $table->string('created_by', 16);
            $table->timestamp('created_at')->nullable();
            $table->unique(['brief_id', 'version']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->foreign('brief_id')->references('id')->on('briefs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['brief_id']);
        });

        Schema::dropIfExists('brief_versions');
        Schema::dropIfExists('briefs');
    }
};
