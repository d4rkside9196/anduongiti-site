<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class LeadRecordScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_scope_contains_all_for_super_admin_and_only_own_or_unassigned_for_support(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $support = User::factory()->support()->create();
        $otherSupport = User::factory()->support()->create();
        $unassigned = $this->createLead(['name' => 'Unassigned']);
        $own = $this->createLead(['name' => 'Own', 'assigned_to' => $support->id]);
        $other = $this->createLead(['name' => 'Other', 'assigned_to' => $otherSupport->id]);
        $historical = $this->createLead(['name' => 'Historical', 'status' => 'archived', 'assigned_to' => $support->id]);

        $this->assertEqualsCanonicalizing(
            [$unassigned->id, $own->id, $other->id, $historical->id],
            Lead::visibleTo($superAdmin)->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$unassigned->id, $own->id, $historical->id],
            Lead::visibleTo($support)->pluck('id')->all(),
        );

        $this->actingAs($support);
        $this->assertEqualsCanonicalizing(
            [$unassigned->id, $own->id, $historical->id],
            LeadResource::getEloquentQuery()->pluck('id')->all(),
        );
    }

    public function test_catalog_manager_and_inactive_support_see_no_leads(): void
    {
        $catalogManager = User::factory()->catalogManager()->create();
        $inactiveSupport = User::factory()->support()->inactive()->create();
        $this->createLead();

        $this->assertSame(0, Lead::visibleTo($catalogManager)->count());
        $this->assertSame(0, Lead::visibleTo($inactiveSupport)->count());
    }

    public function test_support_cannot_open_or_mutate_another_support_users_lead_directly(): void
    {
        $support = User::factory()->support()->create();
        $otherSupport = User::factory()->support()->create();
        $otherLead = $this->createLead(['assigned_to' => $otherSupport->id]);

        $this->actingAs($support)
            ->get("/admin/leads/{$otherLead->id}")
            ->assertNotFound();

        $this->assertFalse(Gate::forUser($support)->allows('view', $otherLead));
        $this->assertFalse(Gate::forUser($support)->allows('update', [$otherLead, ['assigned_to' => $support->id]]));

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($support)
            ->test(ViewLead::class, ['record' => $otherLead->getKey()]);
    }

    public function test_support_can_open_own_and_unassigned_leads_but_cannot_delete_them(): void
    {
        $support = User::factory()->support()->create();
        $own = $this->createLead(['assigned_to' => $support->id]);
        $unassigned = $this->createLead();

        $this->actingAs($support)->get("/admin/leads/{$own->id}")->assertOk();
        $this->assertTrue(Gate::forUser($support)->allows('update', $own));
        $this->assertTrue(Gate::forUser($support)->allows('update', $unassigned));
        $this->assertFalse(Gate::forUser($support)->allows('delete', $own));
    }

    private function createLead(array $overrides = []): Lead
    {
        return Lead::create(array_merge([
            'name' => 'Test Lead',
            'email' => fake()->unique()->safeEmail(),
            'message' => 'Product enquiry',
            'status' => 'new',
            'assigned_to' => null,
        ], $overrides));
    }
}
