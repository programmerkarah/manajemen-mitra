<?php

namespace App\Http\Controllers;

use App\Exports\PengajuanPulsaTemplateExport;
use App\Http\Requests\StorePengajuanPulsaRequest;
use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PengajuanPulsa;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Services\ActiveYearService;
use App\Traits\EffectivePeriodeScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PengajuanPulsaController extends Controller
{
    use EffectivePeriodeScope;

    private const PENGOLAHAN_ROLES = ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'];

    /**
     * Display a listing of pengajuan pulsa.
     */
    public function index(Request $request): Response
    {
        $effectiveUser = effectiveUser($request);

        if ($request->has('state')) {
            $request->merge(decryptFilters((string) $request->query('state')));
        }

        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = ActiveYearService::get();

        $query = PengajuanPulsa::query()
            ->with([
                'petugas:id,nama',
                'kegiatan:id,kode_kegiatan,nama_kegiatan',
                'submittedBy:id,name',
                'reviewedBy:id,name',
            ])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun);

        if ($effectiveUser?->isKetuaTim() && ! $effectiveUser->isAdmin() && ! $effectiveUser->isOperator()) {
            $kegiatanIds = Kegiatan::where(function ($q) use ($effectiveUser) {
                $q->where('ketua_tim_user_id', $effectiveUser->id)
                    ->orWhere('pj_lainnya_id', $effectiveUser->id);
            })->pluck('id');

            $query->whereIn('kegiatan_id', $kegiatanIds);
        }

        $pengajuanList = $query->orderBy('petugas_id')->orderBy('kegiatan_id')->get();

        $encryptedData = encryptData($pengajuanList);

        return Inertia::render('PengajuanPulsa/Index', [
            'pengajuanList' => [
                'encrypted' => $encryptedData,
            ],
            'filters' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
            ],
        ]);
    }

    /**
     * Show the form for creating a new pengajuan pulsa.
     */
    public function create(Request $request): Response
    {
        $effectiveUser = effectiveUser($request);

        if ($request->has('state')) {
            $request->merge(decryptFilters((string) $request->query('state')));
        }

        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = (string) ActiveYearService::get();
        $bulanInt = (int) $bulan;
        $bulanCandidates = array_values(array_unique([
            $bulan,
            (string) $bulanInt,
            str_pad($bulanInt, 2, '0', STR_PAD_LEFT),
        ]));

        $isAdminOrOperator = $effectiveUser?->isAdmin() || $effectiveUser?->isOperator();

        // --- Step 1: Find pelatihan kegiatan with bulan_pelatihan == bulan ---
        // For each, determine the allocation bulan used to retrieve petugas:
        //   - If kegiatan starts in bulan_pelatihan month → use bulan_pelatihan (same as submission)
        //   - Otherwise → use bulan_pelatihan + 1 (petugas already allocated for the work month)
        $pelatihanKegiatanQuery = Kegiatan::query()
            ->where('tahun_anggaran', $tahun)
            ->where('bulan_pelatihan', $bulanInt)
            ->whereNotNull('metode_pelatihan')
            ->where('metode_pelatihan', '!=', 'tidak_ada_pelatihan')
            ->select('id', 'bulan_pelatihan', 'tanggal_mulai');

        if (! $isAdminOrOperator) {
            $pelatihanKegiatanQuery->where(function ($q) use ($effectiveUser) {
                $q->where('ketua_tim_user_id', $effectiveUser->id)
                    ->orWhere('pj_lainnya_id', $effectiveUser->id);
            });
        }

        $pelatihanKegiatanList = $pelatihanKegiatanQuery->get();

        /**
         * Map kegiatan_id → alokasi period info for pelatihan petugas lookup.
         *
         * @var Collection<int, array{kegiatan_id: int, alokasi_bulan: string, alokasi_tahun: string}>
         */
        $pelatihanAlokasiInfo = $pelatihanKegiatanList->map(function ($k) use ($bulanInt, $tahun) {
            $mulaiMonth = $k->tanggal_mulai?->month;
            $useSameBulan = ($mulaiMonth === $bulanInt);

            if ($useSameBulan) {
                return [
                    'kegiatan_id' => $k->id,
                    'alokasi_bulan' => str_pad($bulanInt, 2, '0', STR_PAD_LEFT),
                    'alokasi_tahun' => $tahun,
                ];
            }

            $nextBulan = $bulanInt + 1;
            $nextTahun = (int) $tahun;
            if ($nextBulan > 12) {
                $nextBulan = 1;
                $nextTahun++;
            }

            return [
                'kegiatan_id' => $k->id,
                'alokasi_bulan' => str_pad($nextBulan, 2, '0', STR_PAD_LEFT),
                'alokasi_tahun' => (string) $nextTahun,
            ];
        })->keyBy('kegiatan_id');

        // Find which pelatihan kegiatan have allocations in their computed period
        $kegiatanWithPelatihanPeriod = collect();
        foreach ($pelatihanAlokasiInfo as $kegiatanId => $info) {
            $hasPeriod = PeriodeAlokasi::query()
                ->where('kegiatan_id', $kegiatanId)
                ->whereIn('bulan', [
                    $info['alokasi_bulan'],
                    (string) ((int) $info['alokasi_bulan']),
                    str_pad((int) $info['alokasi_bulan'], 2, '0', STR_PAD_LEFT),
                ])
                ->where('tahun', $info['alokasi_tahun'])
                ->whereHas('alokasiPetugas', function ($q) {
                    $q->whereNotIn('peran', self::PENGOLAHAN_ROLES)
                        ->whereHas('petugas', fn ($q2) => $q2->where('jenis_petugas', 'non-organik'));
                })
                ->exists();

            if ($hasPeriod) {
                $kegiatanWithPelatihanPeriod->push($kegiatanId);
            }
        }

        // --- Step 2: Find kegiatan with pendataan allocations in current bulan ---
        $kegiatanWithPendataanPeriod = PeriodeAlokasi::query()
            ->whereIn('bulan', $bulanCandidates)
            ->where('tahun', $tahun)
            ->whereHas('alokasiPetugas', function ($q) {
                $q->whereNotIn('peran', self::PENGOLAHAN_ROLES)
                    ->whereHas('petugas', fn ($q2) => $q2->where('jenis_petugas', 'non-organik'));
            })
            ->pluck('kegiatan_id');

        $sensusEkonomiPeriodIds = PeriodeAlokasi::query()
            ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
            ->where('periode_alokasi.tahun', $tahun)
            ->where('kegiatan.jenis_kegiatan', 'sensus')
            ->whereRaw('LOWER(COALESCE(kegiatan.nama_kegiatan, \'\')) LIKE ?', ['%sensus ekonomi%'])
            ->whereRaw('? BETWEEN MONTH(kegiatan.tanggal_mulai) AND MONTH(kegiatan.tanggal_selesai)', [$bulanInt])
            ->whereRaw('CAST(periode_alokasi.bulan AS UNSIGNED) = MONTH(kegiatan.tanggal_mulai)')
            ->whereHas('alokasiPetugas', function ($q) {
                $q->whereNotIn('peran', self::PENGOLAHAN_ROLES)
                    ->whereHas('petugas', fn ($q2) => $q2->where('jenis_petugas', 'non-organik'));
            })
            ->pluck('periode_alokasi.id');

        $sensusEkonomiKegiatanIds = $sensusEkonomiPeriodIds->isNotEmpty()
            ? PeriodeAlokasi::query()
                ->whereIn('id', $sensusEkonomiPeriodIds)
                ->pluck('kegiatan_id')
            : collect();
        $kegiatanWithPeriod = $kegiatanWithPendataanPeriod->merge($kegiatanWithPelatihanPeriod)->unique()->values();
        $kegiatanWithPeriod = $kegiatanWithPendataanPeriod
            ->merge($kegiatanWithPelatihanPeriod)
            ->merge($sensusEkonomiKegiatanIds)
            ->unique()
            ->values();
        // --- Step 3: Load eligible kegiatan ---
        $kegiatanQuery = Kegiatan::query()
            ->whereIn('id', $kegiatanWithPeriod)
            ->where('tahun_anggaran', $tahun);

        if (! $isAdminOrOperator) {
            $kegiatanQuery->where(function ($q) use ($effectiveUser) {
                $q->where('ketua_tim_user_id', $effectiveUser->id)
                    ->orWhere('pj_lainnya_id', $effectiveUser->id);
            });
        }

        $eligibleKegiatan = $kegiatanQuery
            ->select('id', 'kode_kegiatan', 'nama_kegiatan', 'jenis_kegiatan', 'metode_pendataan_pencacahan', 'metode_pendataan_listing', 'metode_pelatihan', 'bulan_pelatihan', 'has_listing_updating', 'tanggal_mulai', 'tanggal_selesai')
            ->where(function ($q) use ($kegiatanWithPelatihanPeriod, $bulanInt) {
                // Show if at least one column is available:
                // - Pelatihan: kegiatan is in the pelatihan-eligible set (has allocation in configured bulan)
                // - Pendataan: FASIH method
                // - Legacy: metode_pelatihan not yet set
                // - Sensus Ekonomi: available during the June-August window when allocations exist
                $q->whereIn('id', $kegiatanWithPelatihanPeriod)
                    ->orWhereIn('metode_pendataan_pencacahan', ['CAPI_FASIH', 'CAPI'])
                    ->orWhereNull('metode_pelatihan')
                    ->orWhere(function ($sensusQuery) use ($bulanInt) {
                        $sensusQuery
                            ->where('jenis_kegiatan', 'sensus')
                            ->whereRaw('LOWER(COALESCE(nama_kegiatan, \'\')) LIKE ?', ['%sensus ekonomi%'])
                            ->whereRaw('? BETWEEN MONTH(tanggal_mulai) AND MONTH(tanggal_selesai)', [$bulanInt]);
                    });
            })
            ->orderBy('kode_kegiatan')
            ->get();

        $kegiatanIds = $eligibleKegiatan->pluck('id');

        // --- Step 4: Build petugasPerKegiatanPendataan (from current bulan allocations) ---
        // Pendataan petugas: always filter by current bulan so the column is only
        // activated when there is an allocation in that specific month.
        $pendataanAllocations = AlokasiPetugas::query()
            ->whereHas('periodeAlokasi', function ($q) use ($bulan, $tahun, $kegiatanIds) {
                $q->whereIn('bulan', [
                    $bulan,
                    (string) ((int) $bulan),
                    str_pad((int) $bulan, 2, '0', STR_PAD_LEFT),
                ])
                    ->where('tahun', $tahun)
                    ->whereIn('kegiatan_id', $kegiatanIds);
            })
            ->whereNotIn('peran', self::PENGOLAHAN_ROLES)
            ->whereHas('petugas', fn ($q) => $q->where('jenis_petugas', 'non-organik'))
            ->with([
                'petugas:id,nama,jenis_petugas',
                'periodeAlokasi:id,kegiatan_id,bulan,tahun',
            ])
            ->get();

        $sensusPendataanAllocations = collect();

        if ($sensusEkonomiPeriodIds->isNotEmpty()) {
            $sensusPendataanAllocations = AlokasiPetugas::query()
                ->whereIn('periode_alokasi_id', $sensusEkonomiPeriodIds)
                ->whereNotIn('peran', self::PENGOLAHAN_ROLES)
                ->whereHas('petugas', fn ($q) => $q->where('jenis_petugas', 'non-organik'))
                ->with([
                    'petugas:id,nama,jenis_petugas',
                    'periodeAlokasi:id,kegiatan_id,bulan,tahun',
                ])
                ->get();
        }

        $petugasPerKegiatan = $pendataanAllocations
            ->groupBy(fn ($a) => $a->periodeAlokasi?->kegiatan_id)
            ->map(fn ($group) => $group
                ->unique('petugas_id')
                ->sortBy('petugas.nama')
                ->map(fn ($a) => [
                    'id' => $a->petugas?->id,
                    'nama' => $a->petugas?->nama,
                    'peran' => $a->peran,
                ])
                ->values()
            );

        $sensusPetugasPerKegiatan = $sensusPendataanAllocations
            ->groupBy(fn ($a) => $a->periodeAlokasi?->kegiatan_id)
            ->map(fn ($group) => $group
                ->unique('petugas_id')
                ->sortBy('petugas.nama')
                ->map(fn ($a) => [
                    'id' => $a->petugas?->id,
                    'nama' => $a->petugas?->nama,
                    'peran' => $a->peran,
                ])
                ->values()
            );

        foreach ($sensusPetugasPerKegiatan as $kegiatanId => $petugasList) {
            $petugasPerKegiatan->put($kegiatanId, $petugasList);
        }

        // --- Step 5: Build petugasPerKegiatanPelatihan (from allocation bulan per kegiatan) ---
        // Group eligible pelatihan kegiatan by their alokasi period to minimize queries
        /** @var Collection<string, Collection<int, array{kegiatan_id: int, alokasi_bulan: string, alokasi_tahun: string}>> $byAlokasiPeriod */
        $byAlokasiPeriod = $pelatihanAlokasiInfo
            ->filter(fn ($info) => $kegiatanWithPelatihanPeriod->contains($info['kegiatan_id']))
            ->groupBy(fn ($info) => $info['alokasi_bulan'].'_'.$info['alokasi_tahun']);

        $petugasPerKegiatanPelatihan = collect();

        foreach ($byAlokasiPeriod as $periodKey => $kegiatanGroup) {
            [$alokasiB, $alokasiT] = explode('_', $periodKey, 2);
            $pelatihanKegiatanIds = $kegiatanGroup->pluck('kegiatan_id');

            $pelatihanAllocations = AlokasiPetugas::query()
                ->whereHas('periodeAlokasi', function ($q) use ($alokasiB, $alokasiT, $pelatihanKegiatanIds) {
                    $q->whereIn('bulan', [
                        $alokasiB,
                        (string) ((int) $alokasiB),
                        str_pad((int) $alokasiB, 2, '0', STR_PAD_LEFT),
                    ])
                        ->where('tahun', $alokasiT)
                        ->whereIn('kegiatan_id', $pelatihanKegiatanIds);
                })
                ->whereNotIn('peran', self::PENGOLAHAN_ROLES)
                ->whereHas('petugas', fn ($q) => $q->where('jenis_petugas', 'non-organik'))
                ->with([
                    'petugas:id,nama,jenis_petugas',
                    'periodeAlokasi:id,kegiatan_id,bulan,tahun',
                ])
                ->get();

            $grouped = $pelatihanAllocations
                ->groupBy(fn ($a) => $a->periodeAlokasi?->kegiatan_id)
                ->map(fn ($group) => $group
                    ->unique('petugas_id')
                    ->sortBy('petugas.nama')
                    ->map(fn ($a) => [
                        'id' => $a->petugas?->id,
                        'nama' => $a->petugas?->nama,
                        'peran' => $a->peran,
                    ])
                    ->values()
                );

            foreach ($grouped as $kegiatanId => $petugasList) {
                $petugasPerKegiatanPelatihan->put($kegiatanId, $petugasList);
            }
        }

        // --- Step 6: Build existing submission data ---
        $existingSubmissions = PengajuanPulsa::query()
            ->whereIn('kegiatan_id', $kegiatanIds)
            ->whereIn('bulan', $bulanCandidates)
            ->where('tahun', $tahun)
            ->whereNotIn('status', ['ditolak'])
            ->get(['kegiatan_id', 'petugas_id', 'jenis_pulsa', 'nominal', 'nominal_disetujui', 'status']);

        $existingSubmissionsWithEffectiveNominal = $existingSubmissions->map(function ($row) {
            $effectiveNominal = $row->status === 'diterima'
                ? (float) ($row->nominal_disetujui ?? $row->nominal)
                : (float) $row->nominal;

            return [
                'kegiatan_id' => (int) $row->kegiatan_id,
                'petugas_id' => (int) $row->petugas_id,
                'jenis_pulsa' => (string) $row->jenis_pulsa,
                'nominal' => $effectiveNominal,
            ];
        });

        $existingTotals = $existingSubmissionsWithEffectiveNominal
            ->groupBy('petugas_id')
            ->map(fn ($rows) => $rows->sum('nominal'));

        // Collect all petugas IDs visible in the form (pendataan + pelatihan)
        $allKnownPetugasIds = $pendataanAllocations->pluck('petugas_id')->unique();
        foreach ($petugasPerKegiatanPelatihan as $petugasList) {
            $allKnownPetugasIds = $allKnownPetugasIds->merge(collect($petugasList)->pluck('id'));
        }
        $allKnownPetugasIds = $allKnownPetugasIds->unique()->values();

        // Global total per petugas across ALL kegiatan (not limited to this ketua tim's kegiatan).
        // Used on the form to alert the user when a petugas already has submissions elsewhere.
        $allExistingTotals = PengajuanPulsa::query()
            ->whereIn('petugas_id', $allKnownPetugasIds)
            ->whereIn('bulan', $bulanCandidates)
            ->where('tahun', $tahun)
            ->whereNotIn('status', ['ditolak'])
            ->groupBy('petugas_id')
            ->select('petugas_id', DB::raw('SUM(nominal) as total'))
            ->pluck('total', 'petugas_id')
            ->map(fn ($t) => (float) $t);

        /**
         * Key format: "${kegiatan_id}_${petugas_id}_${jenis_pulsa}" → nominal
         * Used on frontend to lock cells that already have a non-rejected submission.
         *
         * @var array<string, float>
         */
        $existingPerKegiatan = $existingSubmissionsWithEffectiveNominal
            ->mapWithKeys(fn ($row) => [
                "{$row['kegiatan_id']}_{$row['petugas_id']}_{$row['jenis_pulsa']}" => (float) $row['nominal'],
            ]);

        return Inertia::render('PengajuanPulsa/Create', [
            'eligibleKegiatan' => $eligibleKegiatan,
            'petugasPerKegiatan' => $petugasPerKegiatan,
            'petugasPerKegiatanPelatihan' => $petugasPerKegiatanPelatihan,
            'existingTotals' => $existingTotals,
            'allExistingTotals' => $allExistingTotals,
            'existingPerKegiatan' => $existingPerKegiatan,
            'filters' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
            ],
        ]);
    }

    public function downloadTemplate(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'bulan' => ['required', 'string'],
            'tahun' => ['required', 'integer'],
        ]);

        $templateData = $this->buildTemplateDropdownData(
            $request,
            (string) $validated['bulan'],
            (int) $validated['tahun'],
        );

        return Excel::download(
            new PengajuanPulsaTemplateExport(
                bulan: (string) $validated['bulan'],
                tahun: (int) $validated['tahun'],
                petugasOptions: $templateData['petugas_options'],
                kegiatanOptionsByPetugasKey: $templateData['kegiatan_options_by_petugas_key'],
                jenisOptionsByPetugasKey: $templateData['jenis_options_by_petugas_key'],
            ),
            'template-pengajuan-pulsa.xlsx'
        );
    }

    public function importPreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'bulan' => ['required', 'string'],
            'tahun' => ['required', 'integer'],
        ], [
            'file.required' => 'File harus diupload.',
            'file.mimes' => 'File harus berupa Excel (.xlsx, .xls) atau CSV.',
        ]);

        $bulan = str_pad((int) $validated['bulan'], 2, '0', STR_PAD_LEFT);
        $tahun = (int) $validated['tahun'];
        $templateData = $this->buildTemplateDropdownData($request, $bulan, $tahun);

        $spreadsheet = IOFactory::load($validated['file']->getRealPath());
        $rows = $spreadsheet->getSheet(0)->toArray(null, true, true, false);

        $headings = array_map(
            static fn ($heading) => strtolower(trim((string) $heading)),
            array_shift($rows) ?? []
        );

        $previewRows = [];
        $errors = [];
        $skippedRows = 0;

        $kegiatanIds = [];
        $petugasIds = [];
        $petugasLookup = $templateData['petugas_lookup'];
        $kegiatanLookup = $templateData['kegiatan_lookup'];
        $jenisLookup = $templateData['jenis_lookup'];

        foreach ($rows as $row) {
            $rowData = [];
            foreach ($headings as $index => $heading) {
                if ($heading === '') {
                    continue;
                }

                $rowData[$heading] = $row[$index] ?? null;
            }

            $rowBulan = str_pad((int) ($rowData['bulan'] ?? $bulan), 2, '0', STR_PAD_LEFT);
            $rowTahun = (int) ($rowData['tahun'] ?? $tahun);

            if ($rowBulan !== $bulan || $rowTahun !== $tahun) {
                $skippedRows++;

                continue;
            }

            $petugasName = trim((string) ($rowData['petugas_nama'] ?? ''));
            $kegiatanName = trim((string) ($rowData['kegiatan_nama'] ?? ''));
            $petugasId = (int) ($rowData['petugas_id'] ?? 0);
            $kegiatanId = (int) ($rowData['kegiatan_id'] ?? 0);
            $jenisPulsa = strtolower(trim((string) ($rowData['jenis_pulsa'] ?? '')));
            $nominalRaw = trim((string) ($rowData['nominal'] ?? ''));
            $nominal = (float) preg_replace('/[^0-9]/', '', $nominalRaw);

            if ($petugasId <= 0 && $petugasName !== '') {
                $petugasLookupItem = $petugasLookup[Str::lower($petugasName)] ?? null;
                $petugasId = (int) ($petugasLookupItem['id'] ?? 0);
                $petugasName = (string) ($petugasLookupItem['nama'] ?? $petugasName);
            }

            if ($kegiatanId <= 0 && $petugasName !== '' && $kegiatanName !== '') {
                $petugasKey = Str::lower($petugasName);
                $kegiatanLookupItem = $kegiatanLookup[$petugasKey][Str::lower($kegiatanName)] ?? null;
                $kegiatanId = (int) ($kegiatanLookupItem['id'] ?? 0);
                $kegiatanName = (string) ($kegiatanLookupItem['nama'] ?? $kegiatanName);
            }

            if ($petugasName !== '' && $jenisPulsa !== '') {
                $petugasKey = Str::lower($petugasName);
                $allowedJenis = $jenisLookup[$petugasKey] ?? [];

                if ($allowedJenis !== [] && ! in_array($jenisPulsa, $allowedJenis, true)) {
                    $errors[] = "Jenis pulsa {$jenisPulsa} tidak tersedia untuk petugas {$petugasName}.";

                    continue;
                }
            }

            if ($kegiatanId <= 0 || $petugasId <= 0 || $jenisPulsa === '' || $nominal <= 0) {
                if (implode('', $rowData) !== '') {
                    $errors[] = 'Ada baris yang belum lengkap. Pastikan petugas, kegiatan, jenis pulsa, dan nominal terisi.';
                }

                continue;
            }

            if (! in_array($jenisPulsa, ['pelatihan', 'pendataan'], true)) {
                $errors[] = "Jenis pulsa tidak valid untuk kegiatan ID {$kegiatanId}.";

                continue;
            }

            $kegiatanIds[] = $kegiatanId;
            $petugasIds[] = $petugasId;

            $previewRows[] = [
                'bulan' => $rowBulan,
                'tahun' => $rowTahun,
                'kegiatan_id' => $kegiatanId,
                'kegiatan_nama' => $kegiatanName,
                'petugas_id' => $petugasId,
                'petugas_nama' => $petugasName,
                'jenis_pulsa' => $jenisPulsa,
                'nominal' => $nominal,
            ];
        }

        $kegiatanMap = Kegiatan::query()
            ->whereIn('id', array_values(array_unique($kegiatanIds)))
            ->get(['id', 'kode_kegiatan', 'nama_kegiatan'])
            ->keyBy('id');

        $petugasMap = Petugas::query()
            ->whereIn('id', array_values(array_unique($petugasIds)))
            ->get(['id', 'nama'])
            ->keyBy('id');

        $previewRows = array_map(function (array $row) use ($kegiatanMap, $petugasMap): array {
            $kegiatan = $kegiatanMap->get($row['kegiatan_id']);
            $petugas = $petugasMap->get($row['petugas_id']);

            return [
                ...$row,
                'kegiatan_kode' => $kegiatan?->kode_kegiatan,
                'kegiatan_nama' => $kegiatan?->nama_kegiatan,
                'petugas_nama' => $petugas?->nama,
            ];
        }, $previewRows);

        return response()->json([
            'rows' => $previewRows,
            'errors' => array_values(array_unique($errors)),
            'summary' => [
                'total_rows' => count($rows),
                'preview_rows' => count($previewRows),
                'skipped_rows' => $skippedRows,
                'error_count' => count($errors),
            ],
        ]);
    }

    /**
     * @return array{
     *     petugas_options: array<int, array{id:int, nama:string, key:string}>,
     *     petugas_lookup: array<string, array{id:int, nama:string, key:string}>,
     *     kegiatan_options_by_petugas_key: array<string, array<int, array{id:int, nama:string}>>,
    *     kegiatan_lookup: array<string, array<string, array{id:int, nama:string}>>,
    *     jenis_options_by_petugas_key: array<string, array<int, string>>,
    *     jenis_lookup: array<string, array<int, string>>
     * }
     */
    private function buildTemplateDropdownData(Request $request, string $bulan, int $tahun): array
    {
        $effectiveUser = effectiveUser($request);
        $bulanInt = (int) $bulan;
        $bulanCandidates = array_values(array_unique([
            $bulan,
            (string) $bulanInt,
            str_pad($bulanInt, 2, '0', STR_PAD_LEFT),
        ]));

        $isAdminOrOperator = $effectiveUser?->isAdmin() || $effectiveUser?->isOperator();

        $kegiatanQuery = Kegiatan::query()->where('tahun_anggaran', $tahun);

        if (! $isAdminOrOperator) {
            $kegiatanQuery->where(function ($query) use ($effectiveUser): void {
                $query->where('ketua_tim_user_id', $effectiveUser->id)
                    ->orWhere('pj_lainnya_id', $effectiveUser->id);
            });
        }

        $accessibleKegiatanIds = $kegiatanQuery->pluck('id');

        $petugasOptions = [];
        $petugasLookup = [];
        $kegiatanOptionsByPetugasKey = [];
        $kegiatanLookup = [];
        $jenisOptionsByPetugasKey = [];
        $jenisLookup = [];

        $appendAllocation = function (object $allocation, string $jenisPulsa) use (&$petugasOptions, &$petugasLookup, &$kegiatanOptionsByPetugasKey, &$kegiatanLookup, &$jenisOptionsByPetugasKey, &$jenisLookup): void {
            $petugasNama = trim((string) ($allocation->petugas?->nama ?? ''));
            $kegiatanNama = trim((string) ($allocation->periodeAlokasi?->kegiatan?->nama_kegiatan ?? ''));

            if ($petugasNama === '' || $kegiatanNama === '') {
                return;
            }

            $petugasKey = $this->normalizeTemplateKey($petugasNama);
            $petugasLower = Str::lower($petugasNama);
            $kegiatanLower = Str::lower($kegiatanNama);

            $petugasOptions[$petugasKey] = [
                'id' => (int) $allocation->petugas_id,
                'nama' => $petugasNama,
                'key' => $petugasKey,
            ];

            $petugasLookup[$petugasLower] = [
                'id' => (int) $allocation->petugas_id,
                'nama' => $petugasNama,
                'key' => $petugasKey,
            ];

            $kegiatanOptionsByPetugasKey[$petugasKey][$kegiatanNama] = [
                'id' => (int) $allocation->periodeAlokasi?->kegiatan_id,
                'nama' => $kegiatanNama,
            ];

            $kegiatanLookup[$petugasLower][$kegiatanLower] = [
                'id' => (int) $allocation->periodeAlokasi?->kegiatan_id,
                'nama' => $kegiatanNama,
            ];

            $jenisOptionsByPetugasKey[$petugasKey][$jenisPulsa] = $jenisPulsa;
            $jenisLookup[$petugasLower][$jenisPulsa] = $jenisPulsa;
        };

        $pendataanAllocations = AlokasiPetugas::query()
            ->whereHas('periodeAlokasi', function ($query) use ($bulanCandidates, $tahun, $accessibleKegiatanIds): void {
                $query->whereIn('bulan', $bulanCandidates)
                    ->where('tahun', $tahun)
                    ->whereIn('kegiatan_id', $accessibleKegiatanIds);
            })
            ->whereNotIn('peran', self::PENGOLAHAN_ROLES)
            ->whereHas('petugas', fn ($query) => $query->where('jenis_petugas', 'non-organik'))
            ->with([
                'petugas:id,nama,jenis_petugas',
                'periodeAlokasi:id,kegiatan_id,bulan,tahun',
                'periodeAlokasi.kegiatan:id,nama_kegiatan',
            ])
            ->get();

        foreach ($pendataanAllocations as $allocation) {
            $appendAllocation($allocation, 'pendataan');
        }

        $sensusEkonomiPeriodIds = PeriodeAlokasi::query()
            ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
            ->where('periode_alokasi.tahun', $tahun)
            ->whereIn('periode_alokasi.kegiatan_id', $accessibleKegiatanIds)
            ->where('kegiatan.jenis_kegiatan', 'sensus')
            ->whereRaw('LOWER(COALESCE(kegiatan.nama_kegiatan, \'\')) LIKE ?', ['%sensus ekonomi%'])
            ->whereRaw('? BETWEEN MONTH(kegiatan.tanggal_mulai) AND MONTH(kegiatan.tanggal_selesai)', [$bulanInt])
            ->whereRaw('CAST(periode_alokasi.bulan AS UNSIGNED) = MONTH(kegiatan.tanggal_mulai)')
            ->whereHas('alokasiPetugas', function ($query): void {
                $query->whereNotIn('peran', self::PENGOLAHAN_ROLES)
                    ->whereHas('petugas', fn ($petugasQuery) => $petugasQuery->where('jenis_petugas', 'non-organik'));
            })
            ->pluck('periode_alokasi.id');

        if ($sensusEkonomiPeriodIds->isNotEmpty()) {
            $sensusPendataanAllocations = AlokasiPetugas::query()
                ->whereIn('periode_alokasi_id', $sensusEkonomiPeriodIds)
                ->whereNotIn('peran', self::PENGOLAHAN_ROLES)
                ->whereHas('petugas', fn ($query) => $query->where('jenis_petugas', 'non-organik'))
                ->with([
                    'petugas:id,nama,jenis_petugas',
                    'periodeAlokasi:id,kegiatan_id,bulan,tahun',
                    'periodeAlokasi.kegiatan:id,nama_kegiatan',
                ])
                ->get();

            foreach ($sensusPendataanAllocations as $allocation) {
                $appendAllocation($allocation, 'pendataan');
            }
        }

        $pelatihanKegiatanQuery = Kegiatan::query()
            ->whereIn('id', $accessibleKegiatanIds)
            ->where('tahun_anggaran', $tahun)
            ->where('bulan_pelatihan', $bulanInt)
            ->whereNotNull('metode_pelatihan')
            ->where('metode_pelatihan', '!=', 'tidak_ada_pelatihan')
            ->select('id', 'bulan_pelatihan', 'tanggal_mulai');

        $pelatihanKegiatanList = $pelatihanKegiatanQuery->get();

        $pelatihanAlokasiInfo = $pelatihanKegiatanList->map(function ($kegiatan) use ($bulanInt, $tahun) {
            $mulaiMonth = $kegiatan->tanggal_mulai?->month;
            $useSameBulan = $mulaiMonth === $bulanInt;

            if ($useSameBulan) {
                return [
                    'kegiatan_id' => $kegiatan->id,
                    'alokasi_bulan' => str_pad($bulanInt, 2, '0', STR_PAD_LEFT),
                    'alokasi_tahun' => $tahun,
                ];
            }

            $nextBulan = $bulanInt + 1;
            $nextTahun = (int) $tahun;

            if ($nextBulan > 12) {
                $nextBulan = 1;
                $nextTahun++;
            }

            return [
                'kegiatan_id' => $kegiatan->id,
                'alokasi_bulan' => str_pad($nextBulan, 2, '0', STR_PAD_LEFT),
                'alokasi_tahun' => (string) $nextTahun,
            ];
        })->keyBy('kegiatan_id');

        $kegiatanWithPelatihanPeriod = collect();
        foreach ($pelatihanAlokasiInfo as $kegiatanId => $info) {
            $hasPeriod = PeriodeAlokasi::query()
                ->where('kegiatan_id', $kegiatanId)
                ->whereIn('bulan', [
                    $info['alokasi_bulan'],
                    (string) ((int) $info['alokasi_bulan']),
                    str_pad((int) $info['alokasi_bulan'], 2, '0', STR_PAD_LEFT),
                ])
                ->where('tahun', $info['alokasi_tahun'])
                ->whereHas('alokasiPetugas', function ($query): void {
                    $query->whereNotIn('peran', self::PENGOLAHAN_ROLES)
                        ->whereHas('petugas', fn ($petugasQuery) => $petugasQuery->where('jenis_petugas', 'non-organik'));
                })
                ->exists();

            if ($hasPeriod) {
                $kegiatanWithPelatihanPeriod->push($kegiatanId);
            }
        }

        $petugasPerKegiatanPelatihan = collect();

        foreach ($pelatihanAlokasiInfo->filter(fn ($info) => $kegiatanWithPelatihanPeriod->contains($info['kegiatan_id']))->groupBy(fn ($info) => $info['alokasi_bulan'].'_'.$info['alokasi_tahun']) as $periodKey => $kegiatanGroup) {
            [$alokasiB, $alokasiT] = explode('_', $periodKey, 2);
            $pelatihanKegiatanIds = $kegiatanGroup->pluck('kegiatan_id');

            $pelatihanAllocations = AlokasiPetugas::query()
                ->whereHas('periodeAlokasi', function ($query) use ($alokasiB, $alokasiT, $pelatihanKegiatanIds): void {
                    $query->whereIn('bulan', [
                        $alokasiB,
                        (string) ((int) $alokasiB),
                        str_pad((int) $alokasiB, 2, '0', STR_PAD_LEFT),
                    ])
                        ->where('tahun', $alokasiT)
                        ->whereIn('kegiatan_id', $pelatihanKegiatanIds);
                })
                ->whereNotIn('peran', self::PENGOLAHAN_ROLES)
                ->whereHas('petugas', fn ($query) => $query->where('jenis_petugas', 'non-organik'))
                ->with([
                    'petugas:id,nama,jenis_petugas',
                    'periodeAlokasi:id,kegiatan_id,bulan,tahun',
                    'periodeAlokasi.kegiatan:id,nama_kegiatan',
                ])
                ->get();

            foreach ($pelatihanAllocations as $allocation) {
                $appendAllocation($allocation, 'pelatihan');
            }
        }

        foreach ($kegiatanOptionsByPetugasKey as $petugasKey => $activities) {
            uasort($activities, fn (array $left, array $right): int => strcmp($left['nama'], $right['nama']));
            $kegiatanOptionsByPetugasKey[$petugasKey] = array_values($activities);
        }

        foreach ($jenisOptionsByPetugasKey as $petugasKey => $types) {
            $jenisOptionsByPetugasKey[$petugasKey] = array_values(array_unique($types));
        }

        ksort($petugasOptions, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'petugas_options' => array_values($petugasOptions),
            'petugas_lookup' => $petugasLookup,
            'kegiatan_options_by_petugas_key' => $kegiatanOptionsByPetugasKey,
            'kegiatan_lookup' => $kegiatanLookup,
            'jenis_options_by_petugas_key' => $jenisOptionsByPetugasKey,
            'jenis_lookup' => $jenisLookup,
        ];
    }

    private function normalizeTemplateKey(string $value): string
    {
        $normalized = Str::of($value)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/', '_')
            ->trim('_')
            ->upper();

        return 'PTG_'.($normalized !== '' ? $normalized : 'UNKNOWN');
    }

    /**
     * Store newly created pengajuan pulsa records.
     */
    public function store(StorePengajuanPulsaRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $effectiveUser = effectiveUser($request);
        $bulan = $validated['bulan'];
        $tahun = ActiveYearService::get();
        $items = $validated['items'];

        /** @var array<int, array{kegiatan_id: int, petugas_id: int, jenis_pulsa: string, nominal: float}> $items */

        // Validate that all kegiatan are CAPI and belong to this ketua tim
        $kegiatanIds = collect($items)->pluck('kegiatan_id')->unique()->values();
        // Load kegiatan for validation
        $validKegiatanModels = Kegiatan::whereIn('id', $kegiatanIds)
            ->when(
                $effectiveUser?->isKetuaTim() && ! $effectiveUser->isAdmin(),
                fn ($q) => $q->where(function ($q2) use ($effectiveUser) {
                    $q2->where('ketua_tim_user_id', $effectiveUser->id)
                        ->orWhere('pj_lainnya_id', $effectiveUser->id);
                })
            )
            ->get(['id', 'metode_pendataan_pencacahan', 'metode_pelatihan', 'bulan_pelatihan']);

        $validKegiatanById = $validKegiatanModels->keyBy('id');
        $validKegiatan = $validKegiatanModels->pluck('id');

        $invalidKegiatan = $kegiatanIds->diff($validKegiatan);

        if ($invalidKegiatan->isNotEmpty()) {
            return back()->withErrors([
                'items' => 'Beberapa kegiatan tidak ditemukan atau bukan kegiatan Anda.',
            ]);
        }

        // Validate per-item: pulsa pelatihan only allowed for kegiatan with pelatihan month configured
        foreach ($items as $item) {
            if ($item['jenis_pulsa'] === 'pelatihan') {
                /** @var Kegiatan|null $kegiatan */
                $kegiatan = $validKegiatanById->get($item['kegiatan_id']);
                $metodePelatihan = $kegiatan?->metode_pelatihan;
                if ($metodePelatihan === null || $metodePelatihan === 'tidak_ada_pelatihan') {
                    return back()->withErrors([
                        'items' => 'Pulsa pelatihan hanya dapat diajukan untuk kegiatan yang memiliki pelatihan.',
                    ]);
                }

                if ((int) ($kegiatan?->bulan_pelatihan ?? 0) !== (int) $bulan) {
                    return back()->withErrors([
                        'items' => 'Pulsa pelatihan hanya dapat diajukan pada bulan penyelenggaraan pelatihan yang diatur pada kegiatan.',
                    ]);
                }
            }
            if ($item['jenis_pulsa'] === 'pendataan') {
                /** @var Kegiatan|null $kegiatan */
                $kegiatan = $validKegiatanById->get($item['kegiatan_id']);
                if (! Kegiatan::isFasihMetodePendataan($kegiatan?->metode_pendataan_pencacahan)) {
                    return back()->withErrors([
                        'items' => 'Pulsa pendataan hanya dapat diajukan untuk kegiatan dengan metode pendataan FASIH.',
                    ]);
                }
            }
        }

        // Validate per-petugas max 100k across all items for this bulan/tahun
        $newTotalsPerPetugas = collect($items)
            ->groupBy('petugas_id')
            ->map(fn ($group) => collect($group)->sum('nominal'));

        $existingTotals = PengajuanPulsa::whereIn('petugas_id', $newTotalsPerPetugas->keys())
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereNotIn('status', ['ditolak'])
            ->select('petugas_id', DB::raw('SUM(nominal) as total_nominal'))
            ->groupBy('petugas_id')
            ->pluck('total_nominal', 'petugas_id');

        $errors = [];
        foreach ($newTotalsPerPetugas as $petugasId => $newTotal) {
            $existingTotal = (float) ($existingTotals[$petugasId] ?? 0);
            if ($existingTotal + $newTotal > 100000) {
                $errors["petugas_{$petugasId}"] = "Total pulsa petugas ID {$petugasId} melebihi batas Rp100.000 per bulan (saat ini: Rp{$existingTotal}, baru: Rp{$newTotal}).";
            }
        }

        if (! empty($errors)) {
            return back()->withErrors($errors);
        }

        // Find periode_alokasi for each kegiatan+bulan+tahun
        $periodeAlokasiMap = PeriodeAlokasi::whereIn('kegiatan_id', $kegiatanIds)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->pluck('id', 'kegiatan_id');

        $storedCount = 0;
        $totalNominal = 0.0;

        DB::transaction(function () use ($items, $bulan, $tahun, $periodeAlokasiMap, $validated, $effectiveUser, &$storedCount, &$totalNominal) {
            foreach ($items as $item) {
                if ((float) $item['nominal'] <= 0) {
                    continue;
                }

                PengajuanPulsa::create([
                    'petugas_id' => $item['petugas_id'],
                    'kegiatan_id' => $item['kegiatan_id'],
                    'periode_alokasi_id' => $periodeAlokasiMap[$item['kegiatan_id']] ?? null,
                    'bulan' => $bulan,
                    'tahun' => (int) $tahun,
                    'jenis_pulsa' => $item['jenis_pulsa'],
                    'nominal' => $item['nominal'],
                    'status' => 'dikirim',
                    'submitted_by' => $effectiveUser?->id ?? Auth::id(),
                    'submitted_at' => now(),
                    'catatan' => $validated['catatan'] ?? null,
                ]);

                $storedCount++;
                $totalNominal += (float) $item['nominal'];
            }
        });

        $bulanName = Carbon::create()->month((int) $bulan)->translatedFormat('F');
        $uniqueKegiatanCount = $kegiatanIds->count();

        try {
            ActivityLog::log(
                'Kirim Pengajuan Pulsa',
                'pengajuan_pulsa',
                "Berhasil mengirim {$storedCount} pengajuan pulsa untuk {$bulanName} {$tahun} ({$uniqueKegiatanCount} kegiatan, total Rp".number_format($totalNominal, 0, ',', '.').')',
                'success',
                [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'item_count' => $storedCount,
                    'total_nominal' => $totalNominal,
                    'kegiatan_ids' => $kegiatanIds->toArray(),
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to log pengajuan pulsa activity', ['error' => $e->getMessage()]);
        }

        return redirect()->route('pengajuan-pulsa.index', [
            'bulan' => $bulan,
            'tahun' => $tahun,
        ])->with('success', 'Pengajuan pulsa berhasil disimpan.');
    }

    /**
     * Show the detail page for a specific kegiatan's pengajuan pulsa in a given month.
     */
    public function detail(Request $request): Response
    {
        $effectiveUser = effectiveUser($request);

        if ($request->has('state')) {
            $request->merge(decryptFilters((string) $request->query('state')));
        }

        $kegiatanId = (int) $request->input('kegiatan_id');
        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::findOrFail($kegiatanId);

        $isAdminOrOperator = $effectiveUser?->isAdmin() || $effectiveUser?->isOperator();

        if (! $isAdminOrOperator) {
            abort_unless(
                $kegiatan->ketua_tim_user_id === $effectiveUser?->id ||
                $kegiatan->pj_lainnya_id === $effectiveUser?->id,
                403
            );
        }

        $pengajuanList = PengajuanPulsa::query()
            ->with([
                'petugas:id,nama',
                'submittedBy:id,name',
                'reviewedBy:id,name',
            ])
            ->where('kegiatan_id', $kegiatanId)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->orderBy('petugas_id')
            ->orderBy('jenis_pulsa')
            ->get();

        // For the review modal: load ALL pengajuan pulsa for these petugas in this period
        // across ALL kegiatan so reviewer has full context (e.g. total across all kegiatan).
        $petugasIds = $pengajuanList->pluck('petugas_id')->unique()->values();

        $allPulsaForPetugas = PengajuanPulsa::query()
            ->with(['kegiatan:id,kode_kegiatan,nama_kegiatan'])
            ->whereIn('petugas_id', $petugasIds)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereNotIn('status', ['ditolak'])
            ->get(['id', 'petugas_id', 'kegiatan_id', 'jenis_pulsa', 'nominal', 'nominal_disetujui', 'status']);

        /** @var Collection<int, Collection<int, array{id: int, kegiatan_id: int, kegiatan_kode: string|null, kegiatan_nama: string|null, jenis_pulsa: string, nominal: float, nominal_disetujui: float|null, status: string, is_current_kegiatan: bool}>> $allPulsaPerPetugas */
        $allPulsaPerPetugas = $allPulsaForPetugas
            ->groupBy('petugas_id')
            ->map(function ($group) use ($kegiatanId) {
                return $group->map(function ($p) use ($kegiatanId) {
                    return [
                        'id' => $p->id,
                        'kegiatan_id' => $p->kegiatan_id,
                        'kegiatan_kode' => $p->kegiatan?->kode_kegiatan,
                        'kegiatan_nama' => $p->kegiatan?->nama_kegiatan,
                        'jenis_pulsa' => $p->jenis_pulsa,
                        'nominal' => (float) $p->nominal,
                        'nominal_disetujui' => $p->nominal_disetujui !== null ? (float) $p->nominal_disetujui : null,
                        'status' => $p->status,
                        'is_current_kegiatan' => (int) $p->kegiatan_id === $kegiatanId,
                    ];
                })->values();
            });

        return Inertia::render('PengajuanPulsa/Detail', [
            'kegiatan' => $kegiatan->only(['id', 'kode_kegiatan', 'nama_kegiatan', 'metode_pendataan_pencacahan']),
            'pengajuanList' => [
                'encrypted' => encryptData($pengajuanList),
            ],
            'allPulsaPerPetugas' => $allPulsaPerPetugas,
            'filters' => [
                'bulan' => $bulan,
                'tahun' => (string) $tahun,
            ],
            'canReview' => $isAdminOrOperator,
            'canResubmit' => true,
        ]);
    }

    /**
     * Resubmit a rejected pengajuan pulsa after revision.
     */
    public function resubmit(Request $request, PengajuanPulsa $pengajuanPulsa): RedirectResponse
    {
        $effectiveUser = effectiveUser($request);
        $isAdminOrOperator = $effectiveUser?->isAdmin() || $effectiveUser?->isOperator();

        if (! $isAdminOrOperator) {
            $isOwner = Kegiatan::query()
                ->where('id', $pengajuanPulsa->kegiatan_id)
                ->where(function ($q) use ($effectiveUser) {
                    $q->where('ketua_tim_user_id', $effectiveUser?->id)
                        ->orWhere('pj_lainnya_id', $effectiveUser?->id);
                })
                ->exists();

            abort_unless($isOwner, 403);
        }

        if ($pengajuanPulsa->status !== 'ditolak') {
            return back()->withErrors([
                'nominal' => 'Hanya pengajuan dengan status Ditolak yang dapat diperbaiki dan dikirim ulang.',
            ]);
        }

        $validated = $request->validate([
            'nominal' => ['required', 'numeric', 'min:1', 'max:100000'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $nominal = (float) $validated['nominal'];
        if (((int) $nominal) % 1000 !== 0) {
            return back()->withErrors([
                'nominal' => 'Nominal pulsa harus kelipatan Rp1.000.',
            ]);
        }

        $currentTotal = (float) PengajuanPulsa::query()
            ->where('petugas_id', $pengajuanPulsa->petugas_id)
            ->where('bulan', $pengajuanPulsa->bulan)
            ->where('tahun', $pengajuanPulsa->tahun)
            ->whereNotIn('status', ['ditolak'])
            ->sum('nominal');

        if (($currentTotal + $nominal) > 100000) {
            return back()->withErrors([
                'nominal' => 'Total pulsa petugas melebihi batas Rp100.000 per bulan.',
            ]);
        }

        $pengajuanPulsa->update([
            'nominal' => $nominal,
            'status' => 'dikirim',
            'nominal_disetujui' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'catatan_penolakan' => null,
            'submitted_by' => $effectiveUser?->id ?? Auth::id(),
            'submitted_at' => now(),
            'catatan' => $validated['catatan'] ?? $pengajuanPulsa->catatan,
        ]);

        try {
            ActivityLog::log(
                'Kirim Ulang Pengajuan Pulsa',
                'pengajuan_pulsa',
                'Pengajuan pulsa yang ditolak telah diperbaiki dan dikirim ulang.',
                'success',
                [
                    'pengajuan_id' => $pengajuanPulsa->id,
                    'kegiatan_id' => $pengajuanPulsa->kegiatan_id,
                    'petugas_id' => $pengajuanPulsa->petugas_id,
                    'bulan' => $pengajuanPulsa->bulan,
                    'tahun' => $pengajuanPulsa->tahun,
                    'nominal' => $nominal,
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to log resubmit pengajuan pulsa activity', ['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Pengajuan pulsa berhasil diperbaiki dan dikirim ulang.');
    }

    /**
     * Review (approve or reject) a single pengajuan pulsa.
     */
    public function review(Request $request, PengajuanPulsa $pengajuanPulsa): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:diterima,ditolak'],
            'nominal_disetujui' => [
                'required_if:action,diterima',
                'nullable',
                'numeric',
                'min:1',
                'max:'.(float) $pengajuanPulsa->nominal,
            ],
            'catatan_penolakan' => ['required_if:action,ditolak', 'nullable', 'string', 'max:500'],
        ]);

        $pengajuanPulsa->load('petugas:id,nama', 'kegiatan:id,kode_kegiatan,nama_kegiatan');

        $isDiterima = $validated['action'] === 'diterima';
        $nominalDisetujui = $isDiterima ? (float) ($validated['nominal_disetujui'] ?? $pengajuanPulsa->nominal) : null;

        $pengajuanPulsa->update([
            'status' => $validated['action'],
            'nominal_disetujui' => $nominalDisetujui,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'catatan_penolakan' => $isDiterima ? null : ($validated['catatan_penolakan'] ?? null),
        ]);

        $petugasName = $pengajuanPulsa->petugas?->nama ?? 'Petugas #'.$pengajuanPulsa->petugas_id;
        $namaKegiatan = $pengajuanPulsa->kegiatan?->nama_kegiatan ?? 'Kegiatan #'.$pengajuanPulsa->kegiatan_id;
        $bulanName = Carbon::create()->month((int) $pengajuanPulsa->bulan)->translatedFormat('F');
        $nominalFormatted = 'Rp'.number_format((float) $pengajuanPulsa->nominal, 0, ',', '.');
        $disetujuiFormatted = 'Rp'.number_format((float) $nominalDisetujui, 0, ',', '.');
        $jenisPulsa = $pengajuanPulsa->jenis_pulsa === 'pendataan' ? 'Pendataan' : 'Pelatihan';

        try {
            ActivityLog::log(
                $isDiterima ? 'Terima Pengajuan Pulsa' : 'Tolak Pengajuan Pulsa',
                'pengajuan_pulsa',
                $isDiterima
                    ? "Pengajuan pulsa {$jenisPulsa} {$petugasName} ({$namaKegiatan}) {$bulanName} {$pengajuanPulsa->tahun} senilai {$nominalFormatted} diterima (disetujui {$disetujuiFormatted})"
                    : "Pengajuan pulsa {$jenisPulsa} {$petugasName} ({$namaKegiatan}) {$bulanName} {$pengajuanPulsa->tahun} senilai {$nominalFormatted} ditolak",
                'success',
                [
                    'pengajuan_id' => $pengajuanPulsa->id,
                    'kegiatan_id' => $pengajuanPulsa->kegiatan_id,
                    'nama_kegiatan' => $namaKegiatan,
                    'petugas_id' => $pengajuanPulsa->petugas_id,
                    'petugas_nama' => $petugasName,
                    'jenis_pulsa' => $pengajuanPulsa->jenis_pulsa,
                    'nominal' => (float) $pengajuanPulsa->nominal,
                    'nominal_disetujui' => $nominalDisetujui,
                    'bulan' => $pengajuanPulsa->bulan,
                    'tahun' => $pengajuanPulsa->tahun,
                    'action' => $validated['action'],
                    'catatan_penolakan' => $validated['catatan_penolakan'] ?? null,
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to log review pengajuan pulsa activity', ['error' => $e->getMessage()]);
        }

        $message = $isDiterima
            ? 'Pengajuan pulsa berhasil diterima.'
            : 'Pengajuan pulsa ditolak.';

        return back()->with('success', $message);
    }

    /**
     * Bulk-review "dikirim" pengajuan pulsa for a kegiatan in a given period.
     * Accepts per-item decisions including per-item nominal_disetujui.
     *
     * @param  array<int, array{id: int, action: string, nominal_disetujui?: float, catatan_penolakan?: string}>  $items
     */
    public function reviewAll(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kegiatan_id' => ['required', 'integer', 'exists:kegiatan,id'],
            'bulan' => ['required', 'string'],
            'tahun' => ['required', 'integer'],
            'catatan_penolakan' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:pengajuan_pulsa,id'],
            'items.*.action' => ['required', 'in:diterima,ditolak'],
            'items.*.nominal_disetujui' => ['nullable', 'numeric', 'min:1'],
            'items.*.catatan_penolakan' => ['nullable', 'string', 'max:500'],
        ]);

        // Load all targeted items and verify they belong to the given kegiatan/period
        $itemIds = collect($validated['items'])->pluck('id');
        $targetItems = PengajuanPulsa::query()
            ->with('petugas:id,nama', 'kegiatan:id,kode_kegiatan,nama_kegiatan')
            ->whereIn('id', $itemIds)
            ->where('kegiatan_id', $validated['kegiatan_id'])
            ->where('bulan', $validated['bulan'])
            ->where('tahun', $validated['tahun'])
            ->where('status', 'dikirim')
            ->get()
            ->keyBy('id');

        if ($targetItems->isEmpty()) {
            return back()->with('error', 'Tidak ada pengajuan pulsa dengan status "Diajukan" untuk periode ini.');
        }

        $reviewedAt = now();
        $reviewerId = Auth::id();
        $globalCatatan = $validated['catatan_penolakan'] ?? null;

        /** @var array<int, array{action: string, nominal_disetujui?: float|null, catatan_penolakan?: string|null}> $itemMap */
        $itemMap = collect($validated['items'])->keyBy('id');

        $countDiterima = 0;
        $countDitolak = 0;
        $totalDisetujui = 0.0;

        foreach ($targetItems as $item) {
            $itemData = $itemMap[$item->id] ?? null;
            if (! $itemData) {
                continue;
            }

            $isDiterima = $itemData['action'] === 'diterima';
            $nominalDisetujui = $isDiterima
                ? min((float) ($itemData['nominal_disetujui'] ?? $item->nominal), (float) $item->nominal)
                : null;

            $catatanPenolakan = $isDiterima
                ? null
                : ($itemData['catatan_penolakan'] ?? $globalCatatan);

            $item->update([
                'status' => $itemData['action'],
                'nominal_disetujui' => $nominalDisetujui,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => $reviewedAt,
                'catatan_penolakan' => $catatanPenolakan,
            ]);

            if ($isDiterima) {
                $countDiterima++;
                $totalDisetujui += $nominalDisetujui ?? 0.0;
            } else {
                $countDitolak++;
            }
        }

        $firstItem = $targetItems->first();
        $kodeKegiatan = $firstItem->kegiatan?->kode_kegiatan ?? 'Kegiatan #'.$validated['kegiatan_id'];
        $bulanName = Carbon::create()->month((int) $validated['bulan'])->translatedFormat('F');
        $count = $countDiterima + $countDitolak;

        try {
            ActivityLog::log(
                'Proses Review Pengajuan Pulsa',
                'pengajuan_pulsa',
                "Review {$count} pengajuan pulsa ({$kodeKegiatan}) {$bulanName} {$validated['tahun']}: {$countDiterima} diterima, {$countDitolak} ditolak",
                'success',
                [
                    'kegiatan_id' => $validated['kegiatan_id'],
                    'kode_kegiatan' => $kodeKegiatan,
                    'bulan' => $validated['bulan'],
                    'tahun' => $validated['tahun'],
                    'count_diterima' => $countDiterima,
                    'count_ditolak' => $countDitolak,
                    'total_disetujui' => $totalDisetujui,
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to log review-all pengajuan pulsa activity', ['error' => $e->getMessage()]);
        }

        $parts = [];
        if ($countDiterima > 0) {
            $parts[] = "{$countDiterima} diterima";
        }
        if ($countDitolak > 0) {
            $parts[] = "{$countDitolak} ditolak";
        }
        $message = 'Review selesai: '.implode(', ', $parts).'.';

        return back()->with('success', $message);
    }
}
