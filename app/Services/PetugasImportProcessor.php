<?php

namespace App\Services;

use App\Models\Petugas;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class PetugasImportProcessor
{
    private const PREVIEW_COLUMNS = [
        'nama',
        'nik',
        'email',
        'telepon',
        'alamat',
        'pendidikan',
        'tahun_bergabung',
        'status',
        'jenis_petugas',
        'jabatan',
        'golongan',
        'jenis_kelamin',
        'kecamatan',
        'desa_kelurahan',
        'tanggal_lahir',
        'npwp',
        'bank',
        'no_rekening',
        'nama_rekening',
        'catatan',
    ];

    public function process(Collection $rows, bool $persist = false): array
    {
        $errors = [];
        $previewRows = [];
        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        $petugasCollection = Petugas::query()->get();
        $petugasByNik = $petugasCollection->keyBy(fn (Petugas $petugas) => (string) $petugas->getAttribute('nik'));
        $petugasByEmail = $petugasCollection->keyBy(fn (Petugas $petugas) => strtolower((string) $petugas->email));

        $seenNik = [];
        $seenEmail = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->prepareData($row);
            $rowWarnings = [];
            $canImport = true;

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $nik = $data['nik'];
            $email = strtolower($data['email']);

            if ($nik !== '' && isset($seenNik[$nik])) {
                $duplicateNikMessage = "NIK duplikat di file impor (sudah muncul di baris {$seenNik[$nik]}).";
                if ($persist) {
                    $errors[] = "Baris {$rowNumber}: {$duplicateNikMessage}";
                    $skippedCount++;

                    continue;
                }

                $rowWarnings[] = $duplicateNikMessage;
                $canImport = false;
            }

            if ($email !== '' && isset($seenEmail[$email])) {
                $duplicateEmailMessage = "Email duplikat di file impor (sudah muncul di baris {$seenEmail[$email]}).";
                if ($persist) {
                    $errors[] = "Baris {$rowNumber}: {$duplicateEmailMessage}";
                    $skippedCount++;

                    continue;
                }

                $rowWarnings[] = $duplicateEmailMessage;
                $canImport = false;
            }

            $existingByNik = $nik !== '' ? $petugasByNik->get($nik) : null;
            $existingByEmail = $email !== '' ? $petugasByEmail->get($email) : null;

            if ($existingByNik && $existingByEmail && $existingByNik->id !== $existingByEmail->id) {
                $identityConflictMessage = 'NIK dan email mengarah ke data petugas yang berbeda.';
                if ($persist) {
                    $errors[] = "Baris {$rowNumber}: {$identityConflictMessage}";
                    $skippedCount++;

                    continue;
                }

                $rowWarnings[] = $identityConflictMessage;
                $canImport = false;
            }

            $existingPetugas = $existingByNik ?? $existingByEmail;

            $validator = Validator::make($data, [
                'nama' => ['required', 'string', 'max:255'],
                'nik' => ['required', 'string', 'max:18'],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('petugas', 'email')->ignore($existingPetugas?->id),
                ],
                'telepon' => ['nullable', 'string', 'max:15'],
                'alamat' => ['nullable', 'string'],
                'pendidikan' => ['required', Rule::in(['SD', 'SMP', 'SMA', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'])],
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
                'email.required' => 'Email wajib diisi',
                'email.email' => 'Format email tidak valid',
                'email.unique' => 'Email sudah terdaftar',
                'telepon.max' => 'Telepon maksimal 15 karakter',
                'pendidikan.required' => 'Pendidikan wajib diisi',
                'pendidikan.in' => 'Pendidikan harus salah satu dari: SD, SMP, SMA, D1, D2, D3, D4, S1, S2, S3',
                'tahun_bergabung.integer' => 'Tahun bergabung harus berupa angka',
                'tahun_bergabung.min' => 'Tahun bergabung tidak valid',
                'tahun_bergabung.max' => 'Tahun bergabung tidak valid',
                'status.in' => 'Status harus aktif atau nonaktif',
                'jenis_petugas.in' => 'Jenis petugas harus organik atau non-organik',
                'npwp.max' => 'NPWP maksimal 24 karakter',
                'tanggal_lahir.date' => 'Tanggal lahir harus format tanggal yang valid',
            ]);

            if ($validator->fails()) {
                $validationMessages = $validator->errors()->all();

                if ($persist) {
                    $errors[] = "Baris {$rowNumber}: ".implode(', ', $validationMessages);
                    $skippedCount++;

                    continue;
                }

                $rowWarnings = [...$rowWarnings, ...$validationMessages];
                $canImport = false;
            }

            $changeSummary = $this->buildChangeSummary($existingPetugas, $data, $this->buildPreviewColumns($data));
            $action = $existingPetugas ? 'update' : 'create';
            $hasChanges = ! $existingPetugas || $changeSummary['changed_fields'] !== [];

            if (! $hasChanges) {
                $noChangeMessage = 'Tidak ada perubahan data, baris ini akan dilewati saat import.';

                if (! $persist) {
                    $rowWarnings[] = $noChangeMessage;
                    $canImport = false;
                }
            }

            if ($persist) {
                if ($existingPetugas) {
                    if ($hasChanges) {
                        $existingPetugas->update($data);
                        $updatedCount++;
                    } else {
                        $skippedCount++;
                    }
                } else {
                    $existingPetugas = Petugas::create($data);
                    $updatedCount += 0;
                    $createdCount++;
                }
            } else {
                if ($canImport) {
                    if ($existingPetugas) {
                        if ($hasChanges) {
                            $updatedCount++;
                        } else {
                            $skippedCount++;
                        }
                    } else {
                        $createdCount++;
                    }
                } else {
                    $skippedCount++;
                }
            }

            $previewColumns = $this->buildPreviewColumns($data);

            $previewRows[] = [
                'row_number' => $rowNumber,
                'action' => $action,
                'nama' => $data['nama'],
                'nik' => $data['nik'],
                'email' => $data['email'],
                'status' => $data['status'],
                'jenis_petugas' => $data['jenis_petugas'],
                'columns' => $previewColumns,
                'changes' => $changeSummary['changed_labels'],
                'changed_fields' => $changeSummary['changed_fields'],
                'warnings' => $rowWarnings,
                'valid_for_import' => $canImport,
            ];

            if (! $persist && $rowWarnings !== []) {
                foreach ($rowWarnings as $warning) {
                    $errors[] = "Baris {$rowNumber}: {$warning}";
                }
            }

            $seenNik[$nik] = $rowNumber;
            $seenEmail[$email] = $rowNumber;

            if ($persist) {
                $petugasByNik->put($nik, $existingPetugas);
                $petugasByEmail->put($email, $existingPetugas);
            }
        }

        return [
            'rows' => $previewRows,
            'errors' => $errors,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'skipped_count' => $skippedCount,
            'success_count' => $createdCount + $updatedCount,
            'total_rows' => $rows->count(),
        ];
    }

    private function prepareData(mixed $row): array
    {
        $data = is_array($row) ? $row : $row->toArray();

        $prepared = [
            'nama' => trim((string) ($data['nama'] ?? '')),
            'nik' => isset($data['nik']) ? trim((string) $data['nik']) : '',
            'email' => trim((string) ($data['email'] ?? '')),
            'telepon' => isset($data['telepon']) ? trim((string) $data['telepon']) : '',
            'alamat' => trim((string) ($data['alamat'] ?? '')),
            'pendidikan' => trim((string) ($data['pendidikan'] ?? '')),
            'tahun_bergabung' => $data['tahun_bergabung'] !== null && $data['tahun_bergabung'] !== ''
                ? (int) $data['tahun_bergabung']
                : null,
            'status' => strtolower(trim((string) ($data['status'] ?? 'aktif'))),
            'jenis_petugas' => strtolower(trim((string) ($data['jenis_petugas'] ?? 'non-organik'))),
            'jabatan' => trim((string) ($data['jabatan'] ?? '')),
            'golongan' => trim((string) ($data['golongan'] ?? '')),
            'npwp' => isset($data['npwp']) ? trim((string) $data['npwp']) : null,
            'bank' => trim((string) ($data['bank'] ?? '')),
            'no_rekening' => isset($data['no_rekening']) ? trim((string) $data['no_rekening']) : null,
            'nama_rekening' => trim((string) ($data['nama_rekening'] ?? '')),
            'catatan' => trim((string) ($data['catatan'] ?? '')),
            'jenis_kelamin' => isset($data['jenis_kelamin'])
                ? strtolower(trim((string) $data['jenis_kelamin']))
                : null,
            'kecamatan' => trim((string) ($data['kecamatan'] ?? '')),
            'desa_kelurahan' => trim((string) ($data['desa_kelurahan'] ?? '')),
            'tanggal_lahir' => $this->normalizeTanggalLahir($data['tanggal_lahir'] ?? null),
        ];

        if ($prepared['jenis_petugas'] === 'non-organik') {
            $prepared['jabatan'] = 'Mitra Statistik';
            $prepared['golongan'] = 'Non PNS';
        }

        foreach ($prepared as $key => $value) {
            if ($value === '') {
                $prepared[$key] = in_array($key, ['telepon', 'alamat', 'jabatan', 'golongan', 'bank', 'nama_rekening', 'catatan', 'kecamatan', 'desa_kelurahan'], true)
                    ? ''
                    : null;
            }
        }

        return $prepared;
    }

    private function normalizeTanggalLahir(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface || $value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (Throwable) {
                // Fallback to string parsing below.
            }
        }

        $dateString = trim((string) $value);
        if ($dateString === '') {
            return null;
        }

        $acceptedFormats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'Y/m/d', 'd.m.Y'];
        foreach ($acceptedFormats as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $dateString);
                if ($date !== false && $date->format($format) === $dateString) {
                    return $date->format('Y-m-d');
                }
            } catch (Throwable) {
                // Continue to the next supported format.
            }
        }

        try {
            return Carbon::parse($dateString)->format('Y-m-d');
        } catch (Throwable) {
            return $dateString;
        }
    }

    private function isEmptyRow(array $data): bool
    {
        return collect($data)->every(fn ($value) => $value === null || $value === '');
    }

    private function buildPreviewColumns(array $data): array
    {
        $columns = [];

        foreach (self::PREVIEW_COLUMNS as $field) {
            $value = $data[$field] ?? null;
            $columns[$field] = $value === null ? null : (string) $value;
        }

        return $columns;
    }

    private function buildChangeSummary(?Petugas $existingPetugas, array $data, array $previewColumns): array
    {
        $labels = [
            'nama' => 'Nama',
            'nik' => 'NIK',
            'email' => 'Email',
            'telepon' => 'Telepon',
            'alamat' => 'Alamat',
            'pendidikan' => 'Pendidikan',
            'tahun_bergabung' => 'Tahun bergabung',
            'status' => 'Status',
            'jenis_petugas' => 'Jenis petugas',
            'jabatan' => 'Jabatan',
            'golongan' => 'Golongan',
            'jenis_kelamin' => 'Jenis kelamin',
            'kecamatan' => 'Kecamatan',
            'desa_kelurahan' => 'Desa/Kelurahan',
            'tanggal_lahir' => 'Tanggal lahir',
            'npwp' => 'NPWP',
            'bank' => 'Bank',
            'no_rekening' => 'No. rekening',
            'nama_rekening' => 'Nama rekening',
            'catatan' => 'Catatan',
        ];

        if (! $existingPetugas) {
            $changedFields = collect($previewColumns)
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->keys()
                ->values()
                ->all();

            return [
                'changed_fields' => $changedFields,
                'changed_labels' => collect($changedFields)
                    ->map(fn (string $field) => $labels[$field] ?? $field)
                    ->values()
                    ->all(),
            ];
        }

        $changedFields = [];

        foreach ($labels as $field => $label) {
            $currentValue = $existingPetugas->getAttribute($field);
            if ($currentValue instanceof CarbonInterface) {
                $currentValue = $currentValue->format('Y-m-d');
            }

            $incomingValue = $data[$field] ?? null;

            if ((string) ($currentValue ?? '') !== (string) ($incomingValue ?? '')) {
                $changedFields[] = $field;
            }
        }

        return [
            'changed_fields' => $changedFields,
            'changed_labels' => collect($changedFields)
                ->map(fn (string $field) => $labels[$field] ?? $field)
                ->values()
                ->all(),
        ];
    }
}
