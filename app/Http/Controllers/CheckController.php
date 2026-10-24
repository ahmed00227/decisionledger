<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunCheckRequest;
use App\Models\CheckRun;
use App\Models\Decision;
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
        ]);
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
}
