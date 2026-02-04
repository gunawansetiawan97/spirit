<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add audit fields to branches table
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('printed_at')->nullable();
            $table->softDeletes();
        });

        // Add audit fields to roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('printed_at')->nullable();
            $table->softDeletes();
        });

        // Add audit fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('printed_at')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['printed_by']);
            $table->dropColumn(['created_by', 'updated_by', 'deleted_by', 'approved_by', 'approved_at', 'printed_by', 'printed_at', 'deleted_at']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['printed_by']);
            $table->dropColumn(['branch_id', 'created_by', 'updated_by', 'deleted_by', 'approved_by', 'approved_at', 'printed_by', 'printed_at', 'deleted_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['printed_by']);
            $table->dropColumn(['created_by', 'updated_by', 'deleted_by', 'approved_by', 'approved_at', 'printed_by', 'printed_at', 'deleted_at']);
        });
    }
};
