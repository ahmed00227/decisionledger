<?php

namespace App\Services\Checking\Rules;

use App\Services\Checking\CheckEvaluation;
use App\Services\Checking\CheckFinding;
use App\Services\Checking\CriterionInput;

/**
 * Detects direct Eloquent / DB facade query calls inside *Controller classes.
 *
 * Does NOT prove absence of business logic, indirect DB access, or repository-wide compliance.
 * Ignores comments and string literals. Ambiguous patterns yield needs_review, never confirmed.
 */
final class NoDirectEloquentInControllerRule
{
    public const RULE_KEY = 'no_direct_eloquent_in_controller';

    /** @var list<string> */
    private const QUERY_METHODS = [
        'query',
        'table',
        'select',
        'where',
        'whereIn',
        'find',
        'findOrFail',
        'first',
        'firstOrFail',
        'get',
        'all',
        'create',
        'update',
        'delete',
        'destroy',
        'paginate',
        'cursor',
        'value',
        'pluck',
    ];

    /** @var list<string> */
    private const DB_FACADE_NAMES = [
        'DB',
        'Illuminate\\Support\\Facades\\DB',
    ];

    public function evaluate(string $phpCode, CriterionInput $criterion): CheckEvaluation
    {
        $tokens = @token_get_all($phpCode);
        if ($tokens === false || $tokens === []) {
            return new CheckEvaluation(
                self::RULE_KEY,
                [
                    new CheckFinding(
                        CheckFinding::STATUS_INSUFFICIENT_EVIDENCE,
                        'Could not tokenize the submitted PHP; analysis stopped without a confirmed violation.',
                        ruleKey: self::RULE_KEY,
                    ),
                ],
                'Insufficient evidence: PHP could not be tokenized.',
            );
        }

        $scan = $this->analyzeTokens($tokens);

        if ($scan['incomplete_structure']) {
            return new CheckEvaluation(
                self::RULE_KEY,
                [
                    new CheckFinding(
                        CheckFinding::STATUS_INSUFFICIENT_EVIDENCE,
                        'The submitted PHP is incomplete or unbalanced, so controller and method boundaries could not be established reliably. No confirmed violation was recorded.',
                        ruleKey: self::RULE_KEY,
                    ),
                ],
                'Insufficient evidence: incomplete PHP structure.',
            );
        }

        $findings = [];

        foreach ($scan['confirmed'] as $hit) {
            $findings[] = new CheckFinding(
                CheckFinding::STATUS_CONFIRMED,
                sprintf(
                    'Direct %s call inside controller method body matches the supported rule (must not use direct Eloquent/DB queries in controllers). This is a rule match on pasted text only — not a whole-architecture judgment.',
                    $hit['call'],
                ),
                $hit['start_line'],
                $hit['end_line'],
                $hit['snippet'],
                self::RULE_KEY,
            );
        }

        foreach ($scan['needs_review'] as $hit) {
            $findings[] = new CheckFinding(
                CheckFinding::STATUS_NEEDS_REVIEW,
                sprintf(
                    'Possible database or persistence access via an unsupported pattern (%s). The checker cannot confirm a direct Eloquent/DB static query; human review is required.',
                    $hit['call'],
                ),
                $hit['start_line'],
                $hit['end_line'],
                $hit['snippet'],
                self::RULE_KEY,
            );
        }

        if ($findings === []) {
            if ($scan['controller_count'] === 0) {
                $summary = 'No *Controller class found in the pasted text. No supported violation detected for this rule (Eloquent outside controllers is out of scope). This does not imply repository-wide compliance.';
            } else {
                $summary = 'No supported direct Eloquent/DB query violation detected in controller method bodies. This does not imply the controller contains no business logic or that the whole repository complies.';
            }

            return new CheckEvaluation(self::RULE_KEY, [], $summary);
        }

        $summary = sprintf(
            'Found %d confirmed match(es) and %d needs-review item(s) for rule %s.',
            count($scan['confirmed']),
            count($scan['needs_review']),
            self::RULE_KEY,
        );

        return new CheckEvaluation(self::RULE_KEY, $findings, $summary);
    }

    /**
     * @param  array<int, string|array{0:int,1:string,2:int}>  $tokens
     * @return array{
     *     confirmed: list<array{call:string,start_line:int,end_line:int,snippet:string}>,
     *     needs_review: list<array{call:string,start_line:int,end_line:int,snippet:string}>,
     *     controller_count: int,
     *     incomplete_structure: bool
     * }
     */
    private function analyzeTokens(array $tokens): array
    {
        $confirmed = [];
        $needsReview = [];
        $controllerCount = 0;

        $braceDepth = 0;
        $controllerDepth = null;
        $methodDepth = null;
        $expectClassName = false;
        $inControllerClass = false;
        $pendingController = false;

        $i = 0;
        $count = count($tokens);

        while ($i < $count) {
            $token = $tokens[$i];

            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_INLINE_HTML], true)) {
                $i++;

                continue;
            }

            if (is_array($token) && in_array($token[0], [T_WHITESPACE], true)) {
                $i++;

                continue;
            }

            // Skip string literals entirely (including interpolated parts handled as separate tokens inside "...")
            if (is_array($token) && in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_START_HEREDOC, T_END_HEREDOC], true)) {
                $i++;

                continue;
            }

            if ($token === '"') {
                $i = $this->skipDoubleQuotedString($tokens, $i);
                continue;
            }

            if (is_array($token) && $token[0] === T_CLASS) {
                $expectClassName = true;
                $pendingController = false;
                $i++;

                continue;
            }

            if ($expectClassName && is_array($token) && $token[0] === T_STRING) {
                $className = $token[1];
                $pendingController = str_ends_with($className, 'Controller');
                $expectClassName = false;
                $i++;

                continue;
            }

            if ($expectClassName) {
                // anonymous class or unexpected — clear
                if ($token !== '{' && !(is_array($token) && in_array($token[0], [T_EXTENDS, T_IMPLEMENTS, T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true))) {
                    $expectClassName = false;
                }
            }

            if ($token === '{') {
                $braceDepth++;
                if ($pendingController && $controllerDepth === null) {
                    $controllerDepth = $braceDepth;
                    $inControllerClass = true;
                    $controllerCount++;
                    $pendingController = false;
                } elseif ($inControllerClass && $methodDepth === null && $braceDepth === $controllerDepth + 1) {
                    // entering a method (or other nested block at class+1 — also catches property hooks rarely; acceptable for PoC)
                    $methodDepth = $braceDepth;
                }
                $i++;

                continue;
            }

            if ($token === '}') {
                if ($methodDepth !== null && $braceDepth === $methodDepth) {
                    $methodDepth = null;
                }
                if ($controllerDepth !== null && $braceDepth === $controllerDepth) {
                    $controllerDepth = null;
                    $inControllerClass = false;
                    $methodDepth = null;
                }
                $braceDepth--;
                $i++;

                continue;
            }

            $insideControllerMethod = $inControllerClass && $methodDepth !== null && $braceDepth >= $methodDepth;

            if ($insideControllerMethod) {
                $staticCall = $this->matchStaticQueryCall($tokens, $i);
                if ($staticCall !== null) {
                    $confirmed[] = $staticCall;
                    $i = $staticCall['next_index'];

                    continue;
                }

                $ambiguous = $this->matchAmbiguousPersistenceCall($tokens, $i);
                if ($ambiguous !== null) {
                    $needsReview[] = $ambiguous;
                    $i = $ambiguous['next_index'];

                    continue;
                }
            } else {
                // Still skip over static calls outside controller methods so we don't mis-attribute,
                // but do not record confirmed hits for non-controllers.
                $staticCall = $this->matchStaticQueryCall($tokens, $i);
                if ($staticCall !== null) {
                    $i = $staticCall['next_index'];

                    continue;
                }
            }

            $i++;
        }

        $incomplete = $braceDepth !== 0;

        return [
            'confirmed' => $confirmed,
            'needs_review' => $needsReview,
            'controller_count' => $controllerCount,
            'incomplete_structure' => $incomplete,
        ];
    }

    /**
     * @param  array<int, string|array{0:int,1:string,2:int}>  $tokens
     * @return array{call:string,start_line:int,end_line:int,snippet:string,next_index:int}|null
     */
    private function matchStaticQueryCall(array $tokens, int $i): ?array
    {
        $nameParts = $this->readName($tokens, $i);
        if ($nameParts === null) {
            return null;
        }

        [$fqcn, $afterName] = $nameParts;
        $j = $this->skipNoise($tokens, $afterName);

        if (! isset($tokens[$j]) || ! is_array($tokens[$j]) || $tokens[$j][0] !== T_DOUBLE_COLON) {
            return null;
        }

        $j = $this->skipNoise($tokens, $j + 1);
        if (! isset($tokens[$j]) || ! is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING) {
            return null;
        }

        $method = $tokens[$j][1];
        if (! in_array($method, self::QUERY_METHODS, true)) {
            return null;
        }

        $normalized = ltrim($fqcn, '\\');
        $isDbFacade = in_array($normalized, self::DB_FACADE_NAMES, true);
        $isPascalModel = (bool) preg_match('/^[A-Z][A-Za-z0-9_\\\\]*$/', $normalized);

        if (! $isDbFacade && ! $isPascalModel) {
            return null;
        }

        // Prefer not confirming lowercase helpers; DB is allowlisted explicitly.
        if (! $isDbFacade && str_contains($normalized, '\\') === false && ! preg_match('/^[A-Z]/', $normalized)) {
            return null;
        }

        $startLine = is_array($tokens[$i]) ? $tokens[$i][2] : $this->lineAt($tokens, $i);
        $endLine = is_array($tokens[$j]) ? $tokens[$j][2] : $startLine;
        $call = $fqcn.'::'.$method;
        $snippet = $call.'(...)';

        return [
            'call' => $call,
            'start_line' => $startLine,
            'end_line' => $endLine,
            'snippet' => $snippet,
            'next_index' => $j + 1,
        ];
    }

    /**
     * Ambiguous persistence-like chains inside controllers (never confirmed).
     * Targets repository-style collaborators or write APIs — not ordinary service delegation
     * such as $this->orders->find() (the compliant fixture).
     *
     * @param  array<int, string|array{0:int,1:string,2:int}>  $tokens
     * @return array{call:string,start_line:int,end_line:int,snippet:string,next_index:int}|null
     */
    private function matchAmbiguousPersistenceCall(array $tokens, int $i): ?array
    {
        if (! is_array($tokens[$i]) || $tokens[$i][0] !== T_VARIABLE || $tokens[$i][1] !== '$this') {
            return null;
        }

        $startLine = $tokens[$i][2];
        $j = $this->skipNoise($tokens, $i + 1);
        $parts = ['$this'];

        $arrowCount = 0;
        while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_OBJECT_OPERATOR) {
            $j = $this->skipNoise($tokens, $j + 1);
            if (! isset($tokens[$j]) || ! is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING) {
                return null;
            }
            $parts[] = $tokens[$j][1];
            $arrowCount++;
            $j = $this->skipNoise($tokens, $j + 1);
        }

        if ($arrowCount < 2) {
            return null;
        }

        $collaborator = $parts[1];
        $method = $parts[array_key_last($parts)];

        $repositoryish = (bool) preg_match(
            '/^(repository|repo|db|database|queryBuilder|eloquent|model)$/i',
            $collaborator,
        );

        $writeApis = [
            'save', 'updateOrCreate', 'firstOrCreate', 'updateOrInsert', 'insert', 'upsert', 'execute',
        ];

        if (! $repositoryish && ! in_array($method, $writeApis, true)) {
            return null;
        }

        $call = implode('->', $parts);
        $endLine = $startLine;
        for ($k = $i; $k < $j; $k++) {
            if (is_array($tokens[$k])) {
                $endLine = $tokens[$k][2];
            }
        }

        return [
            'call' => $call,
            'start_line' => $startLine,
            'end_line' => $endLine,
            'snippet' => $call.'(...)',
            'next_index' => $j,
        ];
    }

    /**
     * @param  array<int, string|array{0:int,1:string,2:int}>  $tokens
     * @return array{0:string,1:int}|null
     */
    private function readName(array $tokens, int $i): ?array
    {
        if (! isset($tokens[$i])) {
            return null;
        }

        $parts = [];
        $j = $i;

        if ($tokens[$j] === '\\' || (is_array($tokens[$j]) && $tokens[$j][0] === T_NS_SEPARATOR)) {
            $parts[] = '\\';
            $j++;
        }

        while (isset($tokens[$j])) {
            $t = $tokens[$j];
            if (is_array($t) && $t[0] === T_NAME_FULLY_QUALIFIED) {
                return [$t[1], $j + 1];
            }
            if (is_array($t) && $t[0] === T_NAME_QUALIFIED) {
                $parts[] = $t[1];
                $j++;
                break;
            }
            if (is_array($t) && $t[0] === T_STRING) {
                $parts[] = $t[1];
                $j++;
                if (isset($tokens[$j]) && (is_array($tokens[$j]) && $tokens[$j][0] === T_NS_SEPARATOR || $tokens[$j] === '\\')) {
                    $parts[] = '\\';
                    $j++;

                    continue;
                }
                break;
            }
            break;
        }

        if ($parts === [] || $parts === ['\\']) {
            return null;
        }

        return [implode('', $parts), $j];
    }

    /**
     * @param  array<int, string|array{0:int,1:string,2:int}>  $tokens
     */
    private function skipNoise(array $tokens, int $i): int
    {
        while (isset($tokens[$i])) {
            $t = $tokens[$i];
            if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $i++;

                continue;
            }
            break;
        }

        return $i;
    }

    /**
     * @param  array<int, string|array{0:int,1:string,2:int}>  $tokens
     */
    private function skipDoubleQuotedString(array $tokens, int $i): int
    {
        // $tokens[$i] is opening "
        $i++;
        while (isset($tokens[$i])) {
            if ($tokens[$i] === '"') {
                return $i + 1;
            }
            $i++;
        }

        return $i;
    }

    /**
     * @param  array<int, string|array{0:int,1:string,2:int}>  $tokens
     */
    private function lineAt(array $tokens, int $i): int
    {
        for ($k = $i; $k >= 0; $k--) {
            if (is_array($tokens[$k])) {
                return $tokens[$k][2];
            }
        }

        return 1;
    }
}
