@extends('layouts.app')

@section('title', 'Check PHP · DecisionLedger')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Check PHP</h1>
        <p class="mt-1 text-sm text-slate-600">Paste agent-produced PHP. Optional notes are context only — never treated as evidence. Analysis covers pasted text only.</p>
    </div>

    <div class="grid gap-8 lg:grid-cols-2">
        <form method="post" action="{{ route('checks.store') }}" class="space-y-4 rounded-md border border-slate-200 bg-white p-5">
            @csrf

            <div>
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <label class="block text-sm font-medium text-slate-700" for="php_code">PHP code <span class="text-red-700">*</span></label>
                    <p class="text-xs text-slate-500">Demo samples (fill the editor — then Run check)</p>
                </div>
                <div class="mt-2 flex flex-wrap gap-2" id="demo-sample-buttons">
                    @foreach ($demoSamples as $sample)
                        <button type="button"
                                class="demo-sample rounded-md border border-slate-300 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-800 hover:bg-slate-100"
                                data-expect="{{ $sample['expect'] }}"
                                data-code="{{ base64_encode($sample['code']) }}">
                            {{ $sample['label'] }}
                        </button>
                    @endforeach
                </div>
                <p id="demo-sample-expect" class="mt-2 hidden text-xs text-slate-600"></p>
                <textarea id="php_code" name="php_code" rows="18" required
                          class="mt-2 w-full rounded-md border border-slate-300 bg-slate-950 p-3 font-mono text-sm text-slate-100"
                          placeholder="Paste a Laravel controller or related excerpt…">{{ $phpCode }}</textarea>
                @error('php_code')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700" for="decision_scope">Applicable decisions</label>
                <select id="decision_scope" name="decision_scope" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="single" @selected($decisionScope === 'single')>One saved decision</option>
                    <option value="all_compatible" @selected($decisionScope === 'all_compatible')>All compatible decisions</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700" for="decision_id">Decision</label>
                <select id="decision_id" name="decision_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Select —</option>
                    @foreach ($decisions as $decision)
                        <option value="{{ $decision->id }}" @selected((string) old('decision_id', $selectedDecision?->id) === (string) $decision->id)>
                            {{ $decision->title }}
                        </option>
                    @endforeach
                </select>
                @error('decision_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            @if ($selectedDecision)
                <div class="rounded-md border border-slate-200 bg-slate-50 p-3 text-sm">
                    <div class="font-medium text-slate-800">Criteria that will run (preview)</div>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-slate-700">
                        @forelse ($selectedDecision->criteria as $criterion)
                            <li><span class="font-mono text-xs">{{ $criterion->rule_key }}</span> — {{ \Illuminate\Support\Str::limit($criterion->summary, 100) }}</li>
                        @empty
                            <li>No criteria on this decision.</li>
                        @endforelse
                    </ul>
                </div>
            @else
                <p class="text-xs text-slate-500">Supported rule key in this PoC: <code class="font-mono">{{ $supportedRuleKey }}</code>. Unknown keys are reported as unsupported.</p>
            @endif

            <div>
                <label class="block text-sm font-medium text-slate-700" for="context_notes">Context notes (optional)</label>
                <textarea id="context_notes" name="context_notes" rows="3"
                          class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                          placeholder="What the code is intended to do — not used as compliance proof.">{{ $contextNotes }}</textarea>
            </div>

            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Run check</button>
            <a href="{{ route('checks.show', array_filter(['decision' => $selectedDecision?->id])) }}" class="ml-2 text-sm text-slate-600 underline">Clear / check another snippet</a>
        </form>

        <div class="space-y-4">
            @if ($checkRun)
                <section class="rounded-md border border-slate-200 bg-white p-5">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Check summary</h2>
                    <p class="mt-2 text-xs text-slate-500">Check run #{{ $checkRun->id }} · Analyzed pasted text only · Prior runs are kept</p>
                    <div class="mt-3 whitespace-pre-wrap rounded border border-slate-200 bg-slate-50 p-3 text-sm text-slate-800">{{ $checkRun->summary }}</div>

                    @php
                        $confirmedCount = $checkRun->findings->where('checker_status', 'confirmed')->count();
                    @endphp
                    @if ($confirmedCount === 0)
                        <p class="mt-3 rounded border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                            Clean for supported rules in this run: no confirmed violation detected. This does <strong>not</strong> imply whole-controller or repository compliance.
                        </p>
                    @endif
                </section>

                <section class="space-y-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Findings</h2>
                    @forelse ($checkRun->findings as $finding)
                        <article class="rounded-md border border-slate-200 bg-white p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded border px-2 py-0.5 text-xs status-{{ $finding->checker_status }}">{{ str_replace('_', ' ', $finding->checker_status) }}</span>
                                @if ($finding->unsupported_rule)
                                    <span class="rounded border border-slate-300 bg-slate-100 px-2 py-0.5 text-xs text-slate-700">unsupported rule</span>
                                @endif
                                @if ($finding->workflow_status)
                                    <span class="rounded border px-2 py-0.5 text-xs status-{{ $finding->workflow_status }}">{{ $finding->workflow_status }}</span>
                                @endif
                            </div>

                            @if ($finding->decision)
                                <p class="mt-2 text-sm">
                                    Decision:
                                    <a class="font-medium text-slate-900 underline" href="{{ route('decisions.show', $finding->decision) }}">{{ $finding->decision->title }}</a>
                                </p>
                            @endif

                            @if ($finding->criterion)
                                <p class="mt-1 text-sm text-slate-700">
                                    Criterion: <span class="font-mono text-xs">{{ $finding->criterion->rule_key }}</span>
                                    — {{ $finding->criterion->summary }}
                                </p>
                            @endif

                            <p class="mt-2 text-sm text-slate-800">{{ $finding->explanation }}</p>

                            @if ($finding->evidence_start_line)
                                <div class="mt-3">
                                    <div class="text-xs text-slate-500">Evidence lines {{ $finding->evidence_start_line }}–{{ $finding->evidence_end_line }}</div>
                                    <pre class="code-block mt-1">{{ $finding->evidence_snippet }}</pre>
                                </div>
                            @endif

                            @if ($finding->verification_note)
                                <div class="mt-3 rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-900">
                                    <div class="text-xs font-semibold uppercase tracking-wide">Human verification note</div>
                                    <p class="mt-1 whitespace-pre-wrap">{{ $finding->verification_note }}</p>
                                    <p class="mt-1 text-xs">Checker status unchanged: {{ str_replace('_', ' ', $finding->checker_status) }}</p>
                                </div>
                            @endif

                            <form method="post" action="{{ route('findings.review', $finding) }}" class="mt-4 space-y-2 border-t border-slate-100 pt-3">
                                @csrf
                                <label class="block text-xs font-medium text-slate-600" for="verification_note_{{ $finding->id }}">Mark reviewed / resolved (does not change checker result)</label>
                                <textarea id="verification_note_{{ $finding->id }}" name="verification_note" rows="2" required
                                          class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                          placeholder="What you manually verified…">{{ old('verification_note') }}</textarea>
                                <div class="flex flex-wrap gap-2">
                                    <button name="workflow_status" value="reviewed" type="submit" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-800">Mark reviewed</button>
                                    <button name="workflow_status" value="resolved" type="submit" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-800">Mark resolved</button>
                                </div>
                            </form>
                        </article>
                    @empty
                        <p class="rounded-md border border-dashed border-slate-300 bg-white px-4 py-6 text-sm text-slate-600">
                            No finding records for this run (clean supported-rule result is shown in the summary above).
                        </p>
                    @endforelse
                </section>
            @else
                <div class="rounded-md border border-dashed border-slate-300 bg-white px-4 py-10 text-center text-sm text-slate-600">
                    Load a demo sample above, select the seeded decision, then Run check to see summary, evidence lines, and statuses here.
                </div>
            @endif
        </div>
    </div>

    <script>
        document.querySelectorAll('.demo-sample').forEach((button) => {
            button.addEventListener('click', () => {
                const code = atob(button.dataset.code);
                const expect = button.dataset.expect;
                const textarea = document.getElementById('php_code');
                const expectEl = document.getElementById('demo-sample-expect');
                textarea.value = code;
                expectEl.textContent = expect;
                expectEl.classList.remove('hidden');
                const scope = document.getElementById('decision_scope');
                const decision = document.getElementById('decision_id');
                if (scope && decision && decision.options.length > 1) {
                    scope.value = 'single';
                    if (!decision.value) {
                        decision.selectedIndex = 1;
                    }
                }
            });
        });
    </script>
@endsection
