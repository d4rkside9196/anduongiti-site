<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class BrandPrimitiveTest extends TestCase
{
    public function test_compact_download_action_has_a_minimum_touch_target(): void
    {
        $html = Blade::render(
            '<x-site.download-card href="/profile.pdf" title="Profile" subtitle="PDF" variant="compact" downloadLabel="Download" />'
        );

        $this->assertStringContainsString('download class="grid h-12 w-12', $html);
    }
}
