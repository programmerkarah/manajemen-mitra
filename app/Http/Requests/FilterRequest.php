<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'bulan' => ['nullable', 'string', 'regex:/^(0?[1-9]|1[0-2])$/'],
            'status' => ['nullable', 'string', 'max:50'],
            'jenis' => ['nullable', 'string', 'max:50'],
            'jenis_kegiatan' => ['nullable', 'string', 'max:50'],
            'jenis_petugas' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'search' => 'pencarian',
            'tahun' => 'tahun',
            'bulan' => 'bulan',
            'status' => 'status',
            'jenis' => 'jenis',
            'jenis_kegiatan' => 'jenis kegiatan',
            'jenis_petugas' => 'jenis petugas',
        ];
    }

    /**
     * Handle a passed validation attempt.
     * Sanitize search input to prevent XSS.
     */
    protected function passedValidation(): void
    {
        if ($this->filled('search')) {
            $this->merge([
                'search' => strip_tags($this->input('search')),
            ]);
        }
    }
}
