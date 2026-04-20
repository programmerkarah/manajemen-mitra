<?php

namespace App\Imports;

use App\Models\Petugas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PetugasImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
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
        $rowNumber = 1; // Start from row 1 (after header)

        foreach ($rows as $row) {
            $rowNumber++;

            // Prepare data with defaults and cast numeric fields to strings
            $data = [
                'nama' => $row['nama'] ?? null,
                'nik' => isset($row['nik']) ? (string) $row['nik'] : null,
                'email' => $row['email'] ?? null,
                'telepon' => isset($row['telepon']) ? (string) $row['telepon'] : '',
                'alamat' => $row['alamat'] ?? '',
                'pendidikan' => $row['pendidikan'] ?? null,
                'tahun_bergabung' => $row['tahun_bergabung'] ?? now()->year,
                'status' => strtolower(trim($row['status'] ?? 'aktif')),
                'jenis_petugas' => strtolower(trim($row['jenis_petugas'] ?? 'non-organik')),
                'jabatan' => $row['jabatan'] ?? null,
                'golongan' => $row['golongan'] ?? null,
                'npwp' => isset($row['npwp']) ? (string) $row['npwp'] : null,
                'bank' => $row['bank'] ?? null,
                'no_rekening' => isset($row['no_rekening']) ? (string) $row['no_rekening'] : null,
                'nama_rekening' => $row['nama_rekening'] ?? null,
                'catatan' => $row['catatan'] ?? null,
                'jenis_kelamin' => isset($row['jenis_kelamin']) ? strtolower(trim($row['jenis_kelamin'])) : null,
                'kecamatan' => $row['kecamatan'] ?? null,
                'desa_kelurahan' => $row['desa_kelurahan'] ?? null,
                'tanggal_lahir' => $row['tanggal_lahir'] ?? null,
            ];

            // Auto-set jabatan and golongan for non-organik
            if ($data['jenis_petugas'] === 'non-organik') {
                $data['jabatan'] = 'Mitra Statistik';
                $data['golongan'] = 'Non PNS';
            }

            // Validate the row
            $validator = Validator::make($data, [
                'nama' => ['required', 'string', 'max:255'],
                'nik' => ['required', 'string', 'max:18', 'unique:petugas,nik'],
                'email' => ['required', 'email', 'max:255', 'unique:petugas,email'],
                'telepon' => ['nullable', 'string', 'max:15'],
                'alamat' => ['nullable', 'string'],
                'pendidikan' => ['required', Rule::in(['SD', 'SMP', 'SMA', 'D3', 'D4', 'S1', 'S2', 'S3'])],
                'tahun_bergabung' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
                'status' => ['nullable', Rule::in(['aktif', 'nonaktif'])],
                'jenis_petugas' => ['nullable', Rule::in(['organik', 'non-organik'])],
                'jabatan' => ['nullable', 'string', 'max:255'],
                'golongan' => ['nullable', 'string', 'max:50'],
                'npwp' => ['nullable', 'string', 'max:24'],
                'bank' => ['nullable', 'string', 'max:255'],
                'no_rekening' => ['nullable', 'string', 'max:255'],
                'nama_rekening' => ['nullable', 'string', 'max:255'],
                'catatan' => ['nullable', 'string'],
                'jenis_kelamin' => ['nullable', Rule::in(['laki-laki', 'perempuan'])],
                'kecamatan' => ['nullable', Rule::in(['Silungkang', 'Lembah Segar', 'Barangin', 'Talawi'])],
                'desa_kelurahan' => ['nullable', 'string', 'max:255'],
                'tanggal_lahir' => ['nullable', 'date'],
            ], [
                'nama.required' => 'Nama wajib diisi',
                'nik.required' => 'NIK wajib diisi',
                'nik.unique' => 'NIK sudah terdaftar',
                'email.required' => 'Email wajib diisi',
                'email.email' => 'Format email tidak valid',
                'email.unique' => 'Email sudah terdaftar',
                'telepon.max' => 'Telepon maksimal 15 karakter',
                'pendidikan.required' => 'Pendidikan wajib diisi',
                'pendidikan.in' => 'Pendidikan harus salah satu dari: SD, SMP, SMA, D3, D4, S1, S2, S3',
                'tahun_bergabung.integer' => 'Tahun bergabung harus berupa angka',
                'tahun_bergabung.min' => 'Tahun bergabung tidak valid',
                'tahun_bergabung.max' => 'Tahun bergabung tidak valid',
                'status.in' => 'Status harus aktif atau nonaktif',
                'jenis_petugas.in' => 'Jenis petugas harus organik atau non-organik',
                'npwp.max' => 'NPWP maksimal 24 karakter',
            ]);

            if ($validator->fails()) {
                $this->errors[] = "Baris {$rowNumber}: ".implode(', ', $validator->errors()->all());

                continue;
            }

            try {
                Petugas::create($data);
                $this->successCount++;
            } catch (\Exception $e) {
                $this->errors[] = "Baris {$rowNumber} ({$data['nama']}): ".$e->getMessage();
            }
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
