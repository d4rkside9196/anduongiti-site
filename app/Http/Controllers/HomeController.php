<?php

namespace App\Http\Controllers;

use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $locale = app()->getLocale();
        return view('pages.home', [
            'locale' => $locale,
            'coffee' => Product::where(['product_key' => 'coffee', 'locale' => $locale])->first(),
            'rubber' => Product::where(['product_key' => 'rubber', 'locale' => $locale])->first(),
        ]);
    }
}
