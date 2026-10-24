<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criterion extends Model
{
    protected $table = 'criteria';

    protected $fillable = [
        'decision_id',
        'rule_key',
        'summary',
        'sort_order',
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
