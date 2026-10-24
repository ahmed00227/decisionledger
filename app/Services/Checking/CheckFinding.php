<?php

namespace App\Services\Checking;

final class CheckFinding
{
    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_NEEDS_REVIEW = 'needs_review';

    public const STATUS_INSUFFICIENT_EVIDENCE = 'insufficient_evidence';

    public function __construct(
        public readonly string $checkerStatus,
        public readonly string $explanation,
        public readonly ?int $evidenceStartLine = null,
        public readonly ?int $evidenceEndLine = null,
        public readonly ?string $evidenceSnippet = null,
        public readonly ?string $ruleKey = null,
        public readonly bool $unsupportedRule = false,
    ) {}
}
