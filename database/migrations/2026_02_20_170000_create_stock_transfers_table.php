<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 30)->unique();
            $table->date('date');
            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'posted'])->default('draft');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->auditFields();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
