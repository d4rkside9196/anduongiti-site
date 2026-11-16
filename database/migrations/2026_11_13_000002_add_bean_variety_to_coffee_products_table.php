<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coffee_products', function (Blueprint $table) {
            $table->enum('bean_variety', ['arabica', 'robusta'])->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('coffee_products', function (Blueprint $table) {
            $table->dropColumn('bean_variety');
        });
    }
};
