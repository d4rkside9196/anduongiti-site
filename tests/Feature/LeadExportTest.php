<?php

namespace Tests\Feature;

use App\Actions\Leads\ExportLeadsCsv;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class LeadExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_export_is_scoped_before_even_crafted_assignment_filters(): void
    {
        $support = User::factory()->support()->create();
        $otherSupport = User::factory()->support()->create();
        $unassigned = $this->createLead(['name' => 'Unassigned export']);
        $own = $this->createLead(['name' => 'Own export', 'assigned_to' => $support->id]);
        $other = $this->createLead(['name' => 'Secret other export', 'assigned_to' => $otherSupport->id, 'source_slug' => 'secret-other-sku']);
        $this->actingAs($support);
        $action = app(ExportLeadsCsv::class);

        $rows = $this->readCsv($action->handle($support));
        $this->assertCount(3, $rows);
        $this->assertEqualsCanonicalizing(
            ['Unassigned export', 'Own export'],
            array_column(array_slice($rows, 1), 2),
        );
        $this->assertStringNotContainsString('Secret other export', $this->csvContents($action->handle($support, [
            'assigned_to' => $otherSupport->id,
            'source_slug' => $other->source_slug,
        ])));
        $log = ActivityLog::where('action', 'lead.exported')->latest('id')->first();
        $this->assertSame(0, $log->properties['count']);
        $this->assertSame(['assigned_to', 'source_slug'], $log->properties['filter_keys']);
        $this->assertFalse($log->properties['include_notes']);
    }

    public function test_export_has_exact_columns_utf8_and_optional_admin_notes(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $lead = $this->createLead([
            'name' => 'Nguyễn Văn Cà phê',
            'company' => 'Công ty cà phê',
            'source_product_title' => 'Cà phê Cầu Đất',
        ]);
        $this->actingAs($admin);
        $lead->notes()->create(['user_id' => $admin->id, 'body' => 'Private internal note']);
        $action = app(ExportLeadsCsv::class);

        $defaultRows = $this->readCsv($action->handle($admin));
        $this->assertSame([
            'id', 'created_at', 'name', 'company', 'email', 'country', 'product', 'source_slug',
            'source_product_title', 'source_category_slug', 'status', 'assignee_email',
            'first_response_at', 'last_activity_at', 'message',
        ], $defaultRows[0]);
        $this->assertSame('Nguyễn Văn Cà phê', $defaultRows[1][2]);
        $this->assertSame('Cà phê Cầu Đất', $defaultRows[1][8]);
        $this->assertStringNotContainsString('Private internal note', $this->csvContents($action->handle($admin)));

        $withNotes = $this->readCsv($action->handle($admin, [], true));
        $this->assertSame('internal_notes', $withNotes[0][15]);
        $this->assertSame('Private internal note', $withNotes[1][15]);

        $this->expectException(AuthorizationException::class);
        $action->handle(User::factory()->support()->create(), [], true);
    }

    public function test_every_formula_capable_text_cell_is_neutralized_including_whitespace_and_notes(): void
    {
        $admin = User::factory()->superAdmin()->create(['email' => '=admin@gmail.com']);
        $lead = $this->createLead([
            'name' => '=1+1',
            'company' => '+cmd',
            'email' => '@attacker.example',
            'country' => " \t-2",
            'product' => '=category:beans',
            'source_slug' => '=source-sku',
            'source_product_title' => '+Historical title',
            'source_category_slug' => '-beans',
            'message' => '@message-formula',
            'assigned_to' => $admin->id,
        ]);
        $lead->notes()->create(['user_id' => $admin->id, 'body' => "\r=note-formula"]);
        $this->actingAs($admin);

        $rows = $this->readCsv(app(ExportLeadsCsv::class)->handle($admin, [], true));
        foreach ([2, 3, 4, 5, 6, 7, 8, 9, 11, 14, 15] as $index) {
            $this->assertStringStartsWith("'", $rows[1][$index], "CSV column {$index} was not neutralized");
        }
        $this->assertSame("' \t-2", $rows[1][5]);
        $this->assertSame("'\r=note-formula", $rows[1][15]);
    }

    public function test_streaming_defers_lead_loading_and_eager_loads_each_batch(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $support = User::factory()->support()->create();
        $timestamp = now();
        DB::table('leads')->insert(collect(range(1, 101))->map(fn (int $index): array => [
            'name' => "Lead {$index}",
            'email' => "lead{$index}@gmail.com",
            'message' => 'Batched export enquiry',
            'status' => 'new',
            'assigned_to' => $support->id,
            'last_activity_at' => $timestamp,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->all());
        foreach (Lead::query()->limit(3)->get() as $index => $lead) {
            $lead->notes()->create(['user_id' => $admin->id, 'body' => "Note {$index}"]);
        }
        $this->actingAs($admin);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = app(ExportLeadsCsv::class)->handle($admin, [], true);
        $beforeStreaming = DB::getQueryLog();
        $this->csvContents($response);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $leadQueriesBefore = array_values(array_filter($beforeStreaming, fn (array $query): bool => str_contains($query['query'], 'from `leads`')));
        $this->assertCount(1, $leadQueriesBefore, 'Only the export count may query leads before streaming.');
        $this->assertStringContainsString('count(*)', $leadQueriesBefore[0]['query']);

        $selects = array_values(array_filter($queries, fn (array $query): bool => str_starts_with(strtolower($query['query']), 'select')));
        $leadBatchQueries = array_filter($selects, fn (array $query): bool => str_contains($query['query'], 'from `leads`') && str_contains($query['query'], 'limit'));
        $this->assertCount(2, $leadBatchQueries, 'Rows must be fetched in bounded batches during streaming.');
        $this->assertCount(2, array_filter($selects, fn (array $query): bool => str_contains($query['query'], 'from `users`') && str_contains($query['query'], ' where `users`.`id` in')));
        $this->assertCount(2, array_filter($selects, fn (array $query): bool => str_contains($query['query'], 'from `lead_notes`') && str_contains($query['query'], ' in (')));
    }

    public function test_table_export_carries_current_filters_and_uses_the_scoped_action(): void
    {
        $support = User::factory()->support()->create();
        $this->createLead(['status' => 'new']);
        $this->createLead(['status' => 'archived']);

        Livewire::actingAs($support)
            ->test(ListLeads::class)
            ->filterTable('status', 'new')
            ->callTableAction('export', data: ['include_notes' => false])
            ->assertHasNoTableActionErrors();

        $log = ActivityLog::where('action', 'lead.exported')->sole();
        $this->assertSame(1, $log->properties['count']);
        $this->assertSame(['status'], $log->properties['filter_keys']);
    }

    private function csvContents(StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return ob_get_clean();
    }

    private function readCsv(StreamedResponse $response): array
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $this->csvContents($response));
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream, escape: '')) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }

    private function createLead(array $overrides = []): Lead
    {
        return Lead::create(array_merge([
            'name' => 'Test Lead',
            'email' => fake()->unique()->safeEmail(),
            'message' => 'Product enquiry',
            'status' => 'new',
            'assigned_to' => null,
            'source_slug' => null,
            'last_activity_at' => now(),
        ], $overrides));
    }
}
