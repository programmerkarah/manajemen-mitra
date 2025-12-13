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
        return $this->user()->isAdmin() || $this->user()->isPj();
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
            'deskripsi' => ['nullable', 'string'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'tahun_anggaran' => ['required', 'integer', 'min:2000', 'max:'.(date('Y') + 5)],
            'pagu_anggaran' => ['nullable', 'numeric', 'min:0'],
            'pj_user_id' => ['required', 'exists:users,id'],
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
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'tahun_anggaran.required' => 'Tahun anggaran wajib dipilih.',
            'tahun_anggaran.min' => 'Tahun anggaran minimal 2000.',
            'tahun_anggaran.max' => 'Tahun anggaran maksimal 5 tahun ke depan.',
            'pagu_anggaran.numeric' => 'Pagu anggaran harus berupa angka.',
            'pagu_anggaran.min' => 'Pagu anggaran minimal 0.',
            'pj_user_id.required' => 'Penanggung jawab wajib dipilih.',
            'pj_user_id.exists' => 'Penanggung jawab tidak valid.',
            'status.in' => 'Status tidak valid.',
        ];
    }
}
