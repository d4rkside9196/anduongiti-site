<?php

namespace Tests\Feature;

use App\Mail\NewLeadMail;
use App\Models\CoffeeProduct;
use App\Models\Lead;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Support\ContactProductOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeadPipelineRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_coffee_cta_preserves_the_exact_sku_and_dynamic_category(): void
    {
        Mail::fake();
        config()->set('site.name', 'Regression Buyer Desk');

        $product = $this->createCoffeeProduct([
            'slug' => 'cau-dat-washed-highland',
            'bean_variety' => 'arabica',
        ]);

        $this->get('/coffee/'.$product->slug)
            ->assertOk()
            ->assertSee('product=cau-dat-washed-highland', false);

        $this->assertArrayHasKey('category:beans', ContactProductOptions::all('en'));
        $this->get('/contact?product='.$product->slug)
            ->assertOk()
            ->assertSee('name="source_slug" value="cau-dat-washed-highland"', false);

        $response = $this->post('/contact', $this->validLeadPayload([
            'product' => 'category:beans',
            'source_slug' => $product->slug,
        ]));

        $response->assertRedirect(route('contact', ['sent' => 1]));
        $lead = Lead::sole();
        $this->assertSame('cau-dat-washed-highland', $lead->source_slug);
        $this->assertSame('category:beans', $lead->product);
        Mail::assertSent(NewLeadMail::class, function (NewLeadMail $mail): bool {
            return $mail->lead->source_slug === 'cau-dat-washed-highland'
                && $mail->envelope()->subject === '[Regression Buyer Desk] New enquiry from Jane Buyer';
        });
    }

    public function test_direct_contact_submission_has_no_source_slug(): void
    {
        Mail::fake();

        $this->post('/contact', $this->validLeadPayload([
            'product' => 'robusta_beans',
        ]))->assertRedirect(route('contact', ['sent' => 1]));

        $lead = Lead::sole();
        $this->assertNull($lead->source_slug);
        $this->assertSame('robusta_beans', $lead->product);
        Mail::assertSent(NewLeadMail::class);
    }

    public function test_source_slug_must_belong_to_a_visible_product_in_the_active_locale(): void
    {
        Mail::fake();
        $this->createCoffeeProduct([
            'slug' => 'hidden-coffee-sku',
            'visible' => false,
            'bean_variety' => 'arabica',
        ]);
        $this->createCoffeeProduct([
            'slug' => 'vietnamese-only-sku',
            'locale' => 'vi',
            'bean_variety' => 'arabica',
        ]);

        foreach (['does-not-exist', 'hidden-coffee-sku', 'vietnamese-only-sku'] as $sourceSlug) {
            $this->post('/contact', $this->validLeadPayload([
                'product' => 'arabica_beans',
                'source_slug' => $sourceSlug,
            ]))->assertSessionHasErrors(['source_slug']);
        }

        $this->assertDatabaseCount('leads', 0);
        Mail::assertNothingSent();
    }

    public function test_valid_source_slug_derives_category_instead_of_trusting_the_broad_product_input(): void
    {
        Mail::fake();
        $product = $this->createCoffeeProduct([
            'slug' => 'cau-dat-washed-highland',
            'bean_variety' => 'arabica',
        ]);

        $this->post('/contact', $this->validLeadPayload([
            'product' => 'robusta_beans',
            'source_slug' => $product->slug,
        ]))->assertRedirect(route('contact', ['sent' => 1]));

        $this->assertSame('category:beans', Lead::sole()->product);
    }

    public function test_legacy_bean_without_variety_uses_its_dynamic_category(): void
    {
        $product = $this->createCoffeeProduct([
            'slug' => 'legacy-arabica-lot',
            'bean_variety' => null,
        ]);

        $this->assertSame('category:beans', $product->contactProductKey());
        $this->get('/contact?product=legacy-arabica-lot')
            ->assertOk()
            ->assertSee('name="source_slug" value="legacy-arabica-lot"', false);
    }

    public function test_contact_page_merges_defaults_when_settings_only_contain_email(): void
    {
        Setting::write('contact', ['email' => 'sales-only@gmail.com']);

        $this->get('/contact')
            ->assertOk()
            ->assertSee('mailto:sales-only@gmail.com', false)
            ->assertSee('112 Me Tri Ha, Tu Liem Ward, Hanoi, Vietnam', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validLeadPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Buyer',
            'email' => 'jane@gmail.com',
            'message' => 'We would like a quote for five tonnes of coffee.',
            'company' => 'Example Roasters Ltd',
            'country' => 'United Kingdom',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCoffeeProduct(array $overrides = []): CoffeeProduct
    {
        $locale = $overrides['locale'] ?? 'en';
        $category = ProductCategory::firstOrCreate(
            ['slug' => 'beans', 'locale' => $locale],
            ['name' => 'Green coffee beans', 'visible' => true],
        );

        return CoffeeProduct::create(array_merge([
            'slug' => 'cau-dat-washed-highland',
            'locale' => $locale,
            'category_id' => $category->id,
            'title' => 'Cau Dat Washed Highland',
            'eyebrow' => 'Lam Dong · Washed',
            'description' => 'A fully washed export coffee lot from the Cau Dat highlands.',
            'specs' => ['Moisture: 12%'],
            'image' => null,
            'sort_order' => 10,
            'visible' => true,
        ], $overrides));
    }
}
