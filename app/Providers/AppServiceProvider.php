<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Blueprint::macro('auditFields', function () {
            $this->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $this->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $this->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $this->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $this->timestamp('approved_at')->nullable();
            $this->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $this->timestamp('printed_at')->nullable();
            $this->timestamps();
            $this->softDeletes();
        });

        Blueprint::macro('dropAuditFields', function () {
            $this->dropForeign(['created_by', 'updated_by', 'deleted_by', 'approved_by', 'printed_by']);
            $this->dropColumn(['created_by', 'updated_by', 'deleted_by', 'approved_by', 'approved_at', 'printed_by', 'printed_at', 'deleted_at','created_at','updated_at']);
        });
    }
}
