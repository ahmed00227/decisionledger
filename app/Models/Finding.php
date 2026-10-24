<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Finding extends Model
{
    protected $fillable = [
        'check_run_id',
        'decision_id',
        'criterion_id',
        'checker_status',
        'explanation',
        'evidence_start_line',
        'evidence_end_line',
        'evidence_snippet',
        'unsupported_rule',
        'workflow_status',
        'verification_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'unsupported_rule' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function checkRun(): BelongsTo
    {
        return $this->belongsTo(CheckRun::class);
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class);
    }
}
