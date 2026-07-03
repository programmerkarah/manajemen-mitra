<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNonResponseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Ketua tim, admin, dan operator bisa update non response
        $effectiveUser = effectiveUser($this);

        return $effectiveUser->hasActiveRole('ketua_tim') ||
               $effectiveUser->hasActiveRole('admin') ||
               $effectiveUser->hasActiveRole('operator');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'alokasi_petugas' => ['required', 'array'],
            'alokasi_petugas.*.id' => ['required', 'integer', 'exists:alokasi_petugas,id'],
            'alokasi_petugas.*.non_response' => ['nullable', 'integer', 'min:0'],
            'alokasi_petugas.*.non_response_listing' => ['nullable', 'integer', 'min:0'],
            'alokasi_petugas.*.frame_allocation_ids' => ['nullable', 'array'],
            'alokasi_petugas.*.frame_allocation_ids.*' => ['integer', 'exists:alokasi_petugas_frame_sampel,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'alokasi_petugas.*.non_response.integer' => 'Non response harus berupa angka.',
            'alokasi_petugas.*.non_response.min' => 'Non response tidak boleh negatif.',
            'alokasi_petugas.*.non_response_listing.integer' => 'Non response listing harus berupa angka.',
            'alokasi_petugas.*.non_response_listing.min' => 'Non response listing tidak boleh negatif.',
            'alokasi_petugas.*.frame_allocation_ids.array' => 'Daftar sampel non response harus berupa array.',
        ];
    }
}
