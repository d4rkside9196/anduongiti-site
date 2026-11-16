<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ActivityLogger
{
    private const REDACTED = '[redacted]';

    /**
     * Only these fields may cross the audit boundary for each action.
     */
    private const SAFE_FIELDS = [
        'auth.login' => [],
        'auth.failed' => [],
        'user.invited' => ['role', 'is_active'],
        'user.updated' => ['changed'],
        'user.deleted' => [],
        'settings.saved' => ['changed_keys'],
        'catalog.created' => ['attributes'],
        'catalog.updated' => ['changed'],
        'catalog.deleted' => ['attributes'],
        'lead.assigned' => ['assignee_id', 'assignee_email'],
        'lead.unassigned' => ['assignee_id', 'assignee_email'],
        'lead.status_changed' => ['status'],
        'lead.note_added' => ['note_id'],
        'lead.exported' => ['count', 'filter_keys', 'include_notes'],
    ];

    public function record(string $action, ?Model $subject = null, array $properties = []): void
    {
        $actor = auth()->user();
        // Audit must never block a migration or the underlying write when
        // the activity table has not been created yet (legacy schemas).
        if (! Schema::hasTable('activity_logs')) {
            Log::warning('Activity log table missing; skipping audit entry', ['action' => $action]);

            return;
        }

        $properties = Arr::only($properties, self::SAFE_FIELDS[$action] ?? []);
        $properties = $this->redact($properties);

        ActivityLog::create([
            'user_id' => $actor instanceof User ? $actor->getKey() : null,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject ? (string) $subject->getKey() : null,
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    private function redact(array $properties): array
    {
        foreach ($properties as $key => $value) {
            if (is_string($key) && $this->isSecretKey($key)) {
                $properties[$key] = self::REDACTED;

                continue;
            }

            if (is_array($value)) {
                $properties[$key] = $this->redact($value);
            }
        }

        return $properties;
    }

    private function isSecretKey(string $key): bool
    {
        $key = strtolower($key);

        foreach (['password', 'token', 'secret', 'authorization', 'credential', 'cookie', 'message'] as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return in_array($key, ['api_key', 'apikey', 'passcode'], true);
    }
}
