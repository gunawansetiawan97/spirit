<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('prefix', 20);
            $table->string('separator', 5)->default('/');
            $table->string('format', 100);
            $table->enum('reset_type', ['none', 'daily', 'monthly', 'yearly'])->default('monthly');
            $table->enum('scope_type', ['global', 'branch'])->default('global');
            $table->tinyInteger('sequence_length')->unsigned()->default(4);
            $table->boolean('is_active')->default(true);
            $table->auditFields();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
