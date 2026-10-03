<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\BappSeTermin;
use App\Models\Bast;
use App\Models\BastKegiatan;
use App\Models\BastNumberAllocation;
use App\Models\BastPetugas;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use App\Services\PdfMergerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\Tcpdf\Fpdi;

trait BastSensusSupport
{
    private function supportsSensusPetugasColumns(): bool
    {
        return Schema::hasColumn('bast_petugas', 'muatan_input')
            && Schema::hasColumn('bast_petugas', 'muatan_prelist')
            && Schema::hasColumn('bast_petugas', 'realisasi_unit_sampel')
            && Schema::hasColumn('bast_petugas', 'fasih_screenshot_path');
    }

    private function supportsLampiranFasihScreenshotColumns(): bool
    {
        return Schema::hasColumn('bast_kegiatan', 'fasih_screenshot_path')
            && Schema::hasColumn('bast_kegiatan', 'fasih_screenshot_uploaded_at');
    }

    private function shouldUseLampiranFasihScreenshot(?string $kegiatanName, ?string $peran): bool
    {
        if (! $this->isSensusEkonomiName($kegiatanName)) {
            return false;
        }

        return in_array((string) $peran, self::PENDATAAN_ROLES, true);
    }

    private function mergeLampiranScreenshotPathsIntoKegiatanList(array $kegiatanList, iterable $records): array
    {
        if (! $this->supportsLampiranFasihScreenshotColumns() || empty($kegiatanList)) {
            return $kegiatanList;
        }

        $recordMap = collect($records)
            ->filter(fn ($record) => $record instanceof BastKegiatan)
            ->keyBy(fn (BastKegiatan $record) => $this->makeBastKegiatanKey((int) $record->kegiatan_id, (int) $record->periode_alokasi_id));

        return collect($kegiatanList)
            ->map(function (array $item) use ($recordMap) {
                $record = $recordMap->get(
                    $this->makeBastKegiatanKey((int) ($item['kegiatan_id'] ?? 0), (int) ($item['periode_alokasi_id'] ?? 0))
                );

                if ($record) {
                    $item['fasih_screenshot_path'] = $record->fasih_screenshot_path;
                }

                return $item;
            })
            ->all();
    }

    private function mergeSharedSensusScreenshotIntoKegiatanList(array $kegiatanList, ?string $fasihScreenshotPath): array
    {
        if (empty($kegiatanList)) {
            return $kegiatanList;
        }

        return collect($kegiatanList)
            ->map(function (array $item) use ($fasihScreenshotPath) {
                if ($this->shouldUseLampiranFasihScreenshot($item['nama_kegiatan'] ?? null, $item['peran'] ?? null)) {
                    $item['fasih_screenshot_path'] = $fasihScreenshotPath;
                }

                return $item;
            })
            ->all();
    }

    private function normalizeRealisasiUnitSampelValues(array $values): array
    {
        return collect($values)
            ->filter(fn ($value, $key) => ($key !== null && $key !== '') && $value !== null && $value !== '')
            ->mapWithKeys(function ($value, $key): array {
                if (! is_numeric($value)) {
                    return [];
                }

                return [
                    (string) $key => max(0, (int) round((float) $value)),
                ];
            })
            ->all();
    }

    private function sumRealisasiUnitSampelValues(array $values): ?int
    {
        if ($values === []) {
            return null;
        }

        return (int) array_sum($values);
    }

    private function getUnitSampelPencacahanItemsForSpk(?Spk $spk): Collection
    {
        if (! $spk?->alokasiPetugas) {
            return collect();
        }

        $spk->loadMissing('alokasiPetugas.periodeAlokasi.kegiatan');

        $alokasiPetugasIds = collect($spk->alokasi_petugas_ids)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values();

        $kegiatan = collect([$spk->alokasiPetugas?->periodeAlokasi?->kegiatan])
            ->filter();

        if ($alokasiPetugasIds->isNotEmpty()) {
            $kegiatan = $kegiatan->merge(
                AlokasiPetugas::query()
                    ->with('periodeAlokasi.kegiatan')
                    ->whereIn('id', $alokasiPetugasIds->all())
                    ->get()
                    ->map(fn (AlokasiPetugas $alokasi) => $alokasi->periodeAlokasi?->kegiatan)
                    ->filter()
            );
        }

        return $kegiatan
            ->unique('id')
            ->flatMap(function (Kegiatan $kegiatan) {
                return $kegiatan->unitSampelPencacahanItems()
                    ->map(fn ($unit) => ['id' => (int) $unit->id, 'nama' => (string) $unit->nama])
                    ->values();
            })
            ->unique('id')
            ->values();
    }

    private function buildSensusReferencePayload(?Spk $spk, int $bulan, int $tahun, ?BastPetugas $bastPetugas = null): ?array
    {
        if (! $spk) {
            return null;
        }

        // For SE, derive realisasi + screenshot from BAPP Termin I+II instead of manual input
        $bappData = $this->getBappSeTerminDataForSpk((int) $spk->id);
        $realisasiUnitSampel = $bappData['realisasi_unit_sampel'];
        $fasihScreenshotPath = $bappData['fasih_screenshot_path'];
        $targetSls = $bappData['target_sls'];
        $terminIIComplete = $bappData['termin_ii_complete'];

        $muatanPrelistKeluarga = (int) ($spk->muatan_prelist_keluarga_default ?? 0);
        $muatanPrelistUsaha = (int) ($spk->muatan_prelist_usaha_default ?? 0);

        $petugasId = (int) ($spk->alokasiPetugas?->petugas_id ?? 0);
        if ($petugasId > 0) {
            $allSensusAlokasi = $this->getSensusEkonomiAlokasiForPetugasInYear($petugasId, $tahun);
            if ($allSensusAlokasi->isNotEmpty()) {
                $prelistBreakdown = $this->calculateSensusPrelistBreakdown($allSensusAlokasi);

                $muatanPrelistKeluarga = (int) ($prelistBreakdown['keluarga'] ?? $muatanPrelistKeluarga);
                $muatanPrelistUsaha = (int) ($prelistBreakdown['usaha'] ?? $muatanPrelistUsaha);
            }
        }

        $unitItems = $this->getUnitSampelPencacahanItemsForSpk($spk);

        if ($unitItems->isEmpty()) {
            $fallbackUnitKeys = collect(array_keys(is_array($realisasiUnitSampel) ? $realisasiUnitSampel : []))
                ->map(fn ($key) => trim((string) $key))
                ->filter(fn (string $key) => $key !== '')
                ->values();

            if ($fallbackUnitKeys->isEmpty()) {
                $fallbackUnitKeys = collect(['keluarga', 'usaha']);
            }

            if ($muatanPrelistKeluarga > 0 && ! $fallbackUnitKeys->contains(fn (string $key) => str_contains($key, 'keluarga') || str_contains($key, 'rumah_tangga'))) {
                $fallbackUnitKeys->push('keluarga');
            }

            if ($muatanPrelistUsaha > 0 && ! $fallbackUnitKeys->contains(fn (string $key) => str_contains($key, 'usaha'))) {
                $fallbackUnitKeys->push('usaha');
            }

            $unitItems = $fallbackUnitKeys
                ->unique()
                ->values()
                ->map(function (string $unitKey, int $index): array {
                    $label = str_replace('_', ' ', $unitKey);

                    return [
                        'id' => $index + 1,
                        'nama' => ucwords($label),
                    ];
                });
        }

        $muatanPrelistByUnit = $unitItems
            ->mapWithKeys(function (array $unit) use ($muatanPrelistKeluarga, $muatanPrelistUsaha): array {
                $unitName = mb_strtolower(trim((string) ($unit['nama'] ?? '')));
                $unitKey = preg_replace('/\s+/', '_', $unitName ?? '') ?? '';

                if ($unitKey === '') {
                    return [];
                }

                if (str_contains($unitKey, 'usaha')) {
                    return [$unitKey => $muatanPrelistUsaha];
                }

                if (str_contains($unitKey, 'keluarga') || str_contains($unitKey, 'rumah_tangga')) {
                    return [$unitKey => $muatanPrelistKeluarga];
                }

                return [$unitKey => null];
            })
            ->all();

        return [
            'spk_id' => (int) $spk->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'unit_sampel_pencacahan_items' => $unitItems->all(),
            'realisasi_unit_sampel' => $realisasiUnitSampel,
            'muatan_input' => $this->sumRealisasiUnitSampelValues($realisasiUnitSampel),
            'muatan_prelist' => $bastPetugas?->muatan_prelist ?? ($muatanPrelistKeluarga + $muatanPrelistUsaha),
            'muatan_prelist_unit_sampel' => $muatanPrelistByUnit,
            'target_sls' => $targetSls,
            'fasih_screenshot_path' => $fasihScreenshotPath,
            'fasih_screenshot_uploaded_at' => null,
            'bapp_termin_ii_complete' => $terminIIComplete,
        ];
    }

    private function resolveSensusPreviewInput(Spk $spk, Request $request): ?array
    {
        if (! $this->isSensusEkonomiSpk($spk)) {
            return null;
        }

        // Use BAPP Termin I+II data as the canonical source for SE preview
        $bappData = $this->getBappSeTerminDataForSpk((int) $spk->id);
        $realisasiUnitSampel = $bappData['realisasi_unit_sampel'];

        if (empty($realisasiUnitSampel)) {
            abort(422, 'Data realisasi dari BAPP Termin I dan II belum lengkap. Selesaikan BAPP Termin II terlebih dahulu.');
        }

        $muatanInput = $this->sumRealisasiUnitSampelValues($realisasiUnitSampel);
        $muatanPrelist = (int) ($spk->muatan_prelist_keluarga_default ?? 0) + (int) ($spk->muatan_prelist_usaha_default ?? 0);

        return [
            'muatan_input' => $muatanInput,
            'muatan_prelist' => $muatanPrelist,
            'realisasi_unit_sampel' => $realisasiUnitSampel,
        ];
    }

    private function buildSensusEkonomiNarrativeData(Collection $alokasiCollection, ?array $seInput = null): array
    {
        $prelistBreakdown = $this->calculateSensusPrelistBreakdown($alokasiCollection);
        $targetJumlahFrameSampel = (int) $alokasiCollection->sum(function (AlokasiPetugas $alokasi): int {
            return (int) ($alokasi->jumlah_frame_sampel ?? 0);
        });

        $hasilJumlahFrameSampel = data_get($seInput, 'hasil_jumlah_frame_sampel');

        if (! is_numeric($hasilJumlahFrameSampel)) {
            $hasilJumlahFrameSampel = data_get($seInput, 'realisasi_jumlah_frame_sampel');
        }

        if (! is_numeric($hasilJumlahFrameSampel)) {
            $hasilJumlahFrameSampel = data_get($seInput, 'jumlah_frame_sampel');
        }

        $hasilRealisasiKeluarga = data_get($seInput, 'realisasi_unit_sampel.keluarga');
        $hasilRealisasiUsaha = data_get($seInput, 'realisasi_unit_sampel.usaha');

        return [
            'target_jumlah_frame_sampel' => is_numeric($targetJumlahFrameSampel) && (int) $targetJumlahFrameSampel > 0
                ? (int) $targetJumlahFrameSampel
                : null,
            'target_muatan_prelist_keluarga' => (int) ($prelistBreakdown['keluarga'] ?? 0) > 0
                ? (int) $prelistBreakdown['keluarga']
                : null,
            'target_muatan_prelist_usaha' => (int) ($prelistBreakdown['usaha'] ?? 0) > 0
                ? (int) $prelistBreakdown['usaha']
                : null,
            'hasil_jumlah_frame_sampel' => is_numeric($hasilJumlahFrameSampel) && (int) $hasilJumlahFrameSampel > 0
                ? (int) $hasilJumlahFrameSampel
                : null,
            'hasil_realisasi_keluarga' => is_numeric($hasilRealisasiKeluarga) && (int) $hasilRealisasiKeluarga > 0
                ? (int) $hasilRealisasiKeluarga
                : null,
            'hasil_realisasi_usaha' => is_numeric($hasilRealisasiUsaha) && (int) $hasilRealisasiUsaha > 0
                ? (int) $hasilRealisasiUsaha
                : null,
        ];
    }

    private function formatSensusUsahaKeluargaVolume(?int $usaha, ?int $keluarga): string
    {
        $parts = [];

        if (($usaha ?? 0) > 0) {
            $parts[] = number_format((int) $usaha, 0, ',', '.').' usaha';
        }

        if (($keluarga ?? 0) > 0) {
            $parts[] = number_format((int) $keluarga, 0, ',', '.').' keluarga';
        }

        if ($parts === []) {
            return '-';
        }

        return implode('/', $parts);
    }

    private function buildSensusLampiranWilayahKerja(AlokasiPetugas $alokasi): array
    {
        $frames = $alokasi->frameSampelAllocations
            ->map(fn ($allocation) => $allocation->kegiatanFrameSampel)
            ->filter();

        if ($frames->isEmpty()) {
            return [];
        }

        $unitIds = $frames
            ->flatMap(function ($frame): array {
                $targets = is_array($frame?->target_unit_sampel) ? $frame->target_unit_sampel : [];

                return array_values(array_filter(array_map(static function ($key) {
                    return is_numeric($key) ? (int) $key : 0;
                }, array_keys($targets))));
            })
            ->filter(fn ($unitId) => (int) $unitId > 0)
            ->unique()
            ->values();

        $unitNameById = $unitIds->isNotEmpty()
            ? MasterUnitSampel::query()
                ->whereIn('id', $unitIds->all())
                ->pluck('nama', 'id')
                ->map(fn ($name) => mb_strtolower(trim((string) $name)))
                ->toArray()
            : [];

        $rows = [];

        foreach ($frames as $frame) {
            $identitas = is_array($frame->identitas_tambahan) ? $frame->identitas_tambahan : [];
            $kodeKecamatan = (string) ($identitas['kdkec'] ?? $frame->kode_kecamatan ?? '-');
            $namaKecamatan = (string) ($identitas['kdkec_label'] ?? $identitas['nama_kecamatan'] ?? $kodeKecamatan);
            $kodeDesa = (string) ($identitas['kddes'] ?? $frame->kode_desa ?? '-');
            $namaDesa = (string) ($identitas['kddes_label'] ?? $identitas['nama_desa'] ?? $identitas['nama_kelurahan'] ?? $kodeDesa);

            $key = implode('|', [
                $kodeKecamatan,
                $kodeDesa,
            ]);

            if (! isset($rows[$key])) {
                $rows[$key] = [
                    'nama_kecamatan' => $namaKecamatan,
                    'nama_desa' => $namaDesa,
                    'jumlah_sls' => 0,
                    'usaha' => 0,
                    'keluarga' => 0,
                ];
            }

            $rows[$key]['jumlah_sls']++;

            foreach ((array) ($frame->target_unit_sampel ?? []) as $unitKey => $targetValue) {
                $target = max(0, (int) $targetValue);
                if ($target === 0) {
                    continue;
                }

                $normalizedUnitName = '';

                if (is_numeric($unitKey) && (int) $unitKey > 0) {
                    $normalizedUnitName = $unitNameById[(int) $unitKey] ?? '';
                } else {
                    $normalizedUnitName = mb_strtolower(trim((string) $unitKey));
                }

                if (str_contains($normalizedUnitName, 'usaha')) {
                    $rows[$key]['usaha'] += $target;
                }

                if (str_contains($normalizedUnitName, 'keluarga') || str_contains($normalizedUnitName, 'rumah tangga')) {
                    $rows[$key]['keluarga'] += $target;
                }
            }
        }

        return collect(array_values($rows))
            ->values()
            ->map(function (array $row, int $index): array {
                return [
                    'no' => $index + 1,
                    'nama_kecamatan' => $row['nama_kecamatan'] !== '' ? $row['nama_kecamatan'] : '-',
                    'nama_desa' => $row['nama_desa'] !== '' ? $row['nama_desa'] : '-',
                    'jumlah_sls' => number_format((int) $row['jumlah_sls'], 0, ',', '.'),
                    'muatan_label' => $this->formatSensusUsahaKeluargaVolume(
                        (int) $row['usaha'],
                        (int) $row['keluarga']
                    ),
                ];
            })
            ->all();
    }

    private function getSensusEkonomiAlokasiForPetugasInYear(int $petugasId, int $tahun): Collection
    {
        $allAlokasi = AlokasiPetugas::query()
            ->where('petugas_id', $petugasId)
            ->whereHas('periodeAlokasi', function ($q) use ($tahun) {
                $q->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
                    ->whereHas('kegiatan', function ($kegiatanQuery) {
                        $kegiatanQuery->where('nama_kegiatan', 'like', '%Sensus Ekonomi%');
                    });
            })
            ->where(function ($query) {
                $query->where('total_honor', '>', 0)
                    ->orWhere('total_honor_listing', '>', 0)
                    ->orWhere('jumlah_satuan', '>', 0)
                    ->orWhere('jumlah_satuan_listing', '>', 0);
            })
            ->with([
                'periodeAlokasi:id,kegiatan_id,status,created_at,tanggal_selesai,tanggal_selesai_listing,jadwal_pengolahan_listing_selesai,jadwal_pengolahan_pencacahan_selesai',
                'periodeAlokasi.kegiatan',
                'frameSampelAllocations.kegiatanFrameSampel',
                'spk' => function ($query) {
                    $query->orderByDesc('addendum_number');
                },
            ])
            ->get();

        return $this->getEffectiveAlokasiByKegiatan($allAlokasi)->values();
    }

    private function calculateSensusPrelistBreakdown(Collection $alokasiCollection): array
    {
        if ($alokasiCollection->isEmpty()) {
            return [
                'keluarga' => 0,
                'usaha' => 0,
                'total' => 0,
            ];
        }

        $unitIds = $alokasiCollection
            ->flatMap(function (AlokasiPetugas $alokasi): Collection {
                return $alokasi->frameSampelAllocations
                    ->map(fn ($allocation) => $allocation->kegiatanFrameSampel?->target_unit_sampel)
                    ->filter(fn ($target) => is_array($target))
                    ->flatMap(function (array $target): array {
                        return array_values(array_filter(array_map(function ($key) {
                            return is_numeric($key) ? (int) $key : 0;
                        }, array_keys($target))));
                    });
            })
            ->filter(fn ($unitId) => (int) $unitId > 0)
            ->unique()
            ->values();

        $unitNameById = $unitIds->isNotEmpty()
            ? MasterUnitSampel::query()
                ->whereIn('id', $unitIds->all())
                ->pluck('nama', 'id')
                ->map(fn ($name) => mb_strtolower(trim((string) $name)))
                ->toArray()
            : [];

        $totalKeluarga = 0;
        $totalUsaha = 0;

        foreach ($alokasiCollection as $alokasi) {
            foreach ($alokasi->frameSampelAllocations as $allocation) {
                $targetUnitSampel = $allocation->kegiatanFrameSampel?->target_unit_sampel;
                if (! is_array($targetUnitSampel)) {
                    continue;
                }

                foreach ($targetUnitSampel as $unitKey => $targetValue) {
                    $target = max(0, (int) $targetValue);
                    if ($target === 0) {
                        continue;
                    }

                    $normalizedUnitName = '';
                    if (is_numeric($unitKey) && (int) $unitKey > 0) {
                        $normalizedUnitName = $unitNameById[(int) $unitKey] ?? '';
                    } else {
                        $normalizedUnitName = mb_strtolower(trim((string) $unitKey));
                    }

                    if (str_contains($normalizedUnitName, 'usaha')) {
                        $totalUsaha += $target;
                    }

                    if (str_contains($normalizedUnitName, 'keluarga') || str_contains($normalizedUnitName, 'rumah tangga')) {
                        $totalKeluarga += $target;
                    }
                }
            }
        }

        return [
            'keluarga' => $totalKeluarga,
            'usaha' => $totalUsaha,
            'total' => $totalKeluarga + $totalUsaha,
        ];
    }

    private function getSensusRealisasiTemplateSpks(int $bulan, int $tahun): Collection
    {
        return Spk::query()
            ->with(['alokasiPetugas.petugas'])
            ->whereYear('tanggal_selesai_kerja', $tahun)
            ->whereMonth('tanggal_selesai_kerja', $bulan)
            ->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($query) {
                $query->where('nama_kegiatan', 'like', '%Sensus Ekonomi%');
            })
            ->orderBy('nomor_spk')
            ->get();
    }
}
