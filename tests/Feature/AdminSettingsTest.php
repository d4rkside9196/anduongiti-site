<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SocialSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_settings_page(): void
    {
        $this->get('/admin/settings')->assertRedirect('/admin/login');
    }

    public function test_verified_admin_can_open_settings_page(): void
    {
        $user = User::factory()->superAdmin()->create(); // UserFactory marks email verified

        $this->actingAs($user)
            ->get('/admin/settings')
            ->assertStatus(200);
    }

    public function test_settings_page_exposes_seeded_contact_and_social_values(): void
    {
        $this->seed(SocialSettingsSeeder::class);
        $user = User::factory()->superAdmin()->create();

        // Filament v4 renders field values through the Livewire snapshot
        // (not plain value="..." attributes), so assert component state.
        Livewire::actingAs($user)
            ->test(ManageSettings::class)
            ->assertSet('data.contact.email', 'contact@anduongiti.com')
            ->assertSet('data.social.shopee', 'https://shopee.vn/anduongiti')
            ->assertSet('data.contact.whatsapp', '+84 915 663 683');
    }

    public function test_save_merges_without_dropping_unknown_settings_keys(): void
    {
        $this->seed(SocialSettingsSeeder::class);
        $user = User::factory()->superAdmin()->create();

        // The save runs through the Livewire page component — plain POST
        // would not reach it.
        Livewire::actingAs($user)
            ->test(ManageSettings::class)
            ->set('data.contact.email', 'sales@anduongiti.com')
            ->call('save')
            ->assertSuccessful();

        $contact = Setting::read('contact');
        $this->assertSame('sales@anduongiti.com', $contact['email']);
        // Keys written by other seeders/code must survive the save.
        $this->assertSame('https://www.linkedin.com/company/anduongiti', $contact['linkedin']);
        $this->assertSame('https://shopee.vn/anduongiti', Setting::read('social')['shopee']);
    }
}
