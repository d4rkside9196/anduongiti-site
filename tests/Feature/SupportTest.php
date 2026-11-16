<?php

namespace Tests\Feature;

use Database\Seeders\FaqSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_page_lists_faqs_for_current_locale(): void
    {
        $this->seed(FaqSeeder::class);

        $response = $this->get('/support');

        $response->assertStatus(200);
        $response->assertSee('What is your minimum order quantity (MOQ) for wholesale coffee export?', false);
        $response->assertSee('Which payment terms do you accept?', false);
    }

    public function test_support_page_renders_without_faqs(): void
    {
        $this->get('/support')->assertStatus(200);
    }
}
