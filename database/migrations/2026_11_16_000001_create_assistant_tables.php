<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assistant IA (BACKEND_SCHEMA §6) : base de connaissances, conversations, messages,
 * actions proposées (confirmation obligatoire), retours et consommation quotidienne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('source_type', 16);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->longText('body')->nullable();
            $table->string('url', 255);
            $table->string('status', 16)->default('draft')->index();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('review_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['source_type', 'source_id']);
        });

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('knowledge_documents')->cascadeOnDelete();
            $table->unsignedInteger('document_version');
            $table->unsignedSmallInteger('position');
            $table->string('heading', 255);
            $table->text('content');
            $table->string('url', 255);
            $table->unsignedSmallInteger('words');
            $table->timestamps();
            $table->fullText(['heading', 'content'], 'ft_chunks');
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('visitor_key')->nullable()->index();
            $table->char('ip_hash', 64)->index();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('open');
            $table->string('entry_path', 255)->nullable();
            $table->unsignedSmallInteger('message_count')->default(0);
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->unsignedBigInteger('cost_estimate_usd_micros')->default(0);
            $table->dateTime('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->string('role', 16);
            $table->text('content');
            $table->json('citations')->nullable();
            $table->string('grounding', 16)->nullable();
            $table->string('suggested_action', 32)->nullable();
            $table->string('model', 64)->nullable();
            $table->string('prompt_version', 16)->nullable();
            $table->unsignedInteger('tokens_in')->nullable();
            $table->unsignedInteger('tokens_out')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('error_code', 48)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['conversation_id', 'id']);
        });

        Schema::create('ai_action_proposals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->string('type', 32);
            $table->json('payload');
            $table->char('payload_hash', 64);
            $table->char('idempotency_key', 64)->unique();
            $table->string('status', 16)->default('proposed')->index();
            $table->nullableMorphs('result');
            $table->string('error', 255)->nullable();
            $table->dateTime('expires_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->unique()->constrained('messages')->cascadeOnDelete();
            $table->string('rating', 8);
            $table->string('comment', 500)->nullable();
            $table->string('review_status', 16)->default('reviewed')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_usage_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedInteger('requests')->default(0);
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->unsignedBigInteger('cost_estimate_usd_micros')->default(0);
            $table->unsignedInteger('blocked_requests')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_daily');
        Schema::dropIfExists('ai_feedback');
        Schema::dropIfExists('ai_action_proposals');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('knowledge_documents');
    }
};
