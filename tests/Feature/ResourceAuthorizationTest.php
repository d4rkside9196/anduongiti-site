<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\CoffeeProducts\CoffeeProductResource;
use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Partners\PartnerResource;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\ProductCategories\ProductCategoryResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ResourceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private const CATALOG_RESOURCES = [
        PostResource::class,
        ProductResource::class,
        CoffeeProductResource::class,
        ProductCategoryResource::class,
        PartnerResource::class,
        FaqResource::class,
    ];

    #[DataProvider('catalogRoleMatrix')]
    public function test_every_catalog_resource_enforces_read_and_write_capabilities(string $role, bool $canRead, bool $canWrite): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user);
        $post = $this->createPost();

        foreach (self::CATALOG_RESOURCES as $resource) {
            $this->assertSame($canRead, $resource::canViewAny(), "{$role}: {$resource} viewAny");
            $this->assertSame($canWrite, $resource::canCreate(), "{$role}: {$resource} create");
        }

        $this->assertSame($canRead, PostResource::canView($post));
        $this->assertSame($canWrite, PostResource::canEdit($post));
        $this->assertSame($canWrite, PostResource::canDelete($post));
        $this->assertSame($canWrite, PostResource::canDeleteAny());
    }

    public function test_support_is_denied_by_direct_catalog_livewire_action(): void
    {
        $support = User::factory()->support()->create();

        Livewire::actingAs($support)
            ->test(CreatePost::class)
            ->assertForbidden();

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_catalog_manager_can_mutate_catalog_through_livewire(): void
    {
        $catalogManager = User::factory()->catalogManager()->create();

        Livewire::actingAs($catalogManager)
            ->test(CreatePost::class)
            ->fillForm([
                'slug' => 'authorized-post',
                'locale' => 'en',
                'title' => 'Authorized post',
                'excerpt' => 'Authorized excerpt.',
                'body' => 'Authorized body.',
                'tags' => [],
                'cover' => null,
                'published_at' => '2026-11-13 00:00:00',
                'status' => 'draft',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('posts', ['slug' => 'authorized-post']);
    }

    #[DataProvider('settingsRoleMatrix')]
    public function test_only_super_admin_can_access_settings(string $role, bool $allowed): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->get('/admin/settings');
        $response->assertStatus($allowed ? 200 : 403);
    }

    public function test_direct_settings_component_access_is_denied_for_catalog_manager(): void
    {
        $catalogManager = User::factory()->catalogManager()->create();

        Livewire::actingAs($catalogManager)
            ->test(ManageSettings::class)
            ->assertForbidden();
    }

    #[DataProvider('leadRoleMatrix')]
    public function test_lead_resource_capabilities_match_the_role_matrix(string $role, bool $canRead, bool $canWrite, bool $canDelete): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user);

        $this->assertSame($canRead, LeadResource::canViewAny());
        $this->assertSame($canWrite, LeadResource::canCreate());
        $this->assertSame($canDelete, LeadResource::canDelete($this->createLead()));
    }

    public static function catalogRoleMatrix(): array
    {
        return [
            'super admin' => [User::ROLE_SUPER_ADMIN, true, true],
            'catalog manager' => [User::ROLE_CATALOG_MANAGER, true, true],
            'support' => [User::ROLE_SUPPORT, false, false],
        ];
    }

    public static function settingsRoleMatrix(): array
    {
        return [
            'super admin' => [User::ROLE_SUPER_ADMIN, true],
            'catalog manager' => [User::ROLE_CATALOG_MANAGER, false],
            'support' => [User::ROLE_SUPPORT, false],
        ];
    }

    public static function leadRoleMatrix(): array
    {
        return [
            'super admin' => [User::ROLE_SUPER_ADMIN, true, true, true],
            'catalog manager' => [User::ROLE_CATALOG_MANAGER, false, false, false],
            'support' => [User::ROLE_SUPPORT, true, true, false],
        ];
    }

    private function createPost(): Post
    {
        return Post::create([
            'slug' => 'authorization-post',
            'locale' => 'en',
            'title' => 'Authorization post',
            'excerpt' => 'Authorization excerpt.',
            'body' => 'Authorization body.',
            'tags' => [],
            'status' => 'draft',
            'published_at' => '2026-11-13 00:00:00',
        ]);
    }

    private function createLead(): \App\Models\Lead
    {
        return \App\Models\Lead::create([
            'name' => 'Lead User',
            'email' => 'lead@example.com',
            'message' => 'A product enquiry.',
            'status' => 'new',
        ]);
    }
}
