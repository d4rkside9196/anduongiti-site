<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->enum('product_key', ['coffee', 'rubber']);
            $table->string('locale', 2);
            $table->string('title');
            $table->string('eyebrow');
            $table->text('lede');
            $table->json('specs');
            $table->json('origins');
            $table->timestamps();

            $table->unique(['product_key', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
