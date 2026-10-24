<?php

namespace App\Http\Requests;

use App\Models\CheckRun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RunCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'php_code' => ['required', 'string', 'min:1'],
            'context_notes' => ['nullable', 'string'],
            'decision_scope' => ['required', Rule::in([CheckRun::SCOPE_SINGLE, CheckRun::SCOPE_ALL_COMPATIBLE])],
            'decision_id' => [
                Rule::requiredIf(fn () => $this->input('decision_scope') === CheckRun::SCOPE_SINGLE),
                'nullable',
                'integer',
                'exists:decisions,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'php_code.required' => 'Paste PHP code to run a check. Empty input cannot be analyzed.',
        ];
    }
}
