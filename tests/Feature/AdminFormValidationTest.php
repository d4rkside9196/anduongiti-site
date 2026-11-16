<?php

namespace Tests\Feature;

use App\Filament\Resources\CoffeeProducts\Pages\CreateCoffeeProduct;
use App\Filament\Resources\CoffeeProducts\Pages\EditCoffeeProduct;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\CoffeeProduct;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminFormValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_post_slug_in_the_same_locale_is_a_field_error(): void
    {
        $this->createPost(['slug' => 'duplicate-slug', 'locale' => 'en']);

        Livewire::actingAs(User::factory()->create())
            ->test(CreatePost::class)
            ->fillForm($this->validPostData(['slug' => 'duplicate-slug', 'locale' => 'en']))
            ->call('create')
            ->assertHasErrors(['data.slug']);

        $this->assertSame(1, Post::where('slug', 'duplicate-slug')->count());
    }

    public function test_post_slug_must_use_the_safe_slug_format(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreatePost::class)
            ->fillForm($this->validPostData(['slug' => 'Invalid Slug!']))
            ->call('create')
            ->assertHasErrors(['data.slug']);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_same_post_slug_can_be_created_in_another_locale(): void
    {
        $this->createPost(['slug' => 'shared-slug', 'locale' => 'en']);

        Livewire::actingAs(User::factory()->create())
            ->test(CreatePost::class)
            ->fillForm($this->validPostData(['slug' => 'shared-slug', 'locale' => 'vi']))
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('posts', ['slug' => 'shared-slug', 'locale' => 'vi']);
    }

    public function test_editing_a_post_without_changing_its_slug_does_not_report_a_duplicate(): void
    {
        $post = $this->createPost(['slug' => 'unchanged-slug']);

        Livewire::actingAs(User::factory()->create())
            ->test(EditPost::class, ['record' => $post->getKey()])
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_product_key_rejects_values_outside_the_database_enum(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateProduct::class)
            ->fillForm($this->validProductData(['product_key' => 'tea']))
            ->call('create')
            ->assertHasErrors(['data.product_key']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_duplicate_product_key_in_the_same_locale_is_a_field_error(): void
    {
        $this->createProduct(['product_key' => 'coffee', 'locale' => 'en']);

        Livewire::actingAs(User::factory()->create())
            ->test(CreateProduct::class)
            ->fillForm($this->validProductData(['product_key' => 'coffee', 'locale' => 'en']))
            ->call('create')
            ->assertHasErrors(['data.product_key']);

        $this->assertSame(1, Product::where('product_key', 'coffee')->where('locale', 'en')->count());
    }

    public function test_unused_product_key_and_locale_pair_can_be_created(): void
    {
        $this->createProduct(['product_key' => 'coffee', 'locale' => 'en']);

        Livewire::actingAs(User::factory()->create())
            ->test(CreateProduct::class)
            ->fillForm($this->validProductData(['product_key' => 'rubber', 'locale' => 'en']))
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', ['product_key' => 'rubber', 'locale' => 'en']);
    }

    public function test_editing_a_product_without_changing_its_key_does_not_report_a_duplicate(): void
    {
        $product = $this->createProduct(['product_key' => 'coffee', 'locale' => 'en']);

        Livewire::actingAs(User::factory()->create())
            ->test(EditProduct::class, ['record' => $product->getKey()])
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_bean_products_require_a_variety(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateCoffeeProduct::class)
            ->fillForm($this->validCoffeeProductData([
                'category' => 'beans',
                'bean_variety' => null,
            ]))
            ->call('create')
            ->assertHasErrors(['data.bean_variety' => 'required']);

        $this->assertDatabaseCount('coffee_products', 0);
    }

    public function test_bean_variety_rejects_values_outside_the_supported_options(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateCoffeeProduct::class)
            ->fillForm($this->validCoffeeProductData([
                'category' => 'beans',
                'bean_variety' => 'liberica',
            ]))
            ->call('create')
            ->assertHasErrors(['data.bean_variety']);

        $this->assertDatabaseCount('coffee_products', 0);
    }

    public function test_changing_a_bean_product_to_another_category_clears_its_variety(): void
    {
        $product = CoffeeProduct::create($this->validCoffeeProductData([
            'category' => 'beans',
            'bean_variety' => 'arabica',
        ]));

        $ground = ProductCategory::where(['slug' => 'ground', 'locale' => 'en'])->firstOrFail();

        Livewire::actingAs(User::factory()->create())
            ->test(EditCoffeeProduct::class, ['record' => $product->getKey()])
            ->set('data.category_id', $ground->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($ground->id, $product->fresh()->category_id);
        $this->assertNull($product->fresh()->bean_variety);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPost(array $overrides = []): Post
    {
        return Post::create($this->validPostData($overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPostData(array $overrides = []): array
    {
        return array_merge([
            'slug' => 'valid-post-slug',
            'locale' => 'en',
            'title' => 'A valid post',
            'excerpt' => 'A valid excerpt for the post.',
            'body' => 'The complete body of the post.',
            'tags' => [],
            'cover' => null,
            'published_at' => '2026-11-13 00:00:00',
            'status' => 'draft',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createProduct(array $overrides = []): Product
    {
        $data = $this->validProductData($overrides);
        $data['specs'] = [['Origin', 'Vietnam']];

        return Product::create($data);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validProductData(array $overrides = []): array
    {
        return array_merge([
            'product_key' => 'coffee',
            'locale' => 'en',
            'title' => 'Coffee',
            'eyebrow' => 'Vietnamese coffee',
            'lede' => 'Coffee for international buyers.',
            'specs' => [['key' => 'Origin', 'value' => 'Vietnam']],
            'origins' => ['Vietnam'],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validCoffeeProductData(array $overrides = []): array
    {
        $locale = $overrides['locale'] ?? 'en';
        $categorySlug = $overrides['category'] ?? 'beans';
        unset($overrides['category']);
        $category = ProductCategory::firstOrCreate(
            ['slug' => $categorySlug, 'locale' => $locale],
            ['name' => ucfirst($categorySlug), 'visible' => true],
        );

        return array_merge([
            'locale' => $locale,
            'category_id' => $category->id,
            'bean_variety' => 'arabica',
            'slug' => 'valid-coffee-slug',
            'title' => 'Valid coffee',
            'eyebrow' => 'Single origin',
            'description' => 'A complete product description.',
            'specs' => [],
            'image' => null,
            'sort_order' => 0,
            'visible' => true,
        ], $overrides);
    }
}
