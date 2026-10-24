<?php

namespace App\Http\Controllers;

use App\Models\Decision;
use App\Models\Finding;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(): View
    {
        $decisions = Decision::query()
            ->where('status', 'active')
            ->latest()
            ->limit(10)
            ->get();

        $awaitingReview = Finding::query()
            ->whereNull('workflow_status')
            ->whereIn('checker_status', ['confirmed', 'needs_review', 'insufficient_evidence'])
            ->count();

        return view('overview.index', [
            'decisionCount' => Decision::query()->count(),
            'awaitingReview' => $awaitingReview,
            'decisions' => $decisions,
        ]);
    }
}
