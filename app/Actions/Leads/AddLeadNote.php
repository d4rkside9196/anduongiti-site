<?php

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class AddLeadNote
{
    public function __construct(private ActivityLogger $logger) {}

    public function handle(Lead $lead, string $body, User $actor): LeadNote
    {
        Gate::forUser($actor)->authorize('update', $lead);
        $validated = Validator::make(['body' => $body], [
            'body' => ['required', 'string', 'max:5000'],
        ])->validate();

        return DB::transaction(function () use ($lead, $actor, $validated): LeadNote {
            $lockedLead = Lead::query()->whereKey($lead->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $lockedLead);
            $note = $lockedLead->notes()->create([
                'user_id' => $actor->getKey(),
                'body' => $validated['body'],
            ]);
            $lockedLead->update(['last_activity_at' => now()]);

            $this->logger->record('lead.note_added', $lockedLead, ['note_id' => $note->getKey()]);

            return $note;
        });
    }
}
