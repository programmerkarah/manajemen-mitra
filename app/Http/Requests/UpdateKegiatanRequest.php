<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKegiatanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
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
        return $this->user()->isAdmin() || $this->user()->isKetuaTim();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // kode_kegiatan is read-only and cannot be changed
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'jenis_kegiatan' => ['required', 'in:sensus,survei'],
            'deskripsi' => ['nullable', 'string'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'tahun_anggaran' => ['required', 'integer', 'min:2000', 'max:'.(date('Y') + 5)],
            'pagu_pencacahan' => ['nullable', 'numeric', 'min:0'],
            'pagu_listing' => ['nullable', 'numeric', 'min:0'],
            'has_listing_updating' => ['nullable', 'boolean'],
            'frame_sampel_listing_id' => ['nullable', 'exists:master_frame_sampel,id'],
            'frame_sampel_pencacahan_id' => ['nullable', 'exists:master_frame_sampel,id'],
            'unit_sampel_listing_id' => ['nullable', 'exists:master_unit_sampel,id'],
            'unit_sampel_pencacahan_id' => ['nullable', 'exists:master_unit_sampel,id'],
            'kegiatan_frame_sampel' => ['nullable', 'array'],
            'kegiatan_frame_sampel.*.tahapan' => ['required_with:kegiatan_frame_sampel', 'in:listing,pencacahan'],
            'kegiatan_frame_sampel.*.target_unit_sampel' => ['required_with:kegiatan_frame_sampel', 'integer', 'min:1'],
            'kegiatan_frame_sampel.*.identitas_tambahan' => ['nullable', 'array'],
            'ketua_tim_user_id' => ['required', 'exists:users,id'],
            'pj_lainnya_id' => ['nullable', 'exists:users,id'],
            // rate_honor_id dikelola melalui menu khusus, tidak di form edit
            'status' => ['nullable', 'in:draft,aktif,divalidasi,selesai,dibatalkan'],
            'metode_pendataan_pencacahan' => ['required', 'in:PAPI,CAPI'],
            'metode_pendataan_listing' => ['required_if:has_listing_updating,true', 'nullable', 'in:PAPI,CAPI'],
            'metode_pelatihan' => ['required', 'in:daring,luring,hybrid,tidak_ada_pelatihan'],
            'bulan_pelatihan' => ['required_unless:metode_pelatihan,tidak_ada_pelatihan', 'nullable', 'integer', 'between:1,12'],
        ];
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
            'has_listing_updating.boolean' => 'Status tahapan Listing/Updating harus berupa true/false.',
            'frame_sampel_listing_id.exists' => 'Frame sampel listing tidak valid.',
            'frame_sampel_pencacahan_id.exists' => 'Frame sampel pencacahan tidak valid.',
            'unit_sampel_listing_id.exists' => 'Unit sampel listing tidak valid.',
            'unit_sampel_pencacahan_id.exists' => 'Unit sampel pencacahan tidak valid.',
            'kegiatan_frame_sampel.array' => 'Daftar frame sampel harus berupa daftar data.',
            'kegiatan_frame_sampel.*.tahapan.required_with' => 'Tahapan frame sampel wajib diisi.',
            'kegiatan_frame_sampel.*.tahapan.in' => 'Tahapan frame sampel harus listing atau pencacahan.',
            'kegiatan_frame_sampel.*.target_unit_sampel.required_with' => 'Jumlah unit sampel wajib diisi.',
            'kegiatan_frame_sampel.*.target_unit_sampel.integer' => 'Jumlah unit sampel harus berupa angka bulat.',
            'kegiatan_frame_sampel.*.target_unit_sampel.min' => 'Jumlah unit sampel minimal 1.',
            'kegiatan_frame_sampel.*.identitas_tambahan.array' => 'Identitas tambahan frame sampel harus dalam format JSON object.',
            'ketua_tim_user_id.required' => 'Ketua tim wajib dipilih.',
            'ketua_tim_user_id.exists' => 'Ketua tim tidak valid.',
            'status.in' => 'Status tidak valid.',
            'metode_pendataan_pencacahan.required' => 'Metode pendataan pencacahan wajib dipilih.',
            'metode_pendataan_pencacahan.in' => 'Metode pendataan pencacahan harus PAPI atau CAPI.',
            'metode_pendataan_listing.required_if' => 'Metode pendataan listing wajib dipilih jika kegiatan memiliki tahap listing.',
            'metode_pendataan_listing.in' => 'Metode pendataan listing harus PAPI atau CAPI.',
            'metode_pelatihan.required' => 'Metode pelatihan wajib dipilih.',
            'metode_pelatihan.in' => 'Metode pelatihan harus daring, luring, hybrid, atau tidak ada.',
            'bulan_pelatihan.required_unless' => 'Bulan pelatihan wajib dipilih jika metode pelatihan bukan tidak ada.',
            'bulan_pelatihan.integer' => 'Bulan pelatihan harus berupa angka.',
            'bulan_pelatihan.between' => 'Bulan pelatihan harus antara 1 sampai 12.',
        ];
    }
}
