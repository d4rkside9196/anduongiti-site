<?php

namespace App\Observers;

use App\Models\CoffeeProduct;
use Illuminate\Support\Facades\Storage;

class CoffeeProductObserver
{
    public function updated(CoffeeProduct $product): void
    {
        if (! $product->wasChanged('image')) {
            return;
        }

        $oldImage = $product->getOriginal('image');
        if (! is_string($oldImage) || $oldImage === '' || $oldImage === $product->image) {
            return;
        }

        if (str_starts_with($oldImage, 'uploads/')
            || str_starts_with($oldImage, 'http://')
            || str_starts_with($oldImage, 'https://')
            || str_starts_with($oldImage, '/')) {
            return;
        }

        if (CoffeeProduct::query()->where('image', $oldImage)->exists()) {
            return;
        }

        Storage::disk('public')->delete($oldImage);
    }
}
