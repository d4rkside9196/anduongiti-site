<?php

namespace Tests\Feature;

use Database\Seeders\CoffeeProductSeeder;
use Database\Seeders\FaqSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The /vi/... routes register their locale prefix when routes/web.php is
 * evaluated (LaravelLocalization::setLocale() reads the current request).
 * In a feature test the app boots before any request exists, so the prefix
 * would always resolve to '' (EN). ROUTING_LOCALE is the package's official
 * mechanism for forcing the prefix at registration time — the same one
 * per-locale route caching uses. EN-page tests live in their own classes;
 * this class only exercises /vi/* URLs.
 */
class ViRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('ROUTING_LOCALE=vi');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv('ROUTING_LOCALE');
        parent::tearDown();
    }

    public function test_vi_coffee_index_lists_vietnamese_products(): void
    {
        $this->seed(CoffeeProductSeeder::class);

        $response = $this->get('/vi/coffee');

        $response->assertStatus(200);
        $response->assertSee('Cà phê nhân Robusta sàng 18', false);
        $response->assertDontSee('Robusta Scratch 18 Bean Coffee', false);
    }

    public function test_vi_coffee_show_renders_vietnamese_detail(): void
    {
        $this->seed(CoffeeProductSeeder::class);

        $response = $this->get('/vi/coffee/instant-3in1-16g-sticks');

        $response->assertStatus(200);
        $response->assertSee('Cà phê hòa tan 3-in-1 16 g × 20 gói', false);
    }

    public function test_vi_support_page_lists_vietnamese_faqs(): void
    {
        $this->seed(FaqSeeder::class);

        $response = $this->get('/vi/support');

        $response->assertStatus(200);
        $response->assertSee('Số lượng đặt hàng tối thiểu (MOQ) cho cà phê xuất khẩu là bao nhiêu?', false);
        $response->assertSee('Điều kiện thanh toán của quý công ty là gì?', false);
    }
}
