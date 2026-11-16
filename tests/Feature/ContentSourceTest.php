<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_catalogue_render_admin_product_content_and_remove_legacy_copy(): void
    {
        Product::create([
            'product_key' => 'coffee',
            'locale' => 'en',
            'title' => 'EN_COFFEE_TITLE_13',
            'eyebrow' => 'EN_COFFEE_EYEBROW_13',
            'lede' => 'EN_COFFEE_LEDE_13',
            'specs' => [
                ['EN_COFFEE_SPEC_KEY_13', 'EN_COFFEE_SPEC_VALUE_13'],
                ['EN_COFFEE_SECOND_SPEC_KEY_13', 'EN_COFFEE_SECOND_SPEC_VALUE_13'],
            ],
            'origins' => ['EN_COFFEE_ORIGIN_ONE_13', 'EN_COFFEE_ORIGIN_TWO_13'],
        ]);
        Product::create([
            'product_key' => 'rubber',
            'locale' => 'en',
            'title' => 'EN_RUBBER_TITLE_13',
            'eyebrow' => 'EN_RUBBER_EYEBROW_13',
            'lede' => 'EN_RUBBER_LEDE_13',
            'specs' => [
                ['EN_RUBBER_SPEC_KEY_13', 'EN_RUBBER_SPEC_VALUE_13'],
                ['EN_RUBBER_SECOND_SPEC_KEY_13', 'EN_RUBBER_SECOND_SPEC_VALUE_13'],
            ],
            'origins' => ['EN_RUBBER_ORIGIN_13'],
        ]);

        foreach (['/', '/products'] as $uri) {
            $response = $this->get($uri);

            $response->assertOk();
            foreach ([
                'EN_COFFEE_TITLE_13',
                'EN_COFFEE_EYEBROW_13',
                'EN_COFFEE_LEDE_13',
                'EN_COFFEE_SPEC_KEY_13',
                'EN_COFFEE_SPEC_VALUE_13',
                'EN_COFFEE_SECOND_SPEC_KEY_13',
                'EN_COFFEE_SECOND_SPEC_VALUE_13',
                'EN_COFFEE_ORIGIN_ONE_13',
                'EN_COFFEE_ORIGIN_TWO_13',
                'EN_RUBBER_TITLE_13',
                'EN_RUBBER_EYEBROW_13',
                'EN_RUBBER_LEDE_13',
                'EN_RUBBER_SPEC_KEY_13',
                'EN_RUBBER_SPEC_VALUE_13',
                'EN_RUBBER_SECOND_SPEC_KEY_13',
                'EN_RUBBER_SECOND_SPEC_VALUE_13',
            ] as $marker) {
                $response->assertSee($marker, false);
            }

            $response->assertDontSee('Ready-to-drink', false);
            $response->assertDontSee('Single-origin green beans', false);
            $response->assertDontSee('FSC CoC on request', false);
        }
    }

    public function test_missing_coffee_product_hides_only_the_coffee_section(): void
    {
        Product::create([
            'product_key' => 'rubber',
            'locale' => 'en',
            'title' => 'EN_RUBBER_WITHOUT_COFFEE_13',
            'eyebrow' => 'EN_RUBBER_EYEBROW_WITHOUT_COFFEE_13',
            'lede' => 'EN_RUBBER_LEDE_WITHOUT_COFFEE_13',
            'specs' => [['EN_RUBBER_SPEC_WITHOUT_COFFEE_13', 'EN_RUBBER_VALUE_WITHOUT_COFFEE_13']],
            'origins' => [],
        ]);

        foreach (['/', '/products'] as $uri) {
            $response = $this->get($uri);

            $response->assertOk();
            $response->assertSee('EN_RUBBER_WITHOUT_COFFEE_13', false);
            $response->assertSee('href="'.url('/products/rubber').'"', false);
            $response->assertDontSee('href="'.url('/products/coffee').'"', false);
            $response->assertDontSee('View coffee details', false);
        }
    }

    public function test_missing_rubber_product_hides_only_the_rubber_section(): void
    {
        Product::create([
            'product_key' => 'coffee',
            'locale' => 'en',
            'title' => 'EN_COFFEE_WITHOUT_RUBBER_13',
            'eyebrow' => 'EN_COFFEE_EYEBROW_WITHOUT_RUBBER_13',
            'lede' => 'EN_COFFEE_LEDE_WITHOUT_RUBBER_13',
            'specs' => [['EN_COFFEE_SPEC_WITHOUT_RUBBER_13', 'EN_COFFEE_VALUE_WITHOUT_RUBBER_13']],
            'origins' => ['EN_COFFEE_ORIGIN_WITHOUT_RUBBER_13'],
        ]);

        foreach (['/', '/products'] as $uri) {
            $response = $this->get($uri);

            $response->assertOk();
            $response->assertSee('EN_COFFEE_WITHOUT_RUBBER_13', false);
            $response->assertSee('href="'.url('/products/coffee').'"', false);
            $response->assertDontSee('href="'.url('/products/rubber').'"', false);
            $response->assertDontSee('Also offered', false);
        }
    }
    public function test_vietnamese_components_render_admin_product_content(): void
    {
        $coffee = Product::create([
            'product_key' => 'coffee',
            'locale' => 'vi',
            'title' => 'VI_COFFEE_TITLE_13',
            'eyebrow' => 'VI_COFFEE_EYEBROW_13',
            'lede' => 'VI_COFFEE_LEDE_13',
            'specs' => [['VI_COFFEE_SPEC_KEY_13', 'VI_COFFEE_SPEC_VALUE_13']],
            'origins' => ['VI_COFFEE_ORIGIN_13'],
        ]);
        $rubber = Product::create([
            'product_key' => 'rubber',
            'locale' => 'vi',
            'title' => 'VI_RUBBER_TITLE_13',
            'eyebrow' => 'VI_RUBBER_EYEBROW_13',
            'lede' => 'VI_RUBBER_LEDE_13',
            'specs' => [['VI_RUBBER_SPEC_KEY_13', 'VI_RUBBER_SPEC_VALUE_13']],
            'origins' => ['VI_RUBBER_ORIGIN_13'],
        ]);

        $view = $this->blade(
            '<x-site.coffee-showcase locale="vi" :coffee="$coffee" />'
            .'<x-site.rubber-strip locale="vi" :rubber="$rubber" />',
            compact('coffee', 'rubber'),
        );

        foreach ([
            'VI_COFFEE_TITLE_13',
            'VI_COFFEE_EYEBROW_13',
            'VI_COFFEE_LEDE_13',
            'VI_COFFEE_SPEC_KEY_13',
            'VI_COFFEE_SPEC_VALUE_13',
            'VI_COFFEE_ORIGIN_13',
            'VI_RUBBER_TITLE_13',
            'VI_RUBBER_EYEBROW_13',
            'VI_RUBBER_LEDE_13',
            'VI_RUBBER_SPEC_KEY_13',
            'VI_RUBBER_SPEC_VALUE_13',
        ] as $marker) {
            $view->assertSee($marker, false);
        }

        $view->assertDontSee('Pha sẵn · uống ngay', false);
        $view->assertDontSee('Nhân xanh single-origin', false);
        $view->assertDontSee('Có FSC CoC khi yêu cầu', false);
    }
}
