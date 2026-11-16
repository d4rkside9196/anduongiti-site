<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Notifications\Livewire\Notifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class PlatformRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (str_starts_with($this->name(), 'test_vi_')) {
            putenv('ROUTING_LOCALE=vi');
        } else {
            putenv('ROUTING_LOCALE');
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv('ROUTING_LOCALE');
        parent::tearDown();
    }

    public function test_en_future_post_becomes_public_on_index_detail_and_sitemap_at_publish_time(): void
    {
        $this->assertPublishingSchedule('en');
    }

    public function test_vi_future_post_becomes_public_on_index_detail_and_sitemap_at_publish_time(): void
    {
        $this->assertPublishingSchedule('vi');
    }

    public function test_en_partial_contact_row_preserves_email_and_uses_fallbacks_on_public_pages(): void
    {
        Setting::write('contact', ['email' => 'only@example.com']);

        $this->get('/about')
            ->assertOk()
            ->assertSee('only@example.com')
            ->assertSee('+84 915 663 683')
            ->assertSee('112 Me Tri Ha, Tu Liem Ward, Hanoi, Vietnam')
            ->assertSee('tel:+84915663683', false);
        $this->get('/support')
            ->assertOk()
            ->assertSee('only@example.com')
            ->assertSee('+84 915 663 683');
    }

    public function test_vi_partial_contact_row_preserves_email_and_uses_fallbacks_on_public_pages(): void
    {
        Setting::write('contact', ['email' => 'only@example.com']);

        $this->get('/vi/about')
            ->assertOk()
            ->assertSee('only@example.com')
            ->assertSee('+84 915 663 683')
            ->assertSee('112 Mễ Trì Hạ, Phường Từ Liêm, TP Hà Nội, Việt Nam')
            ->assertSee('tel:+84915663683', false);
        $this->get('/vi/support')
            ->assertOk()
            ->assertSee('only@example.com')
            ->assertSee('+84 915 663 683');
    }

    public function test_settings_save_rolls_back_contact_and_sends_no_notification_when_social_write_fails(): void
    {
        $oldContact = [
            'email' => 'old@example.com',
            'phone' => '+84 900 000 000',
            'zalo' => '+84 900 000 001',
            'whatsapp' => '+84 900 000 002',
            'address_en' => 'Old EN address',
            'address_vi' => 'Old VI address',
            'linkedin' => 'https://www.linkedin.com/company/old',
        ];
        $oldSocial = [
            'facebook' => 'https://www.facebook.com/old',
            'shopee' => 'https://shopee.vn/old',
            'alibaba' => 'https://old.en.alibaba.com',
            'linkedin' => 'https://www.linkedin.com/company/old',
        ];
        Setting::write('contact', $oldContact);
        Setting::write('social', $oldSocial);
        $user = User::factory()->superAdmin()->create();

        Setting::saving(function (Setting $setting): void {
            if ($setting->key === 'social') {
                throw new RuntimeException('Injected social write failure');
            }
        });

        $thrown = null;
        try {
            Livewire::actingAs($user)
                ->test(ManageSettings::class)
                ->set('data.contact.email', 'new@example.com')
                ->call('save');
        } catch (RuntimeException $exception) {
            $thrown = $exception;
        } finally {
            Event::forget('eloquent.saving: '.Setting::class);
        }

        $this->assertInstanceOf(RuntimeException::class, $thrown);
        $this->assertSame('Injected social write failure', $thrown->getMessage());
        $this->assertEqualsCanonicalizing($oldContact, Setting::read('contact'));
        $this->assertEqualsCanonicalizing($oldSocial, Setting::read('social'));

        $notifications = new Notifications;
        $notifications->mount();
        $this->assertTrue($notifications->notifications->isEmpty());
    }

    private function assertPublishingSchedule(string $locale): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-13 12:00:00'));

        $path = $locale === 'vi' ? '/vi/news' : '/news';
        $past = Post::create([
            'slug' => "past-platform-post-{$locale}",
            'locale' => $locale,
            'title' => "Past platform post {$locale}",
            'excerpt' => 'Already published.',
            'body' => 'Already published.',
            'published_at' => now()->subDay(),
            'status' => 'published',
        ]);
        $future = Post::create([
            'slug' => "future-platform-post-{$locale}",
            'locale' => $locale,
            'title' => "Future platform post {$locale}",
            'excerpt' => 'Scheduled publication.',
            'body' => 'Scheduled publication.',
            'published_at' => now()->addDay(),
            'status' => 'published',
        ]);

        $this->get($path)
            ->assertOk()
            ->assertSee($past->title)
            ->assertDontSee($future->title);
        $this->get("{$path}/{$past->slug}")->assertOk();
        $this->get("{$path}/{$future->slug}")->assertNotFound();
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee("{$path}/{$past->slug}", false)
            ->assertDontSee("{$path}/{$future->slug}", false);

        $this->travelTo($future->published_at);

        $this->get($path)
            ->assertOk()
            ->assertSee($past->title)
            ->assertSee($future->title);
        $this->get("{$path}/{$past->slug}")->assertOk();
        $this->get("{$path}/{$future->slug}")->assertOk();
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee("{$path}/{$past->slug}", false)
            ->assertSee("{$path}/{$future->slug}", false);
    }
}
