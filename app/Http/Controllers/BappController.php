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
use App\Http\Controllers\Concerns\BappDocumentContextSupport;
use App\Http\Controllers\Concerns\BappNumberingSupport;
use App\Http\Controllers\Concerns\BappFrameSupport;
use App\Http\Controllers\Concerns\BappSensusSupport;
use App\Http\Controllers\Concerns\BappPdfSupport;

class BappController extends Controller
{
    use BappPdfSupport;
    use BappSensusSupport;
    use BappFrameSupport;
    use BappNumberingSupport;
    use BappDocumentContextSupport;
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


    /**
     * Get NIP for ketua tim: first from User.nip, then match Petugas by name and read nik (auto-decrypted).
     */


    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */


    /**
     * Resolve the tanggal_bapp for a realisasi entry.
     *
     * @param  array<string, mixed>  $entry
     */


    /**
     * Build a map of spk_id → auto-generated nomor BAPP.
     * SPKs are sorted alphabetically by petugas name.
     * Format: B-{NNN}/BAPP-{roman}-SE2026/1373/PL.200/{tahun}
     *
     * @param  Collection<int, Spk>  $spks
     * @return array<int, string>
     */


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


    /**
     * Get all SE2026 SPKs (original, non-addendum) in current active year.
     *
     * @return Collection<int, Spk>
     */


    /**
     * @return Collection<int, SensusEkonomiPetugasReplacement>
     */


    /**
     * @return Collection<int, Spk>
     */


    /**
     * @return array<int, int>
     */


    /**
     * @return array<int, int>
     */


    /**
     * Build target_sls and target_unit_sampel for a given SPK and termin.
     *
     * @return array{target_sls: int, target_unit_sampel: array<string, int>}
     */


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


    /**
     * @return array<int, int>
     */


    /**
     * @return array<int, array{id:int, nama:string|null}>
     */


    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<int, array{key:string, label:string, value:string}>
     */


    /**
     * @param  array<string, mixed>|null  $targetUnitSampel
     */


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
        $kegiatan = $this->getSensusEkonomiKegiatan();
        $unitSampelItems = $this->getUnitSampelItems($kegiatan);

        $workflowContexts = [
            [
                'key' => 'regular',
                'label' => 'Petugas utama',
                'description' => 'BAPP petugas utama yang tidak masuk workflow penggantian.',
                'document_type' => 'regular',
                'replacement_termin_count' => 0,
            ],
            [
                'key' => 'stopped',
                'label' => 'Petugas berhenti',
                'description' => 'BAPP terakhir petugas yang berhenti. Termin yang tidak lagi berlaku tidak diwajibkan.',
                'document_type' => 'stopped_petugas',
                'replacement_termin_count' => 0,
            ],
            [
                'key' => 'replacement_2',
                'label' => 'Petugas pengganti · 2 termin',
                'description' => 'BAPP PKPP skema dua termin.',
                'document_type' => 'replacement_pkpp',
                'replacement_termin_count' => 2,
            ],
            [
                'key' => 'replacement_1',
                'label' => 'Petugas pengganti · 1 BAPP',
                'description' => 'Satu BAPP final untuk PKPP skema satu dokumen.',
                'document_type' => 'replacement_pkpp',
                'replacement_termin_count' => 1,
            ],
        ];

        $workflowData = collect($workflowContexts)->map(function (array $context) use ($tahun, $currentMonth, $hasBappTerminTable): array {
            $documentType = (string) $context['document_type'];
            $replacementTerminCount = (int) $context['replacement_termin_count'];
            $contextReplacementTerminCount = $this->getContextReplacementTerminCount($documentType, $replacementTerminCount);

            $terminData = [];
            foreach (self::TERMIN_CONFIG as $terminNumber => $config) {
                if ($documentType === 'replacement_pkpp' && $replacementTerminCount === 1 && $terminNumber === 2) {
                    continue;
                }

                $contextSpks = $this->getSpksForBappContext(
                    $tahun,
                    $terminNumber,
                    $documentType,
                    $contextReplacementTerminCount,
                );
                $spkIds = $contextSpks->pluck('id')->filter()->values();
                $bappCount = 0;

                if ($hasBappTerminTable && $spkIds->isNotEmpty()) {
                    $bappCount = $this->applyBappDocumentContextScope(
                        BappSeTermin::query()
                            ->where('termin', $terminNumber)
                            ->where('tahun', $tahun)
                            ->whereIn('spk_id', $spkIds)
                            ->whereNotNull('signed_file_path'),
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
                    'can_generate' => $currentMonth >= $config['bulan'],
                    'bapp_count' => $bappCount,
                    'spk_count' => $spkCount,
                    'is_complete' => $spkCount > 0 && $bappCount >= $spkCount,
                ];
            }

            return [
                ...$context,
                'termin_data' => $terminData,
                'total_spk' => collect($terminData)->sum('spk_count'),
                'total_uploaded' => collect($terminData)->sum('bapp_count'),
            ];
        })->values()->all();

        return Inertia::render('Bapp/Index', [
            'tahun' => $tahun,
            'workflow_data' => $workflowData,
            'termin_data' => $workflowData[0]['termin_data'] ?? [],
            'document_type' => 'regular',
            'replacement_termin_count' => 0,
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
        $contextReplacementTerminCount = $this->getContextReplacementTerminCount(
            $documentType,
            $replacementTerminCount,
        );

        if ($documentType === 'replacement_pkpp' && $contextReplacementTerminCount === 1 && $terminNumber === 2) {
            return redirect()->route('bapp.index')
                ->with('error', 'PKPP ini hanya memerlukan satu BAPP.');
        }

        $kegiatan = $this->getSensusEkonomiKegiatan();
        if (! $kegiatan) {
            return redirect()->route('bapp.index')->with('error', 'Kegiatan Sensus Ekonomi tidak ditemukan.');
        }

        $unitSampelItems = $this->getUnitSampelItems($kegiatan);
        $spks = $this->getSpksForBappContext($tahun, $terminNumber, $documentType, $contextReplacementTerminCount);
        $ppk = $this->getPpk();
        $nomorBappMap = $this->generateNomorBappMap(
            $spks,
            $config['roman'],
            $tahun,
            $documentType,
            $contextReplacementTerminCount,
        );

        $replacementBySourceSpkId = $documentType === 'replacement_pkpp'
            ? SensusEkonomiPkppContract::query()
                ->where('termin_count', $contextReplacementTerminCount)
                ->whereHas('replacement', fn ($query) => $query->whereNotNull('spk_lama_id'))
                ->with(['replacement.petugasPengganti'])
                ->get()
                ->mapWithKeys(fn (SensusEkonomiPkppContract $contract) => [
                    (int) $contract->replacement?->spk_lama_id => $contract,
                ])
            : collect();

        $spkList = $spks->map(function (Spk $spk) use ($terminNumber, $nomorBappMap, $documentType, $contextReplacementTerminCount, $hasBappTerminTable, $replacementBySourceSpkId): array {
            $targetData = $this->buildTargetForTermin($spk, $terminNumber);
            $replacementContract = $documentType === 'replacement_pkpp'
                ? $replacementBySourceSpkId->get((int) $spk->id)
                : null;
            $petugas = $replacementContract?->replacement?->petugasPengganti ?? $spk->petugas;
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
                'nomor_spk' => $replacementContract?->nomor_pkpp ?? $spk->nomor_spk,
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
                'signed_file_path' => $existing?->signed_file_path,
                'signed_uploaded_at' => $existing?->signed_uploaded_at?->toIso8601String(),
                'nomor_bapp' => $existing?->nomor_bapp,
                'nomor_bapp_urut' => $this->extractManualDocumentSequence($existing?->nomor_bapp),
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

        return Inertia::render('Bapp/Manual', [
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
            'nomor_bapp_suffix' => $documentType === 'replacement_pkpp' && $contextReplacementTerminCount === 1
                ? '/BAPP-SE2026/1373/PL.200/'.$tahun
                : '/BAPP-'.$config['roman'].'-SE2026/1373/PL.200/'.$tahun,
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
     * Upload BAPP SE2026 sebagai dokumen manual.
     */
    public function uploadManual(Request $request): RedirectResponse
    {
        if (! $this->userCanAccessBapp($request)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses.');
        }

        $validated = $request->validate([
            'spk_hashed_id' => ['required', 'string'],
            'termin' => ['required', 'integer', 'in:1,2'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'nomor_bapp' => ['required', 'string', 'regex:/^\d+$/', 'max:12'],
            'tanggal_bapp' => ['nullable', 'date'],
            'document_type' => ['required', 'in:regular,stopped_petugas,replacement_pkpp'],
            'replacement_termin_count' => ['nullable', 'integer', 'in:0,1,2'],
        ]);

        $spkId = Hashids::decode((string) $validated['spk_hashed_id'])[0] ?? null;
        if (! $spkId) {
            return back()->with('error', 'Perjanjian Kerja tidak valid.');
        }

        $tahun = ActiveYearService::get();
        $termin = (int) $validated['termin'];
        $config = self::TERMIN_CONFIG[$termin];
        $documentType = $this->resolveDocumentType($request);
        $replacementTerminCount = $this->resolveReplacementTerminCount($request);
        $contextReplacementTerminCount = $this->getContextReplacementTerminCount(
            $documentType,
            $replacementTerminCount,
        );

        if ($documentType === 'replacement_pkpp' && $contextReplacementTerminCount === 1 && $termin === 2) {
            return back()->with('error', 'PKPP ini hanya memerlukan satu BAPP.');
        }
        $spk = Spk::query()
            ->with(['petugas', 'alokasiPetugas.periodeAlokasi.kegiatan.ketuaTim'])
            ->find($spkId);

        if (! $spk || ! $this->getSpksForBappContext(
            $tahun,
            $termin,
            $documentType,
            $contextReplacementTerminCount,
        )->contains('id', $spk->id)) {
            return back()->with('error', 'Perjanjian Kerja tidak termasuk alur BAPP SE2026 yang dipilih.');
        }

        $existing = $this->applyBappDocumentContextScope(
            BappSeTermin::query()
                ->where('spk_id', $spk->id)
                ->where('termin', $termin)
                ->where('tahun', $tahun),
            $documentType,
            $contextReplacementTerminCount,
        )->first();

        if ($existing && filled($existing->signed_file_path)) {
            Storage::disk('public')->delete($existing->signed_file_path);
        }

        $replacementId = $documentType === 'replacement_pkpp'
            ? ($this->getReplacementIdByReplacementSpkId($contextReplacementTerminCount)[$spk->id] ?? null)
            : null;
        $replacement = $replacementId
            ? SensusEkonomiPetugasReplacement::query()
                ->with('petugasPengganti')
                ->find($replacementId)
            : null;
        $petugas = $documentType === 'replacement_pkpp'
            ? $replacement?->petugasPengganti
            : $spk->petugas;
        $kegiatan = $this->getSensusEkonomiKegiatan();
        $ppk = $this->getPpk();
        $nomorBapp = $this->formatManualBappNumber(
            trim((string) $validated['nomor_bapp']),
            $termin,
            $tahun,
            $documentType,
            $contextReplacementTerminCount,
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nomorBapp);
        $path = $request->file('file')->storeAs(
            'bapp-se/manual/'.$tahun.'/termin-'.$termin,
            'BAPP_'.$safeName.'_'.time().'.pdf',
            'public'
        );

        $bapp = $existing ?? new BappSeTermin();
        $bapp->spk_id = $spk->id;
        $bapp->petugas_id = $petugas?->id;
        $bapp->termin = $termin;
        $bapp->document_type = $documentType;
        $bapp->replacement_termin_count = $contextReplacementTerminCount;
        $bapp->replacement_id = $documentType === 'stopped_petugas'
            ? ($this->getStoppedReplacementIdBySpkId($termin, $tahun)[$spk->id] ?? null)
            : ($documentType === 'replacement_pkpp' ? $replacementId : null);
        $bapp->bulan = $config['bulan'];
        $bapp->tahun = $tahun;
        $bapp->persentase = $config['persentase'];
        $bapp->nomor_bapp = $nomorBapp;
        $bapp->tanggal_bapp = $validated['tanggal_bapp'] ?? ($existing?->tanggal_bapp ?? now()->toDateString());
        $bapp->nama_ketua_tim = $kegiatan?->ketuaTim?->name;
        $bapp->nip_ketua_tim = $this->getNipKetuaTim($kegiatan);
        $bapp->nama_ppk = $ppk ? $this->stripGelar($ppk->nama) : null;
        $bapp->nip_ppk = $ppk?->nip;
        $bapp->jabatan_ppk = $ppk?->jabatan;
        $bapp->nama_kabkota = config('app.instansi_kabupaten', '');
        $bapp->signed_file_path = $path;
        $bapp->signed_uploaded_at = now();
        $bapp->created_by = $bapp->created_by ?: Auth::id();
        $bapp->save();

        $successLabel = $documentType === 'replacement_pkpp'
            && $contextReplacementTerminCount === 1
                ? 'BAPP petugas pengganti berhasil diunggah.'
                : 'BAPP Termin '.$config['roman'].' berhasil diunggah manual.';

        return back()->with('success', $successLabel);
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


    /**
     * Generate and merge three PDF parts into one document:
     *   Part 1 — portrait pages 1-2 (main BAPP content + signatures)
     *   Part 2 — landscape page 3 (table: DAFTAR URAIAN PEKERJAAN)
     *   Part 3 — portrait page 4 (bukti pencapaian + final signatures)
     *
     * @param  array<string, mixed>  $viewData
     */

}
