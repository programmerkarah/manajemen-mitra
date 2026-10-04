<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSensusEkonomiPkppContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isOperator();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'periode_alokasi_id' => ['nullable', 'exists:periode_alokasi,id'],
            'nomor_pkpp' => ['nullable', 'string', 'max:255'],
            'tanggal_kontrak' => ['required', 'date'],
            'tanggal_mulai_lapangan' => ['required', 'date', 'after_or_equal:tanggal_kontrak'],
            'status' => ['nullable', 'in:draft,aktif,selesai,dibatalkan'],
        ];
    }

    public function messages(): array
    {
        return [
            'periode_alokasi_id.exists' => 'Periode alokasi tidak valid.',
            'tanggal_kontrak.required' => 'Tanggal kontrak wajib diisi.',
            'tanggal_mulai_lapangan.required' => 'Tanggal mulai lapangan wajib diisi.',
            'tanggal_mulai_lapangan.after_or_equal' => 'Tanggal mulai lapangan harus sama atau setelah tanggal kontrak.',
            'status.in' => 'Status PKPP tidak valid.',
        ];
    }
}
