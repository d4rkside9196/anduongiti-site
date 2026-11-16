<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression tests for the ProductForm specs bug: the field used to be a
 * plain Textarea that round-tripped the json-cast [[key, value], ...]
 * tuples as raw JSON text, destroying the array shape on every save.
 */
class ProductFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_round_trips_positional_spec_tuples(): void
    {
        $product = Product::create([
            'product_key' => 'coffee',
            'locale' => 'en',
            'title' => 'Coffee',
            'eyebrow' => 'Instant OEM/ODM · Robusta · Arabica',
            'lede' => 'Custom-formula instant coffee.',
            'specs' => [['Instant coffee', 'Spray-dried & freeze-dried'], ['MOQ', '1 ton instant']],
            'origins' => ['Đắk Lắk', 'Sơn La'],
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EditProduct::class, ['record' => $product->getKey()])
            ->assertSet('data.specs.0.value', 'Spray-dried & freeze-dried')
            ->set('data.specs.1.value', '2 tons instant')
            ->set('data.specs.2', ['key' => 'Roast profile', 'value' => 'Custom, from 5 kg'])
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $product->fresh();
        $this->assertSame([
            ['Instant coffee', 'Spray-dried & freeze-dried'],
            ['MOQ', '2 tons instant'],
            ['Roast profile', 'Custom, from 5 kg'],
        ], $fresh->specs);
        $this->assertSame(['Đắk Lắk', 'Sơn La'], $fresh->origins);
    }

    public function test_create_stores_positional_spec_tuples(): void
    {
        $user = User::factory()->create();

        // The KeyValue UI accumulates rows as ['key' => ..., 'value' => ...].
        Livewire::actingAs($user)
            ->test(CreateProduct::class)
            ->set('data.product_key', 'rubber')
            ->set('data.locale', 'en')
            ->set('data.title', 'Natural rubber')
            ->set('data.eyebrow', 'SVR 3L · 10 · CV60')
            ->set('data.lede', 'Vietnamese natural rubber for tyre and industrial applications.')
            ->set('data.specs', [
                ['key' => 'SVR 3L', 'value' => 'Light colour, low impurities'],
                ['key' => 'Packaging', 'value' => '33.33 kg bales'],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $product = Product::where('product_key', 'rubber')->first();
        $this->assertNotNull($product);
        $this->assertSame([
            ['SVR 3L', 'Light colour, low impurities'],
            ['Packaging', '33.33 kg bales'],
        ], $product->specs);
    }
}
