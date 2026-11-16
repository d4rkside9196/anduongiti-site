<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $labels = [
            'beans' => ['en' => 'Green coffee beans', 'vi' => 'Cà phê nhân'],
            'ground' => ['en' => 'Ground coffee', 'vi' => 'Cà phê xay'],
            'instant' => ['en' => 'Instant coffee', 'vi' => 'Cà phê hòa tan'],
            'freeze_dried' => ['en' => 'Freeze-dried coffee', 'vi' => 'Cà phê sấy thăng hoa'],
        ];

        $sortOrder = 10;
        foreach ($labels as $slug => $locales) {
            foreach ($locales as $locale => $name) {
                ProductCategory::updateOrCreate(
                    ['slug' => $slug, 'locale' => $locale],
                    ['name' => $name, 'sort_order' => $sortOrder, 'visible' => true],
                );
            }
            $sortOrder += 10;
        }
    }
}
