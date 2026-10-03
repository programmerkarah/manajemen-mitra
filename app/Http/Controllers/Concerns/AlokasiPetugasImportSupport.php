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

trait AlokasiPetugasImportSupport
{
    private function validateDatesWithinKegiatanPeriod(Kegiatan $kegiatan, array $validated, string $tahapan): array
    {
        if (! $kegiatan->tanggal_mulai || ! $kegiatan->tanggal_selesai) {
            return [];
        }

        $periodStart = Carbon::parse($kegiatan->tanggal_mulai)->startOfDay();
        $periodEnd = Carbon::parse($kegiatan->tanggal_selesai)->endOfDay();
        $errors = [];

        $checkRange = static function (?string $value, string $label) use ($periodStart, $periodEnd, &$errors): void {
            if (! $value) {
                return;
            }

            $date = Carbon::parse($value);
            if ($date->lt($periodStart) || $date->gt($periodEnd)) {
                $errors[] = $label.' harus berada dalam rentang periode pelaksanaan kegiatan.';
            }
        };

        if ($tahapan !== 'listing_only') {
            $checkRange($validated['tanggal_mulai'] ?? null, 'Tanggal mulai');
            $checkRange($validated['tanggal_selesai'] ?? null, 'Tanggal selesai');
        }

        if ($tahapan === 'both' || $tahapan === 'listing_only') {
            $checkRange($validated['tanggal_mulai_listing'] ?? null, 'Tanggal mulai listing');
            $checkRange($validated['tanggal_selesai_listing'] ?? null, 'Tanggal selesai listing');
        }

        return array_values(array_unique($errors));
    }

    private function parseImportNik(mixed $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return sprintf('%.0f', $value);
        }

        $value = trim((string) $value);

        // Handle scientific notation strings like "1.373012410970002E+15"
        if ($value !== '' && is_numeric($value) && stripos($value, 'E') !== false) {
            $value = sprintf('%.0f', (float) $value);
        }

        if (preg_match_all('/\d{8,}/', $value, $matches) === 1) {
            return $matches[0][0];
        }

        if (preg_match_all('/\d{8,}/', $value, $matches) > 1) {
            usort($matches[0], static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

            return $matches[0][0];
        }

        return $value;
    }

    private function extractImportNikCellValue(Collection|array $row): mixed
    {
        $rowArray = $row instanceof Collection ? $row->all() : $row;

        foreach (['nik', 'nik_petugas', 'nama_nik', 'nama_nik_nip', 'nama_niknip'] as $key) {
            if (array_key_exists($key, $rowArray)) {
                return $rowArray[$key];
            }
        }

        foreach ($rowArray as $key => $value) {
            $normalizedKey = strtolower(trim((string) $key));

            if (str_contains($normalizedKey, 'nik') || str_contains($normalizedKey, 'nip')) {
                return $value;
            }
        }

        return '';
    }

    private function extractImportPeranCellValue(Collection|array $row): mixed
    {
        $rowArray = $row instanceof Collection ? $row->all() : $row;

        foreach (['kode_penugasan', 'jenis_penugasan', 'jenis_penugasan_kode', 'peran'] as $key) {
            if (array_key_exists($key, $rowArray)) {
                return $rowArray[$key];
            }
        }

        foreach ($rowArray as $key => $value) {
            $normalizedKey = strtolower(trim((string) $key));

            if (str_contains($normalizedKey, 'penugasan') || $normalizedKey === 'peran') {
                return $value;
            }
        }

        return '';
    }

    private function extractFrameSampelMetadataColumns(Collection $frameRows): array
    {
        $directColumns = [
            'kode_kecamatan' => 'Kecamatan',
            'kode_desa' => 'Desa/Kelurahan',
            'kode_sls' => 'SLS',
            'kode_sub_sls' => 'Sub SLS',
            'kode_segmen' => 'Segmen',
            'nama_target' => 'Nama Usaha',
            'nama_frame' => 'Nama Usaha',
        ];
        $columns = [];
        $seenLabels = [];

        foreach ($frameRows as $frameRow) {
            if (! $frameRow instanceof KegiatanFrameSampel) {
                continue;
            }

            foreach ($directColumns as $code => $label) {
                $value = $frameRow->{$code} ?? null;

                if (! is_scalar($value) || trim((string) $value) === '') {
                    continue;
                }

                $normalizedLabel = Str::lower($label);
                if (isset($seenLabels[$normalizedLabel])) {
                    continue;
                }

                $columns[] = [
                    'code' => $code,
                    'label' => $label,
                ];
                $seenLabels[$normalizedLabel] = true;
            }

            $identitas = is_array($frameRow->identitas_tambahan)
                ? $frameRow->identitas_tambahan
                : [];

            foreach ($identitas as $key => $value) {
                if (! is_scalar($value) || Str::endsWith((string) $key, '_label')) {
                    continue;
                }

                $code = trim((string) $key);
                if ($code === '') {
                    continue;
                }

                $label = $this->formatMetadataLabel($code);
                $normalizedLabel = Str::lower($label);
                if (isset($seenLabels[$normalizedLabel])) {
                    continue;
                }

                $columns[] = [
                    'code' => $code,
                    'label' => $label,
                ];
                $seenLabels[$normalizedLabel] = true;
            }
        }

        $getOrderWeight = static function (string $code): int {
            return match (Str::lower(trim($code))) {
                'kdkec', 'kode_kecamatan' => 0,
                'kddes', 'kode_desa' => 1,
                'kdsls', 'kode_sls' => 2,
                'kdsubsls', 'kode_sub_sls' => 3,
                'idsegmen', 'kdsegmen', 'kode_segmen' => 4,
                'nama_target', 'nama_frame', 'nama_usaha_penggilingan' => 5,
                default => 100,
            };
        };

        usort($columns, static function (array $left, array $right) use ($getOrderWeight): int {
            $leftOrder = $getOrderWeight((string) $left['code']);
            $rightOrder = $getOrderWeight((string) $right['code']);

            if ($leftOrder === $rightOrder) {
                return strcmp((string) $left['code'], (string) $right['code']);
            }

            return $leftOrder <=> $rightOrder;
        });

        return array_values($columns);
    }

    private function formatMetadataLabel(string $code): string
    {
        $normalizedCode = Str::lower(trim($code));

        return match ($normalizedCode) {
            'kdkec', 'kode_kecamatan' => 'Kecamatan',
            'kddes', 'kode_desa' => 'Desa/Kelurahan',
            'kdsls', 'kode_sls' => 'SLS',
            'kdsubsls', 'kode_sub_sls' => 'Sub SLS',
            'idsegmen', 'kdsegmen', 'kode_segmen' => 'ID Segmen',
            default => Str::title(str_replace('_', ' ', $code)),
        };
    }

    private function extractImportFrameMetadataValues(Collection|array $row, array $metadataColumns): array
    {
        $rowArray = $row instanceof Collection ? $row->all() : $row;
        $result = [];

        foreach ($metadataColumns as $metadataColumn) {
            $code = trim((string) ($metadataColumn['code'] ?? ''));
            $label = trim((string) ($metadataColumn['label'] ?? ''));

            if ($code === '') {
                continue;
            }

            $candidates = array_filter([
                $code,
                Str::slug($code, '_'),
                Str::snake($code),
                $label,
                Str::slug($label, '_'),
                Str::snake($label),
            ], fn (string $value): bool => trim($value) !== '');

            $value = '';

            foreach ($rowArray as $key => $rawValue) {
                $normalizedKey = Str::lower(trim((string) $key));
                $isMatch = collect($candidates)->contains(
                    fn (string $candidate): bool => $normalizedKey === Str::lower(trim($candidate))
                );

                if (! $isMatch) {
                    continue;
                }

                $value = trim((string) $rawValue);
                break;
            }

            $result[$code] = $value;
        }

        return $result;
    }

    private function isReferencePetugasSheetRow(Collection|array $row): bool
    {
        $rowArray = $row instanceof Collection ? $row->all() : $row;

        foreach (['nip_nik', 'nama_petugas', 'pilihan_dropdown', 'kode_penugasan_dropdown'] as $key) {
            if (array_key_exists($key, $rowArray)) {
                return true;
            }
        }

        return false;
    }

    private function parseImportSatuan(mixed $value): float
    {
        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            return 0.0;
        }

        $normalized = str_replace(' ', '', $stringValue);
        $lastComma = strrpos($normalized, ',');
        $lastDot = strrpos($normalized, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($lastComma !== false) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (! is_numeric($normalized)) {
            return 0.0;
        }

        return max(0.0, (float) $normalized);
    }

    private function parseImportInteger(mixed $value): int
    {
        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            return 0;
        }

        $normalized = str_replace(['.', ','], '', $stringValue);

        return is_numeric($normalized) ? max(0, (int) $normalized) : 0;
    }

    private function sensusUnitSampleColumnKeys(Kegiatan $kegiatan): array
    {
        if ($kegiatan->jenis_kegiatan !== 'sensus') {
            return [];
        }

        $orderedNames = $this->orderedSensusUnitSampleNames($kegiatan);
        if (count($orderedNames) <= 1) {
            return [];
        }

        return array_map(
            static fn (string $name): string => Str::snake('jumlah '.$name),
            $orderedNames
        );
    }

    private function orderedSensusUnitSampleNames(Kegiatan $kegiatan): array
    {
        $items = $kegiatan->unitSampelPencacahanItems();

        if ($items->isEmpty()) {
            return [];
        }

        return $items
            ->sortBy(function ($item): array {
                $name = Str::lower((string) ($item->nama ?? ''));

                if (Str::contains($name, 'usaha')) {
                    return [0, $name];
                }

                if (Str::contains($name, 'keluarga')) {
                    return [1, $name];
                }

                return [2, $name];
            })
            ->map(fn ($item): string => trim((string) ($item->nama ?? '')))
            ->filter(fn (string $name): bool => $name !== '')
            ->values()
            ->all();
    }

    private function resolveFrameSampelByMetadata(Collection $frameRows, array $metadataValues, int $rowNumber, array &$errors): ?KegiatanFrameSampel
    {
        $filledMetadata = collect($metadataValues)
            ->filter(fn (string $value): bool => trim($value) !== '');

        if ($filledMetadata->isEmpty()) {
            return null;
        }

        $candidates = $frameRows->filter(function (KegiatanFrameSampel $frameRow) use ($filledMetadata): bool {
            $identitas = is_array($frameRow->identitas_tambahan)
                ? $frameRow->identitas_tambahan
                : [];

            foreach ($filledMetadata as $code => $expectedValue) {
                $actualValue = '';

                foreach ($identitas as $identitasKey => $identitasValue) {
                    if (
                        Str::lower((string) $identitasKey) === Str::lower((string) $code) &&
                        is_scalar($identitasValue)
                    ) {
                        $actualValue = trim((string) $identitasValue);
                        break;
                    }
                }

                if (Str::lower($actualValue) !== Str::lower(trim((string) $expectedValue))) {
                    return false;
                }
            }

            return true;
        })->values();

        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        if ($candidates->isEmpty()) {
            $summary = $filledMetadata
                ->map(fn (string $value, string $key): string => $key.'='.$value)
                ->implode(', ');
            $errors[] = "Baris {$rowNumber}: Frame sampel tidak ditemukan untuk metadata [{$summary}].";

            return null;
        }

        $errors[] = "Baris {$rowNumber}: Metadata frame sampel ambigu, cocok ke lebih dari satu frame. Lengkapi kolom metadata hingga unik.";

        return null;
    }

    private function parseImportBoolean(mixed $value): bool
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'ya', 'yes', 'y'], true);
    }

    private function normalizeImportPeranCode(string $value): ?string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'pcl_ppl' => 'pcl_ppl',
            'pcl/ppl' => 'pcl_ppl',
            'pml' => 'pml',
            'pengolahan' => 'pengolahan',
            'petugas pengolahan' => 'pengolahan',
            'pengawas_pengolahan', 'pengawasan_pengolahan' => 'pengawas_pengolahan',
            'pengawas pengolahan' => 'pengawas_pengolahan',
            'koseka' => 'koseka',
            default => null,
        };
    }

    private function mapPeranCodeToDisplayLabel(string $peranCode): string
    {
        return match ($peranCode) {
            'pcl_ppl' => 'PCL/PPL',
            'pml' => 'PML',
            'pengolahan' => 'Petugas Pengolahan',
            'pengawas_pengolahan' => 'Pengawas Pengolahan',
            'koseka' => 'Koseka',
            default => 'PCL',
        };
    }

    private function resolvePeranCodeFromRateHonor(RateHonor $rateHonor): string
    {
        $peranCode = strtolower(trim((string) $rateHonor->jenis_penugasan));

        return in_array($peranCode, ['pcl_ppl', 'pml', 'koseka', 'pengolahan', 'pengawas_pengolahan'], true)
            ? $peranCode
            : 'pcl_ppl';
    }

    private function resolveFrameSampleTarget(KegiatanFrameSampel $frameSample): int
    {
        return max(0, (int) array_sum((array) $frameSample->target_unit_sampel));
    }
}
