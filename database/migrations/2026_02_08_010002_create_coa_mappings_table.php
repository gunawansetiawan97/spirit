<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coa_mappings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('module', 50);        // Sales, Purchasing, Inventory, etc.
            $table->string('mapping_key', 50);    // revenue, receivable, payable, stock, etc.
            $table->foreignId('coa_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->auditFields();

            $table->unique(['module', 'mapping_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coa_mappings');
    }
};
