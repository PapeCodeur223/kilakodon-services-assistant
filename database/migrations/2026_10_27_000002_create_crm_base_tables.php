<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Socle CRM livré au lot 1 pour le formulaire de contact (BACKEND_SCHEMA §5, colonnes complètes).
 * `briefs` arrive au lot 2 : `leads.brief_id` reste sans contrainte de clé étrangère jusque-là.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('sector_code', 48)->nullable();
            $table->string('size_band', 16)->nullable();
            $table->string('sites_band', 16)->nullable();
            $table->string('city', 80)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name', 160);
            $table->string('phone_e164', 20)->nullable()->index();
            $table->string('whatsapp_e164', 20)->nullable()->index();
            $table->string('email', 190)->nullable()->index();
            $table->string('job_title', 120)->nullable();
            $table->string('city', 80)->nullable();
            $table->char('country_code', 2)->default('ML');
            $table->string('preferred_channel', 16)->default('whatsapp');
            $table->boolean('marketing_opt_in')->default(false);
            $table->timestamp('anonymized_at')->nullable();
            $table->foreignId('merged_into_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('visitor_key')->nullable()->index();
            $table->string('purpose', 32);
            $table->boolean('granted');
            $table->string('text_version', 16);
            $table->string('source', 32);
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->index(['contact_id', 'purpose']);
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('brief_id')->nullable()->unique();
            $table->string('type', 16);
            $table->string('source', 64);
            $table->text('message')->nullable();
            $table->string('stage', 16)->default('new')->index();
            $table->foreignId('owner_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('next_action', 255)->nullable();
            $table->timestamp('next_action_at')->nullable()->index();
            $table->unsignedTinyInteger('fit_score')->nullable();
            $table->unsignedTinyInteger('maturity_level')->nullable();
            $table->string('budget_band', 16)->nullable();
            $table->string('timeline', 16)->nullable();
            $table->string('decision_role', 16)->nullable();
            $table->unsignedBigInteger('estimated_amount_xof')->nullable();
            $table->string('lost_reason', 64)->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->char('idempotency_key', 64)->unique();
            $table->json('utm')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
        Schema::dropIfExists('consents');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('companies');
    }
};
