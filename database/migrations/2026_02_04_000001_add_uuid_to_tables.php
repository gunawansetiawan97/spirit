<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add UUID to branches table
        Schema::table('branches', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique()->nullable();
        });

        // Add UUID to roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique()->nullable();
        });

        // Add UUID to users table
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique()->nullable();
        });

        // Add UUID to permissions table
        Schema::table('permissions', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique()->nullable();
        });

        // Generate UUIDs for existing records
        $this->generateUuids('branches');
        $this->generateUuids('roles');
        $this->generateUuids('users');
        $this->generateUuids('permissions');
    }

    /**
     * Generate UUIDs for existing records
     */
    private function generateUuids(string $table): void
    {
        $records = DB::table($table)->whereNull('uuid')->get();
        foreach ($records as $record) {
            DB::table($table)
                ->where('id', $record->id)
                ->update(['uuid' => Str::uuid()->toString()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
