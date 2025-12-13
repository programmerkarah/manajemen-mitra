<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAlokasiMitraRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $alokasi = $this->route('alokasi');

        // Only allow updates for draft status
        if ($alokasi->status !== 'draft') {
            return false;
        }

        return $this->user()->isOperator() || $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kegiatan_id' => ['required', 'exists:kegiatan,id'],
            'mitra_id' => ['required', 'exists:mitra,id'],
            'rate_honor_id' => ['required', 'exists:rate_honor,id'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:'.(date('Y') + 1)],
            'jumlah_satuan' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'kegiatan_id.required' => 'Kegiatan wajib dipilih.',
            'kegiatan_id.exists' => 'Kegiatan tidak valid.',
            'mitra_id.required' => 'Mitra wajib dipilih.',
            'mitra_id.exists' => 'Mitra tidak valid.',
            'rate_honor_id.required' => 'Rate honor wajib dipilih.',
            'rate_honor_id.exists' => 'Rate honor tidak valid.',
            'bulan.required' => 'Bulan wajib dipilih.',
            'bulan.min' => 'Bulan minimal 1 (Januari).',
            'bulan.max' => 'Bulan maksimal 12 (Desember).',
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.min' => 'Tahun minimal 2000.',
            'tahun.max' => 'Tahun maksimal 1 tahun ke depan.',
            'jumlah_satuan.required' => 'Jumlah satuan wajib diisi.',
            'jumlah_satuan.min' => 'Jumlah satuan minimal 1.',
        ];
    }
}
