<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BatchUpdatePetugasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasActiveRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'petugas' => ['required', 'array', 'min:1'],
            'petugas.*.id' => ['required', 'string'],
            'petugas.*.nama' => ['required', 'string', 'max:255'],
            'petugas.*.telepon' => ['required', 'string', 'max:20'],
            'petugas.*.pendidikan' => ['required', 'in:SD,SMP,SMA,D1,D2,D3,D4,S1,S2,S3'],
            'petugas.*.jenis_kelamin' => ['nullable', 'in:laki-laki,perempuan'],
            'petugas.*.tanggal_lahir' => ['nullable', 'date', 'before:today'],
            'petugas.*.kecamatan' => ['nullable', 'in:Silungkang,Lembah Segar,Barangin,Talawi'],
            'petugas.*.desa_kelurahan' => ['nullable', 'string', 'max:255'],
            'petugas.*.alamat' => ['required', 'string'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'petugas.required' => 'Pilih minimal satu petugas.',
            'petugas.*.nama.required' => 'Nama petugas wajib diisi.',
            'petugas.*.telepon.required' => 'Nomor telepon wajib diisi.',
            'petugas.*.pendidikan.required' => 'Pendidikan wajib dipilih.',
            'petugas.*.alamat.required' => 'Alamat wajib diisi.',
        ];
    }
}
