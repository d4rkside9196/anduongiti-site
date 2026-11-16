<?php

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ChangeLeadStatus
{
    public function __construct(private ActivityLogger $logger) {}

    public function handle(Lead $lead, string $status, User $actor): Lead
    {
        Gate::forUser($actor)->authorize('update', $lead);

        if (! in_array($status, ['new', 'in_progress', 'replied', 'archived'], true)) {
            throw ValidationException::withMessages(['status' => 'Unsupported lead status.']);
        }

        return DB::transaction(function () use ($lead, $status, $actor): Lead {
            $lockedLead = Lead::query()->whereKey($lead->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $lockedLead);

            if ($status === 'replied' && $lockedLead->first_response_at === null) {
                $lockedLead->first_response_at = now();
            }

            $lockedLead->status = $status;
            $lockedLead->last_activity_at = now();
            $lockedLead->save();

            $this->logger->record('lead.status_changed', $lockedLead, ['status' => $status]);

            return $lockedLead->fresh();
        });
    }
}
