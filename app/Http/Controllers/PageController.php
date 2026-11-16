<?php

namespace App\Http\Controllers;

use App\Models\CoffeeProduct;
use App\Models\Faq;
use App\Models\Partner;
use App\Support\ContactProductOptions;

class PageController extends Controller
{
    public function about()
    {
        return view('pages.about', ['locale' => app()->getLocale()]);
    }

    public function sustainability()
    {
        return view('pages.sustainability', ['locale' => app()->getLocale()]);
    }

    public function partners()
    {
        return view('pages.partners', [
            'locale' => app()->getLocale(),
            'partners' => Partner::visible()->orderBy('sort_order')->get(),
        ]);
    }

    public function support()
    {
        $locale = app()->getLocale();

        // Already ordered by sort_order — grouping preserves order within
        // each category and the order of first appearance across categories.
        $faqs = Faq::visible()
            ->forLocale($locale)
            ->ordered()
            ->get()
            ->groupBy('category');

        return view('pages.support', [
            'locale' => $locale,
            'faqs' => $faqs,
        ]);
    }

    public function contact()
    {
        $locale = app()->getLocale();
        // Mirror CoffeeController's ?category= handling: bracket-syntax
        // query strings (?product[]=x) arrive as arrays from the raw query
        // bag and must be normalized to null before the typed parameter.
        $productParam = request()->query('product');
        $contactProduct = $this->resolveContactProduct(is_string($productParam) ? $productParam : null, $locale);

        return view('pages.contact', [
            'locale' => $locale,
            'sent' => request()->boolean('sent'),
            // Preselect "product of interest" from /coffee detail CTAs and
            // retain the exact visible catalogue SKU for the eventual lead.
            'product' => $contactProduct['product'],
            'sourceSlug' => $contactProduct['sourceSlug'],
        ]);
    }

    /**
     * Resolve either a direct contact key or a visible coffee slug in the
     * active locale. Unknown and hidden products never reach the form.
     *
     * @return array{product: ?string, sourceSlug: ?string}
     */
    private function resolveContactProduct(?string $param, string $locale): array
    {
        if ($param === null || $param === '') {
            return ['product' => null, 'sourceSlug' => null];
        }

        if (in_array($param, array_keys(ContactProductOptions::all($locale)), true)) {
            return ['product' => $param, 'sourceSlug' => null];
        }

        $product = CoffeeProduct::visible()->forLocale($locale)->where('slug', $param)->first();

        return [
            'product' => $product?->contactProductKey(),
            'sourceSlug' => $product?->slug,
        ];
    }
}
