<?php

namespace Database\Migrations\Traits;

use Illuminate\Database\Schema\Blueprint;

trait AuditFieldsTrait
{
    /**
     * Add common audit fields to table
     */
    protected function addAuditFields(Blueprint $table): void
    {
        $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('approved_at')->nullable();
        $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('printed_at')->nullable();
        $table->timestamps();
        $table->softDeletes();
    }
}
