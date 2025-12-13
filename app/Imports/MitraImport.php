<?php

namespace App\Imports;

use App\Models\Mitra;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class MitraImport implements SkipsEmptyRows, SkipsOnError, SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use Importable;

    protected $errors = [];

    protected $successCount = 0;

    protected $failures = [];

    /**
     * @param  Collection  $collection
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                Mitra::create([
                    'nama' => $row['nama'],
                    'nik' => $row['nik'],
                    'email' => $row['email'],
                    'telepon' => $row['telepon'] ?? '',
                    'alamat' => $row['alamat'] ?? '',
                    'pendidikan' => $row['pendidikan'],
                    'tahun_bergabung' => $row['tahun_bergabung'] ?? now()->year,
                    'status' => $row['status'] ?? 'aktif',
                    'npwp' => $row['npwp'] ?? null,
                    'bank' => $row['bank'] ?? null,
                    'no_rekening' => $row['no_rekening'] ?? null,
                    'nama_rekening' => $row['nama_rekening'] ?? null,
                    'catatan' => $row['catatan'] ?? null,
                ]);

                $this->successCount++;
            } catch (\Exception $e) {
                $this->errors[] = "Row {$row['nama']}: ".$e->getMessage();
            }
        }
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'size:16', 'unique:mitra,nik'],
            'email' => ['required', 'email', 'max:255', 'unique:mitra,email'],
            'telepon' => ['nullable', 'string', 'max:15'],
            'alamat' => ['nullable', 'string'],
            'pendidikan' => ['required', Rule::in(['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2', 'S3'])],
            'tahun_bergabung' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'status' => ['nullable', Rule::in(['aktif', 'nonaktif'])],
            'npwp' => ['nullable', 'string', 'max:20'],
            'bank' => ['nullable', 'string', 'max:255'],
            'no_rekening' => ['nullable', 'string', 'max:255'],
            'nama_rekening' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama.required' => 'Nama wajib diisi',
            'nik.required' => 'NIK wajib diisi',
            'nik.size' => 'NIK harus 16 digit',
            'nik.unique' => 'NIK sudah terdaftar',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'telepon.max' => 'Telepon maksimal 15 karakter',
            'pendidikan.required' => 'Pendidikan wajib diisi',
            'pendidikan.in' => 'Pendidikan harus salah satu dari: SD, SMP, SMA, D3, S1, S2, S3',
            'tahun_bergabung.integer' => 'Tahun bergabung harus berupa angka',
            'tahun_bergabung.min' => 'Tahun bergabung tidak valid',
            'tahun_bergabung.max' => 'Tahun bergabung tidak valid',
            'status.in' => 'Status harus aktif atau nonaktif',
            'npwp.max' => 'NPWP maksimal 20 karakter',
        ];
    }

    public function onError(\Throwable $e)
    {
        $this->errors[] = $e->getMessage();
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failures[] = $failure;
            $this->errors[] = "Baris {$failure->row()}: ".implode(', ', $failure->errors());
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getFailures(): array
    {
        return $this->failures;
    }
}
