<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CRM complet (BACKEND_SCHEMA §5) : historique des étapes, activités, rendez-vous, demandes de droits.
 * Dates « métier » non nulles en DATETIME (voir le journal de DECISIONS : ON UPDATE de MariaDB).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_stage_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('from_stage', 16)->nullable();
            $table->string('to_stage', 16);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['lead_id', 'created_at']);
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 16);
            $table->text('body');
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
            $table->index(['lead_id', 'created_at']);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16)->default('requested')->index();
            $table->string('channel', 16);
            $table->string('topic', 255);
            $table->json('preferred_slots');
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->string('timezone', 48)->default('Africa/Bamako');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('privacy_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('requester_name', 160);
            $table->string('requester_channel', 190);
            $table->string('type', 16);
            $table->string('status', 16)->default('received')->index();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_requests');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('lead_stage_history');
    }
};
