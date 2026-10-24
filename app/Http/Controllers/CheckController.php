<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewFindingRequest;
use App\Http\Requests\RunCheckRequest;
use App\Models\CheckRun;
use App\Models\Decision;
use App\Models\Finding;
use App\Services\Checking\RunPhpCheck;
use App\Services\Checking\Rules\NoDirectEloquentInControllerRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckController extends Controller
{
    public function show(Request $request): View
    {
        $decisions = Decision::query()->where('status', 'active')->orderBy('title')->get();
        $selectedId = $request->query('decision');
        $selectedDecision = $selectedId
            ? Decision::query()->with('criteria')->find($selectedId)
            : null;

        $checkRun = null;
        if ($request->session()->has('last_check_run_id')) {
            $checkRun = CheckRun::query()
                ->with(['findings.decision', 'findings.criterion'])
                ->find($request->session()->get('last_check_run_id'));
        }

        return view('checks.show', [
            'decisions' => $decisions,
            'selectedDecision' => $selectedDecision,
            'checkRun' => $checkRun,
            'phpCode' => old('php_code', $checkRun?->php_code ?? ''),
            'contextNotes' => old('context_notes', $checkRun?->context_notes ?? ''),
            'decisionScope' => old('decision_scope', $selectedDecision ? CheckRun::SCOPE_SINGLE : CheckRun::SCOPE_ALL_COMPATIBLE),
            'supportedRuleKey' => NoDirectEloquentInControllerRule::RULE_KEY,
            'demoSamples' => $this->demoSamples(),
        ]);
    }

    /**
     * @return list<array{id:string,label:string,expect:string,code:string}>
     */
    private function demoSamples(): array
    {
        $dir = database_path('fixtures/php');

        return [
            [
                'id' => 'violating',
                'label' => 'Violating',
                'expect' => 'Expect: confirmed + evidence lines',
                'code' => (string) file_get_contents($dir.'/violating_controller.php'),
            ],
            [
                'id' => 'compliant',
                'label' => 'Compliant',
                'expect' => 'Expect: clean summary (no confirmed)',
                'code' => (string) file_get_contents($dir.'/compliant_controller.php'),
            ],
            [
                'id' => 'ambiguous',
                'label' => 'Ambiguous',
                'expect' => 'Expect: needs review (never confirmed)',
                'code' => (string) file_get_contents($dir.'/ambiguous_repository.php'),
            ],
            [
                'id' => 'incomplete',
                'label' => 'Incomplete',
                'expect' => 'Expect: insufficient evidence',
                'code' => (string) file_get_contents($dir.'/incomplete_controller.php'),
            ],
        ];
    }

    public function store(RunCheckRequest $request, RunPhpCheck $runner): RedirectResponse
    {
        $data = $request->validated();
        $decision = null;

        if ($data['decision_scope'] === CheckRun::SCOPE_SINGLE) {
            $decision = Decision::query()->with('criteria')->findOrFail($data['decision_id']);
        }

        $run = $runner->handle(
            $data['php_code'],
            $data['decision_scope'],
            $decision,
            $data['context_notes'] ?? null,
        );

        return redirect()
            ->route('checks.show', array_filter([
                'decision' => $decision?->id,
            ]))
            ->with('last_check_run_id', $run->id)
            ->with('status', 'Check completed. Analyzed pasted text only.');
    }

    public function review(ReviewFindingRequest $request, Finding $finding): RedirectResponse
    {
        $data = $request->validated();
        $originalStatus = $finding->checker_status;

        $finding->forceFill([
            'workflow_status' => $data['workflow_status'],
            'verification_note' => $data['verification_note'],
            'reviewed_at' => now(),
        ])->save();

        // Guardrail: never allow mutating checker_status via mass assignment / accidental overwrite
        $finding->refresh();
        abort_unless($finding->checker_status === $originalStatus, 500, 'Checker status must remain immutable.');

        return redirect()
            ->route('checks.show')
            ->with('last_check_run_id', $finding->check_run_id)
            ->with('status', 'Review saved. Original checker result and evidence were preserved — this does not mean the app re-verified a fix.');
    }
}
