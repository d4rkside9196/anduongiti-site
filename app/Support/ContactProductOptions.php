<?php

namespace App\Support;

use App\Models\ProductCategory;

final class ContactProductOptions
{
    public static function all(string $locale): array
    {
        $legacy = __('site.contact.products', [], $locale);
        $dynamic = ProductCategory::visible()
            ->forLocale($locale)
            ->ordered()
            ->pluck('name', 'slug')
            ->mapWithKeys(fn (string $name, string $slug): array => [
                'category:'.$slug => $name,
            ])
            ->all();

        return array_merge($legacy, $dynamic);
    }
}
