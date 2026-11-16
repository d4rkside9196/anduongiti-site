<?php

namespace Tests\Feature;

use App\Models\CoffeeProduct;
use App\Models\Lead;
use App\Models\ProductCategory;
use App\Support\ContactProductOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DynamicLeadCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dynamic_category_options_are_localized_and_only_include_visible_categories(): void
    {
        $this->createCategory('en', 'Gift boxes');
        $this->createCategory('vi', 'Hộp quà cà phê');
        $this->createCategory('en', 'Hidden gifts', false, 'hidden-gifts');

        $english = ContactProductOptions::all('en');
        $vietnamese = ContactProductOptions::all('vi');

        $this->assertSame('Gift boxes', $english['category:gift-boxes']);
        $this->assertSame('Hộp quà cà phê', $vietnamese['category:gift-boxes']);
        $this->assertArrayHasKey('arabica_beans', $english);
        $this->assertArrayHasKey('rubber', $english);
        $this->assertArrayNotHasKey('category:hidden-gifts', $english);
    }

    public function test_cta_submission_stores_dynamic_category_and_exact_source_slug(): void
    {
        Mail::fake();
        $category = $this->createCategory('en', 'Gift boxes');
        $product = $this->createProduct($category, 'corporate-gift-box');

        $this->get('/coffee/corporate-gift-box')
            ->assertOk()
            ->assertSee('product=corporate-gift-box', false);
        $this->get('/contact?product=corporate-gift-box')
            ->assertOk()
            ->assertSee('value="category:gift-boxes" selected', false)
            ->assertSee('name="source_slug" value="corporate-gift-box"', false);

        $this->post('/contact', $this->payload([
            'product' => 'category:gift-boxes',
            'source_slug' => $product->slug,
        ]))->assertRedirect(route('contact', ['sent' => 1]));

        $lead = Lead::sole();
        $this->assertSame('category:gift-boxes', $lead->product);
        $this->assertSame('corporate-gift-box', $lead->source_slug);
    }

    public function test_direct_dynamic_and_legacy_product_values_are_accepted(): void
    {
        Mail::fake();
        $this->createCategory('en', 'Gift boxes');

        foreach (['category:gift-boxes', 'arabica_beans', 'rubber'] as $productKey) {
            $this->post('/contact', $this->payload(['product' => $productKey]))
                ->assertRedirect(route('contact', ['sent' => 1]));
        }

        $this->assertSame(
            ['category:gift-boxes', 'arabica_beans', 'rubber'],
            Lead::orderBy('id')->pluck('product')->all(),
        );
    }

    public function test_longest_allowed_dynamic_category_key_is_accepted_and_persisted(): void
    {
        Mail::fake();
        $slug = str_repeat('a', 255);
        $this->createCategory('en', 'Long category', true, $slug);
        $productKey = 'category:'.$slug;

        $this->assertSame(264, strlen($productKey));
        $response = $this->post('/contact', $this->payload(['product' => $productKey]));
        $response->assertRedirect(route('contact', ['sent' => 1]));

        $this->assertSame($productKey, Lead::sole()->product);
    }

    public function test_hidden_or_wrong_locale_dynamic_categories_are_rejected(): void
    {
        Mail::fake();
        $this->createCategory('en', 'Hidden gifts', false, 'hidden-gifts');
        $this->createCategory('vi', 'Hộp quà cà phê');

        foreach (['category:hidden-gifts', 'category:gift-boxes', 'category:unknown'] as $productKey) {
            $this->post('/contact', $this->payload(['product' => $productKey]))
                ->assertSessionHasErrors(['product']);
        }

        $this->assertDatabaseCount('leads', 0);
    }

    private function createCategory(string $locale, string $name, bool $visible = true, string $slug = 'gift-boxes'): ProductCategory
    {
        return ProductCategory::create([
            'slug' => $slug,
            'locale' => $locale,
            'name' => $name,
            'sort_order' => 10,
            'visible' => $visible,
        ]);
    }

    private function createProduct(ProductCategory $category, string $slug): CoffeeProduct
    {
        return CoffeeProduct::create([
            'slug' => $slug,
            'locale' => $category->locale,
            'category_id' => $category->id,
            'title' => 'Corporate gift box',
            'description' => 'A gift box for corporate buyers.',
            'specs' => ['MOQ 10 boxes'],
            'visible' => true,
            'sort_order' => 10,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Buyer',
            'email' => 'jane@gmail.com',
            'message' => 'Please send details for this product line.',
            'company' => 'Example Roasters Ltd',
            'country' => 'United Kingdom',
        ], $overrides);
    }
}
