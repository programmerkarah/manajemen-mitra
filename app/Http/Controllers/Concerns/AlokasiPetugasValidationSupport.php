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

trait AlokasiPetugasValidationSupport
{
    private function validateDecimalSatuanRules(array $alokasiItems): array
    {
        $errors = [];

        foreach ($alokasiItems as $index => $alokasiData) {
            $jenisKegiatan = $alokasiData['jenis_kegiatan'] ?? null;
            if ($jenisKegiatan === 'sensus') {
                continue;
            }

            if ($this->hasDecimalPart($alokasiData['jumlah_satuan'] ?? null)) {
                $errors[] = 'Baris alokasi #'.($index + 1).': jumlah satuan desimal hanya diperbolehkan untuk kegiatan sensus.';
            }

            $isPartialPayment = (bool) ($alokasiData['is_partial_payment'] ?? false);
            if ($isPartialPayment && $this->hasDecimalPart($alokasiData['partial_jumlah_satuan'] ?? null)) {
                $errors[] = 'Baris alokasi #'.($index + 1).': jumlah satuan parsial desimal hanya diperbolehkan untuk kegiatan sensus.';
            }
        }

        return $errors;
    }

    private function hasDecimalPart(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $numericValue = (float) $value;

        return abs($numericValue - round($numericValue)) > 0.000001;
    }

    private function normalizeSatuanForResponse(mixed $value): int|float
    {
        $numericValue = (float) ($value ?? 0);

        if (abs($numericValue - round($numericValue)) <= 0.000001) {
            return (int) round($numericValue);
        }

        return $numericValue;
    }

    private function checkSbmlConstraint(
        int $tahun,
        string $jenisKegiatan,
        string $statusKepegawaian,
        string $jenisPenugasan,
        float $totalHonor,
        ?Kegiatan $kegiatan = null
    ): ?string {
        $sbml = Sbml::where('tahun_anggaran', $tahun)
            ->where('jenis_kegiatan', $jenisKegiatan)
            ->where('status_kepegawaian', $statusKepegawaian)
            ->where('jenis_penugasan', $jenisPenugasan)
            ->where('status', 'aktif')
            ->first();

        if (! $sbml) {
            return 'SBML untuk kombinasi ini belum tersedia. Silakan hubungi admin untuk mengatur SBML terlebih dahulu.';
        }

        $limitMultiplier = $this->getSbmlLimitMultiplier($kegiatan);
        $adjustedHonorMax = (float) $sbml->honor_max * $limitMultiplier;

        if ($totalHonor > $adjustedHonorMax) {
            return 'Total honor (Rp '.number_format($totalHonor, 0, ',', '.').') melebihi batas maksimal SBML (Rp '.number_format($adjustedHonorMax, 0, ',', '.').") untuk tahun {$tahun}.";
        }

        return null;
    }

    private function resolvePencacahanWorkload(Kegiatan $kegiatan, float $jumlahSatuan): float
    {
        if ($jumlahSatuan <= 0) {
            return 0;
        }

        if ($this->isSensusEkonomi2026($kegiatan)) {
            return $jumlahSatuan * 2.5;
        }

        return $jumlahSatuan;
    }

    private function isSensusEkonomi2026(Kegiatan $kegiatan): bool
    {
        return $kegiatan->jenis_kegiatan === 'sensus'
            && mb_strtolower(trim((string) $kegiatan->nama_kegiatan)) === 'sensus ekonomi';
    }

    private function getSbmlLimitMultiplier(?Kegiatan $kegiatan): float
    {
        if ($kegiatan && $this->isSensusEkonomi2026($kegiatan)) {
            return 2.5;
        }

        return 1.0;
    }

    private function checkPetugasTotalHonorInMonth(
        int $petugasId,
        int $tahun,
        int $bulan,
        float $newHonor,
        ?int $excludePeriodeId = null,
        ?string $newPeran = null,
        ?string $newJenisKegiatan = null,
        ?string $newStatusKepegawaian = null,
        ?Kegiatan $kegiatan = null
    ): ?string {
        if (($newJenisKegiatan ?? $kegiatan?->jenis_kegiatan) === 'sensus') {
            return null;
        }

        $petugas = Petugas::find($petugasId);
        if (! $petugas) {
            return 'Petugas tidak ditemukan.';
        }

        $bulanCandidates = $this->resolveBulanCandidates((string) $bulan);

        // Get all existing allocations for this petugas in this month
        $existingAlokasis = AlokasiPetugas::with(['periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($query) use ($tahun, $bulanCandidates, $excludePeriodeId) {
                $query->where('tahun', $tahun)
                    ->whereIn('bulan', $bulanCandidates)
                    ->whereIn('status', ['draft', 'dikirim', 'perubahan']);

                if ($excludePeriodeId) {
                    $query->where('id', '!=', $excludePeriodeId);
                }
            })
            ->where('petugas_id', $petugasId)
            ->get();

        $existingTotalHonor = $existingAlokasis->sum(function ($alokasi) {
            $pencacahanHonor = $alokasi->is_partial_payment && $alokasi->estimasi_honor_partial !== null
                ? (float) $alokasi->estimasi_honor_partial
                : (float) ($alokasi->total_honor ?? 0);

            $listingHonor = $alokasi->is_partial_payment_listing && $alokasi->estimasi_honor_partial_listing !== null
                ? (float) $alokasi->estimasi_honor_partial_listing
                : (float) ($alokasi->total_honor_listing ?? 0);

            return $pencacahanHonor + $listingHonor;
        });

        $totalHonorInMonth = $existingTotalHonor + $newHonor;

        // Collect all jenis penugasan (peran) from existing allocations
        $jenisPenugasanList = $existingAlokasis->pluck('peran')->unique();

        // Add new peran if provided
        if ($newPeran) {
            $jenisPenugasanList->push($newPeran);
            $jenisPenugasanList = $jenisPenugasanList->unique();
        }

        // Map peran ke jenis_penugasan dan ambil honor_max SBML untuk tiap penugasan yang sudah diberikan
        $statusKepegawaian = $this->resolveStatusKepegawaianFromPetugas($petugas);
        // Ambil kombinasi unik dari alokasi: [jenis_kegiatan, jenis_penugasan, status_kepegawaian]
        $alokasiKombinasi = $existingAlokasis->map(function ($alokasi) {
            return [
                'jenis_kegiatan' => $alokasi->periodeAlokasi->jenis_kegiatan ?? null,
                'jenis_penugasan' => $alokasi->peran,
                'status_kepegawaian' => $alokasi->status_kepegawaian,
            ];
        });
        // Tambahkan kombinasi baru jika ada
        if ($newPeran) {
            $alokasiKombinasi->push([
                'jenis_kegiatan' => $newJenisKegiatan ?? $existingAlokasis->first()?->periodeAlokasi?->jenis_kegiatan ?? null,
                'jenis_penugasan' => $newPeran,
                'status_kepegawaian' => $newStatusKepegawaian ?? $statusKepegawaian,
            ]);
        }

        // Jika petugas belum pernah dialokasikan (penugasan perdana), gunakan kombinasi dari penugasan baru
        if ($alokasiKombinasi->isEmpty() && $newPeran && $newJenisKegiatan && $newStatusKepegawaian) {
            $alokasiKombinasi->push([
                'jenis_kegiatan' => $newJenisKegiatan,
                'jenis_penugasan' => $newPeran,
                'status_kepegawaian' => $newStatusKepegawaian,
            ]);
        }
        $uniqueKombinasi = $alokasiKombinasi->unique(function ($item) {
            return $item['jenis_kegiatan'].'|'.$item['jenis_penugasan'].'|'.$item['status_kepegawaian'];
        });

        $honorMaxList = $uniqueKombinasi->map(function ($kombinasi) use ($tahun) {
            $sbml = Sbml::where('tahun_anggaran', $tahun)
                ->where('jenis_kegiatan', $kombinasi['jenis_kegiatan'])
                ->where('status_kepegawaian', $kombinasi['status_kepegawaian'])
                ->where('jenis_penugasan', $kombinasi['jenis_penugasan'])
                ->where('status', 'aktif')
                ->first();

            return $sbml ? $sbml->honor_max : null;
        })->filter();

        if ($honorMaxList->isEmpty()) {
            return 'SBML untuk penugasan yang diberikan ke petugas ini belum tersedia. Silakan hubungi admin untuk mengatur SBML terlebih dahulu.';
        }

        $minAllowed = $honorMaxList->min();

        if ($totalHonorInMonth > $minAllowed) {
            return sprintf(
                'Total honor petugas %s di bulan %s %d (Rp %s) melebihi batas maksimal SBML terendah (Rp %s). Honor yang sudah dialokasikan: Rp %s, Honor baru: Rp %s.',
                $petugas->nama,
                Carbon::create()->month($bulan)->translatedFormat('F'),
                $tahun,
                number_format($totalHonorInMonth, 0, ',', '.'),
                number_format($minAllowed, 0, ',', '.'),
                number_format($existingTotalHonor, 0, ',', '.'),
                number_format($newHonor, 0, ',', '.')
            );
        }

        return null;
    }

    private function resolveStatusKepegawaianFromPetugas(Petugas $petugas): string
    {
        $normalizedJenisPetugas = Str::of((string) $petugas->jenis_petugas)
            ->lower()
            ->replace('_', '-')
            ->trim()
            ->value();

        return $normalizedJenisPetugas === 'organik' ? 'organik' : 'non_organik';
    }
}
