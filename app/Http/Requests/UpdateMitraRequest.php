<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMitraRequest extends FormRequest
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
        $mitra = $this->route('mitra');

        return [
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'size:16', Rule::unique('mitra', 'nik')->ignore($mitra->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('mitra', 'email')->ignore($mitra->id)],
            'telepon' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string'],
            'pendidikan' => ['required', 'in:SD,SMP,SMA,D1,D2,D3,D4,S1,S2,S3'],
            'tahun_bergabung' => ['required', 'integer', 'min:2000', 'max:'.date('Y')],
            'npwp' => ['nullable', 'string', 'size:15', Rule::unique('mitra', 'npwp')->ignore($mitra->id)],
            'bank' => ['nullable', 'string', 'max:100'],
            'no_rekening' => ['nullable', 'string', 'max:50'],
            'nama_rekening' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama mitra wajib diisi.',
            'nik.required' => 'NIK wajib diisi.',
            'nik.size' => 'NIK harus 16 digit.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'telepon.required' => 'Nomor telepon wajib diisi.',
            'alamat.required' => 'Alamat wajib diisi.',
            'pendidikan.required' => 'Pendidikan wajib dipilih.',
            'pendidikan.in' => 'Pendidikan tidak valid.',
            'tahun_bergabung.required' => 'Tahun bergabung wajib diisi.',
            'tahun_bergabung.min' => 'Tahun bergabung minimal 2000.',
            'tahun_bergabung.max' => 'Tahun bergabung tidak boleh melebihi tahun saat ini.',
            'npwp.size' => 'NPWP harus 15 digit.',
            'npwp.unique' => 'NPWP sudah terdaftar.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status harus aktif atau nonaktif.',
        ];
    }
}
