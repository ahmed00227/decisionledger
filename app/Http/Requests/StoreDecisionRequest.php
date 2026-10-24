<?php

namespace App\Http\Requests;

use App\Models\Decision;
use App\Services\Checking\Rules\NoDirectEloquentInControllerRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'statement' => ['required', 'string'],
            'rationale' => ['required', 'string'],
            'category' => ['required', Rule::in(Decision::CATEGORIES)],
            'evaluation_limitations' => ['required', 'string'],
            'rule_key' => ['required', 'string', 'max:255'],
            'criterion_summary' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('rule_key')) {
            $this->merge(['rule_key' => NoDirectEloquentInControllerRule::RULE_KEY]);
        }
    }
}
