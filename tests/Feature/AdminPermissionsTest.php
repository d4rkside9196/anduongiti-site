<?php

namespace Tests\Feature;

use App\Authorization\AdminPermissions;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use InvalidArgumentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_migration_only_elevates_the_seeded_admin(): void
    {
        $migration = require database_path('migrations/2026_11_13_000005_add_auth_fields_to_users_table.php');
        $migration->down();

        DB::table('users')->insert([
            [
                'name' => 'Administrator',
                'email' => 'admin@anduongiti.com',
                'password' => 'not-a-plaintext-password',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Legacy User',
                'email' => 'legacy@example.com',
                'password' => 'not-a-plaintext-password',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $migration->up();

        $this->assertSame(User::ROLE_SUPER_ADMIN, DB::table('users')->where('email', 'admin@anduongiti.com')->value('role'));
        $this->assertSame(User::ROLE_CATALOG_MANAGER, DB::table('users')->where('email', 'legacy@example.com')->value('role'));
        $this->assertSame(1, DB::table('users')->where('email', 'admin@anduongiti.com')->value('is_active'));
        $this->assertSame(0, DB::table('users')->where('email', 'legacy@example.com')->value('is_active'));
        $legacy = User::where('email', 'legacy@example.com')->firstOrFail();
        $this->assertFalse(AdminPermissions::allows($legacy, AdminPermissions::CATALOG_WRITE));
        $this->assertTrue(Schema::hasColumns('users', ['role', 'is_active', 'last_login_at']));
    }

    public function test_fresh_database_seeder_creates_the_documented_super_admin(): void
    {
        (new DatabaseSeeder)->run();

        $admin = User::where('email', 'admin@anduongiti.com')->firstOrFail();
        $this->assertSame(User::ROLE_SUPER_ADMIN, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue($admin->hasVerifiedEmail());
    }

    public function test_seeder_without_admin_password_does_not_use_the_public_default_credential(): void
    {
        $this->setAdminPasswordEnvironment(null);

        try {
            (new DatabaseSeeder)->run();

            $admin = User::where('email', 'admin@anduongiti.com')->firstOrFail();
            $this->assertFalse(Hash::check('anduong2026', $admin->password));
            $this->assertFalse(Hash::check('password', $admin->password));
        } finally {
            $this->setAdminPasswordEnvironment(null);
        }
    }

    public function test_reseeding_does_not_replace_an_existing_admin_password(): void
    {
        $this->setAdminPasswordEnvironment('Initial-Admin!2026');

        try {
            (new DatabaseSeeder)->run();
            $admin = User::where('email', 'admin@anduongiti.com')->firstOrFail();
            $initialHash = $admin->password;

            $this->setAdminPasswordEnvironment('Replacement-Admin!2026');
            (new DatabaseSeeder)->run();

            $this->assertSame($initialHash, $admin->fresh()->password);
            $this->assertTrue(Hash::check('Initial-Admin!2026', $admin->fresh()->password));
            $this->assertFalse(Hash::check('Replacement-Admin!2026', $admin->fresh()->password));
        } finally {
            $this->setAdminPasswordEnvironment(null);
        }
    }

    public function test_factory_creates_each_supported_role_and_role_checks_are_exact(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $catalogManager = User::factory()->catalogManager()->create();
        $support = User::factory()->support()->create();

        $this->assertTrue($superAdmin->hasRole(User::ROLE_SUPER_ADMIN));
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($superAdmin->hasRole(User::ROLE_SUPPORT));
        $this->assertTrue($catalogManager->hasRole(User::ROLE_CATALOG_MANAGER));
        $this->assertFalse($catalogManager->isSuperAdmin());
        $this->assertTrue($support->hasRole(User::ROLE_SUPPORT));
        $this->assertFalse($support->hasRole('support-admin'));
    }

    public function test_unknown_roles_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        User::factory()->create(['role' => 'sales']);
    }

    public function test_permission_matrix_matches_the_least_privilege_contract(): void
    {
        $matrix = [
            User::ROLE_SUPER_ADMIN => [
                AdminPermissions::CATALOG_READ => true,
                AdminPermissions::CATALOG_WRITE => true,
                AdminPermissions::SETTINGS_WRITE => true,
                AdminPermissions::USERS_READ => true,
                AdminPermissions::USERS_WRITE => true,
                AdminPermissions::ACTIVITY_READ => true,
                AdminPermissions::LEADS_READ => true,
                AdminPermissions::LEADS_WRITE => true,
                AdminPermissions::LEADS_DELETE => true,
            ],
            User::ROLE_CATALOG_MANAGER => [
                AdminPermissions::CATALOG_READ => true,
                AdminPermissions::CATALOG_WRITE => true,
                AdminPermissions::SETTINGS_WRITE => false,
                AdminPermissions::USERS_READ => false,
                AdminPermissions::USERS_WRITE => false,
                AdminPermissions::ACTIVITY_READ => false,
                AdminPermissions::LEADS_READ => false,
                AdminPermissions::LEADS_WRITE => false,
                AdminPermissions::LEADS_DELETE => false,
            ],
            User::ROLE_SUPPORT => [
                AdminPermissions::CATALOG_READ => false,
                AdminPermissions::CATALOG_WRITE => false,
                AdminPermissions::SETTINGS_WRITE => false,
                AdminPermissions::USERS_READ => false,
                AdminPermissions::USERS_WRITE => false,
                AdminPermissions::ACTIVITY_READ => false,
                AdminPermissions::LEADS_READ => true,
                AdminPermissions::LEADS_WRITE => true,
                AdminPermissions::LEADS_DELETE => false,
            ],
        ];

        foreach ($matrix as $role => $capabilities) {
            $user = User::factory()->create(['role' => $role]);

            foreach ($capabilities as $capability => $allowed) {
                $this->assertSame($allowed, AdminPermissions::allows($user, $capability), "{$role}: {$capability}");
            }
        }
    }

    public function test_inactive_unverified_and_unknown_capability_checks_are_denied(): void
    {
        $inactive = User::factory()->inactive()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $unverified = User::factory()->unverified()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $active = User::factory()->superAdmin()->create();

        $this->assertFalse(AdminPermissions::allows($inactive, AdminPermissions::USERS_WRITE));
        $this->assertFalse(AdminPermissions::allows($unverified, AdminPermissions::USERS_WRITE));
        $this->assertFalse(AdminPermissions::allows($active, 'unknown_capability'));
    }

    private function setAdminPasswordEnvironment(?string $password): void
    {
        if ($password === null) {
            putenv('ADMIN_PASSWORD');
            unset($_ENV['ADMIN_PASSWORD'], $_SERVER['ADMIN_PASSWORD']);

            return;
        }

        putenv("ADMIN_PASSWORD={$password}");
        $_ENV['ADMIN_PASSWORD'] = $password;
        $_SERVER['ADMIN_PASSWORD'] = $password;
    }
}
