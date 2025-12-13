<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRateHonorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'posisi' => ['required', 'string', 'max:100'],
            'satuan_id' => ['required', 'exists:satuan,id'],
            'rate' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'tahun_berlaku' => ['required', 'integer', 'min:2000', 'max:'.(date('Y') + 5)],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'posisi.required' => 'Posisi wajib diisi.',
            'satuan_id.required' => 'Satuan wajib dipilih.',
            'satuan_id.exists' => 'Satuan tidak valid.',
            'rate.required' => 'Rate honor wajib diisi.',
            'rate.numeric' => 'Rate honor harus berupa angka.',
            'rate.min' => 'Rate honor tidak boleh negatif.',
            'rate.max' => 'Rate honor maksimal 99.999.999.',
            'tahun_berlaku.required' => 'Tahun berlaku wajib diisi.',
            'tahun_berlaku.min' => 'Tahun berlaku minimal 2000.',
            'tahun_berlaku.max' => 'Tahun berlaku maksimal 5 tahun ke depan.',
        ];
    }
}
