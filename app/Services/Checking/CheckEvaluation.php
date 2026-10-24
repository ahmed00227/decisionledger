<?php

namespace App\Services\Checking;

/**
 * Result of evaluating one criterion against pasted PHP.
 * Clean (no supported violation) is represented by zero findings of status confirmed,
 * plus a summary line — not a new finding status.
 */
final class CheckEvaluation
{
    /**
     * @param  list<CheckFinding>  $findings
     */
    public function __construct(
        public readonly string $ruleKey,
        public readonly array $findings,
        public readonly string $summary,
        public readonly bool $unsupportedRule = false,
    ) {}

    public function hasConfirmedViolation(): bool
    {
        foreach ($this->findings as $finding) {
            if ($finding->checkerStatus === CheckFinding::STATUS_CONFIRMED) {
                return true;
            }
        }

        return false;
    }
}
