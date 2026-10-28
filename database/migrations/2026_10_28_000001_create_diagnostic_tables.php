<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnostic (BACKEND_SCHEMA §4). Un template `active` ou `retired` est immuable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->unsignedInteger('version')->unique();
            $table->string('status', 16)->default('draft')->index();
            $table->timestamp('activated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('diagnostic_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('diagnostic_templates')->cascadeOnDelete();
            $table->string('key', 48);
            $table->unsignedTinyInteger('step');
            $table->string('label', 255);
            $table->string('help', 255)->nullable();
            $table->string('type', 16);
            $table->json('options')->nullable();
            $table->boolean('allow_unknown')->default(false);
            $table->boolean('required')->default(false);
            $table->tinyInteger('max_selections')->nullable();
            $table->json('show_if')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['template_id', 'key']);
        });

        Schema::create('diagnostic_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('diagnostic_templates')->cascadeOnDelete();
            $table->string('key', 48);
            $table->string('name', 160);
            $table->json('conditions');
            $table->json('effects');
            $table->string('explanation', 255)->default('');
            $table->unsignedSmallInteger('priority');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['template_id', 'key']);
        });

        Schema::create('diagnostic_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('template_id')->constrained('diagnostic_templates');
            $table->char('resume_token_hash', 64)->unique();
            $table->string('status', 16)->default('in_progress')->index();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->string('entry', 64)->nullable();
            $table->json('prefill')->nullable();
            $table->uuid('visitor_key')->nullable();
            $table->char('ip_hash', 64);
            $table->json('utm')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('diagnostic_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('session_id')->constrained('diagnostic_sessions')->cascadeOnDelete();
            $table->string('question_key', 48);
            $table->json('value');
            $table->timestamps();
            $table->unique(['session_id', 'question_key']);
        });

        Schema::create('diagnostic_results', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('session_id')->unique()->constrained('diagnostic_sessions')->cascadeOnDelete();
            $table->unsignedInteger('template_version');
            $table->decimal('maturity_score', 4, 1);
            $table->unsignedTinyInteger('maturity_level');
            $table->json('pains');
            $table->json('recommendations');
            $table->json('clarifications');
            $table->text('ai_summary')->nullable();
            $table->string('ai_summary_model', 64)->nullable();
            $table->dateTime('computed_at'); // DATETIME : sous MariaDB, un TIMESTAMP NOT NULL recevrait ON UPDATE CURRENT_TIMESTAMP.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_results');
        Schema::dropIfExists('diagnostic_answers');
        Schema::dropIfExists('diagnostic_sessions');
        Schema::dropIfExists('diagnostic_rules');
        Schema::dropIfExists('diagnostic_questions');
        Schema::dropIfExists('diagnostic_templates');
    }
};
