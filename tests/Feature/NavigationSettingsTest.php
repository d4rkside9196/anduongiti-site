<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Models\Setting;
use App\Models\User;
use App\Support\Navigation;
use Database\Seeders\NavigationSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NavigationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_items_follow_config_order_hrefs_and_language_keys(): void
    {
        $items = Navigation::items();

        $this->assertSame(Navigation::allowedKeys(), array_column($items, 'key'));
        $this->assertSame(['about', 'products', 'coffee', 'sustainability', 'partners', 'news', 'support', 'contact'], array_column($items, 'key'));
        $this->assertSame('/coffee', $items[2]['href']);
        $this->assertSame(__('site.nav.coffee'), $items[2]['label']);
    }

    public function test_saved_navigation_reorders_and_disables_items_without_custom_urls(): void
    {
        Setting::write('navigation', ['contact', 'coffee']);

        $this->assertSame([
            ['key' => 'contact', 'href' => '/contact', 'label' => __('site.nav.contact')],
            ['key' => 'coffee', 'href' => '/coffee', 'label' => __('site.nav.coffee')],
        ], Navigation::items());
    }

    public function test_missing_partial_or_invalid_saved_values_fall_back_safely(): void
    {
        $defaults = Navigation::items();

        Setting::write('navigation', ['coffee', 'javascript:alert(1)']);
        $this->assertSame($defaults, Navigation::items());

        Setting::write('navigation', ['coffee', 'coffee']);
        $this->assertSame($defaults, Navigation::items());

        Setting::write('navigation', 'coffee');
        $this->assertSame($defaults, Navigation::items());
    }

    public function test_settings_form_rejects_duplicate_and_arbitrary_keys(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());

        Livewire::test(ManageSettings::class)
            ->set('data.contact.email', 'sales@anduongiti.com')
            ->set('data.navigation', [['key' => 'coffee'], ['key' => 'coffee']])
            ->call('save')
            ->assertHasErrors(['data.navigation.1.key']);

        Livewire::test(ManageSettings::class)
            ->set('data.contact.email', 'sales@anduongiti.com')
            ->set('data.navigation', [['key' => 'coffee'], ['key' => 'javascript:alert(1)']])
            ->call('save')
            ->assertHasErrors(['data.navigation.1.key']);

        $this->assertNull(Setting::read('navigation'));
    }

    public function test_saving_navigation_preserves_unrelated_settings_and_stores_only_keys(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());
        Setting::write('contact', ['email' => 'sales@anduongiti.com', 'legacy' => 'keep-me']);
        Setting::write('unrelated', ['secret' => 'untouched']);

        Livewire::test(ManageSettings::class)
            ->set('data.navigation', [['key' => 'partners'], ['key' => 'about']])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['partners', 'about'], Setting::read('navigation'));
        $this->assertSame('keep-me', Setting::read('contact')['legacy']);
        $this->assertSame(['secret' => 'untouched'], Setting::read('unrelated'));
        $this->assertSame(['partners', 'about'], array_column(Navigation::items(), 'key'));
    }

    public function test_navigation_seeder_is_idempotent_and_uses_allowed_defaults(): void
    {
        (new NavigationSettingsSeeder)->run();
        (new NavigationSettingsSeeder)->run();

        $this->assertSame(Navigation::allowedKeys(), Setting::read('navigation'));
        $this->assertSame(1, Setting::where('key', 'navigation')->count());
    }
}
