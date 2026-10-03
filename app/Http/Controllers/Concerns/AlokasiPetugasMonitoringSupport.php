<?php

namespace App\Http\Controllers\Concerns;

use App\Exports\AlokasiPetugasTemplateExport;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\StoreAlokasiPetugasRequest;
use App\Http\Requests\UpdateAlokasiPetugasRequest;
use App\Http\Requests\UpdateNonResponseRequest;
use App\Imports\AlokasiPetugasImport;
use App\Imports\AlokasiPetugasPreviewImport;
use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\AlokasiPetugasFrameSampel;
use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\ReviewPetugas;
use App\Models\Sbml;
use App\Models\Spk;
use App\Services\ActiveYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Vinkla\Hashids\Facades\Hashids;

trait AlokasiPetugasMonitoringSupport
{
    protected function buildMonitoringReportData(Kegiatan $kegiatan, PeriodeAlokasi $periode, ?Penandatangan $kepala = null): array
    {
        $isPurposiveSampling = $kegiatan->metode_sampling === Kegiatan::METODE_SAMPLING_PURPOSSIVE;
        $frameMetadataColumns = $this->extractFrameSampelMetadataColumns($kegiatan->kegiatanFrameSampel);
        $displayFrameMetadataColumns = array_values(array_filter(
            $frameMetadataColumns,
            static fn (array $column): bool => ! in_array(
                Str::lower((string) ($column['code'] ?? '')),
                ['nama_target', 'nama_frame', 'nama_usaha_penggilingan'],
                true,
            ),
        ));

        $allocatedFrames = [];
        foreach ($periode->alokasiPetugas as $alokasi) {
            foreach ($alokasi->frameSampelAllocations as $frameAllocation) {
                $frameSampleId = (int) ($frameAllocation->kegiatan_frame_sampel_id ?? 0);
                if ($frameSampleId <= 0) {
                    continue;
                }

                if (! isset($allocatedFrames[$frameSampleId])) {
                    $allocatedFrames[$frameSampleId] = [
                        'frame_allocation_ids' => [],
                        'alokasi_petugas_ids' => [],
                        'pengawas_nama' => '-',
                        'pencacah_nama' => '-',
                        'status_non_response' => false,
                        'frame_sample' => $frameAllocation->kegiatanFrameSampel,
                    ];
                }

                $roleKey = $this->resolveMonitoringRoleKey($alokasi->peran);
                $allocatedFrames[$frameSampleId][$roleKey.'_nama'] = $alokasi->petugas?->nama ?? '-';
                $allocatedFrames[$frameSampleId]['frame_allocation_ids'][] = $frameAllocation->id;
                $allocatedFrames[$frameSampleId]['alokasi_petugas_ids'][] = $alokasi->id;
                $allocatedFrames[$frameSampleId]['status_non_response'] = $allocatedFrames[$frameSampleId]['status_non_response']
                    || (bool) ($frameAllocation->is_non_response ?? false);
            }
        }

        $sortedRows = collect($allocatedFrames)
            ->map(function (array $allocatedFrame, int $frameSampleId) use ($frameMetadataColumns): array {
                /** @var KegiatanFrameSampel|null $frameSample */
                $frameSample = $allocatedFrame['frame_sample'] ?? null;

                $targetUnitSampel = is_array($frameSample?->target_unit_sampel)
                    ? $frameSample->target_unit_sampel
                    : [];

                $totalTargetUnit = array_sum(array_map('floatval', $targetUnitSampel));
                $metadataValues = [];

                foreach ($frameMetadataColumns as $column) {
                    $metadataValues[$column['code']] = $frameSample
                        ? $this->resolveFrameMetadataValuePair($frameSample, $column['code'])
                        : ['code' => '-', 'label' => '-'];
                }

                return [
                    'kegiatan_frame_sampel_id' => $frameSampleId,
                    'nama_usaha' => $this->resolveMonitoringNamaUsaha($frameSample),
                    'pengawas_nama' => $allocatedFrame['pengawas_nama'] ?? '-',
                    'pencacah_nama' => $allocatedFrame['pencacah_nama'] ?? '-',
                    'target_unit_total' => $totalTargetUnit > 0 ? $totalTargetUnit : 0,
                    'realisasi_unit_total' => (int) round($totalTargetUnit > 0 ? $totalTargetUnit : 0),
                    'persentase' => $totalTargetUnit > 0 ? 100 : 0,
                    'status_non_response' => $allocatedFrame['status_non_response'] ?? false,
                    'metadata_values' => $metadataValues,
                ];
            })
            ->sort(function (array $left, array $right): int {
                $leftSort = [
                    Str::lower(trim((string) ($left['pengawas_nama'] ?? '-'))),
                    Str::lower(trim((string) ($left['pencacah_nama'] ?? '-'))),
                    Str::lower(trim((string) ($left['nama_usaha'] ?? '-'))),
                    (int) ($left['kegiatan_frame_sampel_id'] ?? 0),
                ];

                $rightSort = [
                    Str::lower(trim((string) ($right['pengawas_nama'] ?? '-'))),
                    Str::lower(trim((string) ($right['pencacah_nama'] ?? '-'))),
                    Str::lower(trim((string) ($right['nama_usaha'] ?? '-'))),
                    (int) ($right['kegiatan_frame_sampel_id'] ?? 0),
                ];

                return $leftSort <=> $rightSort;
            })
            ->values()
            ->all();

        $rows = [];
        $totalRows = count($sortedRows);
        $index = 0;

        while ($index < $totalRows) {
            $pengawasKey = Str::lower(trim((string) ($sortedRows[$index]['pengawas_nama'] ?? '-')));
            $pengawasStart = $index;

            while ($index < $totalRows && Str::lower(trim((string) ($sortedRows[$index]['pengawas_nama'] ?? '-'))) === $pengawasKey) {
                $index++;
            }

            $pengawasEnd = $index;
            $pengawasSpan = $pengawasEnd - $pengawasStart;

            $subIndex = $pengawasStart;
            while ($subIndex < $pengawasEnd) {
                $pencacahKey = Str::lower(trim((string) ($sortedRows[$subIndex]['pencacah_nama'] ?? '-')));
                $pencacahStart = $subIndex;

                while (
                    $subIndex < $pengawasEnd
                    && Str::lower(trim((string) ($sortedRows[$subIndex]['pencacah_nama'] ?? '-'))) === $pencacahKey
                ) {
                    $subIndex++;
                }

                $pencacahEnd = $subIndex;
                $pencacahSpan = $pencacahEnd - $pencacahStart;

                for ($rowIndex = $pencacahStart; $rowIndex < $pencacahEnd; $rowIndex++) {
                    $row = $sortedRows[$rowIndex];
                    $rows[] = $row + [
                        'show_pengawas_cell' => $rowIndex === $pengawasStart,
                        'pengawas_rowspan' => $pengawasSpan,
                        'show_pencacah_cell' => $rowIndex === $pencacahStart,
                        'pencacah_rowspan' => $pencacahSpan,
                    ];
                }
            }
        }

        $kepala ??= Penandatangan::active()->kepala()->first();
        $signatureDate = $this->resolveMonitoringSignatureDate($periode);

        return [
            'judul' => 'Monitoring '.$kegiatan->nama_kegiatan,
            'lokasi' => 'Badan Pusat Statistik Kota Sawahlunto',
            'kegiatan_nama' => $kegiatan->nama_kegiatan,
            'tahun' => (int) $periode->tahun,
            'bulan' => $periode->bulan,
            'periode_label' => $this->formatMonitoringMonthLabel($periode->bulan).' '.$periode->tahun,
            'periode_tanggal_mulai' => $periode->tanggal_mulai?->format('j F Y'),
            'periode_tanggal_selesai' => $periode->tanggal_selesai?->format('j F Y'),
            'generated_at' => now()->timezone(config('app.timezone', 'Asia/Jakarta'))->locale('id')->translatedFormat('d F Y H:i'),
            'tanggal_pengesahan' => $signatureDate->locale('id')->translatedFormat('d F Y'),
            'ketua_tim_nama' => $kegiatan->ketuaTim?->name ?? '-',
            'kepala_nama' => $kepala?->nama ?? '-',
            'show_nama_usaha_column' => $isPurposiveSampling,
            'frame_metadata_columns' => $displayFrameMetadataColumns,
            'rows' => $rows,
            'summary' => [
                'total_frame' => count($rows),
                'total_alokasi' => count($rows),
                'total_belum_alokasi' => 0,
                'total_unit' => (int) collect($rows)->sum('target_unit_total'),
            ],
        ];
    }

    protected function buildMonitoringSkgbReportData(Kegiatan $kegiatan, PeriodeAlokasi $periode, ?Penandatangan $kepala = null): array
    {
        return $this->buildMonitoringReportData($kegiatan, $periode, $kepala);
    }

    private function resolveMonitoringSignatureDate(PeriodeAlokasi $periode): Carbon
    {
        $sourceDate = $periode->tanggal_selesai
            ?? $periode->tanggal_selesai_listing
            ?? $periode->submitted_at
            ?? now();

        return $this->resolveNextWorkingDay($sourceDate);
    }

    private function resolveNextWorkingDay(Carbon|string $date): Carbon
    {
        $carbon = Carbon::parse($date)->startOfDay();

        while (isHariLibur($carbon)) {
            $carbon->addDay();
        }

        return $carbon;
    }

    private function resolveMonitoringSkgbSignatureDate(PeriodeAlokasi $periode): Carbon
    {
        return $this->resolveMonitoringSignatureDate($periode);
    }

    private function resolveFrameMetadataValuePair(KegiatanFrameSampel $frameSample, string $code): array
    {
        $normalizedCode = Str::lower(trim($code));
        $rawValue = $this->resolveFrameMetadataRawValue($frameSample, $normalizedCode);
        $labelValue = $this->resolveFrameMetadataLabelValue($frameSample, $normalizedCode);

        if ($rawValue === '-') {
            return [
                'code' => '-',
                'label' => $labelValue,
            ];
        }

        return [
            'code' => $rawValue,
            'label' => $labelValue,
        ];
    }

    private function resolveMonitoringNamaUsaha(?KegiatanFrameSampel $frameSample): string
    {
        if (! $frameSample) {
            return '-';
        }

        $namaUsaha = $frameSample->nama_target
            ?: $frameSample->nama_frame
            ?: data_get($frameSample->identitas_tambahan, 'nama_usaha_penggilingan')
            ?: data_get($frameSample->identitas_tambahan, 'nama_usaha')
            ?: '-';

        return trim((string) $namaUsaha) !== '' ? trim((string) $namaUsaha) : '-';
    }

    private function resolveFrameMetadataRawValue(KegiatanFrameSampel $frameSample, string $normalizedCode): string
    {
        $attributes = $frameSample->getAttributes();
        $identitasValues = $this->normalizeFrameMetadataSource($frameSample->identitas_tambahan);

        $aliasMap = [
            'kdkec' => ['kode_kecamatan'],
            'kode_kecamatan' => ['kdkec'],
            'kddes' => ['kode_desa'],
            'kode_desa' => ['kddes'],
            'kdsls' => ['kode_sls'],
            'kode_sls' => ['kdsls'],
            'kdsubsls' => ['kode_sub_sls'],
            'kode_sub_sls' => ['kdsubsls'],
            'idsegmen' => ['kode_segmen'],
            'kode_segmen' => ['idsegmen', 'kdsegmen'],
            'kdsegmen' => ['kode_segmen'],
        ];

        $directFields = [
            'kode_kecamatan' => $frameSample->kode_kecamatan,
            'kode_desa' => $frameSample->kode_desa,
            'kode_sls' => $frameSample->kode_sls,
            'kode_sub_sls' => $frameSample->kode_sub_sls,
            'kode_segmen' => $frameSample->kode_segmen,
            'nama_target' => $frameSample->nama_target,
            'nama_frame' => $frameSample->nama_frame,
        ];

        foreach ($directFields as $field => $value) {
            if (Str::lower($field) === $normalizedCode && is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        $candidateKeys = array_values(array_unique([
            $normalizedCode,
            Str::snake($normalizedCode),
            Str::slug($normalizedCode, '_'),
            ...($aliasMap[$normalizedCode] ?? []),
        ]));

        foreach ($candidateKeys as $candidateKey) {
            foreach ([$identitasValues, $attributes] as $source) {
                $value = data_get($source, $candidateKey);

                if (is_scalar($value) && trim((string) $value) !== '') {
                    return trim((string) $value);
                }
            }
        }

        return '-';
    }

    private function resolveFrameMetadataLabelValue(KegiatanFrameSampel $frameSample, string $normalizedCode): string
    {
        $identitasValues = $this->normalizeFrameMetadataSource($frameSample->identitas_tambahan);
        $aliasMap = [
            'kdkec' => ['kode_kecamatan'],
            'kode_kecamatan' => ['kdkec', 'kdkec_label', 'kode_kecamatan_label'],
            'kddes' => ['kode_desa'],
            'kode_desa' => ['kddes', 'kddes_label', 'kode_desa_label'],
            'kdsls' => ['kode_sls'],
            'kode_sls' => ['kdsls', 'kdsls_label', 'kode_sls_label'],
            'kdsubsls' => ['kode_sub_sls'],
            'kode_sub_sls' => ['kdsubsls', 'kdsubsls_label', 'kode_sub_sls_label'],
            'idsegmen' => ['kode_segmen'],
            'kode_segmen' => ['idsegmen', 'idsegmen_label', 'kdsegmen', 'kdsegmen_label', 'kode_segmen_label'],
            'kdsegmen' => ['kode_segmen'],
        ];
        $candidateKeys = array_values(array_unique([
            $normalizedCode.'_label',
            Str::snake($normalizedCode.'_label'),
            Str::slug($normalizedCode.'_label', '_'),
            ...(array_map(static fn (string $alias): string => $alias.'_label', $aliasMap[$normalizedCode] ?? [])),
        ]));

        foreach ($candidateKeys as $candidateKey) {
            $value = data_get($identitasValues, $candidateKey);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return '-';
    }

    private function normalizeFrameMetadataSource(mixed $source): array
    {
        if (is_array($source)) {
            return $source;
        }

        if (is_object($source)) {
            if (method_exists($source, 'toArray')) {
                $arrayValue = $source->toArray();

                if (is_array($arrayValue)) {
                    return $arrayValue;
                }
            }

            $decoded = json_decode(json_encode($source), true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_string($source)) {
            $decoded = json_decode($source, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function resolveMonitoringRoleKey(?string $peran): string
    {
        $normalizedPeran = Str::lower(trim((string) $peran));

        if (Str::contains($normalizedPeran, ['pml', 'pengawas'])) {
            return 'pengawas';
        }

        return 'pencacah';
    }

    private function formatMonitoringTahapanLabel(?string $tahapan): string
    {
        return match ($tahapan) {
            'listing' => 'Listing',
            'pencacahan' => 'Pencacahan',
            default => '-'
        };
    }

    private function formatMonitoringPeranLabel(?string $peran): string
    {
        if (! is_string($peran) || trim($peran) === '') {
            return '-';
        }

        return Str::of($peran)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    private function formatMonitoringMonthLabel(string $bulan): string
    {
        $normalized = str_pad((string) ((int) $bulan), 2, '0', STR_PAD_LEFT);

        return match ($normalized) {
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
            default => $normalized,
        };
    }
}
