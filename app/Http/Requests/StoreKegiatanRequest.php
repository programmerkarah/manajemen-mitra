<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKegiatanRequest extends FormRequest
{
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
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
            'ketua_tim_user_id' => ['required', 'exists:users,id'],
            'rate_honor_id' => ['nullable', 'exists:rate_honor,id'],
            'status' => ['nullable', 'in:draft,aktif,divalidasi,selesai,dibatalkan'],
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
            'ketua_tim_user_id.required' => 'Ketua tim wajib dipilih.',
            'ketua_tim_user_id.exists' => 'Ketua tim tidak valid.',
            'rate_honor_id.exists' => 'Rate honor tidak valid.',
            'status.in' => 'Status tidak valid.',
        ];
    }
}
