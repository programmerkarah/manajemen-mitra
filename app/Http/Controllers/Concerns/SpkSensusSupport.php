<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Requests\FilterRequest;
use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\BappSeTermin;
use App\Models\Bast;
use App\Models\Dipa;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use App\Services\PdfMergerService;
use App\Services\SensusEkonomiReplacementReadService;
use App\Services\SpkActionDecisionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use setasign\Fpdi\Tcpdf\Fpdi;
use Vinkla\Hashids\Facades\Hashids;

trait SpkSensusSupport
{
    private function isSensusEkonomi2026(Kegiatan $kegiatan): bool
    {
        return mb_strtolower((string) $kegiatan->jenis_kegiatan) === 'sensus'
            && str_contains(
                mb_strtolower(trim((string) $kegiatan->nama_kegiatan)),
                'sensus ekonomi'
            );
    }

    private function canAccessSensusMode(?User $user, ?int $tahunAnggaran = null): bool
    {
        if (! $user) {
            return false;
        }

        if (in_array($user->active_role, ['admin', 'operator', 'approver'], true)) {
            return true;
        }

        if ($user->active_role !== 'ketua_tim') {
            return false;
        }

        $activeYear = $tahunAnggaran ?? ActiveYearService::get();

        return Kegiatan::query()
            ->where('tahun_anggaran', $activeYear)
            ->where('nama_kegiatan', 'like', '%Sensus Ekonomi%')
            ->where(function ($query) use ($user) {
                $query->where('ketua_tim_user_id', $user->id)
                    ->orWhere('pj_lainnya_id', $user->id);
            })
            ->exists();
    }

    private function getRequestUser(Request $request): ?User
    {
        return effectiveUser($request) ?? $request->user();
    }

    private function buildSensusEkonomiLampiranPayload(
        mixed $periode,
        array $uraianTugas,
        float $totalHonor,
        Kegiatan $kegiatan,
        mixed $allAlokasi = null,
        mixed $alokasi = null,
    ): array {
        $positiveTask = collect($uraianTugas)->first(function ($task) {
            return (float) ($task['jumlah'] ?? 0) > 0;
        });

        $fallbackVolumeLabel = $this->formatLampiranVolumeLabel(
            $positiveTask['volume'] ?? null,
            $positiveTask['satuan'] ?? null
        );

        $metrics = $this->resolveSensusEkonomiFrameVolumeMetrics($allAlokasi, $alokasi);
        $selectedRows = $metrics['selected_rows'];
        $frameMuatanTotals = $metrics['frame_muatan_totals'];

        $periodeMulai = $periode?->tanggal_mulai;
        $periodeSelesai = $periode?->tanggal_selesai;

        $terminSatuAmount = $this->calculateLampiranMilestoneAmount($totalHonor, 0.40);
        $terminDuaAmount = round($totalHonor - $terminSatuAmount, 2);

        $terminSatuMetrics = $this->calculateSensusEkonomiMilestoneMetrics($selectedRows, $frameMuatanTotals, 40);
        $terminDuaMetrics = $this->calculateSensusEkonomiMilestoneMetrics($selectedRows, $frameMuatanTotals, 60);

        $terminSatuVolume = $this->formatSensusEkonomiVolumeNarrative($terminSatuMetrics['selected_rows']);
        $terminDuaVolume = $this->formatSensusEkonomiVolumeNarrative($terminDuaMetrics['selected_rows']);
        $totalVolumeLabel = $this->formatSensusEkonomiTotalSlsVolumeLabel($selectedRows);

        return [
            'groups' => [
                [
                    'items' => [
                        'Melakukan pendataan lapangan door to door '.$kegiatan->nama_kegiatan.' 2026 termin I',
                        'Memastikan seluruh kelengkapan dokumen hasil pendataan lapangan door to door '.$kegiatan->nama_kegiatan.' 2026',
                    ],
                    'waktu_penyelesaian' => 'Minimal 1 bulan',
                    'persentase' => '40%',
                    'volume' => $terminSatuVolume,
                    'nilai_perjanjian' => $terminSatuAmount,
                ],
                [
                    'items' => [
                        'Melakukan pendataan lapangan door to door '.$kegiatan->nama_kegiatan.' 2026 termin II',
                        'Memastikan seluruh kelengkapan dokumen hasil pendataan lapangan door to door '.$kegiatan->nama_kegiatan.' 2026',
                    ],
                    'waktu_penyelesaian' => $this->formatLampiranDate($periodeSelesai),
                    'persentase' => '60%',
                    'volume' => $terminDuaVolume,
                    'nilai_perjanjian' => $terminDuaAmount,
                ],
            ],
            'total' => [
                'waktu_penyelesaian' => $this->formatLampiranDateRange($periodeMulai, $periodeSelesai),
                'persentase' => '100%',
                'volume' => $totalVolumeLabel,
                'nilai_perjanjian' => $totalHonor,
            ],
            'wilayah_kerja' => $alokasi instanceof AlokasiPetugas
                ? $this->buildWilayahKerjaList($alokasi)
                : [],
        ];
    }

    private function buildPmlSensusEkonomiLampiranPayload(
        mixed $periode,
        array $uraianTugas,
        float $totalHonor,
        Kegiatan $kegiatan,
        mixed $allAlokasi = null,
        mixed $alokasi = null,
    ): array {
        $metrics = $this->resolveSensusEkonomiFrameVolumeMetrics($allAlokasi, $alokasi);
        $selectedRows = $metrics['selected_rows'];
        $frameMuatanTotals = $metrics['frame_muatan_totals'];

        $periodeMulai = $periode?->tanggal_mulai;
        $periodeSelesai = $periode?->tanggal_selesai;

        $terminSatuAmount = $this->calculateLampiranMilestoneAmount($totalHonor, 0.40);
        $terminDuaAmount = round($totalHonor - $terminSatuAmount, 2);

        $defaultTermOneSelectedRows = $this->calculateSensusEkonomiTermSelectedRows($selectedRows, $frameMuatanTotals);
        $terminSatuSelectedRows = $this->resolvePmlSensusEkonomiTermOneSelectedRows(
            $allAlokasi,
            $alokasi,
            $selectedRows,
            $defaultTermOneSelectedRows,
        );

        $terminSatuMetrics = [
            'selected_rows' => $terminSatuSelectedRows,
        ];

        $terminDuaMetrics = [
            'selected_rows' => max(0, $selectedRows - $terminSatuSelectedRows),
        ];

        $terminSatuVolume = $this->formatSensusEkonomiVolumeNarrative($terminSatuMetrics['selected_rows']);
        $terminDuaVolume = $this->formatSensusEkonomiVolumeNarrative($terminDuaMetrics['selected_rows']);
        $totalVolumeLabel = $this->formatSensusEkonomiTotalSlsVolumeLabel($selectedRows);

        $wilayahKerja = $alokasi instanceof AlokasiPetugas
            ? $this->buildWilayahKerjaList($alokasi)
            : [];

        return [
            'groups' => [
                [
                    'items' => [
                        'Melakukan pemeriksaan hasil pendataan Petugas Lapangan door to door '.$kegiatan->nama_kegiatan.' 2026 termin I',
                        'Memastikan seluruh kelengkapan dokumen hasil pendataan Petugas Lapangan door to door '.$kegiatan->nama_kegiatan.' 2026',
                    ],
                    'waktu_penyelesaian' => 'Minimal 1 bulan',
                    'persentase' => '40%',
                    'volume' => $terminSatuVolume,
                    'nilai_perjanjian' => $terminSatuAmount,
                ],
                [
                    'items' => [
                        'Melakukan pemeriksaan hasil pendataan Petugas Lapangan door to door '.$kegiatan->nama_kegiatan.' 2026 termin II',
                        'Memastikan seluruh kelengkapan dokumen hasil pendataan Petugas Lapangan door to door '.$kegiatan->nama_kegiatan.' 2026',
                    ],
                    'waktu_penyelesaian' => $this->formatLampiranDate($periodeSelesai),
                    'persentase' => '60%',
                    'volume' => $terminDuaVolume,
                    'nilai_perjanjian' => $terminDuaAmount,
                ],
            ],
            'total' => [
                'waktu_penyelesaian' => $this->formatLampiranDateRange($periodeMulai, $periodeSelesai),
                'persentase' => '100%',
                'volume' => $totalVolumeLabel,
                'nilai_perjanjian' => $totalHonor,
            ],
            'wilayah_kerja' => $wilayahKerja,
        ];
    }

    private function resolvePmlSensusEkonomiTermOneSelectedRows(
        mixed $allAlokasi,
        mixed $alokasi,
        int $pmlSelectedRows,
        int $defaultTermOneSelectedRows,
    ): int {
        $pmlSelectedRows = max(0, $pmlSelectedRows);
        $defaultTermOneSelectedRows = max(0, min($pmlSelectedRows, $defaultTermOneSelectedRows));

        $alokasiCollection = collect();

        if ($allAlokasi instanceof Collection) {
            $alokasiCollection = $allAlokasi
                ->filter(fn (mixed $item): bool => $item instanceof AlokasiPetugas)
                ->values();
        }

        if ($alokasiCollection->isEmpty() && $alokasi instanceof AlokasiPetugas) {
            $alokasiCollection = collect([$alokasi]);
        }

        if ($alokasiCollection->isEmpty()) {
            return $defaultTermOneSelectedRows;
        }

        /** @var AlokasiPetugas|null $pmlAlokasi */
        $pmlAlokasi = $alokasiCollection->first(function (AlokasiPetugas $item): bool {
            return mb_strtolower((string) $item->peran) === 'pml';
        });

        if (! $pmlAlokasi instanceof AlokasiPetugas && $alokasi instanceof AlokasiPetugas && mb_strtolower((string) $alokasi->peran) === 'pml') {
            $pmlAlokasi = $alokasi;
        }

        if (! $pmlAlokasi instanceof AlokasiPetugas) {
            return $defaultTermOneSelectedRows;
        }

        $pmlAlokasi->loadMissing('frameSampelAllocations.kegiatanFrameSampel');

        $pmlFrameIds = $pmlAlokasi->frameSampelAllocations
            ->pluck('kegiatan_frame_sampel_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($pmlFrameIds)) {
            return $defaultTermOneSelectedRows;
        }

        $pendataanRoles = ['pcl_ppl', 'pcl', 'ppl'];
        $periodeAlokasiId = (int) ($pmlAlokasi->periode_alokasi_id ?? 0);

        $linkedPplAlokasi = $alokasiCollection
            ->filter(function (AlokasiPetugas $item) use ($pendataanRoles, $periodeAlokasiId): bool {
                if (! in_array(mb_strtolower((string) $item->peran), $pendataanRoles, true)) {
                    return false;
                }

                if ($periodeAlokasiId > 0 && (int) ($item->periode_alokasi_id ?? 0) !== $periodeAlokasiId) {
                    return false;
                }

                return true;
            })
            ->values();

        if ($linkedPplAlokasi->isEmpty() && $periodeAlokasiId > 0) {
            $linkedPplAlokasi = AlokasiPetugas::query()
                ->where('periode_alokasi_id', $periodeAlokasiId)
                ->whereIn('peran', $pendataanRoles)
                ->with('frameSampelAllocations.kegiatanFrameSampel')
                ->get();
        }

        if ($linkedPplAlokasi->isEmpty()) {
            return $defaultTermOneSelectedRows;
        }

        $aggregatedTermOneSelectedRows = $linkedPplAlokasi->sum(function (AlokasiPetugas $pplAlokasi) use ($pmlFrameIds): int {
            $pplAlokasi->loadMissing('frameSampelAllocations.kegiatanFrameSampel');

            $matchingFrames = $pplAlokasi->frameSampelAllocations
                ->filter(function ($frameAllocation) use ($pmlFrameIds): bool {
                    return in_array((int) ($frameAllocation?->kegiatan_frame_sampel_id ?? 0), $pmlFrameIds, true);
                })
                ->unique('kegiatan_frame_sampel_id')
                ->values();

            $selectedRows = $matchingFrames->count();

            if ($selectedRows <= 0) {
                return 0;
            }

            $frameMuatanTotals = $matchingFrames
                ->map(function ($frameAllocation): int {
                    $targetUnitSampel = $frameAllocation?->kegiatanFrameSampel?->target_unit_sampel;

                    if (is_array($targetUnitSampel)) {
                        return (int) collect($targetUnitSampel)
                            ->map(fn ($value): int => max(0, (int) $value))
                            ->sum();
                    }

                    if (is_numeric($targetUnitSampel)) {
                        return max(0, (int) $targetUnitSampel);
                    }

                    return 0;
                })
                ->all();

            return $this->calculateSensusEkonomiTermSelectedRows($selectedRows, $frameMuatanTotals);
        });

        if ($aggregatedTermOneSelectedRows <= 0) {
            return $defaultTermOneSelectedRows;
        }

        return min($pmlSelectedRows, $aggregatedTermOneSelectedRows);
    }

    private function buildWilayahKerjaList(AlokasiPetugas $pmlAlokasi): array
    {
        $pmlAlokasi->loadMissing('frameSampelAllocations.kegiatanFrameSampel');
        $frames = $pmlAlokasi->frameSampelAllocations;

        if ($frames->isEmpty()) {
            return [];
        }

        $unitIds = $frames
            ->map(function ($frame): array {
                $targetUnitSampel = $frame->kegiatanFrameSampel?->target_unit_sampel;

                return is_array($targetUnitSampel)
                    ? array_values(array_map('intval', array_keys($targetUnitSampel)))
                    : [];
            })
            ->flatten()
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $unitNameById = ! empty($unitIds)
            ? MasterUnitSampel::query()
                ->whereIn('id', $unitIds)
                ->pluck('nama', 'id')
                ->map(fn ($name) => mb_strtolower(trim((string) $name)))
                ->toArray()
            : [];

        /** @var array<string, array{kdkec:string,kdkec_label:string,kddes:string,kddes_label:string,count:int,prelist_usaha:int,prelist_keluarga:int}> $grouped */
        $grouped = [];

        foreach ($frames as $frame) {
            $kfs = $frame->kegiatanFrameSampel;

            if (! $kfs) {
                continue;
            }

            $identitas = is_array($kfs->identitas_tambahan) ? $kfs->identitas_tambahan : [];
            $kdkec = $identitas['kdkec'] ?? $kfs->kode_kecamatan ?? '';
            $kdkecLabel = $identitas['kdkec_label'] ?? $kdkec;
            $kddes = $identitas['kddes'] ?? $kfs->kode_desa ?? '';
            $kddesLabel = $identitas['kddes_label'] ?? $kddes;

            $key = $kdkec.'_'.$kddes;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'kdkec' => $kdkec,
                    'kdkec_label' => $kdkecLabel,
                    'kddes' => $kddes,
                    'kddes_label' => $kddesLabel,
                    'count' => 0,
                    'prelist_usaha' => 0,
                    'prelist_keluarga' => 0,
                ];
            }

            $grouped[$key]['count']++;

            $targetUnitSampel = is_array($kfs->target_unit_sampel) ? $kfs->target_unit_sampel : [];

            foreach ($targetUnitSampel as $unitId => $total) {
                $totalValue = max(0, (int) $total);

                if ($totalValue === 0) {
                    continue;
                }

                $normalizedUnitName = '';

                if (is_numeric($unitId) && (int) $unitId > 0) {
                    $normalizedUnitName = $unitNameById[(int) $unitId] ?? '';
                } else {
                    $normalizedUnitName = mb_strtolower(trim((string) $unitId));
                }

                if (str_contains($normalizedUnitName, 'usaha')) {
                    $grouped[$key]['prelist_usaha'] += $totalValue;
                }

                if (str_contains($normalizedUnitName, 'keluarga') || str_contains($normalizedUnitName, 'rumah tangga')) {
                    $grouped[$key]['prelist_keluarga'] += $totalValue;
                }
            }
        }

        $result = [];
        $no = 1;

        foreach ($grouped as $entry) {
            $result[] = [
                'no' => $no++,
                'kecamatan' => '['.$entry['kdkec'].'] '.$entry['kdkec_label'],
                'desa' => '['.$entry['kddes'].'] '.$entry['kddes_label'],
                'jumlah_sls' => $entry['count'],
                'muatan_prelist' => $entry['prelist_usaha'].' usaha dan '.$entry['prelist_keluarga'].' keluarga',
            ];
        }

        return $result;
    }

    private function calculateSensusEkonomiMilestoneMetrics(int $selectedRows, array $frameMuatanTotals, int $percentage): array
    {
        $selectedRows = max(0, $selectedRows);

        $terminSatuSelectedRows = $this->calculateSensusEkonomiTermSelectedRows($selectedRows, $frameMuatanTotals);

        if ($percentage === 40) {
            return [
                'selected_rows' => $terminSatuSelectedRows,
            ];
        }

        return [
            'selected_rows' => max(0, $selectedRows - $terminSatuSelectedRows),
        ];
    }

    private function calculateSensusEkonomiTermSelectedRows(int $selectedRows, array $frameMuatanTotals): int
    {
        $selectedRows = max(0, $selectedRows);
        $frameMuatanTotals = array_values(array_filter(
            array_map(static fn ($value): int => max(0, (int) $value), $frameMuatanTotals),
            static fn (int $value): bool => $value > 0,
        ));

        if ($selectedRows === 0) {
            return 0;
        }

        if (empty($frameMuatanTotals)) {
            return (int) ceil($selectedRows * 0.4);
        }

        $totalMuatan = array_sum($frameMuatanTotals);

        if ($totalMuatan <= 0) {
            return (int) ceil($selectedRows * 0.4);
        }

        $threshold = (int) ceil($totalMuatan * 0.4);
        rsort($frameMuatanTotals, SORT_NUMERIC);

        $accumulatedMuatan = 0;
        $count = 0;

        foreach ($frameMuatanTotals as $frameMuatan) {
            $accumulatedMuatan += $frameMuatan;
            $count++;

            if ($accumulatedMuatan >= $threshold) {
                break;
            }
        }

        return max(1, min($selectedRows, $count));
    }

    private function resolveSensusEkonomiFrameVolumeMetrics(mixed $allAlokasi, mixed $alokasi): array
    {
        $alokasiCollection = collect();

        if ($allAlokasi instanceof Collection) {
            $alokasiCollection = $allAlokasi->filter(fn (mixed $item): bool => $item instanceof AlokasiPetugas)->values();
        }

        if ($alokasiCollection->isEmpty() && $alokasi instanceof AlokasiPetugas) {
            $alokasiCollection = collect([$alokasi]);
        }

        if ($alokasiCollection->isEmpty()) {
            return [
                'selected_rows' => 0,
                'prelist_total' => 0,
                'total_volume' => 0,
                'frame_muatan_totals' => [],
                'narrative' => '-',
            ];
        }

        $alokasiCollection->each(function (AlokasiPetugas $alokasiPetugas): void {
            $alokasiPetugas->loadMissing('frameSampelAllocations.kegiatanFrameSampel');
        });

        $frameAllocations = $alokasiCollection
            ->flatMap(function (AlokasiPetugas $alokasiPetugas): array {
                return $alokasiPetugas->frameSampelAllocations->all();
            })
            ->filter(fn (mixed $allocation): bool => $allocation !== null)
            ->unique('kegiatan_frame_sampel_id')
            ->values();

        $selectedRows = $frameAllocations->count();

        $perUnitSampelTotals = [];
        $frameMuatanTotals = [];
        foreach ($frameAllocations as $frameAllocation) {
            $targetUnitSampel = $frameAllocation?->kegiatanFrameSampel?->target_unit_sampel;
            $frameMuatanTotal = 0;

            if (is_array($targetUnitSampel)) {
                foreach ($targetUnitSampel as $unitSampelId => $count) {
                    $uid = (int) $unitSampelId;
                    $countValue = max(0, (int) $count);
                    $perUnitSampelTotals[$uid] = ($perUnitSampelTotals[$uid] ?? 0) + $countValue;
                    $frameMuatanTotal += $countValue;
                }
            } elseif (is_numeric($targetUnitSampel) && (int) $targetUnitSampel > 0) {
                $targetValue = (int) $targetUnitSampel;
                $perUnitSampelTotals[0] = ($perUnitSampelTotals[0] ?? 0) + $targetValue;
                $frameMuatanTotal += $targetValue;
            }

            $frameMuatanTotals[] = $frameMuatanTotal;
        }

        $unitSampelIds = array_values(array_filter(array_keys($perUnitSampelTotals), fn ($id) => $id > 0));
        $unitSampelNames = ! empty($unitSampelIds)
            ? MasterUnitSampel::query()->whereIn('id', $unitSampelIds)->pluck('nama', 'id')->toArray()
            : [];

        $prelistTotal = array_sum($perUnitSampelTotals);
        $totalVolume = $selectedRows + $prelistTotal;

        return [
            'selected_rows' => $selectedRows,
            'prelist_total' => $prelistTotal,
            'per_unit_sampel_totals' => $perUnitSampelTotals,
            'unit_sampel_names' => $unitSampelNames,
            'frame_muatan_totals' => $frameMuatanTotals,
            'total_volume' => $totalVolume,
            'narrative' => $this->formatSensusEkonomiVolumeNarrative($selectedRows),
        ];
    }

    private function formatSensusEkonomiVolumeNarrative(int $selectedRows): string
    {
        if ($selectedRows > 0) {
            return number_format($selectedRows, 0, ',', '.').' SLS/sub-SLS';
        }

        return '-';
    }

    private function formatSensusEkonomiTotalSlsVolumeLabel(int $selectedRows): string
    {
        if ($selectedRows <= 0) {
            return '-';
        }

        return 'Seluruh Muatan '.number_format($selectedRows, 0, ',', '.').' SLS/sub-SLS';
    }
}
