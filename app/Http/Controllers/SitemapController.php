<?php

namespace App\Http\Controllers;

use App\Models\CoffeeProduct;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $base = rtrim(config('site.url'), '/');
        $static = ['/', '/about', '/products', '/coffee', '/sustainability', '/partners',
            '/news', '/support', '/contact'];

        $entries = [];
        foreach ($static as $path) {
            $this->addLocalizedEntries($entries, $base, $path);
        }

        Product::whereIn('product_key', ['coffee', 'rubber'])
            ->orderBy('product_key')
            ->get()
            ->groupBy('product_key')
            ->each(function (Collection $products, $key) use (&$entries, $base) {
                $this->addModelEntries($entries, $base, "/products/{$key}", $products);
            });

        Post::published()->orderByDesc('published_at')->get()
            ->groupBy('slug')
            ->each(function (Collection $posts, $slug) use (&$entries, $base) {
                $this->addModelEntries($entries, $base, "/news/{$slug}", $posts);
            });

        CoffeeProduct::visible()->ordered()->get()
            ->groupBy('slug')
            ->each(function (Collection $products, $slug) use (&$entries, $base) {
                $this->addModelEntries($entries, $base, "/coffee/{$slug}", $products);
            });

        $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
              ."<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xhtml=\"http://www.w3.org/1999/xhtml\">\n"
              .implode("\n", $entries)
              ."\n</urlset>";

        return response($body, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    private function addModelEntries(array &$entries, string $base, string $path, Collection $records): void
    {
        $en = $records->firstWhere('locale', 'en');
        $vi = $records->firstWhere('locale', 'vi');

        $this->addLocalizedEntries(
            $entries,
            $base,
            $path,
            $en?->updated_at,
            $vi?->updated_at,
            $en !== null,
            $vi !== null,
        );
    }

    private function addLocalizedEntries(
        array &$entries,
        string $base,
        string $path,
        $enLastmod = null,
        $viLastmod = null,
        bool $includeEn = true,
        bool $includeVi = true,
    ): void {
        $en = $base.$path;
        $vi = $base.($path === '/' ? '/vi' : "/vi{$path}");
        $hasAlternates = $includeEn && $includeVi;

        if ($includeEn) {
            $entries[] = $this->urlEntry(
                $en,
                $enLastmod,
                $hasAlternates ? $en : null,
                $hasAlternates ? $vi : null,
            );
        }
        if ($includeVi) {
            $entries[] = $this->urlEntry(
                $vi,
                $viLastmod,
                $hasAlternates ? $en : null,
                $hasAlternates ? $vi : null,
            );
        }
    }

    private function urlEntry(
        string $loc,
        $lastmod = null,
        ?string $enAlternate = null,
        ?string $viAlternate = null,
    ): string {
        $links = '';
        if ($enAlternate && $viAlternate) {
            $en = $this->escapeXml($enAlternate);
            $vi = $this->escapeXml($viAlternate);
            $links = "\n    <xhtml:link rel=\"alternate\" hreflang=\"en\" href=\"{$en}\" />"
                   ."\n    <xhtml:link rel=\"alternate\" hreflang=\"vi\" href=\"{$vi}\" />";
        }
        $loc = $this->escapeXml($loc);
        $lm = $lastmod ? "\n    <lastmod>".$lastmod->toAtomString().'</lastmod>' : '';

        return "  <url>\n    <loc>{$loc}</loc>{$lm}{$links}\n  </url>";
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
