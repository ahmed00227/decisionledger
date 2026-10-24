<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Decision extends Model
{
    public const CATEGORIES = [
        'Architecture',
        'Data & Database',
        'Security',
        'Testing',
        'Performance',
        'Other',
    ];

    protected $fillable = [
        'title',
        'statement',
        'rationale',
        'category',
        'status',
        'evaluation_limitations',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    public function criteria(): HasMany
    {
        return $this->hasMany(Criterion::class)->orderBy('sort_order');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }
}
