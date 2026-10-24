<?php

namespace App\Services\Checking;

final class CriterionInput
{
    public function __construct(
        public readonly string $ruleKey,
        public readonly string $summary = '',
        public readonly mixed $id = null,
    ) {}
}
