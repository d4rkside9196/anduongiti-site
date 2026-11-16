<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReleaseArtifactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_mysql_release_database_contains_every_migration_record(): void
    {
        $migrationFiles = array_map(
            fn (string $path): string => basename($path, '.php'),
            glob(database_path('migrations/*.php')) ?: [],
        );
        $recordedMigrations = DB::table('migrations')->pluck('migration')->all();

        sort($migrationFiles);
        sort($recordedMigrations);

        $this->assertSame(
            $migrationFiles,
            $recordedMigrations,
            'The MySQL release database migration ledger is out of sync with database/migrations.',
        );
    }

    public function test_mysql_release_database_contains_seeded_business_data(): void
    {
        $expectedCoffeeSlugs = [
            'arabica-cau-dat-green-beans',
            'arabica-cau-dat-ground-250g',
            'freeze-dried-black-coffee-100g',
            'freeze-dried-premium-jar-80g',
            'instant-3in1-16g-sticks',
            'instant-black-2g-sticks',
            'robusta-house-blend-ground-500g',
            'robusta-scratch-18-bean-coffee',
        ];
        $expectedFaqCategories = ['oem', 'orders', 'payment', 'products', 'samples', 'shipping'];
        $expectedBeanVarieties = [
            'arabica-cau-dat-green-beans' => 'arabica',
            'robusta-scratch-18-bean-coffee' => 'robusta',
        ];

        foreach (['en', 'vi'] as $locale) {
            $coffeeSlugs = DB::table('coffee_products')
                ->where('locale', $locale)
                ->where('visible', true)
                ->orderBy('slug')
                ->pluck('slug')
                ->all();
            $faqCategories = DB::table('faqs')
                ->where('locale', $locale)
                ->where('visible', true)
                ->orderBy('category')
                ->pluck('category')
                ->all();
            $productKeys = DB::table('products')
                ->where('locale', $locale)
                ->whereIn('product_key', ['coffee', 'rubber'])
                ->orderBy('product_key')
                ->pluck('product_key')
                ->all();
            $beanVarieties = DB::table('coffee_products')
                ->where('locale', $locale)
                ->whereIn('slug', array_keys($expectedBeanVarieties))
                ->orderBy('slug')
                ->pluck('bean_variety', 'slug')
                ->all();

            $this->assertSame($expectedCoffeeSlugs, $coffeeSlugs, "Visible coffee slugs drifted for {$locale}.");
            $this->assertSame($expectedFaqCategories, $faqCategories, "Visible FAQ categories drifted for {$locale}.");
            $this->assertSame(['coffee', 'rubber'], $productKeys, "Product sources drifted for {$locale}.");
            $this->assertSame($expectedBeanVarieties, $beanVarieties, "Bean varieties drifted for {$locale}.");
        }

        $admin = DB::table('users')->where('email', 'admin@anduongiti.com')->first();

        $this->assertNotNull($admin, 'The documented admin is missing from the MySQL release database.');
        $this->assertNotEmpty($admin->password, 'The documented admin password hash is missing from the MySQL release database.');
    }

    public function test_mysql_dumps_include_migration_ledger_and_current_schema(): void
    {
        $fullDump = file_get_contents(database_path('anduongiti.sql'));
        $schemaDump = file_get_contents(database_path('anduongiti-schema.sql'));
        $dataDump = file_get_contents(database_path('anduongiti-data.sql'));

        foreach (['2026_11_13_000003_create_product_categories_table', '2026_11_13_000006_create_activity_logs_table'] as $migration) {
            $this->assertStringContainsString($migration, $fullDump);
            $this->assertStringContainsString($migration, $dataDump);
        }

        foreach (['product_categories', 'source_slug', 'bean_variety', 'role', 'activity_logs'] as $column) {
            $this->assertStringContainsString($column, $schemaDump);
        }
    }

    public function test_english_catalog_and_support_routes_work_on_mysql(): void
    {
        $this->get('/coffee')->assertOk();
        $this->get('/support')->assertOk();
    }

    public function test_vietnamese_catalog_and_support_routes_work_on_mysql(): void
    {
        // Locale prefixes are registered while routes load, before a feature
        // request exists. Reboot with the package's routing locale for /vi/*.
        putenv('ROUTING_LOCALE=vi');

        try {
            $this->refreshApplication();
            $this->get('/vi/coffee')->assertOk();
            $this->get('/vi/support')->assertOk();
        } finally {
            putenv('ROUTING_LOCALE');
        }
    }
}
