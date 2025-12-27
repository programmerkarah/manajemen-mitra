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
     * Prepare the data for validation.
     * Decrypt encrypted filters from POST request
     */
    protected function prepareForValidation(): void
    {
        // If it's a POST request with encrypted_filters parameter
        if ($this->isMethod('POST') && $this->has('encrypted_filters')) {
            $decrypted = decryptFilters($this->input('encrypted_filters'));
            
            // Merge decrypted filters into request
            $this->merge($decrypted);
            
            // Remove the encrypted_filters parameter
            $this->request->remove('encrypted_filters');
        }
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
