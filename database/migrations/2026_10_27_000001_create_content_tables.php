<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $this->publishable($table);
            $table->string('module_code', 48)->nullable()->index();
            $table->string('title', 120);
            $table->string('tagline', 200);
            $table->string('icon', 48);
            $table->text('problem');
            $table->text('audience');
            $table->json('benefits');
            $table->json('features');
            $table->json('process')->nullable();
            $table->text('limits')->nullable();
            $table->string('duration_hint', 120)->nullable();
            $table->string('price_hint', 120)->nullable();
            $table->json('faq')->nullable();
        });

        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $this->publishable($table);
            $table->string('code', 48)->unique();
            $table->string('name', 120);
            $table->string('icon', 48)->nullable();
            $table->string('headline', 200);
            $table->text('intro');
            $table->json('pain_points');
            $table->json('outcomes');
            $table->string('demo_slug')->nullable();
        });

        Schema::create('sector_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->unique(['sector_id', 'service_id']);
        });

        Schema::create('case_studies', function (Blueprint $table) {
            $table->id();
            $this->publishable($table);
            $table->string('title', 160);
            $table->string('client_display', 16);
            $table->string('client_name', 160)->nullable();
            $table->string('client_descriptor', 160);
            $table->foreignId('sector_id')->nullable()->constrained()->nullOnDelete();
            $table->text('context');
            $table->text('challenge');
            $table->text('intervention');
            $table->json('deliverables');
            $table->text('results')->nullable();
            $table->boolean('results_verified')->default(false);
            $table->text('quote')->nullable();
            $table->string('quote_author')->nullable();
            $table->string('authorization_status', 16)->default('none');
            $table->text('authorization_note')->nullable();
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('authorized_at')->nullable();
            $table->string('cover_path')->nullable();
        });

        Schema::create('case_study_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_study_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unique(['case_study_id', 'service_id']);
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $this->publishable($table);
            $table->string('title', 160);
            $table->string('excerpt', 300);
            $table->longText('body');
            $table->string('category', 48)->index();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('reading_minutes')->default(1);
            $table->string('cover_path')->nullable();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $this->publishable($table, slug: false);
            $table->string('question', 255);
            $table->text('answer');
            $table->string('category', 48)->index();
            $table->boolean('on_home')->default(false);
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $this->publishable($table);
            $table->string('title', 160);
            $table->longText('body');
            $table->string('template', 32)->default('default');
        });

        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('revisionable');
            $table->unsignedInteger('version');
            $table->json('data');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->unique(['revisionable_type', 'revisionable_id', 'version'], 'content_revisions_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_revisions');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('case_study_service');
        Schema::dropIfExists('case_studies');
        Schema::dropIfExists('sector_service');
        Schema::dropIfExists('sectors');
        Schema::dropIfExists('services');
    }

    /**
     * Colonnes communes du trait Publishable (BACKEND_SCHEMA §3).
     */
    private function publishable(Blueprint $table, bool $slug = true): void
    {
        if ($slug) {
            $table->string('slug', 140)->unique();
        }
        $table->string('status', 16)->default('draft')->index();
        $table->timestamp('published_at')->nullable();
        $table->string('seo_title', 70)->nullable();
        $table->string('seo_description', 170)->nullable();
        $table->string('og_image_path')->nullable();
        $table->unsignedSmallInteger('sort')->default(0);
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
        $table->softDeletes();
    }
};
