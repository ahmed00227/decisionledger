<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckRun extends Model
{
    public const SCOPE_SINGLE = 'single';

    public const SCOPE_ALL_COMPATIBLE = 'all_compatible';

    protected $fillable = [
        'php_code',
        'context_notes',
        'decision_scope',
        'decision_id',
        'summary',
    ];

    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }
}
