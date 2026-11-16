<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The home page renders and exposes the core nav links
     * (config/site.php drives the header markup).
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('href="'.url('/coffee').'"', false);
        $response->assertSee('href="'.url('/support').'"', false);
    }
}
