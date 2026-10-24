@extends('layouts.app')

@section('title', 'Overview · DecisionLedger')

@section('content')
    <div class="mb-8 flex flex-col gap-4 border-b border-slate-200 pb-8 md:flex-row md:items-end md:justify-between">
        <div class="max-w-2xl">
            <h1 class="text-3xl font-semibold tracking-tight text-slate-900">DecisionLedger</h1>
            <p class="mt-2 text-slate-600">Keep architectural decisions connected to implementation.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('checks.show') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Check PHP</a>
            <a href="{{ route('decisions.create') }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50">New Decision</a>
        </div>
    </div>

    <div class="mb-8 grid gap-4 sm:grid-cols-2">
        <div class="rounded-md border border-slate-200 bg-white px-4 py-3">
            <div class="text-xs uppercase tracking-wide text-slate-500">Saved decisions</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $decisionCount }}</div>
        </div>
        <div class="rounded-md border border-slate-200 bg-white px-4 py-3">
            <div class="text-xs uppercase tracking-wide text-slate-500">Findings awaiting review</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $awaitingReview }}</div>
        </div>
    </div>

    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Recent decisions</h2>
            <a href="{{ route('decisions.index') }}" class="text-sm text-slate-600 hover:text-slate-900">View all</a>
        </div>

        @if ($decisions->isEmpty())
            <p class="rounded-md border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-600">
                No decisions yet. Seed the database or create one to begin.
            </p>
        @else
            <ul class="divide-y divide-slate-200 overflow-hidden rounded-md border border-slate-200 bg-white">
                @foreach ($decisions as $decision)
                    <li>
                        <a href="{{ route('decisions.show', $decision) }}" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 hover:bg-slate-50">
                            <span class="font-medium text-slate-900">{{ $decision->title }}</span>
                            <span class="flex gap-2 text-xs text-slate-500">
                                <span class="rounded border border-slate-200 px-2 py-0.5">{{ $decision->category }}</span>
                                <span class="rounded border border-slate-200 px-2 py-0.5">{{ $decision->status }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
