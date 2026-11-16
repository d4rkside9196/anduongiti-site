<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(3, true));

        return [
            'slug' => Str::slug($name),
            'locale' => 'en',
            'name' => $name,
            'description' => fake()->optional()->sentence(),
            'sort_order' => 0,
            'visible' => false,
        ];
    }

    public function forLocale(string $locale): static
    {
        return $this->state(fn () => ['locale' => $locale]);
    }
}
