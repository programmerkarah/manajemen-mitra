<?php

namespace App\Http\Requests;

use App\Models\Kegiatan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKegiatanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeFrameSampelPayload();

        if ($this->input('jenis_kegiatan') === 'sensus') {
            $this->merge([
                'has_listing_updating' => false,
                'metode_pendataan_listing' => null,
                'pagu_listing' => null,
            ]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isOperator() || $this->user()->isKetuaTim();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = effectiveUser($this);

        return [
            // kode_kegiatan will be generated automatically
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'jenis_kegiatan' => ['required', 'in:sensus,survei'],
            'deskripsi' => ['nullable', 'string'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'tahun_anggaran' => ['required', 'integer', 'min:2000', 'max:'.(date('Y') + 5)],
            'pagu_pencacahan' => ['nullable', 'numeric', 'min:0'],
            'pagu_listing' => ['nullable', 'numeric', 'min:0'],
            'has_listing_updating' => ['nullable', 'boolean'],
            'frame_sampel_listing_id' => ['required_if:has_listing_updating,true', 'nullable', 'exists:master_frame_sampel,id'],
            'frame_sampel_pencacahan_id' => ['required', 'exists:master_frame_sampel,id'],
            'unit_sampel_listing_ids' => ['required_if:has_listing_updating,true', 'nullable', 'array', 'min:1'],
            'unit_sampel_listing_ids.*' => ['integer', 'exists:master_unit_sampel,id'],
            'unit_sampel_pencacahan_ids' => ['required', 'nullable', 'array', 'min:1'],
            'unit_sampel_pencacahan_ids.*' => ['integer', 'exists:master_unit_sampel,id'],
            'kegiatan_frame_sampel' => ['nullable', 'array'],
            'kegiatan_frame_sampel.*.id' => ['nullable', 'integer', 'exists:kegiatan_frame_sampel,id'],
            'kegiatan_frame_sampel.*.tahapan' => ['required_with:kegiatan_frame_sampel', 'in:listing,pencacahan'],
            'kegiatan_frame_sampel.*.nama_target' => ['required_if:metode_sampling,purpossive', 'nullable', 'string', 'max:255'],
            'kegiatan_frame_sampel.*.sample_role' => ['required_if:metode_sampling,purpossive', 'nullable', 'string', Rule::in(Kegiatan::purpossiveSampleRoleValues())],
            'kegiatan_frame_sampel.*.is_active' => ['nullable', 'boolean'],
            'kegiatan_frame_sampel.*.target_unit_sampel' => ['required_with:kegiatan_frame_sampel', 'array', 'min:1'],
            'kegiatan_frame_sampel.*.target_unit_sampel.*' => ['integer', 'min:0'],
            'kegiatan_frame_sampel.*.identitas_tambahan' => ['nullable', 'array'],
            // Ketua tim optional if user is ketua_tim (will be auto-assigned)
            'ketua_tim_user_id' => [$user && $user->hasActiveRole('ketua_tim') ? 'nullable' : 'required', 'exists:users,id'],
            'pj_lainnya_id' => ['nullable', 'exists:users,id'],
            'rate_honor_id' => ['nullable', 'exists:rate_honor,id'],
            'status' => ['nullable', 'in:draft,aktif,divalidasi,selesai'],
            'metode_pendataan_pencacahan' => ['required', 'in:PAPI,CAPI_FASIH,CAPI_KSA_PRO,CAPI'],
            'metode_pendataan_listing' => ['required_if:has_listing_updating,true', 'nullable', 'in:PAPI,CAPI_FASIH,CAPI_KSA_PRO,CAPI'],
            'metode_sampling' => ['nullable', 'in:targeted,purpossive'],
            'metode_pelatihan' => ['required', 'in:daring,luring,hybrid,tidak_ada_pelatihan'],
            'bulan_pelatihan' => ['required_unless:metode_pelatihan,tidak_ada_pelatihan', 'nullable', 'integer', 'between:1,12'],
        ];
    }

    private function normalizeFrameSampelPayload(): void
    {
        $kegiatanFrameSampel = $this->input('kegiatan_frame_sampel');

        if (! is_array($kegiatanFrameSampel)) {
            return;
        }

        $normalizedRows = collect($kegiatanFrameSampel)
            ->map(function ($row): array {
                if (! is_array($row)) {
                    return [];
                }

                if (array_key_exists('sample_name', $row)) {
                    $sampleName = trim((string) ($row['sample_name'] ?? ''));
                    $namaTarget = trim((string) ($row['nama_target'] ?? ''));

                    if ($sampleName !== '' && $namaTarget === '') {
                        $row['nama_target'] = $sampleName;
                    }
                }

                return $row;
            })
            ->filter(fn (array $row): bool => $row !== [])
            ->values()
            ->all();

        $this->merge([
            'kegiatan_frame_sampel' => $normalizedRows,
        ]);
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'nama_kegiatan.required' => 'Nama kegiatan wajib diisi.',
            'jenis_kegiatan.required' => 'Jenis kegiatan wajib dipilih.',
            'jenis_kegiatan.in' => 'Jenis kegiatan harus Sensus atau Survei.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'tahun_anggaran.required' => 'Tahun anggaran wajib dipilih.',
            'tahun_anggaran.min' => 'Tahun anggaran minimal 2000.',
            'tahun_anggaran.max' => 'Tahun anggaran maksimal 5 tahun ke depan.',
            'pagu_pencacahan.numeric' => 'Pagu pencacahan harus berupa angka.',
            'pagu_pencacahan.min' => 'Pagu pencacahan minimal 0.',
            'pagu_listing.numeric' => 'Pagu listing harus berupa angka.',
            'pagu_listing.min' => 'Pagu listing minimal 0.',
            'frame_sampel_listing_id.required_if' => 'Frame sampel listing wajib dipilih jika kegiatan memiliki tahap listing.',
            'frame_sampel_pencacahan_id.required' => 'Frame sampel pencacahan wajib dipilih.',
            'frame_sampel_listing_id.exists' => 'Frame sampel listing tidak valid.',
            'frame_sampel_pencacahan_id.exists' => 'Frame sampel pencacahan tidak valid.',
            'unit_sampel_listing_ids.required_if' => 'Unit sampel listing wajib dipilih jika kegiatan memiliki tahap listing.',
            'unit_sampel_listing_ids.array' => 'Unit sampel listing harus berupa daftar.',
            'unit_sampel_listing_ids.*.integer' => 'Unit sampel listing harus berupa angka.',
            'unit_sampel_listing_ids.*.exists' => 'Salah satu unit sampel listing tidak valid.',
            'unit_sampel_pencacahan_ids.required' => 'Unit sampel pencacahan wajib dipilih.',
            'unit_sampel_pencacahan_ids.array' => 'Unit sampel pencacahan harus berupa daftar.',
            'unit_sampel_pencacahan_ids.*.integer' => 'Unit sampel pencacahan harus berupa angka.',
            'unit_sampel_pencacahan_ids.*.exists' => 'Salah satu unit sampel pencacahan tidak valid.',
            'kegiatan_frame_sampel.array' => 'Daftar frame sampel harus berupa daftar data.',
            'kegiatan_frame_sampel.*.id.integer' => 'ID frame sampel harus berupa angka.',
            'kegiatan_frame_sampel.*.id.exists' => 'ID frame sampel tidak valid.',
            'kegiatan_frame_sampel.*.tahapan.required_with' => 'Tahapan frame sampel wajib diisi.',
            'kegiatan_frame_sampel.*.tahapan.in' => 'Tahapan frame sampel harus listing atau pencacahan.',
            'kegiatan_frame_sampel.*.nama_target.required_if' => 'Nama target wajib diisi untuk metode sampling purpossive.',
            'kegiatan_frame_sampel.*.nama_target.string' => 'Nama target harus berupa teks.',
            'kegiatan_frame_sampel.*.nama_target.max' => 'Nama target maksimal 255 karakter.',
            'kegiatan_frame_sampel.*.sample_role.required_if' => 'Peran sampel wajib dipilih untuk metode sampling purpossive.',
            'kegiatan_frame_sampel.*.sample_role.string' => 'Peran sampel harus berupa teks.',
            'kegiatan_frame_sampel.*.sample_role.in' => 'Peran sampel tidak valid.',
            'kegiatan_frame_sampel.*.is_active.boolean' => 'Status sampel aktif harus berupa true/false.',
            'kegiatan_frame_sampel.*.target_unit_sampel.required_with' => 'Jumlah unit sampel wajib diisi.',
            'kegiatan_frame_sampel.*.target_unit_sampel.array' => 'Jumlah unit sampel harus berupa daftar.',
            'kegiatan_frame_sampel.*.target_unit_sampel.min' => 'Pilih minimal 1 unit sampel.',
            'kegiatan_frame_sampel.*.target_unit_sampel.*.integer' => 'Jumlah unit sampel harus berupa angka bulat.',
            'kegiatan_frame_sampel.*.target_unit_sampel.*.min' => 'Jumlah unit sampel minimal 0.',
            'kegiatan_frame_sampel.*.identitas_tambahan.array' => 'Identitas tambahan frame sampel harus dalam format JSON object.',
            'ketua_tim_user_id.required' => 'Ketua tim wajib dipilih.',
            'ketua_tim_user_id.exists' => 'Ketua tim tidak valid.',
            'rate_honor_id.exists' => 'Rate honor tidak valid.',
            'status.in' => 'Status tidak valid.',
            'metode_pendataan_pencacahan.required' => 'Metode pendataan pencacahan wajib dipilih.',
            'metode_pendataan_pencacahan.in' => 'Metode pendataan pencacahan harus PAPI, CAPI (FASIH), atau CAPI (KSA Pro/Aplikasi Lainnya).',
            'metode_pendataan_listing.required_if' => 'Metode pendataan listing wajib dipilih jika kegiatan memiliki tahap listing.',
            'metode_pendataan_listing.in' => 'Metode pendataan listing harus PAPI, CAPI (FASIH), atau CAPI (KSA Pro/Aplikasi Lainnya).',
            'metode_sampling.in' => 'Metode sampling harus targeted atau purpossive.',
            'metode_pelatihan.required' => 'Metode pelatihan wajib dipilih.',
            'metode_pelatihan.in' => 'Metode pelatihan harus daring, luring, hybrid, atau tidak ada.',
            'bulan_pelatihan.required_unless' => 'Bulan pelatihan wajib dipilih jika metode pelatihan bukan tidak ada.',
            'bulan_pelatihan.integer' => 'Bulan pelatihan harus berupa angka.',
            'bulan_pelatihan.between' => 'Bulan pelatihan harus antara 1 sampai 12.',
        ];
    }
}
