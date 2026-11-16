<?php

namespace Tests\Feature;

use App\Models\CoffeeProduct;
use Database\Seeders\CoffeeProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoffeeTest extends TestCase
{
    use RefreshDatabase;

    protected function seedCatalogue(): void
    {
        $this->seed(CoffeeProductSeeder::class);
    }

    public function test_coffee_index_lists_products_for_current_locale(): void
    {
        $this->seedCatalogue();

        $response = $this->get('/coffee');

        $response->assertStatus(200);
        $response->assertSee('Robusta Scratch 18 Bean Coffee', false);
        $response->assertSee('Instant 3-in-1 Coffee 16 g × 20 Sticks', false);
        $response->assertSee('Freeze-dried Black Coffee 100 g', false);
    }

    public function test_coffee_show_renders_product_detail(): void
    {
        $this->seedCatalogue();

        $response = $this->get('/coffee/robusta-scratch-18-bean-coffee');

        $response->assertStatus(200);
        $response->assertSee('Robusta Scratch 18 Bean Coffee', false);
        $response->assertSee('Screen size: 18 (min. 90% above 16)', false);
        // CTA must deep-link into the contact form with the product preselect.
        $response->assertSee('product=robusta-scratch-18-bean-coffee', false);
    }

    public function test_coffee_show_returns_404_for_unknown_slug(): void
    {
        $this->seedCatalogue();

        $this->get('/coffee/does-not-exist')->assertStatus(404);
    }

    public function test_seeded_images_render_from_uploads_not_storage(): void
    {
        $this->seedCatalogue();

        // Regression: seeded rows store "uploads/coffee/..." (a path under
        // public/). The accessor must serve them verbatim — routing them
        // through the /storage mount 403s in every product card.
        $response = $this->get('/coffee');
        $response->assertStatus(200);
        $this->assertStringContainsString(
            'src="'.config('app.url').'/uploads/coffee/robusta-scratch-18-bean-coffee.jpg',
            $response->getContent(),
        );
        $this->assertStringNotContainsString('/storage/uploads/', $response->getContent());
    }

    public function test_hidden_products_never_show(): void
    {
        $this->seedCatalogue();
        CoffeeProduct::where('slug', 'robusta-scratch-18-bean-coffee')->update(['visible' => false]);

        $this->get('/coffee/robusta-scratch-18-bean-coffee')->assertStatus(404);
    }
}
