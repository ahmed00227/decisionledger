<?php

namespace Tests\Feature;

use App\Models\CheckRun;
use App\Models\Criterion;
use App\Models\Decision;
use App\Models\Finding;
use App\Services\Checking\CheckFinding;
use App\Services\Checking\RunPhpCheck;
use App\Services\Checking\Rules\NoDirectEloquentInControllerRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RunPhpCheckPersistenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function migrate_fresh_seed_creates_sample_decision_and_criterion(): void
    {
        $this->seed();

        $this->assertDatabaseCount('decisions', 1);
        $this->assertDatabaseCount('criteria', 1);
        $decision = Decision::query()->first();
        $this->assertSame('Architecture', $decision->category);
        $this->assertSame('active', $decision->status);
        $this->assertSame(
            NoDirectEloquentInControllerRule::RULE_KEY,
            $decision->criteria->first()->rule_key,
        );
    }

    #[Test]
    public function each_check_creates_a_new_run_without_overwriting_prior_findings(): void
    {
        $this->seed();
        $decision = Decision::query()->first();
        $service = app(RunPhpCheck::class);
        $violating = file_get_contents(database_path('fixtures/php/violating_controller.php'));
        $compliant = file_get_contents(database_path('fixtures/php/compliant_controller.php'));

        $first = $service->handle($violating, CheckRun::SCOPE_SINGLE, $decision);
        $second = $service->handle($compliant, CheckRun::SCOPE_SINGLE, $decision);

        $this->assertNotSame($first->id, $second->id);
        $this->assertDatabaseCount('check_runs', 2);
        $this->assertTrue($first->findings()->where('checker_status', CheckFinding::STATUS_CONFIRMED)->exists());
        $this->assertSame($violating, CheckRun::query()->find($first->id)->php_code);
        $this->assertSame($compliant, CheckRun::query()->find($second->id)->php_code);
        $this->assertTrue(
            Finding::query()->where('check_run_id', $first->id)->exists(),
            'Prior run findings must remain',
        );
    }

    #[Test]
    public function unknown_rule_key_is_persisted_as_visible_unsupported_finding(): void
    {
        $decision = Decision::query()->create([
            'title' => 'Custom',
            'statement' => 'Something unsupported',
            'rationale' => 'Test',
            'category' => 'Other',
            'status' => 'active',
            'evaluation_limitations' => 'n/a',
        ]);
        Criterion::query()->create([
            'decision_id' => $decision->id,
            'rule_key' => 'not_a_real_rule',
            'summary' => 'Unsupported rule for test',
            'sort_order' => 1,
        ]);

        $run = app(RunPhpCheck::class)->handle(
            file_get_contents(database_path('fixtures/php/violating_controller.php')),
            CheckRun::SCOPE_SINGLE,
            $decision->fresh('criteria'),
        );

        $finding = $run->findings->first();
        $this->assertNotNull($finding);
        $this->assertTrue($finding->unsupported_rule);
        $this->assertSame(CheckFinding::STATUS_INSUFFICIENT_EVIDENCE, $finding->checker_status);
        $this->assertStringContainsString('not supported', $finding->explanation);
        $this->assertNotSame(CheckFinding::STATUS_CONFIRMED, $finding->checker_status);
    }
}
