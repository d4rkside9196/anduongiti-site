<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'catalog_manager', 'support'])
                ->default('catalog_manager')
                ->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->index(['role', 'is_active']);
        });

        // Existing non-seeded accounts require an explicit administrator review.
        DB::table('users')
            ->where('email', '!=', 'admin@anduongiti.com')
            ->update(['is_active' => false]);

        DB::table('users')
            ->where('email', 'admin@anduongiti.com')
            ->update(['role' => 'super_admin', 'is_active' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'is_active']);
            $table->dropColumn(['role', 'is_active', 'last_login_at']);
        });
    }
};
