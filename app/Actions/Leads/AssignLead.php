<?php

namespace App\Actions\Leads;

use App\Authorization\AdminPermissions;
use App\Models\Lead;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AssignLead
{
    public function __construct(private ActivityLogger $logger) {}

    public function handle(Lead $lead, ?User $assignee, User $actor): Lead
    {
        Gate::forUser($actor)->authorize('update', $lead);

        if ($assignee && ! AdminPermissions::allows($assignee, AdminPermissions::LEADS_WRITE)) {
            throw ValidationException::withMessages([
                'assignee' => 'The assignee must be an active, verified support user or super admin.',
            ]);
        }

        return DB::transaction(function () use ($lead, $assignee, $actor): Lead {
            $lockedLead = Lead::query()->whereKey($lead->getKey())->lockForUpdate()->firstOrFail();

            if (! $actor->isSuperAdmin()) {
                $canClaim = $assignee
                    && $assignee->is($actor)
                    && $lockedLead->assigned_to === null;
                $canRelease = $assignee === null
                    && (int) $lockedLead->assigned_to === $actor->getKey();

                if (! $canClaim && ! $canRelease) {
                    throw new AuthorizationException('You cannot change this lead assignment.');
                }
            }

            $lockedLead->update([
                'assigned_to' => $assignee?->getKey(),
                'last_activity_at' => now(),
            ]);

            $this->logger->record($assignee ? 'lead.assigned' : 'lead.unassigned', $lockedLead, [
                'assignee_id' => $assignee?->getKey(),
                'assignee_email' => $assignee?->email,
            ]);

            return $lockedLead->fresh();
        });
    }
}
