<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Démo Lab (BACKEND_SCHEMA §7) : un espace isolé par visiteur, données fictives, purgé après 24 h.
 * Toutes les tables `demo_*` portent `workspace_id` (ULID, cascade) et le global scope `BelongsToWorkspace`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_workspaces', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('token_hash', 64)->unique();
            $table->string('scenario', 16)->default('retail');
            $table->char('ip_hash', 64)->index();
            $table->unsignedInteger('actions_count')->default(0);
            $table->json('missions')->nullable();
            $table->dateTime('expires_at')->index(); // pas de timestamp : MariaDB y ajouterait ON UPDATE CURRENT_TIMESTAMP
            $table->timestamps();
        });

        Schema::create('demo_products', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('workspace_id')->constrained('demo_workspaces')->cascadeOnDelete();
            $table->string('sku', 32);
            $table->string('name', 120);
            $table->string('category', 48);
            $table->string('unit', 32);
            $table->unsignedInteger('price_xof');
            $table->unsignedInteger('cost_xof');
            $table->integer('stock_qty');
            $table->unsignedInteger('alert_threshold');
            $table->timestamps();
            $table->unique(['workspace_id', 'sku']);
        });

        Schema::create('demo_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('workspace_id')->constrained('demo_workspaces')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('phone_display', 32);
            $table->timestamps();
        });

        Schema::create('demo_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('workspace_id')->constrained('demo_workspaces')->cascadeOnDelete();
            $table->string('number', 16);
            $table->foreignId('customer_id')->nullable()->constrained('demo_customers')->nullOnDelete();
            $table->string('payment_method', 16);
            $table->unsignedInteger('total_xof');
            $table->dateTime('sold_at')->index();
            $table->timestamps();
            $table->unique(['workspace_id', 'number']);
        });

        Schema::create('demo_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('workspace_id')->constrained('demo_workspaces')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('demo_sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('demo_products')->cascadeOnDelete();
            $table->unsignedInteger('qty');
            $table->unsignedInteger('unit_price_xof');
            $table->unsignedInteger('line_total_xof');
            $table->timestamps();
        });

        Schema::create('demo_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('workspace_id')->constrained('demo_workspaces')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('demo_products')->cascadeOnDelete();
            $table->string('type', 16);
            $table->integer('qty');
            $table->string('reason', 160)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_stock_movements');
        Schema::dropIfExists('demo_sale_items');
        Schema::dropIfExists('demo_sales');
        Schema::dropIfExists('demo_customers');
        Schema::dropIfExists('demo_products');
        Schema::dropIfExists('demo_workspaces');
    }
};
