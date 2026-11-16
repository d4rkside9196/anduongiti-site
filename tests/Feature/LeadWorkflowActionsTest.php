<?php

namespace Tests\Feature;

use App\Actions\Leads\AddLeadNote;
use App\Actions\Leads\AssignLead;
use App\Actions\Leads\ChangeLeadStatus;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeadWorkflowActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_assigns_an_active_support_user_with_safe_audit_metadata(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $assignee = User::factory()->support()->create(['email' => 'owner@gmail.com']);
        $lead = $this->createLead([
            'source_slug' => 'historical-sku',
            'source_product_title' => 'Historical title',
            'source_category_slug' => 'beans',
        ]);
        $this->actingAs($admin);
        $previousActivity = $lead->last_activity_at;
        $this->travel(1)->minutes();

        $result = app(AssignLead::class)->handle($lead, $assignee, $admin);

        $this->assertSame($assignee->id, $result->assigned_to);
        $this->assertTrue($result->last_activity_at->greaterThan($previousActivity));
        $this->assertSame('historical-sku', $result->source_slug);
        $this->assertSame('Historical title', $result->source_product_title);
        $this->assertSame('beans', $result->source_category_slug);
        $log = ActivityLog::where('action', 'lead.assigned')->sole();
        $this->assertSame([
            'assignee_id' => $assignee->id,
            'assignee_email' => 'owner@gmail.com',
        ], $log->properties);
        $this->assertSame($admin->id, $log->user_id);
    }

    public function test_support_can_claim_an_unassigned_lead_and_release_their_own_lead(): void
    {
        $support = User::factory()->support()->create();
        $lead = $this->createLead();
        $this->actingAs($support);
        $action = app(AssignLead::class);

        $claimed = $action->handle($lead, $support, $support);
        $this->assertSame($support->id, $claimed->assigned_to);

        $released = $action->handle($claimed, null, $support);
        $this->assertNull($released->assigned_to);
        $this->assertSame(1, ActivityLog::where('action', 'lead.unassigned')->count());
    }

    public function test_support_cannot_assign_another_support_users_lead(): void
    {
        $support = User::factory()->support()->create();
        $otherSupport = User::factory()->support()->create();
        $lead = $this->createLead(['assigned_to' => $otherSupport->id]);
        $this->actingAs($support);

        $this->expectException(AuthorizationException::class);
        app(AssignLead::class)->handle($lead, $support, $support);
    }

    public function test_assignment_rejects_invalid_inactive_and_unverified_assignees_without_mutation(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $lead = $this->createLead();
        $this->actingAs($admin);
        $invalidAssignees = [
            User::factory()->catalogManager()->create(),
            User::factory()->support()->inactive()->create(),
            User::factory()->support()->unverified()->create(),
        ];

        foreach ($invalidAssignees as $assignee) {
            try {
                app(AssignLead::class)->handle($lead, $assignee, $admin);
                $this->fail('Invalid assignee was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('assignee', $exception->errors());
            }
        }

        $this->assertNull($lead->fresh()->assigned_to);
        $this->assertSame(0, ActivityLog::count());
    }

    public function test_status_transitions_preserve_first_response_timestamp_on_repeated_requests(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $lead = $this->createLead();
        $this->actingAs($admin);
        $action = app(ChangeLeadStatus::class);
        $previousActivity = $lead->last_activity_at;
        $this->travel(1)->minutes();

        $this->assertSame('in_progress', $action->handle($lead, 'in_progress', $admin)->status);
        $this->assertNull($lead->fresh()->first_response_at);

        $replied = $action->handle($lead->fresh(), 'replied', $admin);
        $firstResponse = $replied->first_response_at;
        $this->assertNotNull($firstResponse);
        $this->assertTrue($replied->last_activity_at->greaterThan($previousActivity));

        foreach (['in_progress', 'replied', 'archived', 'new', 'replied', 'replied'] as $status) {
            $result = $action->handle($lead->fresh(), $status, $admin);
            $this->assertSame($status, $result->status);
            $this->assertTrue($firstResponse->equalTo($result->first_response_at));
        }

        $this->assertSame(8, ActivityLog::where('action', 'lead.status_changed')->count());
        $this->assertSame(['status' => 'replied'], ActivityLog::where('action', 'lead.status_changed')->latest('id')->first()->properties);
    }

    public function test_status_rejects_unknown_values_and_out_of_scope_records(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $lead = $this->createLead();
        $this->actingAs($admin);

        try {
            app(ChangeLeadStatus::class)->handle($lead, 'closed', $admin);
            $this->fail('Unknown status was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }
        $this->assertSame('new', $lead->fresh()->status);

        $support = User::factory()->support()->create();
        $otherSupport = User::factory()->support()->create();
        $otherLead = $this->createLead(['assigned_to' => $otherSupport->id]);
        $this->actingAs($support);
        $this->expectException(AuthorizationException::class);
        app(ChangeLeadStatus::class)->handle($otherLead, 'replied', $support);
    }

    public function test_notes_are_append_only_validated_and_never_log_their_body(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $lead = $this->createLead();
        $this->actingAs($admin);
        $action = app(AddLeadNote::class);
        $previousActivity = $lead->last_activity_at;
        $this->travel(1)->minutes();

        $first = $action->handle($lead, 'First internal note with customer-secret', $admin);
        $second = $action->handle($lead->fresh(), 'Second internal note', $admin);

        $this->assertSame(2, $lead->notes()->count());
        $this->assertSame('First internal note with customer-secret', $first->fresh()->body);
        $this->assertSame('Second internal note', $second->body);
        $this->assertSame($admin->id, $second->user_id);
        $this->assertTrue($lead->fresh()->last_activity_at->greaterThan($previousActivity));
        $logs = ActivityLog::where('action', 'lead.note_added')->get();
        $this->assertSame([['note_id' => $first->id], ['note_id' => $second->id]], $logs->pluck('properties')->all());
        $this->assertStringNotContainsString('internal note', json_encode($logs->pluck('properties')->all()));

        foreach (['', '   ', str_repeat('a', 5001)] as $body) {
            try {
                $action->handle($lead->fresh(), $body, $admin);
                $this->fail('Invalid note was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('body', $exception->errors());
            }
        }
        $this->assertSame(2, LeadNote::count());
    }

    public function test_notes_and_export_audit_allowlists_drop_unapproved_fields(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $lead = $this->createLead();
        $this->actingAs($admin);
        $logger = app(ActivityLogger::class);

        $logger->record('lead.note_added', $lead, ['note_id' => 123, 'body' => 'private note']);
        $logger->record('lead.exported', $lead, [
            'count' => 2,
            'filter_keys' => ['status', 'assigned_to'],
            'include_notes' => true,
            'message' => 'private message',
        ]);

        $this->assertSame(['note_id' => 123], ActivityLog::where('action', 'lead.note_added')->sole()->properties);
        $this->assertSame([
            'count' => 2,
            'filter_keys' => ['status', 'assigned_to'],
            'include_notes' => true,
        ], ActivityLog::where('action', 'lead.exported')->sole()->properties);
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
