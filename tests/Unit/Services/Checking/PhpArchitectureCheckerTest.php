<?php

namespace Tests\Unit\Services\Checking;

use App\Services\Checking\CheckFinding;
use App\Services\Checking\CriterionInput;
use App\Services\Checking\PhpArchitectureChecker;
use App\Services\Checking\Rules\NoDirectEloquentInControllerRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhpArchitectureCheckerTest extends TestCase
{
    private PhpArchitectureChecker $checker;

    private string $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new PhpArchitectureChecker;
        $this->fixtures = database_path('fixtures/php');
    }

    private function criterion(string $ruleKey = NoDirectEloquentInControllerRule::RULE_KEY): CriterionInput
    {
        return new CriterionInput($ruleKey, 'Must not use direct Eloquent/DB queries in controllers.');
    }

    private function load(string $file): string
    {
        return file_get_contents($this->fixtures.'/'.$file);
    }

    private function evaluate(string $code, string $ruleKey = NoDirectEloquentInControllerRule::RULE_KEY)
    {
        return $this->checker->evaluate($code, [$this->criterion($ruleKey)])[0];
    }

    private function confirmedFindings($evaluation): array
    {
        return array_values(array_filter(
            $evaluation->findings,
            fn (CheckFinding $f) => $f->checkerStatus === CheckFinding::STATUS_CONFIRMED,
        ));
    }

    #[Test]
    public function direct_eloquent_in_controller_is_confirmed_with_accurate_evidence_lines(): void
    {
        $code = $this->load('violating_controller.php');
        $evaluation = $this->evaluate($code);
        $confirmed = $this->confirmedFindings($evaluation);

        $this->assertNotEmpty($confirmed);
        // Fixture: `return Order::where('id', $id)->first();` is on line 11
        $this->assertSame(11, $confirmed[0]->evidenceStartLine);
        $this->assertSame(11, $confirmed[0]->evidenceEndLine);
        $this->assertStringContainsString('Order::where', (string) $confirmed[0]->evidenceSnippet);
        $this->assertStringContainsString('pasted text only', $confirmed[0]->explanation);
    }

    #[Test]
    public function compliant_service_delegation_has_no_confirmed_violation(): void
    {
        $evaluation = $this->evaluate($this->load('compliant_controller.php'));

        $this->assertFalse($evaluation->hasConfirmedViolation());
        $this->assertSame([], $this->confirmedFindings($evaluation));
        $this->assertStringContainsString('No supported direct', $evaluation->summary);
        $this->assertStringContainsString('does not imply', strtolower($evaluation->summary));
    }

    #[Test]
    public function query_like_text_in_comments_and_strings_is_not_confirmed(): void
    {
        $evaluation = $this->evaluate($this->load('comments_and_strings.php'));

        $this->assertFalse($evaluation->hasConfirmedViolation(), 'Comments/strings must never produce confirmed');
        foreach ($evaluation->findings as $finding) {
            $this->assertNotSame(CheckFinding::STATUS_CONFIRMED, $finding->checkerStatus);
        }
    }

    #[Test]
    public function eloquent_in_non_controller_class_is_not_confirmed(): void
    {
        $evaluation = $this->evaluate($this->load('non_controller_eloquent.php'));

        $this->assertFalse($evaluation->hasConfirmedViolation());
        $this->assertSame([], $this->confirmedFindings($evaluation));
        $this->assertStringContainsString('No *Controller class found', $evaluation->summary);
    }

    #[Test]
    public function ambiguous_repository_call_is_never_confirmed(): void
    {
        $evaluation = $this->evaluate($this->load('ambiguous_repository.php'));

        $this->assertFalse(
            $evaluation->hasConfirmedViolation(),
            'RELEASE BLOCKER: ambiguous repository calls must never be confirmed',
        );

        $needsReview = array_values(array_filter(
            $evaluation->findings,
            fn (CheckFinding $f) => $f->checkerStatus === CheckFinding::STATUS_NEEDS_REVIEW,
        ));
        $this->assertNotEmpty($needsReview);
        $this->assertNotNull($needsReview[0]->evidenceStartLine);
        $this->assertStringContainsString('repository->save', (string) $needsReview[0]->evidenceSnippet);
    }

    #[Test]
    public function unknown_rule_key_is_visibly_unsupported_not_confirmed_or_silent(): void
    {
        $evaluation = $this->evaluate($this->load('violating_controller.php'), 'totally_unknown_rule');

        $this->assertTrue($evaluation->unsupportedRule);
        $this->assertFalse($evaluation->hasConfirmedViolation());
        $this->assertNotEmpty($evaluation->findings);
        $this->assertSame(CheckFinding::STATUS_INSUFFICIENT_EVIDENCE, $evaluation->findings[0]->checkerStatus);
        $this->assertTrue($evaluation->findings[0]->unsupportedRule);
        $this->assertStringContainsString('not supported', $evaluation->findings[0]->explanation);
        $this->assertStringContainsString('Unsupported rule key', $evaluation->summary);
    }

    #[Test]
    #[DataProvider('documentedDbFacadeVariants')]
    public function documented_db_facade_variants_in_controller_are_confirmed(string $code, string $expectedSnippetPart, int $expectedLine): void
    {
        $evaluation = $this->evaluate($code);
        $confirmed = $this->confirmedFindings($evaluation);

        $this->assertTrue($evaluation->hasConfirmedViolation(), $code);
        $this->assertNotEmpty($confirmed);
        $this->assertSame($expectedLine, $confirmed[0]->evidenceStartLine);
        $this->assertStringContainsString($expectedSnippetPart, (string) $confirmed[0]->evidenceSnippet);
    }

    public static function documentedDbFacadeVariants(): array
    {
        return [
            'DB::table' => [
                <<<'PHP'
<?php
class ReportController
{
    public function index()
    {
        return DB::table('orders')->get();
    }
}
PHP,
                'DB::table',
                6,
            ],
            'backslash DB::select' => [
                <<<'PHP'
<?php
class ReportController
{
    public function index()
    {
        return \DB::select('select 1');
    }
}
PHP,
                'DB::select',
                6,
            ],
            'FQCN Illuminate Support Facades DB::where' => [
                <<<'PHP'
<?php
class ReportController
{
    public function index()
    {
        return Illuminate\Support\Facades\DB::where('id', 1)->get();
    }
}
PHP,
                'Illuminate\\Support\\Facades\\DB::where',
                6,
            ],
            'leading-backslash FQCN' => [
                <<<'PHP'
<?php
class ReportController
{
    public function index()
    {
        return \Illuminate\Support\Facades\DB::table('orders')->count();
    }
}
PHP,
                'Illuminate\\Support\\Facades\\DB::table',
                6,
            ],
        ];
    }

    #[Test]
    public function incomplete_php_fixture_is_insufficient_evidence_not_confirmed(): void
    {
        $evaluation = $this->evaluate($this->load('incomplete_controller.php'));

        $this->assertFalse($evaluation->hasConfirmedViolation());
        $this->assertNotEmpty($evaluation->findings);
        $this->assertSame(CheckFinding::STATUS_INSUFFICIENT_EVIDENCE, $evaluation->findings[0]->checkerStatus);
    }

    #[Test]
    public function natural_language_decision_text_alone_is_not_enforced_via_unknown_rule(): void
    {
        // Simulates a criterion whose human summary sounds enforceable but rule_key is unimplemented.
        $evaluation = $this->evaluate(
            $this->load('violating_controller.php'),
            'controllers_must_remain_thin_nl_only',
        );

        $this->assertTrue($evaluation->unsupportedRule);
        $this->assertFalse($evaluation->hasConfirmedViolation());
        $this->assertStringContainsString('not supported', $evaluation->findings[0]->explanation);
    }

    #[Test]
    public function model_find_or_fail_in_controller_is_confirmed(): void
    {
        $code = <<<'PHP'
<?php
class OrderController
{
    public function show(int $id)
    {
        return Order::findOrFail($id);
    }
}
PHP;
        $evaluation = $this->evaluate($code);
        $confirmed = $this->confirmedFindings($evaluation);

        $this->assertTrue($evaluation->hasConfirmedViolation());
        $this->assertSame(6, $confirmed[0]->evidenceStartLine);
        $this->assertStringContainsString('Order::findOrFail', (string) $confirmed[0]->evidenceSnippet);
    }
}
