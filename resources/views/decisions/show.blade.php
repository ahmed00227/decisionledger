@extends('layouts.app')

@section('title', $decision->title.' · DecisionLedger')

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">{{ $decision->category }} · {{ $decision->status }}</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ $decision->title }}</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('checks.show', ['decision' => $decision->id]) }}" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white">Check PHP</a>
            <a href="{{ route('decisions.edit', $decision) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">Edit</a>
            <a href="{{ route('decisions.index') }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">Back</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-md border border-slate-200 bg-white p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Decision</h2>
                <p class="mt-2 whitespace-pre-wrap text-slate-900">{{ $decision->statement }}</p>
            </section>

            <section class="rounded-md border border-slate-200 bg-white p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rationale</h2>
                <p class="mt-2 whitespace-pre-wrap text-slate-700">{{ $decision->rationale }}</p>
            </section>

            <section class="rounded-md border-2 border-slate-400 bg-slate-50 p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Verification criteria</h2>
                <p class="mt-1 text-xs text-slate-600">Machine-checkable — visually distinct from rationale.</p>
                <ul class="mt-3 space-y-3">
                    @foreach ($decision->criteria as $criterion)
                        <li class="rounded border border-slate-300 bg-white p-3">
                            <div class="font-mono text-xs text-slate-500">{{ $criterion->rule_key }}</div>
                            <p class="mt-1 whitespace-pre-wrap text-sm text-slate-900">{{ $criterion->summary }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-md border border-amber-200 bg-amber-50 p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-amber-900">Evaluation limitations</h2>
                <p class="mt-2 whitespace-pre-wrap text-sm text-amber-950">{{ $decision->evaluation_limitations }}</p>
            </section>
        </div>

        <aside class="space-y-4">
            <section class="rounded-md border border-slate-200 bg-white p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Related findings</h2>
                @forelse ($decision->findings as $finding)
                    <div class="mt-3 border-t border-slate-100 pt-3 text-sm">
                        <span class="inline-block rounded border px-2 py-0.5 text-xs status-{{ $finding->checker_status }}">{{ str_replace('_', ' ', $finding->checker_status) }}</span>
                        @if ($finding->workflow_status)
                            <span class="ml-1 inline-block rounded border px-2 py-0.5 text-xs status-{{ $finding->workflow_status }}">{{ $finding->workflow_status }}</span>
                        @endif
                        <p class="mt-1 text-slate-700">{{ \Illuminate\Support\Str::limit($finding->explanation, 120) }}</p>
                        <a class="mt-1 inline-block text-xs text-slate-600 underline" href="{{ route('checks.show') }}">Open checks</a>
                    </div>
                @empty
                    <p class="mt-2 text-sm text-slate-500">No findings linked yet.</p>
                @endforelse
            </section>
        </aside>
    </div>
@endsection
