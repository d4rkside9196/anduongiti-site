<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'locale' => 'en',
            'category' => fake()->randomElement(['orders', 'shipping', 'products', 'payment']),
            'question' => fake()->sentence(6, true),
            'answer' => fake()->paragraph(),
            'sort_order' => 0,
            'visible' => true,
        ];
    }

    public function forLocale(string $locale): static
    {
        return $this->state(fn () => ['locale' => $locale]);
    }
}
