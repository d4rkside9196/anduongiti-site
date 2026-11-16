<?php

namespace App\Actions\Catalog;

use App\Models\CoffeeProduct;
use App\Models\ProductCategory;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class DuplicateCoffeeProduct
{
    public function handle(CoffeeProduct $source, string $targetLocale): CoffeeProduct
    {
        if (! in_array($targetLocale, ['en', 'vi'], true)) {
            throw new DomainException('Target locale must be English (en) or Vietnamese (vi).');
        }

        try {
            return DB::transaction(function () use ($source, $targetLocale): CoffeeProduct {
                $source->load('category');

                $targetCategory = ProductCategory::query()
                    ->where('slug', $source->category->slug)
                    ->where('locale', $targetLocale)
                    ->first();

                if (! $targetCategory) {
                    throw new DomainException(
                        'Create the '.strtoupper($targetLocale).' category translation for “'
                        .$source->category->slug.'” before duplicating this product.',
                    );
                }

                $translationExists = CoffeeProduct::query()
                    ->where('slug', $source->slug)
                    ->where('locale', $targetLocale)
                    ->lockForUpdate()
                    ->exists();

                if ($translationExists) {
                    throw new DomainException(
                        'A '.strtoupper($targetLocale).' translation already exists for “'.$source->slug.'”.',
                    );
                }

                $draft = $source->replicate();
                $draft->locale = $targetLocale;
                $draft->category_id = $targetCategory->id;
                $draft->visible = false;
                $draft->save();

                return $draft->fresh('category');
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                throw new DomainException(
                    'A '.strtoupper($targetLocale).' translation already exists for “'.$source->slug.'”.',
                    0,
                    $exception,
                );
            }

            throw $exception;
        }
    }
}
