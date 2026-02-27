<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePengajuanPulsaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = effectiveUser($this);

        return $user?->isKetuaTim() || $user?->isAdmin() || $user?->hasActiveRole('operator');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'bulan' => ['required', 'string', 'size:2'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:'.(date('Y') + 2)],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.kegiatan_id' => ['required', 'exists:kegiatan,id'],
            'items.*.petugas_id' => ['required', 'exists:petugas,id'],
            'items.*.jenis_pulsa' => ['required', 'in:pelatihan,pendataan'],
            'items.*.nominal' => ['required', 'numeric', 'min:0', 'max:100000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bulan.required' => 'Bulan harus diisi.',
            'tahun.required' => 'Tahun harus diisi.',
            'items.required' => 'Minimal satu item pengajuan pulsa harus diisi.',
            'items.min' => 'Minimal satu item pengajuan pulsa harus diisi.',
            'items.*.kegiatan_id.required' => 'Kegiatan harus dipilih.',
            'items.*.kegiatan_id.exists' => 'Kegiatan tidak valid.',
            'items.*.petugas_id.required' => 'Petugas harus dipilih.',
            'items.*.petugas_id.exists' => 'Petugas tidak valid.',
            'items.*.jenis_pulsa.required' => 'Jenis pulsa harus dipilih.',
            'items.*.jenis_pulsa.in' => 'Jenis pulsa tidak valid.',
            'items.*.nominal.required' => 'Nominal harus diisi.',
            'items.*.nominal.max' => 'Nominal pulsa tidak boleh melebihi Rp100.000 per item.',
        ];
    }
}
