<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->json('value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64)->index();
            $table->nullableMorphs('auditable');
            $table->json('changes')->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name', 32);
            $table->smallInteger('year');
            $table->unsignedInteger('last_value')->default(0);
            $table->timestamps();
            $table->unique(['name', 'year']);
        });

        Schema::create('outbound_messages', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 16);
            $table->string('to', 190);
            $table->string('template', 64);
            $table->json('payload');
            $table->string('status', 16)->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->nullableMorphs('related');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 48)->index();
            $table->uuid('visitor_key')->nullable()->index();
            $table->string('path', 255)->nullable();
            $table->json('properties')->nullable();
            $table->json('utm')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->index(['name', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('outbound_messages');
        Schema::dropIfExists('sequences');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
    }
};
