<?php

namespace App\Http\Controllers;

use App\Models\CoffeeProduct;
use App\Models\ProductCategory;
use App\Models\Setting;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CoffeeController extends Controller
{
    public function index()
    {
        $locale = app()->getLocale();

        $categories = ProductCategory::visible()
            ->forLocale($locale)
            ->ordered()
            ->with(['coffeeProducts' => fn ($query) => $query
                ->visible()
                ->forLocale($locale)
                ->ordered()])
            ->get()
            ->filter(fn (ProductCategory $category): bool => $category->coffeeProducts->isNotEmpty());

        if (request()->query->has('category')) {
            $filter = request()->query('category');
            if (! is_string($filter)) {
                $filter = '';
            }
            $categories = $categories->filter(
                fn (ProductCategory $category): bool => $category->slug === $filter,
            );
        }

        $groups = $categories->map(fn (ProductCategory $category): array => [
            'key' => $category->slug,
            'name' => $category->name,
            'products' => $category->coffeeProducts,
        ])->values();

        $response = response()->view('pages.coffee', [
            'locale' => $locale,
            'categories' => $groups,
            'social' => $this->social(),
        ]);

        if (request()->query->has('category')) {
            $response->headers->set('X-Robots-Tag', 'noindex,follow');
        }

        return $response;
    }

    public function show(string $slug)
    {
        $locale = app()->getLocale();

        $product = CoffeeProduct::visible()
            ->forLocale($locale)
            ->with('category')
            ->where('slug', $slug)
            ->first();
        if (! $product) throw new NotFoundHttpException();

        $related = CoffeeProduct::visible()
            ->forLocale($locale)
            ->where('category_id', $product->category_id)
            ->where('slug', '!=', $product->slug)
            ->ordered()
            ->limit(4)
            ->get();

        return view('pages.coffee-product', [
            'locale' => $locale,
            'product' => $product,
            'related' => $related,
            'social' => $this->social(),
        ]);
    }

    private function social(): array
    {
        return Setting::read('social', [
            'facebook' => '', 'shopee' => '', 'alibaba' => '', 'linkedin' => '',
        ]);
    }
}
