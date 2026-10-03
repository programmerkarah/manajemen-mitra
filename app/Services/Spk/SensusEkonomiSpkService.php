<?php

namespace App\Services\Spk;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use Illuminate\Support\Collection;

class SensusEkonomiSpkService
{
    public function isSensusEkonomi2026(Kegiatan $kegiatan): bool
    {
        return mb_strtolower((string) $kegiatan->jenis_kegiatan)
                === 'sensus'
            && str_contains(
                mb_strtolower(
                    trim((string) $kegiatan->nama_kegiatan),
                ),
                'sensus ekonomi',
            );
    }

    public function milestoneMetrics(
        int $selectedRows,
        array $frameMuatanTotals,
        int $percentage,
    ): array {
        $selectedRows = max(0, $selectedRows);
        $termOneRows = $this->termSelectedRows(
            $selectedRows,
            $frameMuatanTotals,
        );

        if ($percentage === 40) {
            return ['selected_rows' => $termOneRows];
        }

        return [
            'selected_rows' =>
                max(0, $selectedRows - $termOneRows),
        ];
    }

    public function termSelectedRows(
        int $selectedRows,
        array $frameMuatanTotals,
    ): int {
        $selectedRows = max(0, $selectedRows);
        $frameMuatanTotals = array_values(
            array_filter(
                array_map(
                    static fn ($value): int =>
                        max(0, (int) $value),
                    $frameMuatanTotals,
                ),
                static fn (int $value): bool => $value > 0,
            ),
        );

        if ($selectedRows === 0) {
            return 0;
        }

        if ($frameMuatanTotals === []) {
            return (int) ceil($selectedRows * 0.4);
        }

        $totalMuatan = array_sum($frameMuatanTotals);

        if ($totalMuatan <= 0) {
            return (int) ceil($selectedRows * 0.4);
        }

        $threshold = (int) ceil($totalMuatan * 0.4);
        rsort($frameMuatanTotals, SORT_NUMERIC);

        $accumulated = 0;
        $count = 0;

        foreach ($frameMuatanTotals as $frameMuatan) {
            $accumulated += $frameMuatan;
            $count++;

            if ($accumulated >= $threshold) {
                break;
            }
        }

        return max(1, min($selectedRows, $count));
    }

    public function frameVolumeMetrics(
        mixed $allAlokasi,
        mixed $alokasi,
    ): array {
        $allocations = collect();

        if ($allAlokasi instanceof Collection) {
            $allocations = $allAlokasi
                ->filter(
                    fn (mixed $item): bool =>
                        $item instanceof AlokasiPetugas,
                )
                ->values();
        }

        if (
            $allocations->isEmpty()
            && $alokasi instanceof AlokasiPetugas
        ) {
            $allocations = collect([$alokasi]);
        }

        if ($allocations->isEmpty()) {
            return [
                'selected_rows' => 0,
                'prelist_total' => 0,
                'total_volume' => 0,
                'frame_muatan_totals' => [],
                'narrative' => '-',
            ];
        }

        $allocations->each(
            fn (AlokasiPetugas $allocation) =>
                $allocation->loadMissing(
                    'frameSampelAllocations.kegiatanFrameSampel',
                ),
        );

        $frameAllocations = $allocations
            ->flatMap(
                fn (AlokasiPetugas $allocation): array =>
                    $allocation->frameSampelAllocations->all(),
            )
            ->filter(
                fn (mixed $allocation): bool =>
                    $allocation !== null,
            )
            ->unique('kegiatan_frame_sampel_id')
            ->values();

        $selectedRows = $frameAllocations->count();
        $perUnitTotals = [];
        $frameMuatanTotals = [];

        foreach ($frameAllocations as $frameAllocation) {
            $targets = $frameAllocation
                ?->kegiatanFrameSampel
                ?->target_unit_sampel;
            $frameTotal = 0;

            if (is_array($targets)) {
                foreach ($targets as $unitId => $count) {
                    $id = (int) $unitId;
                    $value = max(0, (int) $count);
                    $perUnitTotals[$id] =
                        ($perUnitTotals[$id] ?? 0) + $value;
                    $frameTotal += $value;
                }
            } elseif (
                is_numeric($targets)
                && (int) $targets > 0
            ) {
                $value = (int) $targets;
                $perUnitTotals[0] =
                    ($perUnitTotals[0] ?? 0) + $value;
                $frameTotal += $value;
            }

            $frameMuatanTotals[] = $frameTotal;
        }

        $unitIds = array_values(
            array_filter(
                array_keys($perUnitTotals),
                fn ($id) => $id > 0,
            ),
        );

        $unitNames = $unitIds !== []
            ? MasterUnitSampel::query()
                ->whereIn('id', $unitIds)
                ->pluck('nama', 'id')
                ->toArray()
            : [];

        $prelistTotal = array_sum($perUnitTotals);
        $totalVolume = $selectedRows + $prelistTotal;

        return [
            'selected_rows' => $selectedRows,
            'prelist_total' => $prelistTotal,
            'per_unit_sampel_totals' => $perUnitTotals,
            'unit_sampel_names' => $unitNames,
            'frame_muatan_totals' => $frameMuatanTotals,
            'total_volume' => $totalVolume,
            'narrative' => $this->volumeNarrative(
                $selectedRows,
            ),
        ];
    }

    public function volumeNarrative(int $selectedRows): string
    {
        return $selectedRows > 0
            ? number_format($selectedRows, 0, ',', '.')
                .' SLS/sub-SLS'
            : '-';
    }

    public function totalSlsVolumeLabel(int $selectedRows): string
    {
        return $selectedRows > 0
            ? 'Seluruh Muatan '
                .number_format($selectedRows, 0, ',', '.')
                .' SLS/sub-SLS'
            : '-';
    }
}
