<?php

namespace App\Actions\Leads;

use App\Authorization\AdminPermissions;
use App\Models\Lead;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportLeadsCsv
{
    public function __construct(private ActivityLogger $logger) {}

    public function handle(User $actor, array $filters = [], bool $includeNotes = false): StreamedResponse
    {
        if (! AdminPermissions::allows($actor, AdminPermissions::LEADS_WRITE)) {
            throw new AuthorizationException('You cannot export leads.');
        }

        if ($includeNotes && ! $actor->isSuperAdmin()) {
            throw new AuthorizationException('Only super admins can export internal notes.');
        }

        $query = Lead::visibleTo($actor)->with('assignee');
        if ($includeNotes) {
            $query->with('notes');
        }
        $activeFilters = $this->applyFilters($query, $filters);
        $count = (clone $query)->count();

        $this->logger->record('lead.exported', properties: [
            'count' => $count,
            'filter_keys' => array_keys($activeFilters),
            'include_notes' => $includeNotes,
        ]);

        $headers = [
            'id', 'created_at', 'name', 'company', 'email', 'country', 'product', 'source_slug',
            'source_product_title', 'source_category_slug', 'status', 'assignee_email',
            'first_response_at', 'last_activity_at', 'message',
        ];
        if ($includeNotes) {
            $headers[] = 'internal_notes';
        }

        return new StreamedResponse(function () use ($query, $headers, $includeNotes): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers, escape: '');

            $query->chunkById(100, function ($leads) use ($output, $includeNotes): void {
                foreach ($leads as $lead) {
                    $row = [
                        $lead->id,
                        $lead->created_at?->format('Y-m-d H:i:s'),
                        $lead->name,
                        $lead->company,
                        $lead->email,
                        $lead->country,
                        $lead->product,
                        $lead->source_slug,
                        $lead->source_product_title,
                        $lead->source_category_slug,
                        $lead->status,
                        $lead->assignee?->email,
                        $lead->first_response_at?->format('Y-m-d H:i:s'),
                        $lead->last_activity_at?->format('Y-m-d H:i:s'),
                        $lead->message,
                    ];

                    if ($includeNotes) {
                        $row[] = $lead->notes->sortBy('created_at')->pluck('body')->implode("\n");
                    }

                    fputcsv($output, array_map($this->sanitizeCell(...), $row), escape: '');
                }
            });

            fclose($output);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="leads-'.now()->format('Y-m-d-His').'.csv"',
        ]);
    }

    private function applyFilters(Builder $query, array $filters): array
    {
        $active = [];
        foreach (['status', 'assigned_to', 'country', 'source_category_slug', 'source_slug'] as $key) {
            $value = $this->filterValue($filters[$key] ?? null);
            if ($value === null || $value === '') {
                continue;
            }

            $query->where($key, $value);
            $active[$key] = $value;
        }

        $created = $filters['created_at'] ?? [];
        foreach (['from' => '>=', 'until' => '<='] as $key => $operator) {
            $value = is_array($created) ? ($created[$key] ?? null) : null;
            if (! is_string($value) || $value === '') {
                continue;
            }

            $query->whereDate('created_at', $operator, $value);
            $active['created_at'] = $created;
        }

        return $active;
    }

    private function filterValue(mixed $value): mixed
    {
        return is_array($value) ? ($value['value'] ?? null) : $value;
    }

    private function sanitizeCell(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        if (preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
