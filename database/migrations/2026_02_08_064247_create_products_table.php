<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_category_id')->nullable()->constrained();
            $table->foreignId('product_brand_id')->nullable()->constrained(); 
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->enum('type', ['stock', 'non-stock' ])->default('stock');
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->decimal('max_stock', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->foreignId('coa_inventory_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('coa_cogs_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('coa_sales_id')->nullable()->constrained('chart_of_accounts');
            $table->boolean('is_active')->default(true);
            $table->auditFields();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    { 
        Schema::dropIfExists('products');
    }
};
