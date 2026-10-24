<?php

namespace Tests\Unit\Services\Checking;

use App\Services\Checking\CheckFinding;
use App\Services\Checking\CriterionInput;
use App\Services\Checking\PhpArchitectureChecker;
use App\Services\Checking\Rules\NoDirectEloquentInControllerRule;
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

    #[Test]
    public function direct_eloquent_in_controller_is_confirmed_with_evidence_lines(): void
    {
        $code = $this->load('violating_controller.php');
        $evaluation = $this->checker->evaluate($code, [$this->criterion()])[0];

        $this->assertTrue($evaluation->hasConfirmedViolation());
        $confirmed = array_values(array_filter(
            $evaluation->findings,
            fn (CheckFinding $f) => $f->checkerStatus === CheckFinding::STATUS_CONFIRMED,
        ));
        $this->assertNotEmpty($confirmed);
        $this->assertNotNull($confirmed[0]->evidenceStartLine);
        $this->assertNotNull($confirmed[0]->evidenceEndLine);
        $this->assertStringContainsString('Order::where', (string) $confirmed[0]->evidenceSnippet);
        $this->assertGreaterThanOrEqual(1, $confirmed[0]->evidenceStartLine);
    }

    #[Test]
    public function compliant_controller_delegation_has_no_confirmed_violation(): void
    {
        $code = $this->load('compliant_controller.php');
        $evaluation = $this->checker->evaluate($code, [$this->criterion()])[0];

        $this->assertFalse($evaluation->hasConfirmedViolation());
        $this->assertSame([], array_filter(
            $evaluation->findings,
            fn (CheckFinding $f) => $f->checkerStatus === CheckFinding::STATUS_CONFIRMED,
        ));
        $this->assertStringContainsString('No supported direct', $evaluation->summary);
    }

    #[Test]
    public function query_like_text_in_comments_and_strings_is_not_confirmed(): void
    {
        $code = $this->load('comments_and_strings.php');
        $evaluation = $this->checker->evaluate($code, [$this->criterion()])[0];

        $this->assertFalse($evaluation->hasConfirmedViolation(), 'Comments/strings must never produce confirmed');
        foreach ($evaluation->findings as $finding) {
            $this->assertNotSame(CheckFinding::STATUS_CONFIRMED, $finding->checkerStatus);
        }
    }

    #[Test]
    public function eloquent_in_non_controller_class_is_not_confirmed(): void
    {
        $code = $this->load('non_controller_eloquent.php');
        $evaluation = $this->checker->evaluate($code, [$this->criterion()])[0];

        $this->assertFalse($evaluation->hasConfirmedViolation());
        $this->assertStringContainsString('No *Controller class found', $evaluation->summary);
    }

    #[Test]
    public function ambiguous_repository_call_is_never_confirmed(): void
    {
        $code = $this->load('ambiguous_repository.php');
        $evaluation = $this->checker->evaluate($code, [$this->criterion()])[0];

        $this->assertFalse(
            $evaluation->hasConfirmedViolation(),
            'RELEASE BLOCKER: ambiguous repository calls must never be confirmed',
        );

        $needsReview = array_values(array_filter(
            $evaluation->findings,
            fn (CheckFinding $f) => $f->checkerStatus === CheckFinding::STATUS_NEEDS_REVIEW,
        ));
        $this->assertNotEmpty($needsReview);
        $this->assertStringContainsString('repository->save', (string) $needsReview[0]->evidenceSnippet);
    }

    #[Test]
    public function unknown_rule_key_is_visibly_unsupported_not_confirmed(): void
    {
        $code = $this->load('violating_controller.php');
        $evaluation = $this->checker->evaluate($code, [
            $this->criterion('totally_unknown_rule'),
        ])[0];

        $this->assertTrue($evaluation->unsupportedRule);
        $this->assertFalse($evaluation->hasConfirmedViolation());
        $this->assertNotEmpty($evaluation->findings);
        $this->assertSame(CheckFinding::STATUS_INSUFFICIENT_EVIDENCE, $evaluation->findings[0]->checkerStatus);
        $this->assertTrue($evaluation->findings[0]->unsupportedRule);
        $this->assertStringContainsString('not supported', $evaluation->findings[0]->explanation);
    }

    #[Test]
    public function db_facade_in_controller_is_confirmed(): void
    {
        $code = <<<'PHP'
<?php
class ReportController
{
    public function index()
    {
        return DB::table('orders')->get();
    }
}
PHP;
        $evaluation = $this->checker->evaluate($code, [$this->criterion()])[0];
        $this->assertTrue($evaluation->hasConfirmedViolation());
        $this->assertStringContainsString('DB::table', (string) $evaluation->findings[0]->evidenceSnippet);
    }

    #[Test]
    public function incomplete_php_is_insufficient_evidence_not_confirmed(): void
    {
        $code = <<<'PHP'
<?php
class BrokenController {
    public function show() {
        return Order::where(
PHP;
        $evaluation = $this->checker->evaluate($code, [$this->criterion()])[0];
        $this->assertFalse($evaluation->hasConfirmedViolation());
        $this->assertSame(CheckFinding::STATUS_INSUFFICIENT_EVIDENCE, $evaluation->findings[0]->checkerStatus);
    }
}
