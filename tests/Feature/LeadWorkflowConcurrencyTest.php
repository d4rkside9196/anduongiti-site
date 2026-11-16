<?php

namespace Tests\Feature;

use App\Actions\Leads\AddLeadNote;
use App\Actions\Leads\ChangeLeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LeadWorkflowConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction()
    {
        // Fixtures must be committed so a second PHP process can see them.
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanWorkflowTables();
        config()->set('database.connections.mysql_lead_race', config('database.connections.mysql'));
    }

    protected function tearDown(): void
    {
        DB::purge('mysql_lead_race');
        $this->cleanWorkflowTables();
        parent::tearDown();
    }

    #[DataProvider('workflowActions')]
    public function test_reassignment_while_waiting_for_row_lock_denies_the_stale_request(string $operation): void
    {
        $support = User::factory()->support()->create();
        $otherSupport = User::factory()->support()->create();
        $lead = Lead::create([
            'name' => 'Race lead',
            'email' => 'race@gmail.com',
            'message' => 'Concurrent workflow enquiry',
            'status' => 'new',
            'last_activity_at' => now(),
        ]);
        $blocker = DB::connection('mysql_lead_race');
        $blocker->beginTransaction();
        $blocker->table('leads')->where('id', $lead->id)->lockForUpdate()->first();

        $pid = pcntl_fork();
        $this->assertNotSame(-1, $pid, 'Unable to fork workflow race process.');

        if ($pid === 0) {
            DB::purge();

            try {
                if ($operation === 'note') {
                    app(AddLeadNote::class)->handle($lead, 'Must not be appended', $support);
                } else {
                    app(ChangeLeadStatus::class)->handle($lead, 'replied', $support);
                }

                exit(0);
            } catch (AuthorizationException) {
                exit(23);
            } catch (\Throwable $exception) {
                fwrite(STDERR, $exception::class.': '.$exception->getMessage());
                exit(1);
            }
        }

        sleep(1);
        $blocker->table('leads')->where('id', $lead->id)->update(['assigned_to' => $otherSupport->id]);
        $blocker->commit();
        pcntl_waitpid($pid, $status);

        $this->assertSame(23, pcntl_wexitstatus($status), 'The stale request mutated a reassigned lead.');
        $fresh = $lead->fresh();
        $this->assertSame($otherSupport->id, $fresh->assigned_to);
        $this->assertSame('new', $fresh->status);
        $this->assertSame(0, $fresh->notes()->count());
    }

    public static function workflowActions(): array
    {
        return ['add note' => ['note'], 'change status' => ['status']];
    }

    private function cleanWorkflowTables(): void
    {
        DB::table('activity_logs')->delete();
        DB::table('lead_notes')->delete();
        DB::table('leads')->delete();
        DB::table('users')->delete();
    }
}
