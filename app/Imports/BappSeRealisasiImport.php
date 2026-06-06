<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BappSeRealisasiImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    /** @var array<int, array<string, mixed>> */
    private array $rows = [];

    /**
     * @param  Collection<int, Collection<int|string, mixed>>  $collection
     */
    public function collection(Collection $collection): void
    {
        foreach ($collection as $row) {
            $nomorSpk = trim((string) ($row['nomor_spk'] ?? ''));
            $nikPetugas = preg_replace('/\D+/', '', (string) ($row['nik_petugas'] ?? ''));
            $namaPetugas = trim((string) ($row['nama_petugas'] ?? ''));

            if ($nomorSpk === '' && $nikPetugas === '' && $namaPetugas === '') {
                continue;
            }

            $realisasiSls = $this->toNullableInt($row['realisasi_sls'] ?? null);

            $realisasiByUnit = [];
            foreach ($row as $key => $value) {
                if (! is_string($key)) {
                    continue;
                }

                if (! preg_match('/^realisasi_(.+)$/', $key, $matches)) {
                    continue;
                }

                $unitKey = $matches[1];
                if ($unitKey === 'sls') {
                    continue;
                }

                $numericValue = $this->toNullableInt($value);
                if ($numericValue !== null && $numericValue >= 0) {
                    $realisasiByUnit[$unitKey] = $numericValue;
                }
            }

            $this->rows[] = [
                'nomor_spk' => $nomorSpk,
                'nik_petugas' => $nikPetugas,
                'nama_petugas' => $namaPetugas,
                'realisasi_sls' => $realisasiSls,
                'realisasi_unit_sampel' => $realisasiByUnit,
            ];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) round((float) $value);
    }
}
