<?php

namespace App\Services\Checking;

use App\Services\Checking\Rules\NoDirectEloquentInControllerRule;

final class PhpArchitectureChecker
{
    /** @var array<string, NoDirectEloquentInControllerRule> */
    private array $rules;

    public function __construct(?NoDirectEloquentInControllerRule $eloquentInController = null)
    {
        $rule = $eloquentInController ?? new NoDirectEloquentInControllerRule;
        $this->rules = [
            NoDirectEloquentInControllerRule::RULE_KEY => $rule,
        ];
    }

    /**
     * @param  list<CriterionInput>  $criteria
     * @return list<CheckEvaluation>
     */
    public function evaluate(string $phpCode, array $criteria): array
    {
        $evaluations = [];

        foreach ($criteria as $criterion) {
            $rule = $this->rules[$criterion->ruleKey] ?? null;
            if ($rule === null) {
                $evaluations[] = new CheckEvaluation(
                    $criterion->ruleKey,
                    [
                        new CheckFinding(
                            CheckFinding::STATUS_INSUFFICIENT_EVIDENCE,
                            sprintf(
                                'Rule key "%s" is not supported by this proof-of-concept checker. No confirmed violation was recorded.',
                                $criterion->ruleKey,
                            ),
                            ruleKey: $criterion->ruleKey,
                            unsupportedRule: true,
                        ),
                    ],
                    sprintf('Unsupported rule key: %s', $criterion->ruleKey),
                    unsupportedRule: true,
                );

                continue;
            }

            $evaluations[] = $rule->evaluate($phpCode, $criterion);
        }

        return $evaluations;
    }

    /**
     * @return list<string>
     */
    public function supportedRuleKeys(): array
    {
        return array_keys($this->rules);
    }
}
