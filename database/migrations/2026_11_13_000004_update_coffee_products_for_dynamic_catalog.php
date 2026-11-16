<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coffee_products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('category')
                ->constrained('product_categories')->restrictOnDelete();
            $table->string('image_alt')->nullable()->after('image');
        });

        $now = now();
        foreach ($this->labels() as $slug => $locales) {
            foreach ($locales as $locale => $name) {
                DB::table('product_categories')->updateOrInsert(
                    ['slug' => $slug, 'locale' => $locale],
                    [
                        'name' => $name,
                        'sort_order' => (array_search($slug, array_keys($this->labels()), true) + 1) * 10,
                        'visible' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }
        }

        $categories = DB::table('product_categories')->get()->keyBy(
            fn (object $category): string => $category->locale.':'.$category->slug,
        );

        DB::table('coffee_products')->orderBy('id')->each(function (object $product) use ($categories): void {
            $key = $product->locale.':'.$product->category;
            if (! isset($categories[$key])) {
                throw new RuntimeException("Cannot map coffee product {$product->id} to category {$key}.");
            }

            DB::table('coffee_products')->where('id', $product->id)->update([
                'category_id' => $categories[$key]->id,
            ]);
        });

        $unmapped = DB::table('coffee_products')->whereNull('category_id')->count();
        if ($unmapped > 0) {
            throw new RuntimeException("Refusing to remove legacy category: {$unmapped} products are unmapped.");
        }

        Schema::table('coffee_products', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('coffee_products', function (Blueprint $table) {
            $table->enum('category', ['beans', 'ground', 'instant', 'freeze_dried'])->nullable()->after('locale');
        });

        $categories = DB::table('product_categories')->get()->keyBy('id');
        DB::table('coffee_products')->orderBy('id')->each(function (object $product) use ($categories): void {
            $category = $categories[$product->category_id] ?? null;
            if (! $category || $category->locale !== $product->locale) {
                throw new RuntimeException("Cannot restore legacy category for coffee product {$product->id}.");
            }

            DB::table('coffee_products')->where('id', $product->id)->update([
                'category' => $category->slug,
            ]);
        });

        if (DB::table('coffee_products')->whereNull('category')->count() > 0) {
            throw new RuntimeException('Refusing to remove category relation: legacy category backfill is incomplete.');
        }

        Schema::table('coffee_products', function (Blueprint $table) {
            $table->enum('category', ['beans', 'ground', 'instant', 'freeze_dried'])->nullable(false)->change();
            $table->index('category');
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'image_alt']);
        });
    }

    private function labels(): array
    {
        return [
            'beans' => ['en' => 'Green coffee beans', 'vi' => 'Cà phê nhân'],
            'ground' => ['en' => 'Ground coffee', 'vi' => 'Cà phê xay'],
            'instant' => ['en' => 'Instant coffee', 'vi' => 'Cà phê hòa tan'],
            'freeze_dried' => ['en' => 'Freeze-dried coffee', 'vi' => 'Cà phê sấy thăng hoa'],
        ];
    }
};
