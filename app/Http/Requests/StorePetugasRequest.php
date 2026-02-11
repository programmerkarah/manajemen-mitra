<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePetugasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $hasRole = $user ? $user->hasActiveRole('admin') : false;

        \Log::info('🔐 [AUTHORIZATION] StorePetugasRequest::authorize()', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'active_role' => $user?->active_role ?? null,
            'has_admin_role' => $hasRole,
            'request_url' => $this->url(),
            'request_method' => $this->method()
        ]);

        if (!$hasRole) {
            \Log::warning('❌ [AUTHORIZATION] User not authorized - missing admin role', [
                'user_id' => $user?->id,
                'active_role' => $user?->active_role ?? null
            ]);
        }

        return $hasRole;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        \Log::info('📝 [VALIDATION] prepareForValidation - BEFORE', [
            'raw_data' => $this->all(),
            'tahun_bergabung_raw' => $this->tahun_bergabung,
            'tahun_bergabung_type' => gettype($this->tahun_bergabung)
        ]);

        $this->merge([
            'tahun_bergabung' => (int) $this->tahun_bergabung,
            'npwp' => $this->npwp ?: null,
            'bank' => $this->bank ?: null,
            'no_rekening' => $this->no_rekening ?: null,
            'nama_rekening' => $this->nama_rekening ?: null,
            'catatan' => $this->catatan ?: null,
        ]);

        \Log::info('📝 [VALIDATION] prepareForValidation - AFTER', [
            'merged_data' => $this->all(),
            'tahun_bergabung_converted' => $this->tahun_bergabung,
            'tahun_bergabung_type' => gettype($this->tahun_bergabung)
        ]);
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        \Log::info('✅ [VALIDATION] Validation passed', [
            'validated_data' => $this->validated(),
            'user_id' => $this->user()->id
        ]);
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        \Log::error('❌ [VALIDATION] Validation failed', [
            'errors' => $validator->errors()->toArray(),
            'input_data' => $this->all(),
            'user_id' => $this->user()->id ?? null
        ]);

        parent::failedValidation($validator);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:18', 'unique:petugas,nik'],
            'email' => ['required', 'email', 'max:255', 'unique:petugas,email'],
            'telepon' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string'],
            'pendidikan' => ['required', 'in:SD,SMP,SMA,D1,D2,D3,D4,S1,S2,S3'],
            'tahun_bergabung' => ['required', 'integer', 'min:1980', 'max:'.date('Y')],
            'jenis_petugas' => ['required', 'in:organik,non-organik'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'golongan' => ['nullable', 'string', 'max:100'],
            'npwp' => ['nullable', 'string', 'max:24', 'unique:petugas,npwp'],
            'bank' => ['nullable', 'string', 'max:100'],
            'no_rekening' => ['nullable', 'string', 'max:100'],
            'nama_rekening' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'catatan' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama petugas wajib diisi.',
            'nik.required' => 'NIK wajib diisi.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'email.required' => 'Email wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'telepon.required' => 'Nomor telepon wajib diisi.',
            'alamat.required' => 'Alamat wajib diisi.',
            'pendidikan.required' => 'Pendidikan wajib dipilih.',
            'pendidikan.in' => 'Pendidikan tidak valid.',
            'tahun_bergabung.required' => 'Tahun bergabung wajib diisi.',
            'tahun_bergabung.min' => 'Tahun bergabung minimal 1980.',
            'tahun_bergabung.max' => 'Tahun bergabung tidak boleh melebihi tahun saat ini.',
            'jenis_petugas.required' => 'Jenis petugas wajib dipilih.',
            'jenis_petugas.in' => 'Jenis petugas harus organik atau non-organik.',
            'npwp.size' => 'NPWP maksimal 24 digit.',
            'npwp.unique' => 'NPWP sudah terdaftar.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status harus aktif atau nonaktif.',
        ];
    }
}
