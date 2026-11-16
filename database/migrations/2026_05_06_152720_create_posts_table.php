<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 200);
            $table->string('locale', 2);
            $table->string('title');
            $table->string('excerpt', 500);
            $table->longText('body');
            $table->json('tags')->nullable();
            $table->string('cover')->nullable();
            $table->timestamp('published_at');
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();

            $table->unique(['slug', 'locale']);
            $table->index(['locale', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
