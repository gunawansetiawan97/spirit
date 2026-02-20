<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 30)->unique();
            $table->date('date');
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('adjustment_type_id')->constrained();
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('posted');
            $table->auditFields();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
