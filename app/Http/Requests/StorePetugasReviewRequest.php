<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePetugasReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = effectiveUser($this);

        return $user !== null && $user->getActiveRole()?->name !== 'guest';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kegiatan_id' => ['required', 'integer', 'exists:kegiatan,id'],
            'petugas_id' => ['required', 'integer', 'exists:petugas,id'],
            'periode_alokasi_id' => ['required', 'integer', 'exists:periode_alokasi,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'ulasan' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'kegiatan_id.required' => 'Kegiatan wajib dipilih.',
            'kegiatan_id.exists' => 'Kegiatan tidak valid.',
            'petugas_id.required' => 'Petugas wajib dipilih.',
            'petugas_id.exists' => 'Petugas tidak valid.',
            'rating.required' => 'Nilai bintang wajib diisi.',
            'rating.min' => 'Nilai bintang minimal 1.',
            'rating.max' => 'Nilai bintang maksimal 5.',
            'ulasan.max' => 'Ulasan maksimal 500 karakter.',
        ];
    }
}
