<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only allow if user is admin (middleware should enforce this)
        return true;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'enabled.required' => 'Status maintenance harus diisi.',
            'enabled.boolean' => 'Status maintenance tidak valid.',
            'message.max' => 'Pesan maintenance maksimal 500 karakter.',
        ];
    }
}
