<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDecisionRequest;
use App\Http\Requests\UpdateDecisionRequest;
use App\Models\Decision;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DecisionController extends Controller
{
    public function index(): View
    {
        $decisions = Decision::query()->where('status', 'active')->latest()->get();

        return view('decisions.index', compact('decisions'));
    }

    public function create(): View
    {
        return view('decisions.create', [
            'categories' => Decision::CATEGORIES,
        ]);
    }

    public function store(StoreDecisionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $decision = Decision::query()->create([
            'title' => $data['title'],
            'statement' => $data['statement'],
            'rationale' => $data['rationale'],
            'category' => $data['category'],
            'status' => 'active',
            'evaluation_limitations' => $data['evaluation_limitations'],
        ]);

        $decision->criteria()->create([
            'rule_key' => $data['rule_key'],
            'summary' => $data['criterion_summary'],
            'sort_order' => 1,
        ]);

        return redirect()->route('decisions.show', $decision)
            ->with('status', 'Decision created.');
    }

    public function show(Decision $decision): View
    {
        $decision->load(['criteria', 'findings' => fn ($q) => $q->latest()->limit(20)]);

        return view('decisions.show', compact('decision'));
    }

    public function edit(Decision $decision): View
    {
        $decision->load('criteria');

        return view('decisions.edit', [
            'decision' => $decision,
            'categories' => Decision::CATEGORIES,
            'criterion' => $decision->criteria->first(),
        ]);
    }

    public function update(UpdateDecisionRequest $request, Decision $decision): RedirectResponse
    {
        $data = $request->validated();
        $decision->update([
            'title' => $data['title'],
            'statement' => $data['statement'],
            'rationale' => $data['rationale'],
            'category' => $data['category'],
            'evaluation_limitations' => $data['evaluation_limitations'],
        ]);

        $criterion = $decision->criteria()->first();
        if ($criterion) {
            $criterion->update([
                'rule_key' => $data['rule_key'],
                'summary' => $data['criterion_summary'],
            ]);
        } else {
            $decision->criteria()->create([
                'rule_key' => $data['rule_key'],
                'summary' => $data['criterion_summary'],
                'sort_order' => 1,
            ]);
        }

        return redirect()->route('decisions.show', $decision)
            ->with('status', 'Decision updated.');
    }
}
