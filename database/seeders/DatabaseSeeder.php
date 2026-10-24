<?php

namespace Database\Seeders;

use App\Models\Criterion;
use App\Models\Decision;
use App\Services\Checking\Rules\NoDirectEloquentInControllerRule;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $decision = Decision::query()->create([
            'title' => 'No direct Eloquent or DB queries in controllers',
            'statement' => 'Business data access must not live as direct Eloquent model queries or DB facade calls inside Laravel controller classes.',
            'rationale' => 'Controllers should handle HTTP concerns. Keeping queries out of controllers keeps domain logic reusable, testable, and harder for an AI coding agent to silently reverse.',
            'category' => 'Architecture',
            'status' => 'active',
            'evaluation_limitations' => implode("\n\n", [
                'This checker analyzes pasted PHP text only. It does not scan a repository or live agent session.',
                'Supported detection: direct Eloquent-style static calls (e.g. Model::where) and documented DB facade forms (DB::…, \\DB::…, Illuminate\\Support\\Facades\\DB::…) inside a class whose name ends with Controller, ignoring comments and string literals.',
                'It does not detect indirect database access through services, repositories, helpers, or arbitrary method calls, and it does not prove a controller contains no business logic.',
                'Confirmed means a supported rule match — not that the whole implementation is architecturally wrong. Unsupported or ambiguous patterns yield needs review or insufficient evidence, never a silent pass-as-confirmed.',
            ]),
        ]);

        Criterion::query()->create([
            'decision_id' => $decision->id,
            'rule_key' => NoDirectEloquentInControllerRule::RULE_KEY,
            'summary' => 'MUST NOT: Controllers must not contain direct Eloquent query operations or documented DB facade query calls. MUST: Database access belongs outside controllers (e.g. services). If the paste cannot establish controller context or uses unsupported patterns, result is needs review / insufficient evidence — not confirmed.',
            'sort_order' => 1,
        ]);
    }
}
