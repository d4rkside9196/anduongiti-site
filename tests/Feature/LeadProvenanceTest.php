<?php

namespace Tests\Feature;

use App\Models\CoffeeProduct;
use App\Models\Lead;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeadProvenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_enquiry_captures_immutable_provenance_and_nulls_only_fk_on_delete(): void
    {
        Mail::fake();
        $originalCategory = ProductCategory::query()->firstOrCreate(
            ['slug' => 'beans', 'locale' => 'en'],
            ['name' => 'Green coffee beans', 'visible' => true],
        );
        $product = CoffeeProduct::factory()->create([
            'slug' => 'original-sku',
            'locale' => 'en',
            'category_id' => $originalCategory->id,
            'title' => 'Original product title',
            'visible' => true,
        ]);

        $this->post('/contact', [
            'name' => 'Jane Buyer',
            'email' => 'jane@gmail.com',
            'message' => 'Please send details for this exact coffee product.',
            'product' => 'category:beans',
            'source_slug' => 'original-sku',
        ])->assertRedirect(route('contact', ['sent' => 1]));

        $lead = Lead::sole();
        $this->assertSame($product->id, $lead->coffee_product_id);
        $this->assertSame('en', $lead->source_locale);
        $this->assertSame('Original product title', $lead->source_product_title);
        $this->assertSame('beans', $lead->source_category_slug);
        $this->assertSame('original-sku', $lead->source_slug);
        $this->assertSame('category:beans', $lead->product);
        $this->assertNotNull($lead->last_activity_at);

        $newCategory = ProductCategory::query()->firstOrCreate(
            ['slug' => 'ground', 'locale' => 'en'],
            ['name' => 'Ground coffee', 'visible' => true],
        );
        $product->update([
            'slug' => 'renamed-sku',
            'title' => 'Renamed product title',
            'category_id' => $newCategory->id,
        ]);

        $lead->refresh();
        $this->assertSame('original-sku', $lead->source_slug);
        $this->assertSame('Original product title', $lead->source_product_title);
        $this->assertSame('beans', $lead->source_category_slug);
        $this->assertSame('category:beans', $lead->product);

        $product->delete();
        $lead->refresh();
        $this->assertNull($lead->coffee_product_id);
        $this->assertSame('original-sku', $lead->source_slug);
        $this->assertSame('Original product title', $lead->source_product_title);
        $this->assertSame('beans', $lead->source_category_slug);
    }

    public function test_migration_backfills_only_unique_product_matches_and_dynamic_categories(): void
    {
        $migration = require database_path('migrations/2026_11_13_000007_add_workflow_fields_to_leads_table.php');
        $migration->down();

        $beans = ProductCategory::query()->where(['slug' => 'beans', 'locale' => 'en'])->firstOrFail();
        $ground = ProductCategory::query()->where(['slug' => 'ground', 'locale' => 'en'])->firstOrFail();
        $vietnameseBeans = ProductCategory::query()->where(['slug' => 'beans', 'locale' => 'vi'])->firstOrFail();
        $unique = CoffeeProduct::factory()->create([
            'slug' => 'unique-historical-sku',
            'locale' => 'en',
            'category_id' => $beans->id,
            'title' => 'Hidden historical title',
            'visible' => false,
        ]);
        CoffeeProduct::factory()->create([
            'slug' => 'ambiguous-historical-sku',
            'locale' => 'en',
            'category_id' => $beans->id,
            'title' => 'English title',
        ]);
        CoffeeProduct::factory()->create([
            'slug' => 'ambiguous-historical-sku',
            'locale' => 'vi',
            'category_id' => $vietnameseBeans->id,
            'title' => 'Vietnamese title',
        ]);

        $updatedAt = '2025-01-02 03:04:05';
        foreach ([
            ['name' => 'Unique', 'product' => 'category:beans', 'source_slug' => 'unique-historical-sku'],
            ['name' => 'Ambiguous', 'product' => 'category:beans', 'source_slug' => 'ambiguous-historical-sku'],
            ['name' => 'Category', 'product' => 'category:ground', 'source_slug' => null],
        ] as $lead) {
            DB::table('leads')->insert(array_merge($lead, [
                'email' => strtolower($lead['name']).'@gmail.com',
                'message' => 'Historical enquiry',
                'status' => 'new',
                'created_at' => $updatedAt,
                'updated_at' => $updatedAt,
            ]));
        }

        $migration->up();

        $uniqueLead = Lead::where('name', 'Unique')->sole();
        $this->assertSame($unique->id, $uniqueLead->coffee_product_id);
        $this->assertSame('en', $uniqueLead->source_locale);
        $this->assertSame('Hidden historical title', $uniqueLead->source_product_title);
        $this->assertSame('beans', $uniqueLead->source_category_slug);
        $this->assertSame($updatedAt, $uniqueLead->last_activity_at->format('Y-m-d H:i:s'));

        $ambiguousLead = Lead::where('name', 'Ambiguous')->sole();
        $this->assertNull($ambiguousLead->coffee_product_id);
        $this->assertNull($ambiguousLead->source_locale);
        $this->assertNull($ambiguousLead->source_product_title);
        $this->assertNull($ambiguousLead->source_category_slug);
        $this->assertSame('ambiguous-historical-sku', $ambiguousLead->source_slug);
        $this->assertSame('category:beans', $ambiguousLead->product);

        $categoryLead = Lead::where('name', 'Category')->sole();
        $this->assertNull($categoryLead->coffee_product_id);
        $this->assertSame('ground', $categoryLead->source_category_slug);
        $this->assertSame($ground->slug, $categoryLead->source_category_slug);
    }

    public function test_rollback_refuses_to_discard_in_progress_rows(): void
    {
        $migration = require database_path('migrations/2026_11_13_000007_add_workflow_fields_to_leads_table.php');
        Lead::create([
            'name' => 'In progress',
            'email' => 'progress@gmail.com',
            'message' => 'Historical enquiry',
            'status' => 'in_progress',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('in_progress');

        $migration->down();
    }
}
