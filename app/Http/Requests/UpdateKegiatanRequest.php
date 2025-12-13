<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKegiatanRequest extends FormRequest
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
            // kode_kegiatan is read-only and cannot be changed
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'jenis_kegiatan' => ['required', 'in:sensus,survei'],
            'deskripsi' => ['nullable', 'string'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'tahun_anggaran' => ['required', 'integer', 'min:2000', 'max:'.(date('Y') + 5)],
            'pagu_anggaran' => ['nullable', 'numeric', 'min:0'],
            'ketua_tim_user_id' => ['required', 'exists:users,id'],
            // rate_honor_id dikelola melalui menu khusus, tidak di form edit
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
            'pagu_anggaran.numeric' => 'Pagu anggaran harus berupa angka.',
            'pagu_anggaran.min' => 'Pagu anggaran minimal 0.',
            'ketua_tim_user_id.required' => 'Ketua tim wajib dipilih.',
            'ketua_tim_user_id.exists' => 'Ketua tim tidak valid.',
            'status.in' => 'Status tidak valid.',
        ];
    }
}
