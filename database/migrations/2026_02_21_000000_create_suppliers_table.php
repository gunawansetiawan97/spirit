<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->foreignId('coa_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('coa_dp_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('city', 100)->nullable();
            $table->string('npwp_no', 20)->nullable();
            $table->string('npwp_name', 100)->nullable();
            $table->text('npwp_address')->nullable();
            $table->string('nik', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->auditFields();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
