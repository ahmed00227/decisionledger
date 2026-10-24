@extends('layouts.app')

@section('title', 'Decisions · DecisionLedger')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-slate-900">Decisions</h1>
        <a href="{{ route('decisions.create') }}" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white">New Decision</a>
    </div>

    <ul class="divide-y divide-slate-200 overflow-hidden rounded-md border border-slate-200 bg-white">
        @forelse ($decisions as $decision)
            <li>
                <a href="{{ route('decisions.show', $decision) }}" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 hover:bg-slate-50">
                    <span class="font-medium">{{ $decision->title }}</span>
                    <span class="text-xs text-slate-500">{{ $decision->category }} · {{ $decision->status }}</span>
                </a>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-slate-600">No decisions yet.</li>
        @endforelse
    </ul>
@endsection
