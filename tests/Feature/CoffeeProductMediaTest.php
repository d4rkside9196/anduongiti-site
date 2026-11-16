<?php

namespace Tests\Feature;

use App\Models\CoffeeProduct;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CoffeeProductMediaTest extends TestCase
{
    use RefreshDatabase;

    private ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->category = ProductCategory::where(['slug' => 'beans', 'locale' => 'en'])->firstOrFail();
    }

    public function test_public_card_and_detail_use_custom_alt_or_title_fallback(): void
    {
        $this->createProduct('fallback-alt', null, null);
        $this->createProduct('custom-alt', null, 'Hand-packed corporate gift box');

        $this->get('/coffee')
            ->assertOk()
            ->assertSee('alt="Fallback alt product"', false)
            ->assertSee('alt="Hand-packed corporate gift box"', false);

        $this->get('/coffee/fallback-alt')
            ->assertOk()
            ->assertSee('alt="Fallback alt product"', false);
        $this->get('/coffee/custom-alt')
            ->assertOk()
            ->assertSee('alt="Hand-packed corporate gift box"', false);
    }

    public function test_replacing_a_storage_managed_image_deletes_only_the_old_file(): void
    {
        Storage::disk('public')->put('coffee/old.jpg', 'old');
        Storage::disk('public')->put('coffee/new.jpg', 'new');
        $product = $this->createProduct('replace-image', 'coffee/old.jpg');

        $product->update(['image' => 'coffee/new.jpg']);

        Storage::disk('public')->assertMissing('coffee/old.jpg');
        Storage::disk('public')->assertExists('coffee/new.jpg');
    }

    public function test_seeded_public_upload_is_preserved_after_replacement(): void
    {
        Storage::disk('public')->put('uploads/coffee/seeded.jpg', 'seeded');
        $product = $this->createProduct('seeded-image', 'uploads/coffee/seeded.jpg');

        $product->update(['image' => 'coffee/new.jpg']);

        Storage::disk('public')->assertExists('uploads/coffee/seeded.jpg');
    }

    public function test_shared_image_is_retained_while_another_product_references_it(): void
    {
        Storage::disk('public')->put('coffee/shared.jpg', 'shared');
        $first = $this->createProduct('first-shared', 'coffee/shared.jpg');
        $second = $this->createProduct('second-shared', 'coffee/shared.jpg');

        $first->update(['image' => 'coffee/first-new.jpg']);
        Storage::disk('public')->assertExists('coffee/shared.jpg');

        $second->update(['image' => 'coffee/second-new.jpg']);
        Storage::disk('public')->assertMissing('coffee/shared.jpg');
    }

    public function test_external_image_url_is_never_sent_to_storage_cleanup(): void
    {
        $product = $this->createProduct('external-image', 'https://cdn.example.com/old.jpg');
        Storage::shouldReceive('disk')->with('public')->never();

        $product->update(['image' => 'coffee/new.jpg']);

        $this->assertSame('coffee/new.jpg', $product->fresh()->image);
    }

    public function test_missing_image_uses_an_existing_placeholder_on_card_and_detail(): void
    {
        $product = $this->createProduct('placeholder-product', null);

        $this->assertFileExists(public_path('uploads/coffee/placeholder.svg'));
        $this->assertSame(asset('uploads/coffee/placeholder.svg'), $product->image_url);
        $this->get('/coffee')->assertOk()->assertSee('/uploads/coffee/placeholder.svg', false);
        $this->get('/coffee/placeholder-product')->assertOk()->assertSee('/uploads/coffee/placeholder.svg', false);
    }

    private function createProduct(string $slug, ?string $image, ?string $imageAlt = null): CoffeeProduct
    {
        return CoffeeProduct::create([
            'slug' => $slug,
            'locale' => 'en',
            'category_id' => $this->category->id,
            'bean_variety' => 'arabica',
            'title' => ucfirst(str_replace('-', ' ', $slug)).' product',
            'eyebrow' => null,
            'description' => 'Product description for media testing.',
            'specs' => [],
            'image' => $image,
            'image_alt' => $imageAlt,
            'sort_order' => 10,
            'visible' => true,
        ]);
    }
}
