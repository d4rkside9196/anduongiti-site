<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('company', 200)->nullable();
            $table->string('email', 200);
            $table->string('country', 100)->nullable();
            $table->string('product', 100)->nullable();
            $table->text('message');
            $table->enum('status', ['new', 'replied', 'archived'])->default('new');
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
