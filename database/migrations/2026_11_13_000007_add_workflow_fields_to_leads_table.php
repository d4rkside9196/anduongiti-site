<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE leads MODIFY status ENUM('new', 'in_progress', 'replied', 'archived') NOT NULL DEFAULT 'new'");

        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('status')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('coffee_product_id')->nullable()->after('source_slug')
                ->constrained('coffee_products')->nullOnDelete();
            $table->string('source_locale', 10)->nullable()->after('coffee_product_id');
            $table->string('source_product_title')->nullable()->after('source_locale');
            $table->string('source_category_slug')->nullable()->after('source_product_title');
            $table->timestamp('first_response_at')->nullable()->after('source_category_slug');
            $table->timestamp('last_activity_at')->nullable()->after('first_response_at');

            $table->index(['assigned_to', 'status']);
            $table->index('last_activity_at');
        });

        DB::table('leads')->update(['last_activity_at' => DB::raw('updated_at')]);

        DB::table('leads')->orderBy('id')->each(function (object $lead): void {
            $updates = [];

            if (filled($lead->source_slug)) {
                $products = DB::table('coffee_products')
                    ->leftJoin('product_categories', 'product_categories.id', '=', 'coffee_products.category_id')
                    ->where('coffee_products.slug', $lead->source_slug)
                    ->limit(2)
                    ->get([
                        'coffee_products.id',
                        'coffee_products.locale',
                        'coffee_products.title',
                        'product_categories.slug as category_slug',
                    ]);

                if ($products->count() === 1) {
                    $product = $products->first();
                    $updates = [
                        'coffee_product_id' => $product->id,
                        'source_locale' => $product->locale,
                        'source_product_title' => $product->title,
                        'source_category_slug' => $product->category_slug,
                    ];
                }
            } elseif (is_string($lead->product) && str_starts_with($lead->product, 'category:')) {
                $categorySlug = substr($lead->product, strlen('category:'));
                if ($categorySlug !== '') {
                    $updates['source_category_slug'] = $categorySlug;
                }
            }

            if ($updates !== []) {
                DB::table('leads')->where('id', $lead->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        if (DB::table('leads')->where('status', 'in_progress')->exists()) {
            throw new RuntimeException('Refusing to restore the legacy status enum while in_progress leads remain.');
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['coffee_product_id']);
            $table->dropIndex(['assigned_to', 'status']);
            $table->dropIndex(['last_activity_at']);
            $table->dropColumn([
                'assigned_to',
                'coffee_product_id',
                'source_locale',
                'source_product_title',
                'source_category_slug',
                'first_response_at',
                'last_activity_at',
            ]);
        });

        DB::statement("ALTER TABLE leads MODIFY status ENUM('new', 'replied', 'archived') NOT NULL DEFAULT 'new'");
    }
};
