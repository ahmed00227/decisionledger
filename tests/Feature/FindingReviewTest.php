<?php

namespace Tests\Feature;

use App\Models\CheckRun;
use App\Models\Decision;
use App\Models\Finding;
use App\Services\Checking\RunPhpCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FindingReviewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function review_updates_workflow_without_changing_checker_status_or_evidence(): void
    {
        $this->seed();
        $decision = Decision::query()->first();
        $run = app(RunPhpCheck::class)->handle(
            file_get_contents(database_path('fixtures/php/violating_controller.php')),
            CheckRun::SCOPE_SINGLE,
            $decision,
        );

        /** @var Finding $finding */
        $finding = $run->findings()->where('checker_status', 'confirmed')->firstOrFail();
        $originalStatus = $finding->checker_status;
        $originalSnippet = $finding->evidence_snippet;
        $originalStart = $finding->evidence_start_line;

        $this->post(route('findings.review', $finding), [
            'workflow_status' => 'resolved',
            'verification_note' => 'Moved query into OrderService; verified manually in editor.',
        ])->assertRedirect();

        $finding->refresh();
        $this->assertSame($originalStatus, $finding->checker_status);
        $this->assertSame($originalSnippet, $finding->evidence_snippet);
        $this->assertSame($originalStart, $finding->evidence_start_line);
        $this->assertSame('resolved', $finding->workflow_status);
        $this->assertStringContainsString('OrderService', $finding->verification_note);
        $this->assertNotNull($finding->reviewed_at);
    }
}
