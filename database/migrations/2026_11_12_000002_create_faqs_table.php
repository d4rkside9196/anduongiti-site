<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            // Row-per-locale content, matching the products/posts pattern.
            $table->enum('locale', ['en', 'vi']);
            // Free-form grouping key (orders / shipping / products / payment / ...),
            // rendered as accordion sections on /support in sort_order.
            $table->string('category');
            $table->string('question');
            $table->text('answer');
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->index(['locale', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
