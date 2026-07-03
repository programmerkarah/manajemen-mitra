<?php

namespace App\Http\Requests\Settings;

use App\Models\FeatureToggle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeatureToggleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', Rule::in(array_column(FeatureToggle::defaultDefinitions(), 'key'))],
            'enabled' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => 'Kunci fitur harus diisi.',
            'key.in' => 'Kunci fitur tidak valid.',
            'enabled.required' => 'Status fitur harus diisi.',
            'enabled.boolean' => 'Status fitur tidak valid.',
        ];
    }
}
