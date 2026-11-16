<?php

use App\Http\Controllers\CoffeeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    // 'localize' detects locale from URL prefix; 'localizationRedirect'
    // normalises a redundant /en/... prefix to /. We deliberately DO NOT
    // use 'localeSessionRedirect' — it stores the visited locale in the
    // session and force-redirects every subsequent / request to /vi,
    // which makes the header language switcher one-way (VI but never EN).
    'middleware' => ['localize', 'localizationRedirect'],
], function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/about', [PageController::class, 'about'])->name('about');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{key}', [ProductController::class, 'show'])
        ->where('key', 'coffee|rubber')
        ->name('products.show');

    Route::get('/coffee', [CoffeeController::class, 'index'])->name('coffee');
    Route::get('/coffee/{slug}', [CoffeeController::class, 'show'])
        ->where('slug', '[^/]+')
        ->name('coffee.show');

    Route::get('/sustainability', [PageController::class, 'sustainability'])->name('sustainability');
    Route::get('/partners', [PageController::class, 'partners'])->name('partners');

    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

    Route::get('/support', [PageController::class, 'support'])->name('support');

    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    // Production defaults to 10 per IP per hour. Isolated test environments
    // may raise the limit explicitly without changing the production default.
    Route::post('/contact', [LeadController::class, 'store'])
        ->middleware('throttle:'.config('site.contact_throttle_limit').',60')
        ->name('contact.store');
});

// Locale-agnostic, served at root for SEO.
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
