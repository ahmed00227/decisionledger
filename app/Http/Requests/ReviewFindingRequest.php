<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'workflow_status' => ['required', Rule::in(['reviewed', 'resolved'])],
            'verification_note' => ['required', 'string', 'max:2000'],
        ];
    }
}
