<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained();
            $table->decimal('conversion', 12, 4)->default(1);
            $table->boolean('is_base_unit')->default(false);

            $table->unique(['product_id', 'unit_id']);
            $table->auditFields();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};
