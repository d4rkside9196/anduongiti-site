<?php

namespace Tests\Feature;

use App\Actions\Auth\InviteUser;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_success_and_failure_record_context_without_credentials(): void
    {
        $user = User::factory()->superAdmin()->create();
        request()->server->set('REMOTE_ADDR', '203.0.113.10');
        request()->headers->set('User-Agent', 'Security Test Agent');

        $this->actingAs($user);
        event(new Login('web', $user, false));
        auth()->logout();
        event(new Failed('web', $user, [
            'email' => $user->email,
            'password' => 'Plaintext-Login-Secret!',
            'token' => 'reset-token-probe',
        ]));

        $success = ActivityLog::where('action', 'auth.login')->firstOrFail();
        $failure = ActivityLog::where('action', 'auth.failed')->firstOrFail();

        $this->assertSame($user->id, $success->user_id);
        $this->assertSame($user->id, (int) $success->subject_id);
        $this->assertSame(User::class, $success->subject_type);
        $this->assertSame('203.0.113.10', $success->ip_address);
        $this->assertSame('Security Test Agent', $success->user_agent);
        $this->assertNotNull($success->created_at);
        $this->assertNull($failure->user_id);
        $this->assertSame($user->id, (int) $failure->subject_id);

        $serialized = json_encode(ActivityLog::all()->toArray());
        $this->assertStringNotContainsString('Plaintext-Login-Secret!', $serialized);
        $this->assertStringNotContainsString('reset-token-probe', $serialized);
        $this->assertStringNotContainsString('password', $serialized);
        $this->assertStringNotContainsString('token', $serialized);
    }

    public function test_invitation_logs_only_role_and_active_state(): void
    {
        Notification::fake();
        $actor = User::factory()->superAdmin()->create();
        $this->actingAs($actor);

        $invited = app(InviteUser::class)->handle([
            'name' => 'Audited Invite',
            'email' => 'audited-invite@example.com',
            'role' => User::ROLE_SUPPORT,
        ], $actor);

        $log = ActivityLog::where('action', 'user.invited')->firstOrFail();
        $this->assertSame($actor->id, $log->user_id);
        $this->assertSame($invited->id, (int) $log->subject_id);
        $this->assertSame(['role' => User::ROLE_SUPPORT, 'is_active' => true], $log->properties);

        $serialized = json_encode($log->toArray());
        $this->assertStringNotContainsString($invited->password, $serialized);
        $this->assertStringNotContainsString('password', $serialized);
        $this->assertStringNotContainsString('token', $serialized);
    }

    public function test_settings_save_logs_changed_keys_but_not_values(): void
    {
        $actor = User::factory()->superAdmin()->create();
        $secretEmail = 'private-settings-value@example.com';

        Livewire::actingAs($actor)
            ->test(ManageSettings::class)
            ->set('data.contact.email', $secretEmail)
            ->call('save')
            ->assertHasNoErrors();

        $log = ActivityLog::where('action', 'settings.saved')->firstOrFail();
        $this->assertSame($actor->id, $log->user_id);
        $this->assertSame(['changed_keys' => ['contact.email']], $log->properties);
        $this->assertStringNotContainsString($secretEmail, json_encode($log->toArray()));
    }

    public function test_catalog_mutations_log_safe_attribute_names_not_content(): void
    {
        $actor = User::factory()->catalogManager()->create();
        $this->actingAs($actor);
        $post = $this->createPost(['title' => 'Sensitive Catalog Title']);

        $post->update(['title' => 'Updated Sensitive Catalog Title']);
        $post->delete();

        foreach (['catalog.created', 'catalog.updated', 'catalog.deleted'] as $action) {
            $this->assertDatabaseHas('activity_logs', ['action' => $action, 'user_id' => $actor->id]);
        }

        $created = ActivityLog::where('action', 'catalog.created')->firstOrFail();
        $updated = ActivityLog::where('action', 'catalog.updated')->firstOrFail();
        $this->assertContains('title', $created->properties['attributes']);
        $this->assertSame(['title'], $updated->properties['changed']);

        $serialized = json_encode(ActivityLog::all()->toArray());
        $this->assertStringNotContainsString('Sensitive Catalog Title', $serialized);
        $this->assertStringNotContainsString('Updated Sensitive Catalog Title', $serialized);
    }

    public function test_logger_recursively_redacts_secrets_and_discards_unlisted_fields(): void
    {
        $actor = User::factory()->superAdmin()->create();
        $this->actingAs($actor);
        $post = $this->createPost();

        app(ActivityLogger::class)->record('catalog.updated', $post, [
            'changed' => [
                'password' => 'nested-password',
                'nested' => [
                    'password_confirmation' => 'nested-confirmation',
                    'token' => 'nested-token',
                    'secret' => 'nested-secret',
                    'authorization' => 'Bearer nested-authorization',
                    'message' => 'private lead message',
                    'safe' => 'visible-safe-value',
                ],
            ],
            'unexpected' => 'discard-me',
        ]);

        $log = ActivityLog::where('action', 'catalog.updated')->firstOrFail();
        $changed = $log->properties['changed'];
        $this->assertSame('[redacted]', $changed['password']);
        $this->assertSame('[redacted]', $changed['nested']['password_confirmation']);
        $this->assertSame('[redacted]', $changed['nested']['token']);
        $this->assertSame('[redacted]', $changed['nested']['secret']);
        $this->assertSame('[redacted]', $changed['nested']['authorization']);
        $this->assertSame('[redacted]', $changed['nested']['message']);
        $this->assertSame('visible-safe-value', $changed['nested']['safe']);
        $this->assertArrayNotHasKey('unexpected', $log->properties);

        $serialized = json_encode($log->toArray());
        foreach (['nested-password', 'nested-confirmation', 'nested-token', 'nested-secret', 'nested-authorization', 'private lead message', 'discard-me'] as $secret) {
            $this->assertStringNotContainsString($secret, $serialized);
        }
    }

    public function test_user_role_changes_are_logged_by_attribute_name_only(): void
    {
        $actor = User::factory()->superAdmin()->create();
        $target = User::factory()->support()->create();
        $this->actingAs($actor);

        $target->update(['role' => User::ROLE_CATALOG_MANAGER]);

        $log = ActivityLog::where('action', 'user.updated')->firstOrFail();
        $this->assertSame(['changed' => ['role']], $log->properties);
        $this->assertSame($target->id, (int) $log->subject_id);
        $this->assertStringNotContainsString(User::ROLE_CATALOG_MANAGER, json_encode($log->properties));
    }

    public function test_activity_log_is_read_only_and_only_super_admin_can_open_it(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $catalogManager = User::factory()->catalogManager()->create();
        $support = User::factory()->support()->create();
        $log = ActivityLog::create(['action' => 'test.event', 'created_at' => now()]);

        $this->actingAs($catalogManager)->get('/admin/activity-logs')->assertForbidden();
        $this->actingAs($support)->get('/admin/activity-logs')->assertForbidden();
        $this->actingAs($superAdmin)->get('/admin/activity-logs')->assertOk();
        $this->get('/admin/activity-logs/create')->assertNotFound();

        $this->assertFalse(ActivityLogResource::canCreate());
        $this->assertFalse(ActivityLogResource::canEdit($log));
        $this->assertFalse(ActivityLogResource::canDelete($log));
        $this->assertSame(['index'], array_keys(ActivityLogResource::getPages()));
    }

    private function createPost(array $overrides = []): Post
    {
        return Post::create(array_merge([
            'slug' => 'activity-'.uniqid(),
            'locale' => 'en',
            'title' => 'Activity post',
            'excerpt' => 'Activity excerpt.',
            'body' => 'Activity body.',
            'tags' => [],
            'published_at' => '2026-11-13 00:00:00',
            'status' => 'draft',
        ], $overrides));
    }
}
