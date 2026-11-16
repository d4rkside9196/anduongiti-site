<?php

namespace Tests\Feature;

use App\Filament\Resources\CoffeeProducts\Pages\CreateCoffeeProduct;
use App\Filament\Resources\CoffeeProducts\Pages\EditCoffeeProduct;
use App\Filament\Resources\CoffeeProducts\Pages\ListCoffeeProducts;
use App\Filament\Resources\ProductCategories\Pages\CreateProductCategories;
use App\Filament\Resources\ProductCategories\Pages\EditProductCategories;
use App\Filament\Resources\ProductCategories\Pages\ListProductCategories;
use App\Models\CoffeeProduct;
use App\Models\ProductCategory;
use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    public function test_category_resource_is_available_at_the_admin_route(): void
    {
        $this->get('/admin/product-categories')->assertOk();
    }

    public function test_category_slug_is_scoped_unique_per_locale_and_must_be_kebab_case(): void
    {
        $this->createCategory('en', 'gift-boxes');

        Livewire::test(CreateProductCategories::class)
            ->fillForm($this->validCategoryData(['locale' => 'en', 'slug' => 'gift-boxes']))
            ->call('create')
            ->assertHasErrors(['data.slug']);

        Livewire::test(CreateProductCategories::class)
            ->fillForm($this->validCategoryData(['locale' => 'vi', 'slug' => 'gift-boxes']))
            ->call('create')
            ->assertHasNoErrors();

        Livewire::test(CreateProductCategories::class)
            ->fillForm($this->validCategoryData(['slug' => 'Invalid Slug!']))
            ->call('create')
            ->assertHasErrors(['data.slug']);

        $this->assertSame(2, ProductCategory::where('slug', 'gift-boxes')->count());
    }

    public function test_legacy_underscore_category_slug_can_be_kept_when_editing(): void
    {
        $category = ProductCategory::where(['slug' => 'freeze_dried', 'locale' => 'en'])->firstOrFail();

        Livewire::test(EditProductCategories::class, ['record' => $category->getKey()])
            ->fillForm(['name' => 'Freeze-dried coffee updated'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Freeze-dried coffee updated', $category->fresh()->name);
        $this->assertSame('freeze_dried', $category->fresh()->slug);
    }

    public function test_category_locale_cannot_change_while_products_are_attached(): void
    {
        $category = $this->createCategory('en', 'gift-boxes');
        $product = $this->createProduct($category, 'english-gift-box', true);

        Livewire::test(EditProductCategories::class, ['record' => $category->getKey()])
            ->fillForm(['locale' => 'vi'])
            ->call('save')
            ->assertHasErrors(['data.locale']);

        $this->assertSame('en', $category->fresh()->locale);
        $this->assertSame('en', $product->fresh()->locale);
        $this->assertSame($category->id, $product->fresh()->category_id);
    }

    public function test_coffee_product_rejects_a_category_from_another_locale(): void
    {
        $vietnamese = $this->createCategory('vi', 'gift-boxes', 'Hộp quà');

        Livewire::test(CreateCoffeeProduct::class)
            ->fillForm($this->validCoffeeData(['locale' => 'en', 'category_id' => $vietnamese->id]))
            ->call('create')
            ->assertHasErrors(['data.category_id']);

        $this->assertDatabaseCount('coffee_products', 0);
    }

    public function test_coffee_product_create_rejects_an_unsafe_slug(): void
    {
        Livewire::test(CreateCoffeeProduct::class)
            ->fillForm($this->validCoffeeData(['slug' => 'invalid slug/with spaces']))
            ->call('create')
            ->assertHasErrors(['data.slug']);

        $this->assertDatabaseCount('coffee_products', 0);
    }

    public function test_coffee_product_edit_rejects_an_unsafe_slug_and_preserves_the_original(): void
    {
        $category = ProductCategory::where(['slug' => 'beans', 'locale' => 'en'])->firstOrFail();
        $product = $this->createProduct($category, 'valid-coffee-slug', true);

        Livewire::test(EditCoffeeProduct::class, ['record' => $product->getKey()])
            ->fillForm(['slug' => 'invalid/slug'])
            ->call('save')
            ->assertHasErrors(['data.slug']);

        $this->assertSame('valid-coffee-slug', $product->fresh()->slug);
    }

    public function test_coffee_form_only_displays_categories_for_the_selected_locale(): void
    {
        $english = $this->createCategory('en', 'english-only', 'English category');
        $vietnamese = $this->createCategory('vi', 'vietnamese-only', 'Vietnamese category');

        $component = Livewire::test(CreateCoffeeProduct::class)
            ->fillForm(['locale' => 'en'])
            ->assertFormFieldExists('category_id', checkFieldUsing: function (Select $field) use ($english, $vietnamese): bool {
                $options = $field->getOptions();

                return ($options[$english->id] ?? null) === 'English category'
                    && ! array_key_exists($vietnamese->id, $options);
            });

        $component->fillForm(['locale' => 'vi'])
            ->assertFormFieldExists('category_id', checkFieldUsing: function (Select $field) use ($english, $vietnamese): bool {
                $options = $field->getOptions();

                return ($options[$vietnamese->id] ?? null) === 'Vietnamese category'
                    && ! array_key_exists($english->id, $options);
            });
    }

    public function test_category_table_filters_searches_and_sorts_records(): void
    {
        $first = $this->createCategory('en', 'alpha-search', 'Alpha search', false, 10);
        $second = $this->createCategory('en', 'beta-search', 'Beta search', false, 20);
        $visible = $this->createCategory('en', 'visible-other', 'Visible other', true, 5);

        Livewire::test(ListProductCategories::class)
            ->filterTable('visible', false)
            ->assertCanSeeTableRecords([$first, $second])
            ->assertCanNotSeeTableRecords([$visible])
            ->searchTable('Beta search')
            ->assertCanSeeTableRecords([$second])
            ->assertCanNotSeeTableRecords([$first])
            ->searchTable('search')
            ->sortTable('sort_order', 'desc')
            ->assertCanSeeTableRecords([$second, $first], inOrder: true);
    }

    public function test_coffee_table_filters_by_visibility_and_category(): void
    {
        $beans = ProductCategory::where(['slug' => 'beans', 'locale' => 'en'])->firstOrFail();
        $ground = ProductCategory::where(['slug' => 'ground', 'locale' => 'en'])->firstOrFail();
        $visible = $this->createProduct($beans, 'visible-beans', true);
        $hidden = $this->createProduct($ground, 'hidden-ground', false);

        Livewire::test(ListCoffeeProducts::class)
            ->filterTable('visible', false)
            ->assertCanSeeTableRecords([$hidden])
            ->assertCanNotSeeTableRecords([$visible])
            ->filterTable('visible', null)
            ->filterTable('category_id', $beans->id)
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    public function test_bulk_show_and_hide_use_shared_visibility_action_and_report_changed_count(): void
    {
        $category = ProductCategory::where(['slug' => 'beans', 'locale' => 'en'])->firstOrFail();
        $hidden = $this->createProduct($category, 'hidden-product', false);
        $visible = $this->createProduct($category, 'visible-product', true);

        Livewire::test(ListCoffeeProducts::class)
            ->callTableBulkAction('show', [$hidden, $visible]);

        $this->assertTrue($hidden->fresh()->visible);
        $this->assertTrue($visible->fresh()->visible);
        $this->assertSame('1 record shown.', session('filament.claimed_notifications.0.title'));
        session()->forget('filament.claimed_notifications');

        Livewire::test(ListCoffeeProducts::class)
            ->callTableBulkAction('hide', [$hidden, $visible]);

        $this->assertSame('2 records hidden.', session('filament.claimed_notifications.0.title'));

        $this->assertFalse($hidden->fresh()->visible);
        $this->assertFalse($visible->fresh()->visible);
    }

    private function createCategory(
        string $locale,
        string $slug,
        string $name = 'Gift boxes',
        bool $visible = true,
        int $sortOrder = 10,
    ): ProductCategory {
        return ProductCategory::create([
            'locale' => $locale,
            'slug' => $slug,
            'name' => $name,
            'description' => null,
            'sort_order' => $sortOrder,
            'visible' => $visible,
        ]);
    }

    private function createProduct(ProductCategory $category, string $slug, bool $visible): CoffeeProduct
    {
        return CoffeeProduct::create($this->validCoffeeData([
            'category_id' => $category->id,
            'locale' => $category->locale,
            'slug' => $slug,
            'visible' => $visible,
        ]));
    }

    private function validCategoryData(array $overrides = []): array
    {
        return array_merge([
            'locale' => 'en',
            'slug' => 'new-category',
            'name' => 'New category',
            'description' => null,
            'sort_order' => 10,
            'visible' => false,
        ], $overrides);
    }

    private function validCoffeeData(array $overrides = []): array
    {
        $category = ProductCategory::where(['slug' => 'beans', 'locale' => 'en'])->firstOrFail();

        return array_merge([
            'locale' => 'en',
            'category_id' => $category->id,
            'bean_variety' => 'arabica',
            'slug' => 'valid-coffee-product',
            'title' => 'Valid coffee product',
            'eyebrow' => null,
            'description' => 'A valid product description.',
            'specs' => [],
            'image' => null,
            'image_alt' => null,
            'sort_order' => 10,
            'visible' => true,
        ], $overrides);
    }
}
