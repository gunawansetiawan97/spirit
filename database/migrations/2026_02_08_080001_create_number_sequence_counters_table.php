<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequence_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('number_sequence_id')->constrained()->cascadeOnDelete();
            $table->string('scope_key', 100);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['number_sequence_id', 'scope_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequence_counters');
    }
};
