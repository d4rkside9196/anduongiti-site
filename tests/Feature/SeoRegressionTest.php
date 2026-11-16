<?php

namespace Tests\Feature;

use App\Models\CoffeeProduct;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use SimpleXMLElement;
use Tests\TestCase;

class SeoRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('ROUTING_LOCALE');
        if (str_starts_with($this->name(), 'test_vi_')) {
            putenv('ROUTING_LOCALE=vi');
        }
        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv('ROUTING_LOCALE');
        parent::tearDown();
    }

    public static function detailTypes(): array
    {
        return [
            'coffee' => ['coffee'],
            'news' => ['news'],
            'coffee product' => ['product-coffee'],
            'rubber product' => ['product-rubber'],
        ];
    }

    public function test_sitemap_static_entries_have_reciprocal_alternates(): void
    {
        $xml = $this->sitemapXml();

        $this->assertSitemapAlternates($xml, '/about', true);
        $this->assertSitemapAlternates($xml, '/vi/about', true);
    }

    public function test_sitemap_dynamic_entries_only_pair_public_translations(): void
    {
        $this->createProduct('coffee', 'en');
        $this->createProduct('rubber', 'vi');
        $this->createPost('shared-post', 'en');
        $this->createPost('shared-post', 'vi');
        $this->createPost('english-only-post', 'en');
        $this->createCoffee('shared-coffee', 'en');
        $this->createCoffee('shared-coffee', 'vi');
        $this->createCoffee('english-only-coffee', 'en');

        $xml = $this->sitemapXml();

        $this->assertSitemapAlternates($xml, '/products/coffee', false);
        $this->assertSitemapUrlMissing($xml, '/vi/products/coffee');
        $this->assertSitemapAlternates($xml, '/vi/products/rubber', false);
        $this->assertSitemapUrlMissing($xml, '/products/rubber');

        $this->assertSitemapAlternates($xml, '/news/shared-post', true);
        $this->assertSitemapAlternates($xml, '/vi/news/shared-post', true);
        $this->assertSitemapAlternates($xml, '/news/english-only-post', false);
        $this->assertSitemapUrlMissing($xml, '/vi/news/english-only-post');

        $this->assertSitemapAlternates($xml, '/coffee/shared-coffee', true);
        $this->assertSitemapAlternates($xml, '/vi/coffee/shared-coffee', true);
        $this->assertSitemapAlternates($xml, '/coffee/english-only-coffee', false);
        $this->assertSitemapUrlMissing($xml, '/vi/coffee/english-only-coffee');
    }

    public function test_sitemap_product_translation_pair_is_reciprocal(): void
    {
        $this->createProduct('coffee', 'en');
        $this->createProduct('coffee', 'vi');

        $xml = $this->sitemapXml();

        $this->assertSitemapAlternates($xml, '/products/coffee', true);
        $this->assertSitemapAlternates($xml, '/vi/products/coffee', true);
    }

    public function test_sitemap_includes_visible_coffee_lastmod_and_excludes_hidden(): void
    {
        $this->createCoffee('robusta-scratch-18-bean-coffee', 'en');
        $this->createCoffee('robusta-scratch-18-bean-coffee', 'vi');
        $this->createCoffee('hidden-coffee', 'en', false);
        $this->createCoffee('hidden-coffee', 'vi', false);

        $xml = $this->sitemapXml();
        $base = $this->siteBaseUrl();

        $en = $this->sitemapUrl($xml, $base.'/coffee/robusta-scratch-18-bean-coffee');
        $vi = $this->sitemapUrl($xml, $base.'/vi/coffee/robusta-scratch-18-bean-coffee');
        $this->assertNotNull($en);
        $this->assertNotNull($vi);
        $this->assertSame('2026-01-02T03:04:05+00:00', $this->lastmod($en));
        $this->assertSame('2026-01-02T03:04:05+00:00', $this->lastmod($vi));
        $this->assertSitemapUrlMissing($xml, '/coffee/hidden-coffee');
        $this->assertSitemapUrlMissing($xml, '/vi/coffee/hidden-coffee');
    }

    public function test_sitemap_escapes_locations_and_alternate_hrefs(): void
    {
        $this->createPost('research&development', 'en');
        $this->createPost('research&development', 'vi');

        $response = $this->get('/sitemap.xml');
        $response->assertOk();
        $response->assertSee('/news/research&amp;development', false);
        $response->assertSee('href="'.$this->siteBaseUrl().'/news/research&amp;development"', false);
        $response->assertSee('href="'.$this->siteBaseUrl().'/vi/news/research&amp;development"', false);

        $xml = simplexml_load_string($response->getContent());
        $this->assertInstanceOf(SimpleXMLElement::class, $xml, 'The sitemap must remain parseable XML.');
    }

    public function test_en_static_x_default_is_current_english_page(): void
    {
        $response = $this->get('/about');

        $response->assertOk();
        $this->assertSeoTargets($response, 'vi', url('/vi/about'), url('/about'));
    }

    public function test_vi_static_x_default_is_current_english_page(): void
    {
        $response = $this->get('/vi/about');

        $response->assertOk();
        $this->assertSeoTargets($response, 'en', url('/about'), url('/about'));
    }

    #[DataProvider('detailTypes')]
    public function test_en_missing_translations_fall_back_to_vietnamese_indexes(string $type): void
    {
        $this->createDetail($type, 'en');

        $response = $this->get($this->detailPath($type));

        $response->assertOk();
        $this->assertSeoTargets(
            $response,
            'vi',
            url('/vi'.$this->indexPath($type)),
            url($this->detailPath($type)),
        );
    }

    #[DataProvider('detailTypes')]
    public function test_vi_fallback_indexes_for_english_only_details_resolve(string $type): void
    {
        $this->createDetail($type, 'en');

        $this->get('/vi'.$this->indexPath($type))->assertOk();
    }

    #[DataProvider('detailTypes')]
    public function test_vi_missing_translations_fall_back_to_english_indexes(string $type): void
    {
        $this->createDetail($type, 'vi');

        $response = $this->get('/vi'.$this->detailPath($type));

        $response->assertOk();
        $this->assertSeoTargets(
            $response,
            'en',
            url($this->indexPath($type)),
            url($this->indexPath($type)),
        );
    }

    #[DataProvider('detailTypes')]
    public function test_en_fallback_indexes_for_vietnamese_only_details_resolve(string $type): void
    {
        $this->createDetail($type, 'vi');

        $this->get($this->indexPath($type))->assertOk();
    }

    #[DataProvider('detailTypes')]
    public function test_en_translated_details_keep_deep_links_and_english_x_default(string $type): void
    {
        $this->createDetail($type, 'en');
        $this->createDetail($type, 'vi');

        $response = $this->get($this->detailPath($type));

        $response->assertOk();
        $this->assertSeoTargets(
            $response,
            'vi',
            url('/vi'.$this->detailPath($type)),
            url($this->detailPath($type)),
        );
    }

    #[DataProvider('detailTypes')]
    public function test_vi_translated_details_keep_deep_links_and_english_x_default(string $type): void
    {
        $this->createDetail($type, 'en');
        $this->createDetail($type, 'vi');

        $response = $this->get('/vi'.$this->detailPath($type));

        $response->assertOk();
        $this->assertSeoTargets(
            $response,
            'en',
            url($this->detailPath($type)),
            url($this->detailPath($type)),
        );
    }

    #[DataProvider('detailTypes')]
    public function test_en_translated_detail_targets_resolve(string $type): void
    {
        $this->createDetail($type, 'en');
        $this->createDetail($type, 'vi');

        $this->get($this->detailPath($type))->assertOk();
    }

    #[DataProvider('detailTypes')]
    public function test_vi_translated_detail_targets_resolve(string $type): void
    {
        $this->createDetail($type, 'en');
        $this->createDetail($type, 'vi');

        $this->get('/vi'.$this->detailPath($type))->assertOk();
    }

    public function test_en_hidden_or_draft_translations_are_not_public_deep_links(): void
    {
        $this->createCoffee('shared-coffee', 'en');
        $this->createCoffee('shared-coffee', 'vi', false);
        $this->createPost('shared-post', 'en');
        Post::create([
            'slug' => 'shared-post',
            'locale' => 'vi',
            'title' => 'Bản nháp',
            'excerpt' => 'Bản nháp',
            'body' => 'Bản nháp',
            'published_at' => '2026-01-01 00:00:00',
            'status' => 'draft',
        ]);

        $this->assertSeoTargets(
            $this->get('/coffee/shared-coffee'),
            'vi',
            url('/vi/coffee'),
            url('/coffee/shared-coffee'),
        );
        $this->assertSeoTargets(
            $this->get('/news/shared-post'),
            'vi',
            url('/vi/news'),
            url('/news/shared-post'),
        );
    }

    public function test_en_future_post_translation_falls_back_to_vietnamese_news_index(): void
    {
        $this->createPost('shared-post', 'en');
        $this->createFuturePost('shared-post', 'vi');

        $response = $this->get('/news/shared-post');

        $this->assertSeoTargets(
            $response,
            'vi',
            url('/vi/news'),
            url('/news/shared-post'),
        );
        $response->assertDontSee('href="'.url('/vi/news/shared-post').'"', false);
    }

    public function test_vi_future_post_translation_falls_back_to_english_news_index(): void
    {
        $this->createPost('shared-post', 'vi');
        $this->createFuturePost('shared-post', 'en');

        $response = $this->get('/vi/news/shared-post');

        $this->assertSeoTargets(
            $response,
            'en',
            url('/news'),
            url('/news'),
        );
        $response->assertDontSee('href="'.url('/news/shared-post').'"', false);
    }

    private function assertSeoTargets(
        TestResponse $response,
        string $alternateLocale,
        string $alternateUrl,
        string $xDefaultUrl,
    ): void {
        $response->assertOk();
        $response->assertSee(
            '<link rel="alternate" hreflang="'.$alternateLocale.'" href="'.$alternateUrl.'" />',
            false,
        );
        $response->assertSee(
            '<link rel="alternate" hreflang="x-default" href="'.$xDefaultUrl.'" />',
            false,
        );
        $response->assertSee('<a href="'.$alternateUrl.'" class="hidden sm:inline-flex', false);
        $response->assertSee('<a href="'.$alternateUrl.'" class="py-3 text-sm', false);
    }

    private function createDetail(string $type, string $locale): void
    {
        match ($type) {
            'coffee' => $this->createCoffee('shared-coffee', $locale),
            'news' => $this->createPost('shared-post', $locale),
            'product-coffee' => $this->createProduct('coffee', $locale),
            'product-rubber' => $this->createProduct('rubber', $locale),
        };
    }

    private function detailPath(string $type): string
    {
        return match ($type) {
            'coffee' => '/coffee/shared-coffee',
            'news' => '/news/shared-post',
            'product-coffee' => '/products/coffee',
            'product-rubber' => '/products/rubber',
        };
    }

    private function indexPath(string $type): string
    {
        return match ($type) {
            'coffee' => '/coffee',
            'news' => '/news',
            'product-coffee', 'product-rubber' => '/products',
        };
    }

    private function createCoffee(string $slug, string $locale, bool $visible = true): CoffeeProduct
    {
        $category = ProductCategory::firstOrCreate(
            ['slug' => 'beans', 'locale' => $locale],
            ['name' => 'Green coffee beans', 'visible' => true],
        );
        $product = CoffeeProduct::create([
            'slug' => $slug,
            'locale' => $locale,
            'category_id' => $category->id,
            'title' => 'Coffee '.$slug,
            'eyebrow' => 'Robusta',
            'description' => 'Coffee description',
            'specs' => ['Moisture ≤ 12.5%'],
            'sort_order' => 1,
            'visible' => $visible,
        ]);
        $product->forceFill([
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-02 03:04:05',
        ])->saveQuietly();

        return $product;
    }

    private function createPost(string $slug, string $locale): Post
    {
        return Post::create([
            'slug' => $slug,
            'locale' => $locale,
            'title' => 'Post '.$slug,
            'excerpt' => 'Post excerpt',
            'body' => 'Post body',
            'published_at' => '2026-01-01 00:00:00',
            'status' => 'published',
        ]);
    }

    private function createFuturePost(string $slug, string $locale): Post
    {
        return Post::create([
            'slug' => $slug,
            'locale' => $locale,
            'title' => 'Future post '.$slug,
            'excerpt' => 'Scheduled post excerpt',
            'body' => 'Scheduled post body',
            'published_at' => now()->addDay(),
            'status' => 'published',
        ]);
    }

    private function createProduct(string $key, string $locale): Product
    {
        return Product::create([
            'product_key' => $key,
            'locale' => $locale,
            'title' => ucfirst($key),
            'eyebrow' => ucfirst($key),
            'lede' => ucfirst($key).' description',
            'specs' => [['Origin', 'Vietnam']],
            'origins' => ['Vietnam'],
        ]);
    }

    private function siteBaseUrl(): string
    {
        return rtrim(config('site.url'), '/');
    }

    private function sitemapXml(): SimpleXMLElement
    {
        $response = $this->get('/sitemap.xml');
        $response->assertOk();
        $xml = simplexml_load_string($response->getContent());
        $this->assertInstanceOf(SimpleXMLElement::class, $xml, 'The sitemap must be parseable XML.');
        $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->registerXPathNamespace('x', 'http://www.w3.org/1999/xhtml');

        return $xml;
    }

    private function sitemapUrl(SimpleXMLElement $xml, string $loc): ?SimpleXMLElement
    {
        foreach ($xml->xpath('//s:url') ?: [] as $url) {
            $url->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
            $locations = $url->xpath('./s:loc');
            if ((string) ($locations[0] ?? '') === $loc) {
                return $url;
            }
        }

        return null;
    }

    private function lastmod(SimpleXMLElement $url): string
    {
        $url->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        return (string) ($url->xpath('./s:lastmod')[0] ?? '');
    }

    private function assertSitemapAlternates(SimpleXMLElement $xml, string $path, bool $expected): void
    {
        $url = $this->sitemapUrl($xml, $this->siteBaseUrl().$path);
        $this->assertNotNull($url, "Missing sitemap URL: {$path}");
        $url->registerXPathNamespace('x', 'http://www.w3.org/1999/xhtml');
        $alternates = [];
        foreach ($url->xpath('./x:link') ?: [] as $link) {
            $alternates[(string) $link['hreflang']] = (string) $link['href'];
        }

        if (! $expected) {
            $this->assertSame([], $alternates, "Unpaired sitemap URL must not advertise alternates: {$path}");

            return;
        }

        $unlocalized = str_starts_with($path, '/vi/') ? substr($path, 3) : $path;
        $unlocalized = $path === '/vi' ? '/' : $unlocalized;
        $vietnamese = $unlocalized === '/' ? '/vi' : '/vi'.$unlocalized;
        $this->assertSame([
            'en' => $this->siteBaseUrl().$unlocalized,
            'vi' => $this->siteBaseUrl().$vietnamese,
        ], $alternates, "Sitemap alternates must be reciprocal for: {$path}");
    }

    private function assertSitemapUrlMissing(SimpleXMLElement $xml, string $path): void
    {
        $this->assertNull(
            $this->sitemapUrl($xml, $this->siteBaseUrl().$path),
            "Unexpected sitemap URL: {$path}",
        );
    }
}
