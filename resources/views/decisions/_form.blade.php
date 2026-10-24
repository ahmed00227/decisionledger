@php
    $criterion = $criterion ?? null;
@endphp

<div class="space-y-5">
    <div>
        <label class="block text-sm font-medium text-slate-700" for="title">Title</label>
        <input id="title" name="title" type="text" value="{{ old('title', $decision->title ?? '') }}" required
               class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('title')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700" for="category">Category</label>
        <select id="category" name="category" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(old('category', $decision->category ?? 'Architecture') === $category)>{{ $category }}</option>
            @endforeach
        </select>
        @error('category')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700" for="statement">Decision</label>
        <textarea id="statement" name="statement" rows="3" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('statement', $decision->statement ?? '') }}</textarea>
        @error('statement')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700" for="rationale">Rationale</label>
        <textarea id="rationale" name="rationale" rows="4" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('rationale', $decision->rationale ?? '') }}</textarea>
        <p class="mt-1 text-xs text-slate-500">Human-readable why — not automatically machine-checkable.</p>
        @error('rationale')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>

    <div class="rounded-md border border-slate-300 bg-slate-50 p-4">
        <h2 class="text-sm font-semibold text-slate-900">Verification criteria</h2>
        <p class="mt-1 text-xs text-slate-600">Explicit machine-checkable conditions (distinct from rationale).</p>

        <div class="mt-3">
            <label class="block text-sm font-medium text-slate-700" for="rule_key">Rule key</label>
            <input id="rule_key" name="rule_key" type="text"
                   value="{{ old('rule_key', $criterion->rule_key ?? 'no_direct_eloquent_in_controller') }}"
                   required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 font-mono text-sm">
            @error('rule_key')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div class="mt-3">
            <label class="block text-sm font-medium text-slate-700" for="criterion_summary">Criterion summary (must / must-not)</label>
            <textarea id="criterion_summary" name="criterion_summary" rows="4" required
                      class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('criterion_summary', $criterion->summary ?? '') }}</textarea>
            @error('criterion_summary')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700" for="evaluation_limitations">Evaluation limitations</label>
        <textarea id="evaluation_limitations" name="evaluation_limitations" rows="5" required
                  class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('evaluation_limitations', $decision->evaluation_limitations ?? '') }}</textarea>
        @error('evaluation_limitations')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
</div>
