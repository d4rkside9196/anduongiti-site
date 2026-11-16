<?php

namespace Tests\Feature;

use App\Filament\Resources\CoffeeProducts\Pages\CreateCoffeeProduct;
use App\Models\CoffeeProduct;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityPrivacyRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_csp_keeps_scripts_and_forms_self_only(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'unsafe-inline' 'unsafe-eval'(?:;|$)/", $csp);
        $this->assertStringNotContainsString('https://fonts.googleapis.com', explode('style-src', $csp)[0]);
    }

    public function test_catalog_upload_rejects_non_image_mime(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('browser-payload.php', 10, 'application/x-php');

        Livewire::actingAs(User::factory()->superAdmin()->create())
            ->test(CreateCoffeeProduct::class)
            ->fillForm($this->validProductWithImage($file))
            ->call('create')
            ->assertHasErrors(['data.image']);

        $this->assertDatabaseCount('coffee_products', 0);
    }

    public function test_catalog_upload_rejects_images_over_two_megabytes(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('oversized.jpg')->size(2049);

        Livewire::actingAs(User::factory()->superAdmin()->create())
            ->test(CreateCoffeeProduct::class)
            ->fillForm($this->validProductWithImage($file))
            ->call('create')
            ->assertHasErrors(['data.image']);

        $this->assertDatabaseCount('coffee_products', 0);
    }

    public function test_catalog_upload_rejects_svg_content(): void
    {
        Storage::fake('public');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
        $file = UploadedFile::fake()->createWithContent('probe.svg', $svg);

        Livewire::actingAs(User::factory()->superAdmin()->create())
            ->test(CreateCoffeeProduct::class)
            ->fillForm($this->validProductWithImage($file))
            ->call('create')
            ->assertHasErrors(['data.image']);

        $this->assertDatabaseCount('coffee_products', 0);
        Storage::disk('public')->assertDirectoryEmpty('coffee');
    }

    public function test_catalog_upload_rejects_polyglot_content_with_executable_client_extension(): void
    {
        Storage::fake('public');
        // GIF89a magic bytes so the content sniffs as image/gif; the client
        // filename carries a PHP extension. Neither the sniffed GIF content
        // nor the client extension may reach the public disk.
        $file = UploadedFile::fake()->createWithContent('shell.php', 'GIF89a'.$this->randomPadding());

        Livewire::actingAs(User::factory()->superAdmin()->create())
            ->test(CreateCoffeeProduct::class)
            ->fillForm($this->validProductWithImage($file))
            ->call('create')
            ->assertHasErrors(['data.image']);

        $this->assertDatabaseCount('coffee_products', 0);
        Storage::disk('public')->assertDirectoryEmpty('coffee');
    }

    public function test_catalog_upload_never_preserves_client_extension_for_accepted_content(): void
    {
        Storage::fake('public');
        // Accepted content must be stored with a content-derived extension
        // (and none of the client-supplied name): even for a legitimate PNG
        // the stored filename may not inherit anything from the client.
        $file = UploadedFile::fake()->image('treacherous-client-name.php.png');

        Livewire::actingAs(User::factory()->superAdmin()->create())
            ->test(CreateCoffeeProduct::class)
            ->fillForm($this->validProductWithImage($file))
            ->call('create')
            ->assertHasNoErrors();

        $stored = (string) CoffeeProduct::query()->where('slug', 'security-upload-probe')->sole()->image;

        $this->assertStringEndsWith('.png', $stored);
        $this->assertStringNotContainsString('php', $stored);
        $this->assertStringNotContainsString('treacherous', $stored);
        Storage::disk('public')->assertExists($stored);
    }

    private function randomPadding(): string
    {
        return str_repeat('A', 64);
    }

    private function validProductWithImage(UploadedFile $file): array
    {
        $category = ProductCategory::firstOrCreate(
            ['slug' => 'beans', 'locale' => 'en'],
            ['name' => 'Beans', 'visible' => true],
        );

        return [
            'locale' => 'en',
            'category_id' => $category->id,
            'bean_variety' => 'robusta',
            'slug' => 'security-upload-probe',
            'title' => 'Security upload probe',
            'description' => 'Disposable security validation probe.',
            'image' => $file,
        ];
    }
}
