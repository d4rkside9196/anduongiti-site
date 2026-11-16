<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coffee_products', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            // Row-per-locale content, matching the products/posts pattern.
            $table->enum('locale', ['en', 'vi']);
            $table->enum('category', ['beans', 'ground', 'instant', 'freeze_dried']);
            $table->string('title');
            $table->string('eyebrow')->nullable();
            $table->text('description');
            // Flat list of spec strings ("Moisture ≤ 12.5%", "MOQ 1 × 20' FCL", ...).
            $table->json('specs')->nullable();
            // Path under public/ (seeded placeholders) or the public disk (uploads).
            $table->string('image')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->unique(['slug', 'locale']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coffee_products');
    }
};
