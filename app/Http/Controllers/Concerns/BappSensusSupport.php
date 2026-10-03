<?php

namespace App\Http\Controllers\Concerns;

use App\Exports\BappSeRealisasiTemplateExport;
use App\Imports\BappSeRealisasiImport;
use App\Models\AlokasiPetugas;
use App\Models\BappSeTermin;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use App\Models\Penandatangan;
use App\Models\Petugas;
use App\Models\SensusEkonomiPetugasReplacement;
use App\Models\SensusEkonomiPkppContract;
use App\Models\Spk;
use App\Services\ActiveYearService;
use App\Services\SensusEkonomiBappNumberService;
use App\Services\SensusEkonomiPkNumberService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use setasign\Fpdi\Tcpdf\Fpdi;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Vinkla\Hashids\Facades\Hashids;

trait BappSensusSupport
{
    private function getSensusEkonomiKegiatan(): ?Kegiatan
    {
        return Kegiatan::query()
            ->where('jenis_kegiatan', 'sensus')
            ->where(function ($q): void {
                $q->where('nama_kegiatan', 'like', '%sensus ekonomi%');
            })
            ->with('ketuaTim')
            ->first();
    }

    private function hasSensusEkonomiSpkSourceTables(): bool
    {
        $requiredTables = [
            'spk',
            'alokasi_petugas',
            'periode_alokasi',
            'kegiatan',
        ];

        foreach ($requiredTables as $table) {
            if (Schema::hasTable($table)) {
                continue;
            }

            if (! self::$hasLoggedMissingBappSpkSourceTables) {
                self::$hasLoggedMissingBappSpkSourceTables = true;

                Log::warning('BAPP source tables are missing; returning empty SPK list for compatibility.', [
                    'missing_table' => $table,
                    'required_tables' => $requiredTables,
                ]);
            }

            return false;
        }

        return true;
    }

    protected function getSensusEkonomiSpks(int $tahun): Collection
    {
        if (! $this->hasSensusEkonomiSpkSourceTables()) {
            return new Collection;
        }

        $spks = Spk::query()
            ->where('addendum_number', 0)
            ->whereIn('lampiran_template', ['sensus_ekonomi', 'pml_sensus_ekonomi'])
            ->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($query): void {
                $query->where('jenis_kegiatan', 'sensus')
                    ->where('nama_kegiatan', 'like', '%sensus ekonomi%');
            })
            ->with([
                'petugas',
                'alokasiPetugas.periodeAlokasi.kegiatan',
            ])
            ->orderBy('id')
            ->get();

        return $spks;
    }

    private function isStoppedPetugasEligibleForTermin(Carbon $tanggalBerhenti, int $terminNumber, int $tahun): bool
    {
        $stopDate = $tanggalBerhenti->copy()->startOfDay();
        $contractStart = Carbon::create($tahun, 6, 1)->startOfDay();
        $terminTwoStart = Carbon::create($tahun, 8, 1)->startOfDay();
        $contractEnd = Carbon::create($tahun, 8, 31)->endOfDay();

        if ($stopDate->lt($contractStart) || $stopDate->gt($contractEnd)) {
            return false;
        }

        if ($terminNumber === 1) {
            return true;
        }

        if ($terminNumber === 2) {
            // Termin II hanya menjadi kewajiban bila petugas lama masih bekerja
            // memasuki periode Agustus. Petugas yang berhenti sebelum Agustus
            // berhenti pada Termin I dan tidak lagi dihitung belum lengkap.
            return $stopDate->greaterThanOrEqualTo($terminTwoStart);
        }

        return false;
    }

    private function getStoppedPetugasEligibleReplacementsForTermin(int $terminNumber, int $tahun): Collection
    {
        if (! Schema::hasTable('sensus_ekonomi_petugas_replacements') || ! Schema::hasTable('spk')) {
            return new Collection;
        }

        return SensusEkonomiPetugasReplacement::query()
            ->whereNotNull('spk_lama_id')
            ->where('status', '!=', 'dibatalkan')
            ->where(function ($q): void {
                $q->whereNull('termin_i_paid')->orWhere('termin_i_paid', 1);
            })
            ->with([
                'spkLama.petugas',
                'spkLama.alokasiPetugas.periodeAlokasi.kegiatan',
            ])
            ->latest('id')
            ->get()
            ->filter(function (SensusEkonomiPetugasReplacement $replacement) use ($terminNumber, $tahun): bool {
                $spk = $replacement->spkLama;
                if (! $spk instanceof Spk) {
                    return false;
                }

                if ($spk->addendum_number !== 0) {
                    return false;
                }

                if (! in_array($spk->lampiran_template, ['sensus_ekonomi', 'pml_sensus_ekonomi'], true)) {
                    return false;
                }

                $kegiatan = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan;
                if (! $kegiatan
                    || $kegiatan->jenis_kegiatan !== 'sensus'
                    || ! str_contains(strtolower((string) $kegiatan->nama_kegiatan), 'sensus ekonomi')) {
                    return false;
                }

                if (! $replacement->tanggal_berhenti instanceof \DateTimeInterface) {
                    return false;
                }

                return $this->isStoppedPetugasEligibleForTermin(
                    Carbon::instance($replacement->tanggal_berhenti),
                    $terminNumber,
                    $tahun,
                );
            })
            ->unique('spk_lama_id')
            ->values();
    }

    protected function getSpksForBappContext(
        int $tahun,
        int $terminNumber,
        string $documentType,
        int $replacementTerminCount = 0,
    ): Collection {
        if ($documentType === 'stopped_petugas') {
            return $this->getStoppedPetugasEligibleReplacementsForTermin($terminNumber, $tahun)
                ->map(fn (SensusEkonomiPetugasReplacement $replacement) => $replacement->spkLama)
                ->filter(fn ($spk) => $spk instanceof Spk)
                ->unique('id')
                ->values();
        }

        if ($documentType === 'replacement_pkpp') {
            if (
                ! Schema::hasTable('sensus_ekonomi_pkpp_contracts')
                || ! Schema::hasTable('sensus_ekonomi_petugas_replacements')
            ) {
                return new Collection;
            }

            $terminCount = $replacementTerminCount === 1 ? 1 : 2;
            if ($terminCount === 1 && $terminNumber !== 1) {
                return new Collection;
            }

            return SensusEkonomiPkppContract::query()
                ->where('termin_count', $terminCount)
                ->whereHas('replacement', fn ($query) => $query
                    ->where('status', '!=', 'dibatalkan')
                    ->whereNotNull('spk_lama_id'))
                ->with([
                    'replacement.petugasPengganti',
                    'replacement.spkLama.petugas',
                    'replacement.spkLama.alokasiPetugas.periodeAlokasi.kegiatan',
                ])
                ->get()
                ->map(fn (SensusEkonomiPkppContract $contract) => $contract->replacement?->spkLama)
                ->filter(function ($spk) use ($tahun): bool {
                    if (! $spk instanceof Spk) {
                        return false;
                    }

                    $kegiatan = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan;

                    return (int) ($spk->alokasiPetugas?->periodeAlokasi?->tahun ?? 0) === $tahun
                        && $kegiatan?->jenis_kegiatan === 'sensus'
                        && str_contains(mb_strtolower((string) $kegiatan?->nama_kegiatan), 'sensus ekonomi');
                })
                ->unique('id')
                ->values();
        }

        $spks = $this->getSensusEkonomiSpks($tahun);

        if (! Schema::hasTable('sensus_ekonomi_petugas_replacements')) {
            return $spks;
        }

        // Petugas yang sudah masuk workflow berhenti/pengganti dikelola di konteks
        // tersendiri agar BAPP reguler tidak tetap menagih Termin II untuk petugas lama.
        $stoppedSpkIds = SensusEkonomiPetugasReplacement::query()
            ->whereNotNull('spk_lama_id')
            ->where('status', '!=', 'dibatalkan')
            ->pluck('spk_lama_id')
            ->filter()
            ->map(fn ($id) => (int) $id);

        $replacementSpkIds = Schema::hasTable('sensus_ekonomi_pkpp_contracts')
            ? SensusEkonomiPkppContract::query()
                ->whereNotNull('spk_id')
                ->pluck('spk_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
            : collect();

        $excludedIds = $stoppedSpkIds->merge($replacementSpkIds)->unique();

        return $spks
            ->reject(fn (Spk $spk) => $excludedIds->contains((int) $spk->id))
            ->values();
    }

    private function getReplacementIdByReplacementSpkId(int $replacementTerminCount): array
    {
        if (! Schema::hasTable('sensus_ekonomi_pkpp_contracts')) {
            return [];
        }

        $terminCount = $replacementTerminCount === 1 ? 1 : 2;

        return SensusEkonomiPkppContract::query()
            ->where('termin_count', $terminCount)
            ->whereHas('replacement', fn ($query) => $query->whereNotNull('spk_lama_id'))
            ->with('replacement:id,spk_lama_id')
            ->get()
            ->mapWithKeys(fn (SensusEkonomiPkppContract $contract) => [
                (int) $contract->replacement?->spk_lama_id => (int) $contract->replacement_id,
            ])
            ->filter(fn ($replacementId, $spkId) => $spkId > 0 && $replacementId > 0)
            ->all();
    }

    private function getStoppedReplacementIdBySpkId(int $terminNumber, int $tahun): array
    {
        return $this->getStoppedPetugasEligibleReplacementsForTermin($terminNumber, $tahun)
            ->mapWithKeys(fn (SensusEkonomiPetugasReplacement $replacement) => [
                (int) $replacement->spk_lama_id => (int) $replacement->id,
            ])
            ->all();
    }

    private function buildTargetForTermin(Spk $spk, int $terminNumber): array
    {
        $alokasi = $spk->alokasiPetugas;
        $config = self::TERMIN_CONFIG[$terminNumber];
        $persentase = $config['persentase'];

        // Build per-unit-sampel targets from frame sampel allocations
        $alokasi?->loadMissing('frameSampelAllocations.kegiatanFrameSampel');
        $frameAllocations = $alokasi?->frameSampelAllocations ?? collect();

        // Target SLS = unique frame sampel rows (each row = 1 SLS/sub-SLS)
        $totalSls = $frameAllocations->unique('kegiatan_frame_sampel_id')->count();
        $targetSls = (int) ceil($totalSls * $persentase / 100);

        $kegiatan = $alokasi?->periodeAlokasi?->kegiatan;
        $unitSampelIds = $kegiatan?->unit_sampel_pencacahan_ids ?? [];
        $targetUnitSampel = [];

        $perUnitSampelRaw = [];

        foreach ($frameAllocations as $frameAlloc) {
            $kfsTarget = $frameAlloc?->kegiatanFrameSampel?->target_unit_sampel;
            if (is_array($kfsTarget)) {
                foreach ($kfsTarget as $uid => $count) {
                    $uid = (int) $uid;
                    if ($uid > 0) {
                        $perUnitSampelRaw[$uid] = ($perUnitSampelRaw[$uid] ?? 0) + max(0, (int) $count);
                    }
                }
            }
        }

        if (! empty($perUnitSampelRaw)) {
            $units = MasterUnitSampel::query()->whereIn('id', array_keys($perUnitSampelRaw))->get();
            foreach ($units as $unit) {
                $raw = $perUnitSampelRaw[$unit->id] ?? 0;
                $targetUnitSampel[strtolower($unit->nama)] = (int) ceil($raw * $persentase / 100);
            }
        } elseif (! empty($unitSampelIds)) {
            // Fallback: keys with 0 so types are shown
            $units = MasterUnitSampel::query()->whereIn('id', $unitSampelIds)->get();
            foreach ($units as $unit) {
                $targetUnitSampel[strtolower($unit->nama)] = 0;
            }
        }

        return [
            'target_sls' => $targetSls,
            'target_unit_sampel' => $targetUnitSampel,
        ];
    }
}
