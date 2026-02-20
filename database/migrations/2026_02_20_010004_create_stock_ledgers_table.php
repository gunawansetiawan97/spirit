<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->dateTime('transaction_date');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('ref_type', 50);
            $table->unsignedBigInteger('ref_id');
            $table->foreignId('uom_id')->constrained('units');
            $table->string('batch_number', 50)->nullable();
            $table->decimal('qty_in', 14, 4)->default(0);
            $table->decimal('qty_out', 14, 4)->default(0);
            $table->decimal('base_qty_in', 14, 4)->default(0);
            $table->decimal('base_qty_out', 14, 4)->default(0);
            $table->decimal('base_balance', 14, 4)->default(0);
            $table->decimal('unit_cost', 16, 4)->default(0);
            $table->decimal('total_value', 18, 4)->default(0);
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'batch_number']);
            $table->index(['ref_type', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
    }
};
