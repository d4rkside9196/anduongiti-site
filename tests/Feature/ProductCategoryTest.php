<?php

namespace Tests\Feature;

use App\Models\CoffeeProduct;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_slug_can_exist_once_per_locale(): void
    {
        ProductCategory::factory()->create(['slug' => 'gift-boxes', 'locale' => 'en']);
        ProductCategory::factory()->create(['slug' => 'gift-boxes', 'locale' => 'vi']);

        $this->assertSame(2, ProductCategory::where('slug', 'gift-boxes')->count());

        $this->expectException(QueryException::class);
        ProductCategory::factory()->create(['slug' => 'gift-boxes', 'locale' => 'en']);
    }

    public function test_visible_localized_categories_are_ordered_by_sort_order_then_id(): void
    {
        ProductCategory::query()->delete();

        $later = ProductCategory::factory()->create([
            'locale' => 'en', 'sort_order' => 20, 'visible' => true,
        ]);
        $first = ProductCategory::factory()->create([
            'locale' => 'en', 'sort_order' => 10, 'visible' => true,
        ]);
        $sameOrderAfterFirst = ProductCategory::factory()->create([
            'locale' => 'en', 'sort_order' => 10, 'visible' => true,
        ]);
        ProductCategory::factory()->create([
            'locale' => 'en', 'sort_order' => 0, 'visible' => false,
        ]);
        ProductCategory::factory()->forLocale('vi')->create([
            'sort_order' => 0, 'visible' => true,
        ]);

        $this->assertSame(
            [$first->id, $sameOrderAfterFirst->id, $later->id],
            ProductCategory::visible()->forLocale('en')->ordered()->pluck('id')->all(),
        );
    }

    public function test_category_defaults_to_hidden_and_casts_attributes(): void
    {
        $category = ProductCategory::factory()->create(['visible' => false, 'sort_order' => '7']);

        $this->assertFalse($category->visible);
        $this->assertSame(7, $category->sort_order);
    }

    public function test_coffee_product_factory_keeps_category_and_product_locales_aligned(): void
    {
        $product = CoffeeProduct::factory()->forLocale('vi')->create();

        $this->assertSame('vi', $product->category->locale);
    }

    public function test_coffee_products_is_a_has_many_relation_using_category_id(): void
    {
        $relation = (new ProductCategory)->coffeeProducts();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(CoffeeProduct::class, $relation->getRelated());
        $this->assertSame('coffee_products.category_id', $relation->getQualifiedForeignKeyName());
    }
}
