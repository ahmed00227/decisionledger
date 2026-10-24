<?php

namespace App\Services\Checking;

use App\Models\CheckRun;
use App\Models\Criterion;
use App\Models\Decision;
use App\Models\Finding;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class RunPhpCheck
{
    public function __construct(private PhpArchitectureChecker $checker) {}

    /**
     * @param  Collection<int, Criterion>|null  $criteria
     */
    public function handle(
        string $phpCode,
        string $decisionScope,
        ?Decision $decision = null,
        ?string $contextNotes = null,
        ?Collection $criteria = null,
    ): CheckRun {
        return DB::transaction(function () use ($phpCode, $decisionScope, $decision, $contextNotes, $criteria) {
            $criteria = ($criteria ?? $this->resolveCriteria($decisionScope, $decision))->values();

            $inputs = $criteria->map(
                fn (Criterion $c) => new CriterionInput($c->rule_key, $c->summary, $c->id),
            )->all();

            $evaluations = $this->checker->evaluate($phpCode, $inputs);

            $run = CheckRun::query()->create([
                'php_code' => $phpCode,
                'context_notes' => $contextNotes,
                'decision_scope' => $decisionScope,
                'decision_id' => $decision?->id,
                'summary' => $this->buildRunSummary($evaluations),
            ]);

            foreach ($evaluations as $index => $evaluation) {
                /** @var Criterion|null $criterion */
                $criterion = $criteria->get($index);

                if ($evaluation->findings === []) {
                    continue;
                }

                foreach ($evaluation->findings as $finding) {
                    Finding::query()->create([
                        'check_run_id' => $run->id,
                        'decision_id' => $criterion?->decision_id ?? $decision?->id,
                        'criterion_id' => $criterion?->id,
                        'checker_status' => $finding->checkerStatus,
                        'explanation' => $finding->explanation,
                        'evidence_start_line' => $finding->evidenceStartLine,
                        'evidence_end_line' => $finding->evidenceEndLine,
                        'evidence_snippet' => $finding->evidenceSnippet,
                        'unsupported_rule' => $finding->unsupportedRule || $evaluation->unsupportedRule,
                    ]);
                }
            }

            return $run->load(['findings.decision', 'findings.criterion']);
        });
    }

    /**
     * @return Collection<int, Criterion>
     */
    private function resolveCriteria(string $decisionScope, ?Decision $decision): Collection
    {
        if ($decisionScope === CheckRun::SCOPE_SINGLE) {
            if ($decision === null) {
                throw new \InvalidArgumentException('A decision is required for single-decision checks.');
            }

            return $decision->criteria()->get();
        }

        return Criterion::query()
            ->whereHas('decision', fn ($q) => $q->where('status', 'active'))
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<CheckEvaluation>  $evaluations
     */
    private function buildRunSummary(array $evaluations): string
    {
        $parts = [];
        foreach ($evaluations as $evaluation) {
            $parts[] = $evaluation->summary;
        }

        return implode("\n", $parts);
    }
}
