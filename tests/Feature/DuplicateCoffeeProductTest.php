<?php

namespace Tests\Feature;

use App\Actions\Catalog\DuplicateCoffeeProduct;
use App\Filament\Resources\CoffeeProducts\Pages\ListCoffeeProducts;
use App\Models\CoffeeProduct;
use App\Models\ProductCategory;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DuplicateCoffeeProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_en_to_vi_copy_preserves_product_fields_as_a_hidden_draft_and_source_is_unchanged(): void
    {
        $source = $this->createSourceProduct();
        $sourceAttributes = $source->fresh()->getAttributes();

        $draft = app(DuplicateCoffeeProduct::class)->handle($source, 'vi');

        $this->assertSame('corporate-gift-box', $draft->slug);
        $this->assertSame('vi', $draft->locale);
        $this->assertSame('gift-boxes', $draft->category->slug);
        $this->assertSame('vi', $draft->category->locale);
        $this->assertSame('arabica', $draft->bean_variety);
        $this->assertSame('Corporate gift box', $draft->title);
        $this->assertSame('Premium gifts', $draft->eyebrow);
        $this->assertSame('A corporate gift box for export buyers.', $draft->description);
        $this->assertSame(['MOQ 10 boxes', 'Custom sleeve'], $draft->specs);
        $this->assertSame('coffee/gift-box.jpg', $draft->image);
        $this->assertSame('Gift box product photo', $draft->image_alt);
        $this->assertSame(25, $draft->sort_order);
        $this->assertFalse($draft->visible);
        $this->assertSame($sourceAttributes, $source->fresh()->getAttributes());
    }

    public function test_second_duplication_is_denied_without_creating_another_translation(): void
    {
        $source = $this->createSourceProduct();
        $action = app(DuplicateCoffeeProduct::class);
        $action->handle($source, 'vi');

        try {
            $action->handle($source->fresh(), 'vi');
            $this->fail('A duplicate translation was unexpectedly created.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('already exists', $exception->getMessage());
        }

        $this->assertSame(1, CoffeeProduct::where(['slug' => 'corporate-gift-box', 'locale' => 'vi'])->count());
        $this->assertTrue($source->fresh()->visible);
    }

    public function test_missing_category_translation_is_denied_without_copying_the_product(): void
    {
        $source = $this->createSourceProduct();
        ProductCategory::where(['slug' => 'gift-boxes', 'locale' => 'vi'])->delete();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('category translation');

        try {
            app(DuplicateCoffeeProduct::class)->handle($source, 'vi');
        } finally {
            $this->assertSame(1, CoffeeProduct::count());
            $this->assertTrue($source->fresh()->visible);
        }
    }

    public function test_arbitrary_target_locale_is_rejected(): void
    {
        $source = $this->createSourceProduct();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Target locale');

        app(DuplicateCoffeeProduct::class)->handle($source, 'fr');
    }

    public function test_table_action_creates_draft_hides_existing_translation_and_reports_category_failure(): void
    {
        $this->actingAs(User::factory()->create());
        $source = $this->createSourceProduct();

        Livewire::test(ListCoffeeProducts::class)
            ->assertTableActionVisible('duplicateToLocale', $source)
            ->callTableAction('duplicateToLocale', $source);

        $this->assertDatabaseHas('coffee_products', [
            'slug' => 'corporate-gift-box', 'locale' => 'vi', 'visible' => false,
        ]);

        Livewire::test(ListCoffeeProducts::class)
            ->assertTableActionHidden('duplicateToLocale', $source->fresh());

        CoffeeProduct::where(['slug' => 'corporate-gift-box', 'locale' => 'vi'])->delete();
        session()->forget('filament.claimed_notifications');
        session()->forget('filament.notifications');

        $secondSource = $this->createSourceProduct('second-gift-box');
        ProductCategory::where(['slug' => 'gift-boxes', 'locale' => 'vi'])->delete();

        Livewire::test(ListCoffeeProducts::class)
            ->callTableAction('duplicateToLocale', $secondSource);

        $this->assertSame(0, CoffeeProduct::where(['slug' => 'second-gift-box', 'locale' => 'vi'])->count());
        $this->assertSame('Cannot duplicate product', session('filament.claimed_notifications.0.title'));
    }

    private function createSourceProduct(string $slug = 'corporate-gift-box'): CoffeeProduct
    {
        foreach (['en', 'vi'] as $locale) {
            ProductCategory::updateOrCreate(
                ['slug' => 'gift-boxes', 'locale' => $locale],
                ['name' => $locale === 'en' ? 'Gift boxes' : 'Hộp quà', 'visible' => true],
            );
        }

        $category = ProductCategory::where(['slug' => 'gift-boxes', 'locale' => 'en'])->firstOrFail();

        return CoffeeProduct::create([
            'slug' => $slug,
            'locale' => 'en',
            'category_id' => $category->id,
            'bean_variety' => 'arabica',
            'title' => 'Corporate gift box',
            'eyebrow' => 'Premium gifts',
            'description' => 'A corporate gift box for export buyers.',
            'specs' => ['MOQ 10 boxes', 'Custom sleeve'],
            'image' => 'coffee/gift-box.jpg',
            'image_alt' => 'Gift box product photo',
            'sort_order' => 25,
            'visible' => true,
        ]);
    }
}
