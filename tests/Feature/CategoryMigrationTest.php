<?php

namespace Tests\Feature;

use Database\Seeders\CoffeeProductSeeder;
use Database\Seeders\ProductCategorySeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoryMigrationTest extends TestCase
{
    private string $databaseName = 'catalog_migration_test';

    private string $originalConnection;

    private object $migration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = config('database.default');
        $pdo = new \PDO('mysql:host=127.0.0.1;port=3306', 'root', '123456');
        $pdo->exec("DROP DATABASE IF EXISTS {$this->databaseName}");
        $pdo->exec("CREATE DATABASE {$this->databaseName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        config()->set('database.connections.catalog_migration_test', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => $this->databaseName,
            'username' => 'root',
            'password' => '123456',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]);
        config()->set('database.default', 'catalog_migration_test');
        DB::purge('catalog_migration_test');

        (require database_path('migrations/2026_11_13_000003_create_product_categories_table.php'))->up();

        Schema::create('coffee_products', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->enum('locale', ['en', 'vi']);
            $table->enum('category', ['beans', 'ground', 'instant', 'freeze_dried']);
            $table->enum('bean_variety', ['arabica', 'robusta'])->nullable();
            $table->string('title');
            $table->string('eyebrow')->nullable();
            $table->text('description');
            $table->json('specs')->nullable();
            $table->string('image')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('visible')->default(true);
            $table->timestamps();
            $table->unique(['slug', 'locale']);
            $table->index('category');
        });

        $this->migration = require database_path('migrations/2026_11_13_000004_update_coffee_products_for_dynamic_catalog.php');
    }

    protected function tearDown(): void
    {
        DB::disconnect('catalog_migration_test');
        config()->set('database.default', $this->originalConnection);
        $pdo = new \PDO('mysql:host=127.0.0.1;port=3306', 'root', '123456');
        $pdo->exec("DROP DATABASE IF EXISTS {$this->databaseName}");

        parent::tearDown();
    }

    public function test_migration_backfills_by_slug_and_locale_and_rollback_restores_every_row(): void
    {
        $categories = ['beans', 'ground', 'instant', 'freeze_dried'];
        $originalRows = [];

        foreach (['en', 'vi'] as $locale) {
            foreach ($categories as $position => $category) {
                $originalRows[] = [
                    'slug' => $locale.'-'.$category,
                    'locale' => $locale,
                    'category' => $category,
                    'bean_variety' => $category === 'beans' ? 'arabica' : null,
                    'title' => strtoupper($locale).' '.$category,
                    'eyebrow' => null,
                    'description' => 'Description '.$locale.' '.$category,
                    'specs' => json_encode(['Moisture ≤ 12.5%', 'MOQ 500 kg']),
                    'image' => 'uploads/coffee/'.$locale.'-'.$category.'.jpg',
                    'sort_order' => $position,
                    'visible' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('coffee_products')->insert($originalRows);

        $this->migration->up();

        $this->assertSame(8, DB::table('product_categories')->count());
        $this->assertSame(4, DB::table('product_categories')->where('locale', 'en')->count());
        $this->assertSame(4, DB::table('product_categories')->where('locale', 'vi')->count());
        $this->assertSame(8, DB::table('coffee_products')->count());
        $this->assertFalse(Schema::hasColumn('coffee_products', 'category'));
        $this->assertTrue(Schema::hasColumn('coffee_products', 'image_alt'));
        $this->assertSame(8, DB::table('coffee_products')->whereNull('image_alt')->count());
        $this->assertSame(0, DB::table('coffee_products')->whereNull('category_id')->count());

        foreach ($originalRows as $original) {
            $product = DB::table('coffee_products')
                ->where('slug', $original['slug'])
                ->where('locale', $original['locale'])
                ->first();
            $category = DB::table('product_categories')->find($product->category_id);

            $this->assertSame($original['category'], $category->slug);
            $this->assertSame($original['locale'], $category->locale);
            $this->assertSame(json_decode($original['specs'], true), json_decode($product->specs, true));
            $this->assertSame($original['image'], $product->image);
        }

        $this->migration->down();

        $this->assertSame(8, DB::table('coffee_products')->count());
        $this->assertTrue(Schema::hasColumn('coffee_products', 'category'));
        $this->assertFalse(Schema::hasColumn('coffee_products', 'category_id'));
        $this->assertFalse(Schema::hasColumn('coffee_products', 'image_alt'));

        foreach ($originalRows as $original) {
            $this->assertSame(
                $original['category'],
                DB::table('coffee_products')->where('slug', $original['slug'])->value('category'),
            );
        }
    }

    public function test_catalogue_seeders_are_idempotent_and_match_category_locales(): void
    {
        $this->migration->up();

        (new ProductCategorySeeder)->run();
        (new ProductCategorySeeder)->run();
        (new CoffeeProductSeeder)->run();
        (new CoffeeProductSeeder)->run();

        $this->assertSame(8, DB::table('product_categories')->count());
        $this->assertSame(16, DB::table('coffee_products')->count());
        $this->assertSame('Green coffee beans', DB::table('product_categories')->where(['slug' => 'beans', 'locale' => 'en'])->value('name'));
        $this->assertSame('Cà phê nhân', DB::table('product_categories')->where(['slug' => 'beans', 'locale' => 'vi'])->value('name'));
        $this->assertSame('Ground coffee', DB::table('product_categories')->where(['slug' => 'ground', 'locale' => 'en'])->value('name'));
        $this->assertSame('Cà phê xay', DB::table('product_categories')->where(['slug' => 'ground', 'locale' => 'vi'])->value('name'));
        $this->assertSame('Instant coffee', DB::table('product_categories')->where(['slug' => 'instant', 'locale' => 'en'])->value('name'));
        $this->assertSame('Cà phê hòa tan', DB::table('product_categories')->where(['slug' => 'instant', 'locale' => 'vi'])->value('name'));
        $this->assertSame('Freeze-dried coffee', DB::table('product_categories')->where(['slug' => 'freeze_dried', 'locale' => 'en'])->value('name'));
        $this->assertSame('Cà phê sấy thăng hoa', DB::table('product_categories')->where(['slug' => 'freeze_dried', 'locale' => 'vi'])->value('name'));

        $mismatches = DB::table('coffee_products')
            ->join('product_categories', 'coffee_products.category_id', '=', 'product_categories.id')
            ->whereColumn('coffee_products.locale', '!=', 'product_categories.locale')
            ->count();
        $this->assertSame(0, $mismatches);
    }
}
