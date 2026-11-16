<?php

namespace Tests\Feature;

use App\Models\CoffeeProduct;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicCatalogTest extends TestCase
{
    use RefreshDatabase;

    private ProductCategory $englishCategory;

    private ProductCategory $vietnameseCategory;

    protected function setUp(): void
    {
        putenv('ROUTING_LOCALE');
        if (str_starts_with($this->name(), 'test_vi_')) {
            putenv('ROUTING_LOCALE=vi');
        }
        parent::setUp();

        $this->englishCategory = $this->createCategory('en', 'Gift boxes', 5);
        $this->vietnameseCategory = $this->createCategory('vi', 'Hộp quà cà phê', 5);
        $this->createProduct($this->englishCategory, 'english-gift-box', 'English gift box', true);
        $this->createProduct($this->vietnameseCategory, 'vietnamese-gift-box', 'Hộp quà Việt Nam', true);
    }

    protected function tearDown(): void
    {
        putenv('ROUTING_LOCALE');
        parent::tearDown();
    }

    public function test_index_displays_localized_dynamic_category_groups(): void
    {
        $this->get('/coffee')
            ->assertOk()
            ->assertSee('Gift boxes')
            ->assertSee('English gift box')
            ->assertDontSee('Hộp quà Việt Nam');
    }

    public function test_vi_index_displays_localized_dynamic_category_groups(): void
    {
        $this->get('/vi/coffee')
            ->assertOk()
            ->assertSee('Hộp quà cà phê')
            ->assertSee('Hộp quà Việt Nam')
            ->assertDontSee('English gift box');
    }

    public function test_category_filter_limits_products_and_sets_noindex_follow_header(): void
    {
        $otherCategory = $this->createCategory('en', 'Wholesale lots', 20, true, 'wholesale-lots');
        $this->createProduct($otherCategory, 'wholesale-lot', 'Wholesale lot', true);

        $this->get('/coffee?category=gift-boxes')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex,follow')
            ->assertSee('English gift box')
            ->assertDontSee('Wholesale lot');

        $this->get('/coffee')->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_non_string_category_filter_returns_empty_result_instead_of_an_error(): void
    {
        $this->get('/coffee?category[]=gift-boxes')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex,follow')
            ->assertDontSee('English gift box');
    }

    public function test_invalid_filter_returns_an_empty_result_instead_of_an_error(): void
    {
        $this->get('/coffee?category=does-not-exist')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex,follow')
            ->assertDontSee('English gift box');
    }

    public function test_hidden_categories_and_products_stay_private(): void
    {
        $hiddenCategory = $this->createCategory('en', 'Private category', 1, false, 'private-category');
        $this->createProduct($hiddenCategory, 'private-category-product', 'Private category product', true);
        $this->createProduct($this->englishCategory, 'hidden-gift-box', 'Hidden gift box', false);

        $this->get('/coffee')
            ->assertOk()
            ->assertDontSee('Private category')
            ->assertDontSee('Private category product')
            ->assertDontSee('Hidden gift box');

        $this->get('/coffee?category=private-category')
            ->assertOk()
            ->assertDontSee('Private category product');
    }

    public function test_product_detail_and_sitemap_keep_existing_urls_without_filter_entries(): void
    {
        $this->get('/coffee/english-gift-box')->assertOk();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/coffee/english-gift-box', false)
            ->assertDontSee('?category=', false);
    }

    public function test_product_json_ld_uses_the_localized_category_name(): void
    {
        $response = $this->get('/coffee/english-gift-box')->assertOk();
        $matched = preg_match(
            '/<script type="application\/ld\+json">(.*?)<\/script>/s',
            $response->getContent(),
            $matches,
        );

        $this->assertSame(1, $matched);
        $jsonLd = json_decode(trim($matches[1]), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Gift boxes', $jsonLd['category']);
        $this->assertSame('english-gift-box', $jsonLd['sku']);
    }

    private function createCategory(string $locale, string $name, int $sortOrder, bool $visible = true, string $slug = 'gift-boxes'): ProductCategory
    {
        return ProductCategory::create([
            'slug' => $slug,
            'locale' => $locale,
            'name' => $name,
            'description' => null,
            'sort_order' => $sortOrder,
            'visible' => $visible,
        ]);
    }

    private function createProduct(ProductCategory $category, string $slug, string $title, bool $visible): CoffeeProduct
    {
        return CoffeeProduct::create([
            'slug' => $slug,
            'locale' => $category->locale,
            'category_id' => $category->id,
            'bean_variety' => null,
            'title' => $title,
            'eyebrow' => null,
            'description' => $title.' description',
            'specs' => ['MOQ 10 boxes'],
            'image' => null,
            'sort_order' => 10,
            'visible' => $visible,
        ]);
    }
}
