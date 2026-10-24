@extends('layouts.app')

@section('title', 'Edit Decision · DecisionLedger')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold text-slate-900">Edit Decision</h1>
    <form method="post" action="{{ route('decisions.update', $decision) }}" class="max-w-3xl space-y-6 rounded-md border border-slate-200 bg-white p-6">
        @csrf
        @method('PUT')
        @include('decisions._form')
        <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Update decision</button>
    </form>
@endsection
