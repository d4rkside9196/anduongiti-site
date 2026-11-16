<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\Leads\RelationManagers\NotesRelationManager;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadSupportWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_table_contains_only_unassigned_and_own_records_while_admin_sees_all(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $support = User::factory()->support()->create();
        $otherSupport = User::factory()->support()->create();
        $unassigned = $this->createLead(['name' => 'Unassigned']);
        $own = $this->createLead(['name' => 'Own', 'assigned_to' => $support->id]);
        $other = $this->createLead(['name' => 'Other', 'assigned_to' => $otherSupport->id]);

        Livewire::actingAs($support)
            ->test(ListLeads::class)
            ->assertCanSeeTableRecords([$unassigned, $own])
            ->assertCanNotSeeTableRecords([$other]);

        Livewire::actingAs($admin)
            ->test(ListLeads::class)
            ->assertCanSeeTableRecords([$unassigned, $own, $other]);
    }

    public function test_support_cannot_open_another_users_view_or_notes_relation_manager(): void
    {
        $support = User::factory()->support()->create();
        $otherSupport = User::factory()->support()->create();
        $otherLead = $this->createLead(['assigned_to' => $otherSupport->id]);

        $this->actingAs($support)
            ->get("/admin/leads/{$otherLead->id}")
            ->assertNotFound();

        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($support)->test(NotesRelationManager::class, [
            'ownerRecord' => $otherLead,
            'pageClass' => ViewLead::class,
        ]);
    }

    public function test_workspace_actions_use_workflow_invariants_and_append_notes(): void
    {
        $support = User::factory()->support()->create();
        $lead = $this->createLead();

        $component = Livewire::actingAs($support)->test(ListLeads::class);
        $component->callTableAction('claim', $lead);
        $this->assertSame($support->id, $lead->fresh()->assigned_to);

        $component->callTableAction('changeStatus', $lead, ['status' => 'in_progress']);
        $this->assertSame('in_progress', $lead->fresh()->status);
        $component->callTableAction('changeStatus', $lead, ['status' => 'replied']);
        $firstResponse = $lead->fresh()->first_response_at;
        $this->assertNotNull($firstResponse);
        $component->callTableAction('changeStatus', $lead, ['status' => 'in_progress']);
        $component->callTableAction('changeStatus', $lead, ['status' => 'replied']);
        $this->assertTrue($firstResponse->equalTo($lead->fresh()->first_response_at));

        Livewire::actingAs($support)
            ->test(NotesRelationManager::class, [
                'ownerRecord' => $lead->fresh(),
                'pageClass' => ViewLead::class,
            ])
            ->callTableAction('createNote', data: ['body' => '<b>Internal</b> note'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('<b>Internal</b> note', $lead->notes()->sole()->body);
        $this->assertSame(6, ActivityLog::where('subject_id', (string) $lead->id)->count());
    }

    public function test_super_admin_assignment_options_and_legacy_edit_route_cannot_bypass_actions(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $support = User::factory()->support()->create();
        User::factory()->support()->inactive()->create();
        User::factory()->catalogManager()->create();
        $lead = $this->createLead();

        Livewire::actingAs($admin)
            ->test(ListLeads::class)
            ->callTableAction('assign', $lead, ['assignee_id' => $support->id])
            ->assertHasNoTableActionErrors();

        $this->assertSame($support->id, $lead->fresh()->assigned_to);
        $this->assertSame('lead.assigned', ActivityLog::latest('id')->first()->action);
        $this->actingAs($admin)->get("/admin/leads/{$lead->id}/edit")->assertNotFound();
    }

    private function createLead(array $overrides = []): Lead
    {
        return Lead::create(array_merge([
            'name' => 'Test Lead',
            'email' => fake()->unique()->safeEmail(),
            'message' => 'Product enquiry',
            'status' => 'new',
            'assigned_to' => null,
            'last_activity_at' => now(),
        ], $overrides));
    }
}
