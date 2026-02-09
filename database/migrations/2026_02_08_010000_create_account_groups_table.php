<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('account_type_id')->constrained('account_types')->restrictOnDelete();
            $table->string('group_name', 100);
            $table->string('normal_balance', 10); // Debit / Credit
            $table->boolean('is_active')->default(true);
            $table->auditFields();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_groups');
    }
};
