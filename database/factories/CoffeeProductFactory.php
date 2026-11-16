<?php

namespace Database\Factories;

use App\Models\CoffeeProduct;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CoffeeProduct>
 */
class CoffeeProductFactory extends Factory
{
    public function definition(): array
    {
        $title = ucfirst(fake()->unique()->words(3, true));

        return [
            'slug' => Str::slug(fake()->unique()->words(3, true)),
            'locale' => 'en',
            'category_id' => function (array $attributes): int {
                return ProductCategory::query()->firstOrCreate(
                    ['slug' => 'beans', 'locale' => $attributes['locale'] ?? 'en'],
                    ['name' => 'Green coffee beans', 'visible' => true],
                )->id;
            },
            'title' => $title,
            'eyebrow' => fake()->randomElement(['Robusta', 'Arabica', 'OEM/ODM', 'Single origin']),
            'description' => fake()->sentence(12),
            'specs' => ['Moisture ≤ 12.5%', 'MOQ 500 kg'],
            'image' => null,
            'sort_order' => 0,
            'visible' => true,
        ];
    }

    public function forLocale(string $locale): static
    {
        return $this->state(fn () => ['locale' => $locale]);
    }

    public function category(string $category): static
    {
        return $this->state(function (array $attributes) use ($category): array {
            $locale = $attributes['locale'] ?? 'en';
            $record = ProductCategory::query()->firstOrCreate(
                ['slug' => $category, 'locale' => $locale],
                ['name' => ucfirst(str_replace('_', ' ', $category)), 'visible' => true],
            );

            return ['category_id' => $record->id];
        });
    }
}
