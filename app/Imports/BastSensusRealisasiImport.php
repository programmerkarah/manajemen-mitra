<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BastSensusRealisasiImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    /**
     * @var array<int, array<string, mixed>>
     */
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

            $muatanPrelistKeluarga = $this->toNullableInt(
                $row['muatan_prelist_keluarga']
                ?? $row['muatan_prelist_target_pk_keluarga']
                ?? null
            );
            $muatanPrelistUsaha = $this->toNullableInt(
                $row['muatan_prelist_usaha']
                ?? $row['muatan_prelist_target_pk_usaha']
                ?? null
            );

            $realisasiKeluarga = $this->toNullableFloat(
                $row['realisasi_keluarga']
                ?? $row['realisasi_keluarga_rumah_tangga']
                ?? null
            );
            $realisasiUsaha = $this->toNullableFloat(
                $row['realisasi_usaha']
                ?? null
            );

            $realisasiByUnit = [];
            foreach ($row as $key => $value) {
                if (! is_string($key)) {
                    continue;
                }

                if (! preg_match('/^realisasi_unit_(\d+)/', $key, $matches)) {
                    continue;
                }

                $unitId = (int) $matches[1];
                $numericValue = $this->toNullableFloat($value);
                if ($unitId > 0 && $numericValue !== null) {
                    $realisasiByUnit[(string) $unitId] = $numericValue;
                }
            }

            if ($realisasiKeluarga !== null) {
                $realisasiByUnit['keluarga'] = $realisasiKeluarga;
            }

            if ($realisasiUsaha !== null) {
                $realisasiByUnit['usaha'] = $realisasiUsaha;
            }

            $this->rows[] = [
                'nomor_spk' => $nomorSpk,
                'nik_petugas' => $nikPetugas,
                'nama_petugas' => $namaPetugas,
                'muatan_prelist_keluarga' => $muatanPrelistKeluarga,
                'muatan_prelist_usaha' => $muatanPrelistUsaha,
                'realisasi_keluarga' => $realisasiKeluarga,
                'realisasi_usaha' => $realisasiUsaha,
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

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
