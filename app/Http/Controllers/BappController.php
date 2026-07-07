<?php

namespace App\Http\Controllers;

use App\Exports\BappSeRealisasiTemplateExport;
use App\Imports\BappSeRealisasiImport;
use App\Models\AlokasiPetugas;
use App\Models\BappSeTermin;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use App\Models\Penandatangan;
use App\Models\Petugas;
use App\Models\SensusEkonomiPetugasReplacement;
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

class BappController extends Controller
{
    private static ?bool $hasBappTerminTable = null;

    private static bool $hasLoggedMissingBappTerminTable = false;

    private static ?bool $supportsBappDocumentContextColumns = null;

    private static bool $hasLoggedMissingBappDocumentContextColumns = false;

    private static bool $hasLoggedMissingBappSpkSourceTables = false;

    private const TERMIN_CONFIG = [
        1 => ['bulan' => 7, 'bulan_label' => 'Juli', 'persentase' => 40, 'roman' => 'I', 'tanggal_min' => '%d-07-15', 'tanggal_max' => '%d-07-31', 'tanggal_default' => null],
        2 => ['bulan' => 8, 'bulan_label' => 'Agustus', 'persentase' => 60, 'roman' => 'II', 'tanggal_min' => '%d-08-01', 'tanggal_max' => '%d-08-31', 'tanggal_default' => '%d-08-31'],
    ];

    private const SE_PENDATAAN_ROLES = ['pcl_ppl', 'pcl', 'ppl'];

    private const SE_PEMERIKSAAN_ROLES = ['pml'];

    private const DOCUMENT_TYPES = [
        'regular',
        'stopped_petugas',
        'replacement_pkpp',
    ];

    /**
     * Strip academic/professional titles from a name and apply title case.
     */
    private function stripGelar(string $nama): string
    {
        $nama = trim($nama);
        // Strip prefix titles (e.g. Dr., Drs., Prof., Ir., H., Hj.)
        $nama = preg_replace('/^(Prof\.?|Dr\.?|Drs\.?|Dra\.?|Ir\.?|H\.?|Hj\.?|KH\.?)\s+/i', '', $nama);
        // Strip suffix titles after comma (e.g. , S.E., M.M., S.AP.)
        $nama = preg_replace('/,\s*[A-Z][A-Za-z.]+(?:,?\s*[A-Z][A-Za-z.]+)*\.?$/', '', $nama);

        return ucwords(strtolower(trim($nama, ' ,')));
    }

    /**
     * Get NIP for ketua tim: first from User.nip, then match Petugas by name and read nik (auto-decrypted).
     */
    private function getNipKetuaTim(?Kegiatan $kegiatan): ?string
    {
        $user = $kegiatan?->ketuaTim;
        if ($user?->nip) {
            return $user->nip;
        }
        if ($user?->name) {
            return Petugas::query()->where('nama', $user->name)->first()?->nik;
        }

        return null;
    }

    private function resolveDocumentType(Request $request): string
    {
        $documentType = (string) $request->input('document_type', 'regular');

        if (! in_array($documentType, self::DOCUMENT_TYPES, true)) {
            return 'regular';
        }

        if (! $this->supportsBappDocumentContextColumns()) {
            return 'regular';
        }

        return $documentType;
    }

    private function resolveReplacementTerminCount(Request $request): int
    {
        return (int) $request->input('replacement_termin_count', 2) === 1 ? 1 : 2;
    }

    private function getContextReplacementTerminCount(string $documentType, int $replacementTerminCount): int
    {
        if (! $this->supportsBappDocumentContextColumns()) {
            return 0;
        }

        return $documentType === 'replacement_pkpp' ? $replacementTerminCount : 0;
    }

    private function supportsBappDocumentContextColumns(): bool
    {
        if (self::$supportsBappDocumentContextColumns !== null) {
            return self::$supportsBappDocumentContextColumns;
        }

        if (! $this->hasBappTerminTable()) {
            $this->logMissingBappDocumentContextWarning();
            self::$supportsBappDocumentContextColumns = false;

            return false;
        }

        self::$supportsBappDocumentContextColumns = Schema::hasColumn('bapp_se_termin', 'document_type')
            && Schema::hasColumn('bapp_se_termin', 'replacement_termin_count');

        if (! self::$supportsBappDocumentContextColumns) {
            $this->logMissingBappDocumentContextWarning();
        }

        return self::$supportsBappDocumentContextColumns;
    }

    private function hasBappTerminTable(): bool
    {
        if (self::$hasBappTerminTable !== null) {
            return self::$hasBappTerminTable;
        }

        self::$hasBappTerminTable = Schema::hasTable('bapp_se_termin');

        if (! self::$hasBappTerminTable && ! self::$hasLoggedMissingBappTerminTable) {
            self::$hasLoggedMissingBappTerminTable = true;

            Log::warning('BAPP table is missing; compatibility mode is active.', [
                'table' => 'bapp_se_termin',
            ]);
        }

        return self::$hasBappTerminTable;
    }

    private function logMissingBappDocumentContextWarning(): void
    {
        if (self::$hasLoggedMissingBappDocumentContextColumns) {
            return;
        }

        self::$hasLoggedMissingBappDocumentContextColumns = true;

        Log::warning('BAPP context columns are missing; fallback compatibility mode is active.', [
            'table' => 'bapp_se_termin',
            'required_columns' => ['document_type', 'replacement_termin_count'],
        ]);
    }

    private function applyBappDocumentContextScope(Builder $query, string $documentType, int $contextReplacementTerminCount): Builder
    {
        if (! $this->supportsBappDocumentContextColumns()) {
            return $query;
        }

        return $query
            ->where('document_type', $documentType)
            ->where('replacement_termin_count', $contextReplacementTerminCount);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withBappDocumentContextAttributes(array $attributes, string $documentType, int $contextReplacementTerminCount): array
    {
        if (! $this->supportsBappDocumentContextColumns()) {
            return $attributes;
        }

        $attributes['document_type'] = $documentType;
        $attributes['replacement_termin_count'] = $contextReplacementTerminCount;

        return $attributes;
    }

    /**
     * Resolve the tanggal_bapp for a realisasi entry.
     *
     * @param  array<string, mixed>  $entry
     */
    protected function resolveEntryTanggalBapp(array $entry, ?string $sharedTanggalBapp): ?string
    {
        $entryTanggalBapp = isset($entry['tanggal_bapp']) ? trim((string) $entry['tanggal_bapp']) : '';

        if ($entryTanggalBapp !== '') {
            return $entryTanggalBapp;
        }

        $sharedTanggalBapp = $sharedTanggalBapp !== null ? trim($sharedTanggalBapp) : '';

        return $sharedTanggalBapp !== '' ? $sharedTanggalBapp : null;
    }

    /**
     * Build a map of spk_id → auto-generated nomor BAPP.
     * SPKs are sorted alphabetically by petugas name.
     * Format: B-{NNN}/BAPP-{roman}-SE2026/1373/PL.200/{tahun}
     *
     * @param  Collection<int, Spk>  $spks
     * @return array<int, string>
     */
    private function generateNomorBappMap(
        Collection $spks,
        string $roman,
        int $tahun,
        string $documentType = 'regular',
        int $terminCount = 2,
    ): array {
        $sorted = $spks->sortBy(function (Spk $spk): string {
            return mb_strtolower(trim($spk->petugas?->nama ?? ''));
        })->values();

        $map = [];
        $numberService = new SensusEkonomiBappNumberService;
        foreach ($sorted as $index => $spk) {
            $sequence = $index + 1;

            if ($documentType === 'stopped_petugas') {
                $map[$spk->id] = $numberService->formatStoppedPetugasNumber(
                    $sequence,
                    $tahun,
                );

                continue;
            }

            if ($documentType === 'replacement_pkpp') {
                $map[$spk->id] = $numberService->formatReplacementNumber(
                    $sequence,
                    $tahun,
                    $terminCount,
                    $roman,
                );

                continue;
            }

            $map[$spk->id] = sprintf(
                'B-%03d/BAPP-%s-SE2026/1373/PL.200/%d',
                $sequence,
                $roman,
                $tahun,
            );
        }

        return $map;
    }

    /**
     * Determine the "jenis_pihak_kedua" based on peran.
     */
    private function getJenisPihakKedua(string $peran): string
    {
        if (in_array($peran, self::SE_PEMERIKSAAN_ROLES, true)) {
            return 'pemeriksa_lapangan';
        }

        return 'petugas_lapangan';
    }

    /**
     * Get SE2026 kegiatan.
     */
    /**
     * Check whether the currently authenticated user may access BAPP pages.
     * Admins and operators always can. Ketua_tim can access if they are the
     * assigned ketua tim or pj lainnya for the Sensus Ekonomi kegiatan.
     */
    private function userCanAccessBapp(Request $request): bool
    {
        $user = effectiveUser($request);
        if (! $user) {
            return false;
        }

        $role = $user->getActiveRole()?->name;

        if (in_array($role, ['admin', 'operator'], true)) {
            return true;
        }

        if ($role !== 'ketua_tim') {
            return false;
        }

        $seKegiatan = $this->getSensusEkonomiKegiatan();

        return $seKegiatan !== null
            && (
                $seKegiatan->ketua_tim_user_id === $user->id
                || $seKegiatan->pj_lainnya_id === $user->id
            );
    }

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

    /**
     * Get all SE2026 SPKs (original, non-addendum) in current active year.
     *
     * @return Collection<int, Spk>
     */
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
        $july14 = Carbon::create($tahun, 7, 14)->startOfDay();
        $july15 = Carbon::create($tahun, 7, 15)->startOfDay();
        $aug31 = Carbon::create($tahun, 8, 31)->startOfDay();

        if ($stopDate->lt($july14)) {
            return false;
        }

        if ($terminNumber !== 1) {
            return false;
        }

        return $stopDate->greaterThanOrEqualTo($july15)
            && $stopDate->lt($aug31);
    }

    /**
     * @return Collection<int, SensusEkonomiPetugasReplacement>
     */
    private function getStoppedPetugasEligibleReplacementsForTermin(int $terminNumber, int $tahun): Collection
    {
        if (! Schema::hasTable('sensus_ekonomi_petugas_replacements') || ! Schema::hasTable('spk')) {
            return new Collection;
        }

        return SensusEkonomiPetugasReplacement::query()
            ->whereNotNull('spk_lama_id')
            ->where('status', '!=', 'dibatalkan')
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

    /**
     * @return Collection<int, Spk>
     */
    protected function getSpksForBappContext(int $tahun, int $terminNumber, string $documentType): Collection
    {
        if ($documentType !== 'stopped_petugas') {
            return $this->getSensusEkonomiSpks($tahun);
        }

        return SensusEkonomiPetugasReplacement::query()
            ->whereNotNull('spk_lama_id')
            ->where('status', '!=', 'dibatalkan')
            ->with([
                'spkLama.petugas',
                'spkLama.alokasiPetugas.periodeAlokasi.kegiatan',
            ])
            ->latest('id')
            ->get()
            ->map(fn (SensusEkonomiPetugasReplacement $replacement) => $replacement->spkLama)
            ->filter(fn ($spk) => $spk instanceof Spk)
            ->values();
    }

    /**
     * @return array<int, int>
     */
    private function getStoppedReplacementIdBySpkId(int $terminNumber, int $tahun): array
    {
        return $this->getStoppedPetugasEligibleReplacementsForTermin($terminNumber, $tahun)
            ->mapWithKeys(fn (SensusEkonomiPetugasReplacement $replacement) => [
                (int) $replacement->spk_lama_id => (int) $replacement->id,
            ])
            ->all();
    }

    /**
     * Build target_sls and target_unit_sampel for a given SPK and termin.
     *
     * @return array{target_sls: int, target_unit_sampel: array<string, int>}
     */
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

    /**
     * Get PPK penandatangan.
     */
    private function getPpk(): ?Penandatangan
    {
        return Penandatangan::query()
            ->where('jenis_penandatangan', 'ppk')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get unit sampel items for SE kegiatan.
     *
     * @return array<int, array{id:int, nama:string}>
     */
    private function getUnitSampelItems(?Kegiatan $kegiatan): array
    {
        if (! $kegiatan) {
            return [];
        }

        $ids = $kegiatan->unit_sampel_pencacahan_ids ?? [];
        if (empty($ids)) {
            return [];
        }

        return MasterUnitSampel::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get()
            ->map(fn (MasterUnitSampel $u) => ['id' => $u->id, 'nama' => $u->nama])
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function extractSpkFrameSampelIds(Spk $spk): array
    {
        $alokasi = $spk->alokasiPetugas;
        if (! $alokasi) {
            return [];
        }

        $alokasi->loadMissing('frameSampelAllocations');

        return $alokasi->frameSampelAllocations
            ->pluck('kegiatan_frame_sampel_id')
            ->filter(fn ($id) => filled($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int, nama:string|null}>
     */
    private function getReplacementPetugasOptions(Collection $spks, Collection $activeReplacementRows): array
    {
        if (! Schema::hasTable('petugas')) {
            return [];
        }

        $sensusPetugasIds = collect();
        if (Schema::hasTable('alokasi_petugas') && Schema::hasTable('periode_alokasi') && Schema::hasTable('kegiatan')) {
            $sensusPetugasIds = AlokasiPetugas::query()
                ->whereNotNull('petugas_id')
                ->whereHas('periodeAlokasi.kegiatan', function ($query): void {
                    $query->where('jenis_kegiatan', 'sensus')
                        ->where('nama_kegiatan', 'like', '%sensus ekonomi%');
                })
                ->pluck('petugas_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique();
        }

        return Petugas::query()
            ->when(
                Schema::hasColumn('petugas', 'status'),
                fn ($query) => $query->where('status', 'aktif')
            )
            ->when(
                $sensusPetugasIds->isNotEmpty(),
                fn ($query) => $query->whereNotIn('id', $sensusPetugasIds->all())
            )
            ->orderBy('nama')
            ->get(['id', 'nama'])
            ->map(fn (Petugas $petugas) => [
                'id' => $petugas->id,
                'nama' => $petugas->nama,
            ])
            ->sortBy(fn (array $petugas): string => mb_strtolower((string) ($petugas['nama'] ?? '')))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<int, array{key:string, label:string, value:string}>
     */
    private function buildFrameMetadataItems(?array $metadata): array
    {
        if (! is_array($metadata)) {
            return [];
        }

        return collect($metadata)
            ->filter(function ($value, $key): bool {
                return is_string($key)
                    && trim($key) !== ''
                    && ! str_ends_with(strtolower($key), '_label')
                    && ! is_array($value)
                    && ! is_object($value)
                    && filled($value);
            })
            ->map(function ($value, $key) use ($metadata): array {
                $normalizedKey = (string) $key;

                $storedLabel = $metadata[$normalizedKey.'_label'] ?? null;
                $label = is_scalar($storedLabel) && filled($storedLabel)
                    ? (string) $storedLabel
                    : str_replace('_', ' ', ucfirst($normalizedKey));

                return [
                    'key' => $normalizedKey,
                    'label' => $label,
                    'value' => (string) $value,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $targetUnitSampel
     */
    private function resolveFrameTarget(?array $targetUnitSampel): float
    {
        if (! is_array($targetUnitSampel)) {
            return 0;
        }

        return (float) collect($targetUnitSampel)
            ->map(fn ($value) => max(0, (float) $value))
            ->sum();
    }

    /**
     * @param  Collection<int, Spk>  $spks
     * @return array{
     *   enabled:bool,
     *   default_periode_alokasi_id:int|null,
     *   stopped_petugas_options:array<int, array{id:int, nama:string|null}>,
     *   replacement_petugas_options:array<int, array{id:int, nama:string|null}>,
     *   spk_lama_options_by_stopped_petugas:array<int, array<int, array{id:int, hashed_id:string, nomor_spk:string, periode_alokasi_id:int|null, wilayah_keys:array<int, string>}>>,
     *   pml_cover_options_by_spk_lama_id:array<int, array<int, array{id:int, nama:string|null}>>,
     *   frame_detail_options_by_spk_lama_id:array<int, array<int, array{alokasi_petugas_frame_sampel_id:int, kegiatan_frame_sampel_id:int|null, target_awal:float, metadata_items:array<int, array{key:string, label:string, value:string}>}>>,
     *   spk_options:array<int, array{id:int, hashed_id:string, nomor_spk:string, petugas_id:int|null, petugas_nama:string|null, periode_alokasi_id:int|null}>,
     *   replacement_options:array<int, array{id:int, hashed_id:string, periode_alokasi_id:int, petugas_berhenti_id:int, petugas_berhenti_nama:string|null, petugas_pengganti_id:int|null, petugas_pengganti_nama:string|null, spk_lama_id:int|null, target_sisa:float, status:string}>,
     *   next_pkpp_nomor_preview:string|null
     * }
     */
    private function buildReplacementWorkflowPayload(Collection $spks, ?string $activeRoleName, int $tahun): array
    {
        $workflowPeriodeIds = $spks
            ->pluck('alokasiPetugas.periode_alokasi_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $activeReplacementRows = collect();
        if (Schema::hasTable('sensus_ekonomi_petugas_replacements') && $workflowPeriodeIds->isNotEmpty()) {
            $activeReplacementRows = SensusEkonomiPetugasReplacement::query()
                ->whereIn('periode_alokasi_id', $workflowPeriodeIds->all())
                ->where('status', '!=', 'dibatalkan')
                ->with(['petugasBerhenti', 'petugasPengganti'])
                ->latest('id')
                ->get();
        }

        $spkOptions = $spks
            ->map(fn (Spk $spk) => [
                'id' => $spk->id,
                'hashed_id' => $spk->hashed_id,
                'nomor_spk' => $spk->nomor_spk,
                'petugas_id' => $spk->petugas_id,
                'petugas_nama' => $spk->petugas?->nama,
                'periode_alokasi_id' => $spk->alokasiPetugas?->periode_alokasi_id,
            ])
            ->values()
            ->all();

        $stoppedPetugasOptionsById = [];
        $spkLamaOptionsByStoppedPetugas = [];
        $pmlCandidateSpks = collect();

        foreach ($spks as $spk) {
            $petugas = $spk->petugas;
            if (! $petugas) {
                continue;
            }

            $stoppedPetugasOptionsById[(int) $petugas->id] = [
                'id' => (int) $petugas->id,
                'nama' => $petugas->nama,
            ];
        }

        foreach ($activeReplacementRows as $replacement) {
            $petugasBerhentiId = (int) ($replacement->petugas_berhenti_id ?? 0);
            $petugasPenggantiId = (int) ($replacement->petugas_pengganti_id ?? 0);
            if ($petugasPenggantiId > 0) {
                $stoppedPetugasOptionsById[$petugasPenggantiId] = [
                    'id' => $petugasPenggantiId,
                    'nama' => $replacement->petugasPengganti?->nama,
                ];
            }

            $spkLama = $replacement->spkLama;
            if (! $spkLama instanceof Spk) {
                continue;
            }

            $spkLamaOptionsByStoppedPetugas[$petugasBerhentiId] ??= [];
            $spkLamaOptionsByStoppedPetugas[$petugasBerhentiId][] = [
                'id' => $spkLama->id,
                'hashed_id' => $spkLama->hashed_id,
                'nomor_spk' => $spkLama->nomor_spk,
                'periode_alokasi_id' => $spkLama->alokasiPetugas?->periode_alokasi_id,
                'wilayah_keys' => [],
            ];
        }

        if ($stoppedPetugasOptionsById === [] && Schema::hasTable('alokasi_petugas') && Schema::hasTable('periode_alokasi') && Schema::hasTable('kegiatan')) {
            AlokasiPetugas::query()
                ->with('petugas')
                ->whereNotNull('petugas_id')
                ->whereHas('periodeAlokasi.kegiatan', function ($query): void {
                    $query->where('jenis_kegiatan', 'sensus')
                        ->where('nama_kegiatan', 'like', '%sensus ekonomi%');
                })
                ->get()
                ->each(function (AlokasiPetugas $alokasiPetugas) use (&$stoppedPetugasOptionsById): void {
                    $petugas = $alokasiPetugas->petugas;
                    if (! $petugas) {
                        return;
                    }

                    $stoppedPetugasOptionsById[(int) $petugas->id] = [
                        'id' => (int) $petugas->id,
                        'nama' => $petugas->nama,
                    ];
                });
        }

        foreach ($spks as $spk) {
            $petugasId = $spk->petugas_id;
            if (! $petugasId) {
                continue;
            }

            $peran = $spk->alokasiPetugas?->peran ?? 'pcl_ppl';
            $frameSampelIds = $this->extractSpkFrameSampelIds($spk);

            if ($peran === 'pml') {
                $pmlCandidateSpks->push([
                    'petugas_id' => (int) $petugasId,
                    'petugas_nama' => $spk->petugas?->nama,
                    'periode_alokasi_id' => $spk->alokasiPetugas?->periode_alokasi_id,
                    'frame_sampel_ids' => $frameSampelIds,
                ]);

                continue;
            }

            if (! isset($stoppedPetugasOptionsById[$petugasId])) {
                continue;
            }

            if (! isset($spkLamaOptionsByStoppedPetugas[$petugasId])) {
                $spkLamaOptionsByStoppedPetugas[$petugasId] = [];
            }

            $spkLamaOptionsByStoppedPetugas[$petugasId][] = [
                'id' => $spk->id,
                'hashed_id' => $spk->hashed_id,
                'nomor_spk' => $spk->nomor_spk,
                'periode_alokasi_id' => $spk->alokasiPetugas?->periode_alokasi_id,
                'wilayah_keys' => [],
            ];
        }

        foreach ($spkLamaOptionsByStoppedPetugas as $petugasId => $options) {
            $uniqueById = collect($options)
                ->unique('id')
                ->sortBy('nomor_spk')
                ->values()
                ->all();

            $spkLamaOptionsByStoppedPetugas[$petugasId] = $uniqueById;
        }

        $pmlCoverOptionsBySpkLamaId = [];

        foreach ($spkLamaOptionsByStoppedPetugas as $spkOptions) {
            foreach ($spkOptions as $spkOption) {
                $spkLamaId = (int) ($spkOption['id'] ?? 0);
                $selectedPeriodeAlokasiId = (int) ($spkOption['periode_alokasi_id'] ?? 0);

                $stoppedSpk = $spks->firstWhere('id', $spkLamaId);
                $stoppedFrameSampelIds = $stoppedSpk instanceof Spk
                    ? $this->extractSpkFrameSampelIds($stoppedSpk)
                    : [];

                if ($spkLamaId <= 0 || $selectedPeriodeAlokasiId <= 0 || empty($stoppedFrameSampelIds)) {
                    $pmlCoverOptionsBySpkLamaId[$spkLamaId] = [];

                    continue;
                }

                $pmlCoverOptionsBySpkLamaId[$spkLamaId] = $pmlCandidateSpks
                    ->filter(function (array $candidate) use ($selectedPeriodeAlokasiId, $stoppedFrameSampelIds): bool {
                        $candidatePeriodeAlokasiId = (int) ($candidate['periode_alokasi_id'] ?? 0);
                        if ($candidatePeriodeAlokasiId !== $selectedPeriodeAlokasiId) {
                            return false;
                        }

                        $candidateFrameSampelIds = array_values(array_unique(array_map(
                            'intval',
                            $candidate['frame_sampel_ids'] ?? []
                        )));

                        if (empty($candidateFrameSampelIds)) {
                            return false;
                        }

                        return count(array_intersect($stoppedFrameSampelIds, $candidateFrameSampelIds)) > 0;
                    })
                    ->unique('petugas_id')
                    ->map(fn (array $candidate) => [
                        'id' => (int) $candidate['petugas_id'],
                        'nama' => $candidate['petugas_nama'],
                    ])
                    ->sortBy('nama')
                    ->values()
                    ->all();
            }
        }

        $frameDetailOptionsBySpkLamaId = [];
        foreach ($spkLamaOptionsByStoppedPetugas as $spkOptionsForPetugas) {
            foreach ($spkOptionsForPetugas as $spkOption) {
                $spkLamaId = (int) ($spkOption['id'] ?? 0);
                $spkLama = $spks->firstWhere('id', $spkLamaId);

                if (! $spkLama instanceof Spk) {
                    $frameDetailOptionsBySpkLamaId[$spkLamaId] = [];

                    continue;
                }

                $spkLama->loadMissing('alokasiPetugas.frameSampelAllocations.kegiatanFrameSampel');

                $frameDetailOptionsBySpkLamaId[$spkLamaId] = $spkLama->alokasiPetugas?->frameSampelAllocations
                    ?->map(function ($frameAllocation): array {
                        $kegiatanFrame = $frameAllocation->kegiatanFrameSampel;

                        return [
                            'alokasi_petugas_frame_sampel_id' => (int) $frameAllocation->id,
                            'kegiatan_frame_sampel_id' => $kegiatanFrame?->id,
                            'target_awal' => $this->resolveFrameTarget($kegiatanFrame?->target_unit_sampel),
                            'metadata_items' => $this->buildFrameMetadataItems(
                                is_array($kegiatanFrame?->identitas_tambahan) ? $kegiatanFrame->identitas_tambahan : null
                            ),
                        ];
                    })
                    ->values()
                    ->all() ?? [];
            }
        }

        $replacementOptions = $activeReplacementRows
            ->filter(fn (SensusEkonomiPetugasReplacement $replacement) => filled($replacement->petugas_pengganti_id))
            ->map(fn (SensusEkonomiPetugasReplacement $replacement) => [
                'id' => $replacement->id,
                'hashed_id' => $replacement->hashed_id,
                'periode_alokasi_id' => (int) $replacement->periode_alokasi_id,
                'petugas_berhenti_id' => (int) $replacement->petugas_berhenti_id,
                'petugas_berhenti_nama' => $replacement->petugasBerhenti?->nama,
                'petugas_pengganti_id' => $replacement->petugas_pengganti_id,
                'petugas_pengganti_nama' => $replacement->petugasPengganti?->nama,
                'spk_lama_id' => $replacement->spk_lama_id,
                'tanggal_berhenti' => $replacement->tanggal_berhenti?->format('Y-m-d'),
                'tanggal_mulai_pkpp' => $replacement->tanggal_mulai_pkpp?->format('Y-m-d'),
                'target_sisa' => (float) $replacement->target_sisa,
                'status' => $replacement->status,
            ])
            ->values()
            ->all();

        $nextPkppNomorPreview = null;
        if (Schema::hasTable('spk') && Schema::hasTable('sensus_ekonomi_pkpp_contracts')) {
            $nextPkppNomorPreview = app(SensusEkonomiPkNumberService::class)->previewNextNumber($tahun);
        }

        return [
            'enabled' => in_array($activeRoleName, ['admin', 'operator'], true),
            'default_periode_alokasi_id' => $workflowPeriodeIds->first(),
            'stopped_petugas_options' => array_values($stoppedPetugasOptionsById),
            'replacement_petugas_options' => $this->getReplacementPetugasOptions($spks, $activeReplacementRows),
            'spk_lama_options_by_stopped_petugas' => $spkLamaOptionsByStoppedPetugas,
            'pml_cover_options_by_spk_lama_id' => $pmlCoverOptionsBySpkLamaId,
            'frame_detail_options_by_spk_lama_id' => $frameDetailOptionsBySpkLamaId,
            'spk_options' => $spkOptions,
            'replacement_options' => $replacementOptions,
            'next_pkpp_nomor_preview' => $nextPkppNomorPreview,
        ];
    }

    /**
     * Index: show BAPP status per termin.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $this->userCanAccessBapp($request)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman BAPP SE2026.');
        }

        $tahun = ActiveYearService::get();
        $currentMonth = (int) now()->format('m');
        $hasBappTerminTable = $this->hasBappTerminTable();
        $documentType = $this->resolveDocumentType($request);
        $replacementTerminCount = $this->resolveReplacementTerminCount($request);
        $contextReplacementTerminCount = $this->getContextReplacementTerminCount($documentType, $replacementTerminCount);
        $kegiatan = $this->getSensusEkonomiKegiatan();
        $unitSampelItems = $this->getUnitSampelItems($kegiatan);

        $terminData = [];
        foreach (self::TERMIN_CONFIG as $terminNumber => $config) {
            $canGenerate = $currentMonth >= $config['bulan'];
            $contextSpks = $this->getSpksForBappContext($tahun, $terminNumber, $documentType);
            $bappCount = 0;
            if ($hasBappTerminTable) {
                $bappCount = $this->applyBappDocumentContextScope(
                    BappSeTermin::query()
                        ->where('termin', $terminNumber)
                        ->where('tahun', $tahun),
                    $documentType,
                    $contextReplacementTerminCount,
                )->count();
            }
            $spkCount = $contextSpks->count();

            $terminData[] = [
                'termin' => $terminNumber,
                'termin_hashed' => Hashids::encode($terminNumber),
                'termin_roman' => $config['roman'],
                'bulan' => $config['bulan'],
                'bulan_label' => $config['bulan_label'],
                'persentase' => $config['persentase'],
                'can_generate' => $canGenerate,
                'bapp_count' => $bappCount,
                'spk_count' => $spkCount,
                'is_complete' => $spkCount > 0 && $bappCount >= $spkCount,
            ];
        }

        return Inertia::render('Bapp/Index', [
            'tahun' => $tahun,
            'termin_data' => $terminData,
            'document_type' => $documentType,
            'replacement_termin_count' => $contextReplacementTerminCount,
            'has_kegiatan' => $kegiatan !== null,
            'unit_sampel_items' => $unitSampelItems,
        ]);
    }

    /**
     * Show the realisasi input form for a specific termin.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $this->userCanAccessBapp($request)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman BAPP SE2026.');
        }

        $tahun = ActiveYearService::get();
        $terminHashed = (string) $request->query('termin', '');
        $decoded = Hashids::decode($terminHashed);
        $terminNumber = isset($decoded[0]) ? (int) $decoded[0] : null;

        if ($terminNumber === null || ! isset(self::TERMIN_CONFIG[$terminNumber])) {
            return redirect()->route('bapp.index')->with('error', 'Termin tidak valid.');
        }

        $config = self::TERMIN_CONFIG[$terminNumber];
        $currentMonth = (int) now()->format('m');
        $hasBappTerminTable = $this->hasBappTerminTable();
        $documentType = $this->resolveDocumentType($request);
        $replacementTerminCount = $this->resolveReplacementTerminCount($request);
        $contextReplacementTerminCount = $this->getContextReplacementTerminCount($documentType, $replacementTerminCount);

        $kegiatan = $this->getSensusEkonomiKegiatan();
        if (! $kegiatan) {
            return redirect()->route('bapp.index')->with('error', 'Kegiatan Sensus Ekonomi tidak ditemukan.');
        }

        $unitSampelItems = $this->getUnitSampelItems($kegiatan);
        $spks = $this->getSpksForBappContext($tahun, $terminNumber, $documentType);
        $ppk = $this->getPpk();
        $nomorBappMap = $this->generateNomorBappMap(
            $spks,
            $config['roman'],
            $tahun,
            $documentType,
            $contextReplacementTerminCount,
        );

        $spkList = $spks->map(function (Spk $spk) use ($terminNumber, $nomorBappMap, $documentType, $contextReplacementTerminCount, $hasBappTerminTable): array {
            $targetData = $this->buildTargetForTermin($spk, $terminNumber);
            $petugas = $spk->petugas;
            $alokasi = $spk->alokasiPetugas;
            $peran = $alokasi?->peran ?? 'pcl_ppl';

            /** @var BappSeTermin|null $existing */
            $existing = null;
            if ($hasBappTerminTable) {
                $existing = $this->applyBappDocumentContextScope(
                    BappSeTermin::query()
                        ->where('spk_id', $spk->id)
                        ->where('termin', $terminNumber),
                    $documentType,
                    $contextReplacementTerminCount,
                )->first();
            }

            return [
                'spk_id' => $spk->id,
                'spk_hashed_id' => $spk->hashed_id,
                'nomor_spk' => $spk->nomor_spk,
                'nilai_kontrak' => (float) $spk->nilai_kontrak,
                'peran' => $peran,
                'jenis_pihak_kedua' => $this->getJenisPihakKedua($peran),
                'petugas' => [
                    'id' => $petugas?->id,
                    'nama' => $petugas?->nama,
                    'nik' => $petugas?->nik ?? '',
                ],
                'target_sls' => $targetData['target_sls'],
                'target_unit_sampel' => $targetData['target_unit_sampel'],
                'nomor_bapp_auto' => $nomorBappMap[$spk->id] ?? '',
                'has_bapp' => $existing !== null,
                'bapp_hashed_id' => $existing?->hashed_id,
                'tanggal_bapp' => $existing?->tanggal_bapp?->format('Y-m-d'),
                'bapp_preview_url' => $existing ? route('bapp.preview', $existing) : null,
                'bapp_download_url' => $existing ? route('bapp.download', $existing) : null,
                'realisasi_sls' => $existing?->realisasi_sls,
                'realisasi_unit_sampel' => $existing?->realisasi_unit_sampel ?? [],
                'file_path' => $existing?->file_path,
                'fasih_screenshot_path' => $existing?->fasih_screenshot_path,
            ];
        })->values()->all();

        // Shared tanggal_bapp: pick from first existing BAPP record or use default
        $existingBapp = null;
        if ($hasBappTerminTable) {
            $existingBapp = $this->applyBappDocumentContextScope(
                BappSeTermin::query()
                    ->where('termin', $terminNumber)
                    ->where('tahun', $tahun),
                $documentType,
                $contextReplacementTerminCount,
            )
                ->whereNotNull('tanggal_bapp')
                ->first();
        }

        $tahunStr = (string) $tahun;
        $tanggalDefault = $config['tanggal_default']
            ? sprintf($config['tanggal_default'], $tahunStr)
            : null;
        $tanggalBapp = $existingBapp?->tanggal_bapp?->format('Y-m-d') ?? $tanggalDefault;

        $activeRoleName = effectiveUser($request)?->getActiveRole()?->name;
        $tanggalMin = sprintf($config['tanggal_min'], $tahunStr);
        $canInputRealisasi = $activeRoleName !== 'ketua_tim' || now()->format('Y-m-d') >= $tanggalMin;

        return Inertia::render('Bapp/Create', [
            'tahun' => $tahun,
            'termin' => $terminNumber,
            'termin_hashed' => Hashids::encode($terminNumber),
            'termin_roman' => $config['roman'],
            'bulan' => $config['bulan'],
            'bulan_label' => $config['bulan_label'],
            'persentase' => $config['persentase'],
            'can_generate' => $currentMonth >= $config['bulan'],
            'can_input_realisasi' => $canInputRealisasi,
            'tanggal_bapp' => $tanggalBapp,
            'tanggal_min' => $tanggalMin,
            'tanggal_max' => sprintf($config['tanggal_max'], $tahunStr),
            'tanggal_fixed' => $config['tanggal_default'] !== null,
            'spk_list' => $spkList,
            'document_type' => $documentType,
            'replacement_termin_count' => $contextReplacementTerminCount,
            'unit_sampel_items' => $unitSampelItems,
            'ketua_tim' => [
                'nama' => $kegiatan->ketuaTim?->name,
                'nip' => $kegiatan->ketuaTim?->nip,
            ],
            'ppk' => [
                'nama' => $ppk?->nama,
                'nip' => $ppk?->nip,
                'jabatan' => $ppk?->jabatan,
            ],
            'import_preview' => session('import_preview'),
        ]);
    }

    /**
     * Save realisasi data for multiple SPKs.
     */
    public function storeRealisasi(Request $request): RedirectResponse
    {
        if (! $this->hasBappTerminTable()) {
            return back()->with('error', 'Tabel BAPP belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $tahun = ActiveYearService::get();
        $terminNumber = (int) $request->input('termin', 1);
        $documentType = $this->resolveDocumentType($request);
        $replacementTerminCount = $this->resolveReplacementTerminCount($request);
        $contextReplacementTerminCount = $this->getContextReplacementTerminCount($documentType, $replacementTerminCount);

        if (! isset(self::TERMIN_CONFIG[$terminNumber])) {
            return back()->with('error', 'Termin tidak valid.');
        }

        $config = self::TERMIN_CONFIG[$terminNumber];
        $entries = $request->input('entries', []);
        $sharedTanggalBapp = $request->input('tanggal_bapp');

        if (empty($entries)) {
            return back()->with('error', 'Tidak ada data realisasi yang dikirim.');
        }

        $saved = 0;
        $kegiatan = $this->getSensusEkonomiKegiatan();
        $ppk = $this->getPpk();
        $unitSampelItems = $this->getUnitSampelItems($kegiatan);
        $allSpks = $this->getSpksForBappContext($tahun, $terminNumber, $documentType);
        $allowedSpkIds = $allSpks->pluck('id')->map(fn ($id) => (int) $id)->all();
        $stoppedReplacementIdBySpkId = $documentType === 'stopped_petugas'
            ? $this->getStoppedReplacementIdBySpkId($terminNumber, $tahun)
            : [];
        $nomorBappMap = $this->generateNomorBappMap(
            $allSpks,
            $config['roman'],
            $tahun,
            $documentType,
            $contextReplacementTerminCount,
        );

        foreach ($entries as $entry) {
            $spkId = null;
            if (! empty($entry['spk_hashed_id'])) {
                $spkId = Hashids::decode((string) $entry['spk_hashed_id'])[0] ?? null;
            } elseif (! empty($entry['spk_id'])) {
                $spkId = (int) $entry['spk_id'];
            }
            if (! $spkId) {
                continue;
            }

            $spk = Spk::query()->find($spkId);
            if (! $spk || ! in_array($spkId, $allowedSpkIds, true)) {
                continue;
            }

            $realisasiSls = isset($entry['realisasi_sls']) && $entry['realisasi_sls'] !== '' && $entry['realisasi_sls'] !== null
                ? max(0, (int) $entry['realisasi_sls'])
                : null;

            $realisasiUnitSampel = [];
            foreach ($unitSampelItems as $unit) {
                $unitKey = strtolower($unit['nama']);
                $val = $entry['realisasi_unit_sampel'][$unitKey] ?? null;
                if ($val !== null && $val !== '') {
                    $realisasiUnitSampel[$unitKey] = max(0, (int) $val);
                }
            }

            $targetData = $this->buildTargetForTermin($spk, $terminNumber);
            $nilaiPerjanjian = round((float) $spk->nilai_kontrak * $config['persentase'] / 100, 2);
            $entryTanggalBapp = $this->resolveEntryTanggalBapp($entry, $sharedTanggalBapp);

            BappSeTermin::query()->updateOrCreate(
                $this->withBappDocumentContextAttributes([
                    'spk_id' => $spkId,
                    'termin' => $terminNumber,
                ], $documentType, $contextReplacementTerminCount),
                $this->withBappDocumentContextAttributes([
                    'replacement_id' => $documentType === 'stopped_petugas'
                        ? ($stoppedReplacementIdBySpkId[$spkId] ?? null)
                        : null,
                    'petugas_id' => $spk->petugas_id,
                    'bulan' => $config['bulan'],
                    'tahun' => $tahun,
                    'nomor_bapp' => $nomorBappMap[$spkId] ?? null,
                    'tanggal_bapp' => $entryTanggalBapp ?: null,
                    'nama_ketua_tim' => $kegiatan?->ketuaTim?->name,
                    'nip_ketua_tim' => $this->getNipKetuaTim($kegiatan),
                    'nama_ppk' => $ppk ? $this->stripGelar($ppk->nama) : null,
                    'nip_ppk' => $ppk?->nip,
                    'jabatan_ppk' => $ppk?->jabatan,
                    'nama_kabkota' => config('app.instansi_kabupaten', ''),
                    'target_sls' => $targetData['target_sls'],
                    'target_unit_sampel' => empty($targetData['target_unit_sampel']) ? null : $targetData['target_unit_sampel'],
                    'realisasi_sls' => $realisasiSls,
                    'realisasi_unit_sampel' => empty($realisasiUnitSampel) ? null : $realisasiUnitSampel,
                    'persentase' => $config['persentase'],
                    'nilai_perjanjian' => $nilaiPerjanjian,
                    'created_by' => Auth::id(),
                ], $documentType, $contextReplacementTerminCount)
            );

            $saved++;
        }

        return back()->with('success', "Data realisasi berhasil disimpan untuk {$saved} SPK.");
    }

    /**
     * Generate BAPP PDF for a specific SPK+termin.
     */
    public function generate(Request $request): BinaryFileResponse|\Illuminate\Http\Response
    {
        $documentType = $this->resolveDocumentType($request);
        $replacementTerminCount = $this->resolveReplacementTerminCount($request);
        $contextReplacementTerminCount = $this->getContextReplacementTerminCount($documentType, $replacementTerminCount);

        $spkHashedId = $request->input('spk_hashed_id') ?? $request->input('spk_id');
        $spkId = Hashids::decode((string) ($spkHashedId ?? ''))[0] ?? null;
        if (! $spkId) {
            abort(422, 'SPK tidak valid.');
        }
        $terminNumber = (int) $request->input('termin', 1);

        if (! isset(self::TERMIN_CONFIG[$terminNumber])) {
            abort(422, 'Termin tidak valid.');
        }

        if (! $this->hasBappTerminTable()) {
            abort(503, 'Tabel BAPP belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $spk = Spk::query()
            ->with(['petugas', 'alokasiPetugas.periodeAlokasi.kegiatan'])
            ->findOrFail($spkId);

        /** @var BappSeTermin $bapp */
        $bapp = $this->applyBappDocumentContextScope(
            BappSeTermin::query()
                ->where('spk_id', $spkId)
                ->where('termin', $terminNumber),
            $documentType,
            $contextReplacementTerminCount,
        )->firstOrFail();

        $config = self::TERMIN_CONFIG[$terminNumber];
        $alokasi = $spk->alokasiPetugas;
        $peran = $alokasi?->peran ?? 'pcl_ppl';
        $petugas = $spk->petugas;

        $viewData = $this->buildPdfViewData($bapp, $spk, $peran, $petugas, $config);

        $pdf = Pdf::loadView('bapp-se', $viewData);
        $pdf->setPaper('A4', 'portrait');

        $nomorBappSafe = preg_replace('/[\/\\\:*?"<>|]/', '-', $bapp->nomor_bapp ?? ('BAPP-'.$config['roman']));
        $filename = sprintf(
            'BAPP-Termin%s-%s-%s.pdf',
            $config['roman'],
            $nomorBappSafe,
            now()->format('YmdHis')
        );

        $merged = $this->buildMergedPdf($viewData);

        // Save to storage
        $storagePath = 'bapp-se/'.$bapp->tahun.'/termin-'.$terminNumber.'/'.$spk->id.'.pdf';
        Storage::put($storagePath, $merged);

        BappSeTermin::query()->where('id', $bapp->id)->update(['file_path' => $storagePath]);

        return response($merged, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Generate BAPP PDFs for all SPKs in a termin (batch).
     */
    public function generateBatch(Request $request): RedirectResponse
    {
        if (! $this->hasBappTerminTable()) {
            return back()->with('error', 'Tabel BAPP belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $tahun = ActiveYearService::get();
        $terminNumber = (int) $request->input('termin', 1);
        $documentType = $this->resolveDocumentType($request);
        $replacementTerminCount = $this->resolveReplacementTerminCount($request);
        $contextReplacementTerminCount = $this->getContextReplacementTerminCount($documentType, $replacementTerminCount);

        if (! isset(self::TERMIN_CONFIG[$terminNumber])) {
            return back()->with('error', 'Termin tidak valid.');
        }

        $config = self::TERMIN_CONFIG[$terminNumber];
        $currentMonth = (int) now()->format('m');
        $selectedSpkHashedIds = collect($request->input('spk_hashed_ids', []))
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->values()
            ->all();

        if ($currentMonth < $config['bulan']) {
            return back()->with('error', 'Generate BAPP Termin '.$config['roman'].' hanya dapat dilakukan mulai bulan '.$config['bulan_label'].'.');
        }

        $spks = $this->getSpksForBappContext($tahun, $terminNumber, $documentType);
        if (! empty($selectedSpkHashedIds)) {
            $spks = $spks->filter(fn (Spk $spk) => in_array($spk->hashed_id, $selectedSpkHashedIds, true))->values();
        }

        if ($spks->isEmpty()) {
            return back()->with('error', 'Tidak ada SPK terpilih untuk digenerate.');
        }

        $nomorBappMap = $this->generateNomorBappMap(
            $spks,
            $config['roman'],
            $tahun,
            $documentType,
            $contextReplacementTerminCount,
        );
        $generated = 0;

        foreach ($spks as $spk) {
            $bapp = $this->applyBappDocumentContextScope(
                BappSeTermin::query()
                    ->where('spk_id', $spk->id)
                    ->where('termin', $terminNumber),
                $documentType,
                $contextReplacementTerminCount,
            )->first();

            if (! $bapp || $bapp->realisasi_sls === null) {
                continue;
            }

            try {
                // Ensure nomor_bapp is set
                if (empty($bapp->nomor_bapp) && isset($nomorBappMap[$spk->id])) {
                    BappSeTermin::query()->where('id', $bapp->id)->update(['nomor_bapp' => $nomorBappMap[$spk->id]]);
                    $bapp->nomor_bapp = $nomorBappMap[$spk->id];
                }

                $alokasi = $spk->alokasiPetugas;
                $peran = $alokasi?->peran ?? 'pcl_ppl';
                $petugas = $spk->petugas;

                $viewData = $this->buildPdfViewData($bapp, $spk, $peran, $petugas, $config);

                $storagePath = 'bapp-se/'.$tahun.'/termin-'.$terminNumber.'/'.$spk->id.'.pdf';
                Storage::put($storagePath, $this->buildMergedPdf($viewData));

                BappSeTermin::query()->where('id', $bapp->id)->update(['file_path' => $storagePath]);
                $generated++;
            } catch (\Throwable $e) {
                Log::error('Error generating BAPP for SPK '.$spk->id.': '.$e->getMessage());
            }
        }

        return back()->with('success', "Berhasil generate {$generated} BAPP Termin {$config['roman']}.");
    }

    /**
     * Preview a BAPP PDF inline in browser.
     */
    public function preview(BappSeTermin $bapp): \Illuminate\Http\Response
    {
        $bapp->loadMissing(['spk.petugas', 'spk.alokasiPetugas.periodeAlokasi.kegiatan']);
        $spk = $bapp->spk;

        if (! $spk) {
            abort(404, 'SPK tidak ditemukan.');
        }

        $terminNumber = $bapp->termin;
        $config = self::TERMIN_CONFIG[$terminNumber] ?? self::TERMIN_CONFIG[1];
        $alokasi = $spk->alokasiPetugas;
        $peran = $alokasi?->peran ?? 'pcl_ppl';
        $petugas = $spk->petugas;

        $viewData = $this->buildPdfViewData($bapp, $spk, $peran, $petugas, $config);

        $merged = $this->buildMergedPdf($viewData);

        return response($merged, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.sprintf(
                'BAPP-Termin%s-%s-preview.pdf',
                $config['roman'],
                preg_replace('/[\/\\\\:*?"<>|]/', '-', $bapp->nomor_bapp ?? $spk->nomor_spk)
            ).'"',
        ]);
    }

    /**
     * Download a specific BAPP PDF (re-generate from saved data).
     */
    public function download(BappSeTermin $bapp): \Illuminate\Http\Response|BinaryFileResponse
    {
        $bapp->loadMissing(['spk.petugas', 'spk.alokasiPetugas.periodeAlokasi.kegiatan']);
        $spk = $bapp->spk;

        if (! $spk) {
            abort(404, 'SPK tidak ditemukan.');
        }

        $terminNumber = $bapp->termin;
        $config = self::TERMIN_CONFIG[$terminNumber] ?? self::TERMIN_CONFIG[1];
        $alokasi = $spk->alokasiPetugas;
        $peran = $alokasi?->peran ?? 'pcl_ppl';
        $petugas = $spk->petugas;

        $viewData = $this->buildPdfViewData($bapp, $spk, $peran, $petugas, $config);

        $filename = sprintf(
            'BAPP-Termin%s-%s.pdf',
            $config['roman'],
            preg_replace('/[\/\\\\:*?"<>|]/', '-', $bapp->nomor_bapp ?? $spk->nomor_spk)
        );

        return response($this->buildMergedPdf($viewData), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Download the Excel template for realisasi input.
     */
    public function downloadTemplate(Request $request): BinaryFileResponse
    {
        $tahun = ActiveYearService::get();
        $documentType = $this->resolveDocumentType($request);
        $terminNumber = (int) $request->input('termin', 1);
        if (! isset(self::TERMIN_CONFIG[$terminNumber])) {
            $terminNumber = 1;
        }

        $kegiatan = $this->getSensusEkonomiKegiatan();
        $unitSampelItems = $this->getUnitSampelItems($kegiatan);
        $spks = $this->getSpksForBappContext($tahun, $terminNumber, $documentType);

        $rows = $spks->map(function (Spk $spk): array {
            return [
                'nomor_spk' => $spk->nomor_spk,
                'nik_petugas' => $spk->petugas?->nik ?? '',
                'nama_petugas' => $spk->petugas?->nama ?? '',
            ];
        })->all();

        return Excel::download(
            new BappSeRealisasiTemplateExport($rows, $unitSampelItems),
            'Template-Realisasi-BAPP-SE2026.xlsx'
        );
    }

    /**
     * Import realisasi from Excel.
     */
    public function importRealisasi(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'termin' => ['required', 'integer', 'in:1,2'],
        ]);

        $terminNumber = (int) $request->input('termin');
        $tahun = ActiveYearService::get();
        $kegiatan = $this->getSensusEkonomiKegiatan();
        $unitSampelItems = $this->getUnitSampelItems($kegiatan);

        $import = new BappSeRealisasiImport;
        Excel::import($import, $request->file('file'));
        $rows = $import->rows();

        if (empty($rows)) {
            return back()->with('error', 'File Excel tidak memiliki data valid.');
        }

        $previewRows = [];
        $unmatchedSpks = [];

        foreach ($rows as $row) {
            $nomorSpk = $row['nomor_spk'] ?? '';
            $nikPetugas = $row['nik_petugas'] ?? '';

            $spk = Spk::query()
                ->where('nomor_spk', $nomorSpk)
                ->orWhereHas('petugas', function ($q) use ($nikPetugas): void {
                    if ($nikPetugas) {
                        $q->where('nik', 'like', '%'.$nikPetugas.'%');
                    }
                })
                ->where('lampiran_template', 'sensus_ekonomi')
                ->where('addendum_number', 0)
                ->first();

            if (! $spk && $nomorSpk) {
                $spk = Spk::query()->where('nomor_spk', $nomorSpk)->first();
            }

            if (! $spk) {
                if ($nomorSpk) {
                    $unmatchedSpks[] = $nomorSpk;
                }

                continue;
            }

            $targetData = $this->buildTargetForTermin($spk, $terminNumber);
            $realisasiUnitSampel = [];

            if (! empty($row['realisasi_unit_sampel'])) {
                foreach ($row['realisasi_unit_sampel'] as $unitKey => $count) {
                    $realisasiUnitSampel[$unitKey] = max(0, (int) $count);
                }
            }

            $previewRows[] = [
                'spk_id' => $spk->id,
                'spk_hashed_id' => Hashids::encode($spk->id),
                'nomor_spk' => $spk->nomor_spk,
                'petugas_nama' => $spk->petugas?->nama,
                'realisasi_sls' => $row['realisasi_sls'] !== null ? max(0, (int) $row['realisasi_sls']) : null,
                'realisasi_unit_sampel' => $realisasiUnitSampel,
                'target_sls' => $targetData['target_sls'],
                'target_unit_sampel' => $targetData['target_unit_sampel'],
            ];
        }

        if (empty($previewRows)) {
            $message = 'Tidak ada SPK yang cocok dalam file Excel.';
            if (! empty($unmatchedSpks)) {
                $message .= ' SPK tidak ditemukan: '.implode(', ', array_slice($unmatchedSpks, 0, 5));
            }

            return back()->with('error', $message);
        }

        /** @var array{preview_rows: array<int, array<string, mixed>>, unmatched_spks: string[]} $importPreview */
        $importPreview = [
            'preview_rows' => $previewRows,
            'unmatched_spks' => $unmatchedSpks,
        ];

        return back()->with('import_preview', $importPreview);
    }

    /**
     * Upload Fasih screenshot for a specific BAPP.
     */
    public function uploadFasihScreenshot(BappSeTermin $bapp, Request $request): RedirectResponse
    {
        $request->validate([
            'screenshot' => ['required', 'file', 'image', 'max:5120'],
        ]);

        $file = $request->file('screenshot');
        $path = $file->store('bapp-se/screenshots/'.$bapp->tahun, 'public');

        BappSeTermin::query()->where('id', $bapp->id)->update([
            'fasih_screenshot_path' => $path,
        ]);

        return back()->with('success', 'Screenshot berhasil diupload.');
    }

    /**
     * Show detail page for a specific termin: list all petugas with BAPP status.
     */
    public function show(Request $request, string $terminHashed): Response|RedirectResponse
    {
        if (! $this->userCanAccessBapp($request)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman BAPP SE2026.');
        }

        $decoded = Hashids::decode($terminHashed);
        $termin = ! empty($decoded) ? (int) $decoded[0] : null;

        if ($termin === null || ! isset(self::TERMIN_CONFIG[$termin])) {
            return redirect()->route('bapp.index')->with('error', 'Termin tidak valid.');
        }

        $tahun = ActiveYearService::get();
        $config = self::TERMIN_CONFIG[$termin];
        $hasBappTerminTable = $this->hasBappTerminTable();
        $documentType = $this->resolveDocumentType($request);
        $replacementTerminCount = $this->resolveReplacementTerminCount($request);
        $contextReplacementTerminCount = $this->getContextReplacementTerminCount($documentType, $replacementTerminCount);
        $tahunStr = (string) $tahun;
        $tanggalMin = sprintf($config['tanggal_min'], $tahunStr);
        $currentMonth = (int) now()->format('m');
        $canGenerate = $currentMonth >= $config['bulan'];
        $activeRoleName = effectiveUser($request)?->getActiveRole()?->name;
        $canInputRealisasi = $activeRoleName !== 'ketua_tim' || now()->format('Y-m-d') >= $tanggalMin;

        $spks = $this->getSpksForBappContext($tahun, $termin, $documentType);
        $nomorBappMap = $this->generateNomorBappMap(
            $spks,
            $config['roman'],
            $tahun,
            $documentType,
            $contextReplacementTerminCount,
        );

        $bappRecords = collect();
        if ($hasBappTerminTable) {
            $bappRecords = $this->applyBappDocumentContextScope(
                BappSeTermin::query()
                    ->where('termin', $termin)
                    ->where('tahun', $tahun),
                $documentType,
                $contextReplacementTerminCount,
            )
                ->get()
                ->keyBy('spk_id');
        }

        $spkList = $spks->map(function (Spk $spk) use ($bappRecords, $nomorBappMap) {
            /** @var BappSeTermin|null $bapp */
            $bapp = $bappRecords->get($spk->id);
            $petugas = $spk->petugas;
            $peran = $spk->alokasiPetugas?->peran ?? 'pcl_ppl';

            return [
                'spk_id' => $spk->id,
                'spk_hashed_id' => $spk->hashed_id,
                'nomor_spk' => $spk->nomor_spk,
                'petugas' => [
                    'id' => $petugas?->id,
                    'nama' => $petugas?->nama,
                    'nik' => $petugas?->nik,
                ],
                'peran' => $peran,
                'nomor_bapp_auto' => $nomorBappMap[$spk->id] ?? '',
                'has_bapp' => $bapp !== null,
                'bapp_hashed_id' => $bapp?->hashed_id,
                'bapp_preview_url' => $bapp ? route('bapp.preview', $bapp) : null,
                'bapp_download_url' => $bapp ? route('bapp.download', $bapp) : null,
                'bapp_download_signed_url' => $bapp ? route('bapp.download-signed', $bapp) : null,
                'nomor_bapp' => $bapp?->nomor_bapp,
                'tanggal_bapp' => $bapp?->tanggal_bapp?->format('Y-m-d'),
                'file_path' => $bapp?->file_path,
                'signed_file_path' => $bapp?->signed_file_path,
                'signed_uploaded_at' => $bapp?->signed_uploaded_at?->format('d M Y H:i'),
                'fasih_screenshot_path' => $bapp?->fasih_screenshot_path,
                'realisasi_sls' => $bapp?->realisasi_sls,
                'realisasi_unit_sampel' => $bapp?->realisasi_unit_sampel ?? [],
            ];
        })->values()->all();

        $totalCount = count($spkList);
        $generatedCount = collect($spkList)->filter(fn ($item) => $item['has_bapp'] && $item['file_path'])->count();
        $signedCount = collect($spkList)->filter(fn ($item) => filled($item['signed_file_path']))->count();

        return Inertia::render('Bapp/Show', [
            'tahun' => $tahun,
            'termin' => $termin,
            'termin_hashed' => Hashids::encode($termin),
            'termin_roman' => $config['roman'],
            'termin_options' => [
                ['termin' => 1, 'termin_hashed' => Hashids::encode(1), 'termin_roman' => self::TERMIN_CONFIG[1]['roman']],
                ['termin' => 2, 'termin_hashed' => Hashids::encode(2), 'termin_roman' => self::TERMIN_CONFIG[2]['roman']],
            ],
            'bulan_label' => $config['bulan_label'],
            'persentase' => $config['persentase'],
            'can_generate' => $canGenerate,
            'can_input_realisasi' => $canInputRealisasi,
            'tanggal_min' => $tanggalMin,
            'document_type' => $documentType,
            'replacement_termin_count' => $contextReplacementTerminCount,
            'spk_list' => $spkList,
            'summary' => [
                'total' => $totalCount,
                'generated' => $generatedCount,
                'signed' => $signedCount,
            ],
        ]);
    }

    /**
     * Upload signed PDF for a specific BAPP record.
     */
    public function uploadSignedBapp(BappSeTermin $bapp, Request $request): RedirectResponse
    {
        if (! $this->userCanAccessBapp($request)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        if (filled($bapp->signed_file_path)) {
            Storage::disk('public')->delete($bapp->signed_file_path);
        }

        $file = $request->file('file');
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($bapp->nomor_bapp ?? 'BAPP'));
        $filename = 'BAPP_SIGNED_'.$safeName.'_'.time().'.pdf';
        $path = $file->storeAs('bapp-se/signed/'.$bapp->tahun, $filename, 'public');

        $bapp->update([
            'signed_file_path' => $path,
            'signed_uploaded_at' => now(),
        ]);

        return back()->with('success', 'BAPP bertanda tangan berhasil diunggah.');
    }

    /**
     * Download signed BAPP file.
     */
    public function downloadSigned(BappSeTermin $bapp): BinaryFileResponse|\Illuminate\Http\Response
    {
        $absolutePath = Storage::disk('public')->path($bapp->signed_file_path ?? '');

        if (! $bapp->signed_file_path || ! file_exists($absolutePath)) {
            abort(404, 'File bertanda tangan belum tersedia.');
        }

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($bapp->nomor_bapp ?? 'BAPP'));

        return response()->download($absolutePath, 'BAPP_SIGNED_'.$safeName.'.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Build the view data array for PDF rendering.
     *
     * @param  array{bulan:int, bulan_label:string, persentase:int, roman:string}  $config
     * @return array<string, mixed>
     */
    private function buildPdfViewData(BappSeTermin $bapp, Spk $spk, string $peran, ?Petugas $petugas, array $config): array
    {
        $jenisPihakKedua = $this->getJenisPihakKedua($peran);

        return [
            'nomor_bapp' => $bapp->nomor_bapp,
            'tanggal_bapp' => $bapp->tanggal_bapp,
            'termin_roman' => $config['roman'],
            'termin_number' => $bapp->termin,
            'persentase' => $config['persentase'],
            'jenis_pihak_kedua' => $jenisPihakKedua,
            'is_usaha_besar' => false,
            'nama_petugas' => $petugas?->nama,
            'nik_petugas' => $petugas?->nik ?? '',
            'nama_ketua_tim' => $bapp->nama_ketua_tim,
            'nip_ketua_tim' => $bapp->nip_ketua_tim ?: $this->getNipKetuaTim($this->getSensusEkonomiKegiatan()),
            'nama_ppk' => $bapp->nama_ppk ? $this->stripGelar($bapp->nama_ppk) : null,
            'nip_ppk' => $bapp->nip_ppk,
            'jabatan_ppk' => $bapp->jabatan_ppk,
            'nama_kabkota' => $bapp->nama_kabkota ?: config('app.instansi_kabupaten', ''),
            'nomor_spk' => $spk->nomor_spk,
            'target_sls' => $bapp->target_sls,
            'target_unit_sampel' => $bapp->target_unit_sampel ?? [],
            'realisasi_sls' => $bapp->realisasi_sls,
            'realisasi_unit_sampel' => $bapp->realisasi_unit_sampel ?? [],
            'nilai_perjanjian' => (float) ($bapp->nilai_perjanjian ?? 0),
            'fasih_screenshot_path' => $bapp->fasih_screenshot_path,
        ];
    }

    /**
     * Generate and merge three PDF parts into one document:
     *   Part 1 — portrait pages 1-2 (main BAPP content + signatures)
     *   Part 2 — landscape page 3 (table: DAFTAR URAIAN PEKERJAAN)
     *   Part 3 — portrait page 4 (bukti pencapaian + final signatures)
     *
     * @param  array<string, mixed>  $viewData
     */
    private function buildMergedPdf(array $viewData): string
    {
        $portraitPdf = Pdf::loadView('bapp-se', array_merge($viewData, ['page_number_offset' => 0]))->setPaper('A4', 'portrait')->output();
        $landscapePdf = Pdf::loadView('bapp-se-lampiran-table', array_merge($viewData, ['page_number_offset' => 2]))->setPaper('A4', 'landscape')->output();
        $screenshotPdf = Pdf::loadView('bapp-se-lampiran-screenshot', array_merge($viewData, ['page_number_offset' => 3]))->setPaper('A4', 'portrait')->output();

        $tmpPortrait = tempnam(sys_get_temp_dir(), 'bapp_').'.pdf';
        $tmpLandscape = tempnam(sys_get_temp_dir(), 'bapp_').'.pdf';
        $tmpScreenshot = tempnam(sys_get_temp_dir(), 'bapp_').'.pdf';

        file_put_contents($tmpPortrait, $portraitPdf);
        file_put_contents($tmpLandscape, $landscapePdf);
        file_put_contents($tmpScreenshot, $screenshotPdf);

        $fpdi = new Fpdi;
        $fpdi->setPrintHeader(false);
        $fpdi->setPrintFooter(false);
        $fpdi->SetMargins(0, 0, 0);
        $fpdi->SetAutoPageBreak(false, 0);

        foreach ([$tmpPortrait, $tmpLandscape, $tmpScreenshot] as $tmpFile) {
            $pageCount = $fpdi->setSourceFile($tmpFile);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplIdx = $fpdi->importPage($pageNo);
                $size = $fpdi->getTemplateSize($tplIdx);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($tplIdx, 0, 0, $size['width'], $size['height'], true);
            }
        }

        $merged = $fpdi->Output('merged.pdf', 'S');

        @unlink($tmpPortrait);
        @unlink($tmpLandscape);
        @unlink($tmpScreenshot);

        return $merged;
    }
}
