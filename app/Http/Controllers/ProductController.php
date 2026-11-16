<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    public function index()
    {
        $locale = app()->getLocale();
        return view('pages.products', [
            'locale' => $locale,
            'coffee' => Product::where(['product_key' => 'coffee', 'locale' => $locale])->first(),
            'rubber' => Product::where(['product_key' => 'rubber', 'locale' => $locale])->first(),
        ]);
    }

    public function show(string $key)
    {
        $locale = app()->getLocale();
        $product = Product::where(['product_key' => $key, 'locale' => $locale])->first();
        if (! $product) throw new NotFoundHttpException();

        $others = Product::whereIn('product_key', ['coffee', 'rubber'])
            ->where('locale', $locale)
            ->where('product_key', '!=', $key)
            ->get();

        return view('pages.product-detail', compact('locale', 'product', 'others'));
    }
}
