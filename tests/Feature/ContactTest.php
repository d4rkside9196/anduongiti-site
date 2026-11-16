<?php

namespace Tests\Feature;

use App\Mail\NewLeadMail;
use Database\Seeders\CoffeeProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_submission_creates_lead_and_sends_mail(): void
    {
        Mail::fake();

        // gmail.com: the production rule set is email:strict,dns,spoof and
        // the dns part needs a domain with real records.
        $response = $this->post('/contact', [
            'name' => 'Jane Buyer',
            'email' => 'jane@gmail.com',
            'message' => 'We would like a quote for 5 tonnes of Robusta Scratch 18.',
            'company' => 'Example Roasters Ltd',
            'country' => 'United Kingdom',
            'product' => 'robusta_beans',
        ]);

        // Redirect always goes through the named contact route with sent=1.
        $response->assertRedirect(route('contact', ['sent' => 1]));

        $this->assertDatabaseHas('leads', [
            'name' => 'Jane Buyer',
            'email' => 'jane@gmail.com',
            'product' => 'robusta_beans',
            'status' => 'new',
        ]);

        Mail::assertSent(NewLeadMail::class, function (NewLeadMail $mail): bool {
            return $mail->lead->email === 'jane@gmail.com';
        });
    }

    public function test_invalid_submission_shows_errors_and_stores_nothing(): void
    {
        Mail::fake();

        $response = $this->post('/contact', [
            'name' => 'Jane Buyer',
            'email' => 'not-an-email',
            'message' => 'too short',
        ]);

        $response->assertSessionHasErrors(['email', 'message']);
        $this->assertDatabaseCount('leads', 0);
        Mail::assertNothingSent();
    }

    public function test_product_param_from_coffee_cta_preselects_option(): void
    {
        $this->seed(CoffeeProductSeeder::class);

        // A coffee slug maps through its visible dynamic category.
        $response = $this->get('/contact?product=robusta-scratch-18-bean-coffee');

        $response->assertStatus(200);
        $response->assertSee('value="category:beans" selected', false);
    }

    public function test_legacy_product_keys_remain_accepted_for_direct_enquiries(): void
    {
        Mail::fake();

        foreach (['robusta_beans', 'arabica_beans'] as $product) {
            $email = str_replace('_', '.', $product).'@gmail.com';

            $this->post('/contact', [
                'name' => 'Legacy Buyer',
                'email' => $email,
                'message' => 'Please send product documentation and wholesale sample details.',
                'product' => $product,
            ])->assertRedirect(route('contact', ['sent' => 1]));

            $this->assertDatabaseHas('leads', [
                'email' => $email,
                'product' => $product,
            ]);
        }

        Mail::assertSent(NewLeadMail::class, 2);
    }

    public function test_unknown_product_param_does_not_preselect(): void
    {
        $response = $this->get('/contact?product=junk-value');

        $response->assertStatus(200);
        $response->assertDontSee('value="other" selected', false);
    }

    public function test_array_valued_product_param_is_normalized_instead_of_fatal(): void
    {
        // Bracket-syntax query strings (?product[]=x) arrive from the raw
        // query bag as arrays; the controller must normalize them to null
        // (mirroring CoffeeController's ?category= guard) instead of
        // TypeError-ing into an unhandled 500 on a public, unthrottled route.
        foreach (['/contact?product[]=x', '/contact?product[0]=x'] as $url) {
            $response = $this->get($url);

            $response->assertStatus(200);
            $response->assertDontSee('value="other" selected', false);
            $response->assertDontSee('value="category:beans" selected', false);
        }
    }
}
