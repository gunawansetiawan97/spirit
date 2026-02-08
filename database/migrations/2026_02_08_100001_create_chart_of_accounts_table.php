<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->foreignId('account_group_id')->constrained('account_groups')->restrictOnDelete();
            $table->string('posting_type', 10)->default('Posting'); // Posting / Header
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_manual_journal')->default(true);
            $table->string('currency', 5)->default('IDR');
            $table->boolean('cost_center')->default(false);
            $table->auditFields();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
