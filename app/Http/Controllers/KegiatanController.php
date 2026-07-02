<?php

namespace App\Http\Controllers;

use App\Exports\FrameSampelDetailTemplateExport;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\StoreKegiatanRequest;
use App\Http\Requests\UpdateKegiatanRequest;
use App\Models\ActivityLog;
use App\Models\Kegiatan;
use App\Models\MasterFrameSampel;
use App\Models\MasterUnitSampel;
use App\Models\PeriodeAlokasi;
use App\Models\RateHonor;
use App\Models\Satuan;
use App\Models\User;
use App\Services\ActiveYearService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KegiatanController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(FilterRequest $request): Response
    {
        $validated = $request->validated();
        $activeYear = ActiveYearService::get();

        // Get only the fields that have non-null values
        $actualFilters = array_filter([
            'search' => $validated['search'] ?? null,
            'status' => $validated['status'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $query = Kegiatan::query()
            ->select('kegiatan.*')
            ->with('ketuaTim:id,name')
            ->withCount([
                'periodeAlokasi as periode_alokasi_count' => function ($q) {
                    $q->where('status', '!=', 'dihapus');
                },
            ])
            ->where('tahun_anggaran', $activeYear);

        // Search
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama_kegiatan', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Filter by Ketua Tim for ketua_tim role (exclude admin)
        $effectiveUser = effectiveUser($request);
        if ($effectiveUser->isKetuaTim() && ! $effectiveUser->hasActiveRole('admin')) {
            $query->where('ketua_tim_user_id', $effectiveUser->id)->orWhere('pj_lainnya_id', $effectiveUser->id);
        }

        // Load ALL data for client-side filtering, sorting, and pagination
        $kegiatans = $query->latest()->get();

        // Encrypt sensitive data
        $encryptedData = encryptData($kegiatans);
        $totalData = $kegiatans->count();

        return Inertia::render('Kegiatan/Index', [
            'kegiatans' => [
                'encrypted' => $encryptedData,
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $totalData,
                    'total' => $totalData,
                    'from' => $totalData > 0 ? 1 : 0,
                    'to' => $totalData,
                ],
                'links' => [],
            ],
            'filters' => [
                'encrypted' => encryptFilters($actualFilters),
                'decrypted' => $actualFilters,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $ketuaTimUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'ketua_tim'))
            ->where('is_active', true)
            ->select('id', 'name', 'email')
            ->get();

        // PJ lainnya list for create form (only ketua_tim users)
        $pjLainnyaUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'ketua_tim'))
            ->where('is_active', true)
            ->select('id', 'name', 'email')
            ->get();

        $rateHonors = RateHonor::with(['satuan', 'satuanListing'])
            ->where('status', 'aktif')
            ->where('tahun_berlaku', now()->year)
            ->get();

        // Generate tahun options (current year - 2 to current year + 5)
        $currentYear = (int) date('Y');
        $tahunOptions = range($currentYear - 2, $currentYear + 5);

        $masterFrameSampel = MasterFrameSampel::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode']);

        $masterUnitSampel = MasterUnitSampel::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode']);

        return Inertia::render('Kegiatan/Create', [
            'ketuaTimUsers' => $ketuaTimUsers,
            'pjLainnyaUsers' => $pjLainnyaUsers,
            'rateHonors' => $rateHonors,
            'tahunOptions' => $tahunOptions,
            'masterFrameSampel' => $masterFrameSampel,
            'masterUnitSampel' => $masterUnitSampel,
            'kegiatanFrameSampel' => [],
        ]);
    }

    /**
     * Show the form for copying an existing kegiatan.
     */
    public function copy(Request $request, Kegiatan $kegiatan): Response
    {
        // Authorization via policy
        $this->authorize('view', $kegiatan);

        $sourceUserIds = collect([
            $kegiatan->ketua_tim_user_id,
            $kegiatan->pj_lainnya_id,
        ])->filter()->values();

        $ketuaTimUsers = User::query()
            ->where(function ($q) use ($sourceUserIds) {
                $q->where(function ($q2) {
                    $q2->whereHas('roles', fn ($q3) => $q3->where('name', 'ketua_tim'))
                        ->where('is_active', true);
                });

                if ($sourceUserIds->isNotEmpty()) {
                    $q->orWhereIn('id', $sourceUserIds);
                }
            })
            ->select('id', 'name', 'email')
            ->get();

        // PJ lainnya list for create form (only ketua_tim users)
        $pjLainnyaUsers = User::query()
            ->where(function ($q) use ($sourceUserIds) {
                $q->where(function ($q2) {
                    $q2->whereHas('roles', fn ($q3) => $q3->where('name', 'ketua_tim'))
                        ->where('is_active', true);
                });

                if ($sourceUserIds->isNotEmpty()) {
                    $q->orWhereIn('id', $sourceUserIds);
                }
            })
            ->select('id', 'name', 'email')
            ->get();

        // Generate tahun options (current year - 2 to current year + 5)
        $currentYear = (int) date('Y');
        $tahunOptions = range($currentYear - 2, $currentYear + 5);

        $masterFrameSampel = MasterFrameSampel::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode']);

        $masterUnitSampel = MasterUnitSampel::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode']);

        // Prepare copy data from source kegiatan
        $copyData = [
            'nama_kegiatan' => $kegiatan->nama_kegiatan,
            'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
            'deskripsi' => $kegiatan->deskripsi,
            'tahun_anggaran' => $kegiatan->tahun_anggaran,
            'has_listing_updating' => $kegiatan->has_listing_updating,
            'metode_pendataan_pencacahan' => Kegiatan::normalizeMetodePendataan($kegiatan->metode_pendataan_pencacahan),
            'metode_pendataan_listing' => Kegiatan::normalizeMetodePendataan($kegiatan->metode_pendataan_listing),
            'ketua_tim_user_id' => $kegiatan->ketua_tim_user_id,
            'pj_lainnya_id' => $kegiatan->pj_lainnya_id,
        ];

        $kegiatanFrameSampel = $kegiatan->kegiatanFrameSampel()
            ->select('id', 'tahapan', 'target_unit_sampel', 'identitas_tambahan')
            ->orderBy('id')
            ->get();

        return Inertia::render('Kegiatan/Create', [
            'ketuaTimUsers' => $ketuaTimUsers,
            'pjLainnyaUsers' => $pjLainnyaUsers,
            'tahunOptions' => $tahunOptions,
            'copyData' => $copyData,
            'isCopyMode' => true,
            'masterFrameSampel' => $masterFrameSampel,
            'masterUnitSampel' => $masterUnitSampel,
            'kegiatanFrameSampel' => $kegiatanFrameSampel,
        ]);
    }

    /**
     * Generate kode kegiatan otomatis.
     */
    private function generateKodeKegiatan(int $tahunAnggaran): string
    {
        // Format: KEG-{TAHUN}-{NOMOR_URUT}
        $prefix = "KEG-{$tahunAnggaran}-";

        // Get last kegiatan number for this year
        $lastKegiatan = Kegiatan::where('kode_kegiatan', 'like', $prefix.'%')
            ->orderBy('kode_kegiatan', 'desc')
            ->first();

        if ($lastKegiatan) {
            // Extract number from last code
            $lastNumber = (int) str_replace($prefix, '', $lastKegiatan->kode_kegiatan);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreKegiatanRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $kegiatanFrameSampel = $data['kegiatan_frame_sampel'] ?? [];
        unset($data['kegiatan_frame_sampel']);

        // If ketua_tim creates kegiatan, automatically assign themselves as ketua_tim
        $effectiveUser = effectiveUser($request);
        if ($effectiveUser->hasActiveRole('ketua_tim')) {
            $data['ketua_tim_user_id'] = $effectiveUser->id;
        }

        // Generate kode kegiatan otomatis
        $data['kode_kegiatan'] = $this->generateKodeKegiatan($data['tahun_anggaran']);

        // Pastikan field baru ikut tersimpan
        if (isset($data['has_listing_updating'])) {
            $data['has_listing_updating'] = (bool) $data['has_listing_updating'];
        }
        if (isset($data['pagu_listing'])) {
            $data['pagu_listing'] = $data['pagu_listing'];
        }

        if (! isset($data['status'])) {
            $data['status'] = 'draft';
        }

        $kegiatan = DB::transaction(function () use ($data, $kegiatanFrameSampel): Kegiatan {
            $kegiatan = Kegiatan::create($data);

            $this->syncKegiatanFrameSampelRows(
                $kegiatan,
                $kegiatanFrameSampel,
                [
                    'listing' => $data['frame_sampel_listing_id'] ?? null,
                    'pencacahan' => $data['frame_sampel_pencacahan_id'] ?? null,
                ]
            );

            if (isset($data['pj_lainnya_id'])) {
                $kegiatan->update(['pj_lainnya_id' => $data['pj_lainnya_id']]);
            }

            return $kegiatan;
        });

        ActivityLog::log(
            'Tambah Kegiatan',
            'kegiatan',
            "Berhasil menambahkan kegiatan baru: {$kegiatan->nama_kegiatan}",
            'success',
            [
                'kegiatan_id' => $kegiatan->id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'data' => $this->buildKegiatanSnapshot($kegiatan),
            ]
        );

        return redirect()->route('kegiatan.index')
            ->with('success', 'Data kegiatan baru sudah berhasil disimpan ke sistem.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Kegiatan $kegiatan): Response
    {
        // Authorization via policy
        $this->authorize('view', $kegiatan);

        $kegiatan->load([
            'ketuaTim',
            'pjLainnya',
            'frameSampelListing:id,nama,kode',
            'frameSampelPencacahan:id,nama,kode',
            'rateHonors.satuan',
            'rateHonors.satuanListing',
            'periodeAlokasi.alokasiPetugas.petugas',
        ]);

        // unitSampelListing and unitSampelPencacahan are now stored as ID arrays,
        // not Eloquent relationships - resolve them manually.
        $kegiatan->unit_sampel_listing = $kegiatan->unitSampelListingItems();
        $kegiatan->unit_sampel_pencacahan = $kegiatan->unitSampelPencacahanItems();

        // Flatten periodeAlokasi->alokasiPetugas for backward compatibility
        $alokasi = $kegiatan->periodeAlokasi->flatMap(function ($periode) {
            return $periode->alokasiPetugas->map(function ($alok) use ($periode) {
                $alok->bulan = (int) $periode->bulan;
                $alok->tahun = $periode->tahun;
                $alok->jenis_kegiatan = $periode->jenis_kegiatan;
                $alok->status = $periode->status;

                return $alok;
            });
        });

        $kegiatan->alokasi = $alokasi;
        unset($kegiatan->periodeAlokasi);

        $effectiveUser = effectiveUser($request);

        return Inertia::render('Kegiatan/Show', [
            'kegiatan' => $kegiatan,
            'can' => [
                'update' => $effectiveUser->can('update', $kegiatan),
                'approve' => $effectiveUser->can('approve', $kegiatan),
                'reject' => $effectiveUser->can('reject', $kegiatan),
                'delete' => $effectiveUser->can('delete', $kegiatan),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Kegiatan $kegiatan): Response
    {
        // Authorization via policy
        $this->authorize('update', $kegiatan);

        // Additional check: only allow editing draft or divalidasi status
        if (! in_array($kegiatan->status, ['draft', 'divalidasi'])) {
            abort(403, 'Kegiatan hanya bisa diedit jika statusnya draft atau divalidasi.');
        }

        $ketuaTimUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'ketua_tim'))
            ->where('is_active', true)
            ->select('id', 'name', 'email')
            ->get();

        // PJ lainnya list for edit form (only ketua_tim users)
        $pjLainnyaUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'ketua_tim'))
            ->where('is_active', true)
            ->select('id', 'name', 'email')
            ->get();

        // Generate tahun options (current year - 2 to current year + 5)
        $currentYear = (int) date('Y');
        $tahunOptions = range($currentYear - 2, $currentYear + 5);

        $masterFrameSampel = MasterFrameSampel::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode']);

        $masterUnitSampel = MasterUnitSampel::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode']);

        return Inertia::render('Kegiatan/Edit', [
            'kegiatan' => $kegiatan,
            'ketuaTimUsers' => $ketuaTimUsers,
            'pjLainnyaUsers' => $pjLainnyaUsers,
            'tahunOptions' => $tahunOptions,
            'masterFrameSampel' => $masterFrameSampel,
            'masterUnitSampel' => $masterUnitSampel,
            'kegiatanFrameSampel' => $kegiatan->kegiatanFrameSampel()
                ->select('id', 'tahapan', 'target_unit_sampel', 'identitas_tambahan')
                ->orderBy('id')
                ->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateKegiatanRequest $request, Kegiatan $kegiatan): RedirectResponse
    {
        // Authorization via policy
        $this->authorize('update', $kegiatan);

        // Additional check: only allow updating draft or divalidasi status
        if (! in_array($kegiatan->status, ['draft', 'divalidasi'])) {
            return back()->with('error', 'Maaf, kegiatan ini tidak bisa diubah karena statusnya sudah bukan draft atau divalidasi.');
        }

        $data = $request->validated();
        $kegiatanFrameSampel = $data['kegiatan_frame_sampel'] ?? [];
        unset($data['kegiatan_frame_sampel']);

        // Transform pagu_pencacahan to pagu_pencacahan (database column name)
        // Pastikan field baru ikut tersimpan
        if (isset($data['has_listing_updating'])) {
            $data['has_listing_updating'] = (bool) $data['has_listing_updating'];
        }
        if (isset($data['pagu_listing'])) {
            $data['pagu_listing'] = $data['pagu_listing'];
        }

        // Ketua Tim can validate kegiatan (check before updating)
        $effectiveUser = effectiveUser($request);
        if ($effectiveUser->isKetuaTim() && $request->filled('validate')) {
            $data['status'] = 'divalidasi';
            $data['tanggal_validasi'] = now();
        }

        // Check if tanggal kegiatan is being changed
        $oldTanggalMulai = $kegiatan->tanggal_mulai;
        $oldTanggalSelesai = $kegiatan->tanggal_selesai;
        $newTanggalMulai = isset($data['tanggal_mulai']) ? Carbon::parse($data['tanggal_mulai']) : $oldTanggalMulai;
        $newTanggalSelesai = isset($data['tanggal_selesai']) ? Carbon::parse($data['tanggal_selesai']) : $oldTanggalSelesai;
        $tanggalChanged = $oldTanggalMulai != $newTanggalMulai || $oldTanggalSelesai != $newTanggalSelesai;

        if ($tanggalChanged) {
            // Load existing periode alokasi
            $existingPeriodes = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                ->orderBy('tahun')
                ->orderBy('bulan')
                ->get();

            // Check if any periode is outside the new date range
            $invalidPeriodes = [];
            foreach ($existingPeriodes as $periode) {
                // Create date from periode (first day of the month)
                $periodeTanggal = Carbon::createFromDate($periode->tahun, (int) $periode->bulan, 1);

                // Compare with start and end of month ranges (use copy to avoid mutating original)
                $rangeStart = $newTanggalMulai->copy()->startOfMonth();
                $rangeEnd = $newTanggalSelesai->copy()->endOfMonth();

                if ($periodeTanggal->lt($rangeStart) || $periodeTanggal->gt($rangeEnd)) {
                    // Get month name in Indonesian
                    $bulanInt = (int) $periode->bulan;
                    $namabulan = Carbon::create()->month($bulanInt)->translatedFormat('F');

                    $invalidPeriodes[] = sprintf('%s %d', $namabulan, $periode->tahun);
                }
            }

            if (! empty($invalidPeriodes)) {
                return back()->withErrors([
                    'tanggal_mulai' => sprintf(
                        'Tidak dapat mengubah tanggal kegiatan karena terdapat periode alokasi di luar rentang tanggal baru: %s. Hapus atau ubah periode tersebut terlebih dahulu.',
                        implode(', ', $invalidPeriodes)
                    ),
                ])->withInput();
            }
        }

        // Check if pagu_pencacahan (pagu) is being changed
        $oldPagu = (float) ($kegiatan->pagu_pencacahan ?? 0);
        $newPagu = (float) ($data['pagu_pencacahan'] ?? 0);
        $paguChanged = $oldPagu != $newPagu;

        if ($paguChanged) {
            // Load all alokasi for this kegiatan
            $kegiatan->load(['periodeAlokasi' => function ($query) {
                $query->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                    ->with('alokasiPetugas');
            }]);

            // Calculate total honor already allocated
            $totalHonorAlokasi = $kegiatan->periodeAlokasi->sum(function ($periode) {
                return $periode->alokasiPetugas->sum('total_honor');
            });

            // Check if new pagu is smaller than total allocated honor
            if ($newPagu < $totalHonorAlokasi) {
                return back()->withErrors([
                    'pagu_pencacahan' => sprintf(
                        'Pagu anggaran tidak boleh lebih kecil dari total honor yang sudah dialokasikan (Rp %s). Total honor saat ini: Rp %s',
                        number_format($newPagu, 0, ',', '.'),
                        number_format($totalHonorAlokasi, 0, ',', '.')
                    ),
                ])->withInput();
            }
        }

        // Update kegiatan with all validated data
        // Normalize pj_lainnya_id: empty string => null
        if (array_key_exists('pj_lainnya_id', $data) && ($data['pj_lainnya_id'] === '' || $data['pj_lainnya_id'] === null)) {
            $data['pj_lainnya_id'] = null;
        }

        $oldSnapshot = $this->buildKegiatanSnapshot($kegiatan);
        DB::transaction(function () use ($kegiatan, $kegiatanFrameSampel, $data): void {
            $kegiatan->update($data);

            $this->syncKegiatanFrameSampelRows(
                $kegiatan,
                $kegiatanFrameSampel,
                [
                    'listing' => $data['frame_sampel_listing_id'] ?? $kegiatan->frame_sampel_listing_id,
                    'pencacahan' => $data['frame_sampel_pencacahan_id'] ?? $kegiatan->frame_sampel_pencacahan_id,
                ]
            );
        });
        $kegiatan->refresh();
        $logChanges = $this->computeKegiatanChanges($oldSnapshot, $this->buildKegiatanSnapshot($kegiatan));

        // If pagu changed, recalculate all periode sisa_pagu
        if ($paguChanged) {
            // Recalculate sisa_pagu for all periods sequentially (January to December)
            $periodes = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                ->orderBy('tahun')
                ->orderBy('bulan')
                ->with('alokasiPetugas')
                ->get();

            $currentSisaPagu = $newPagu;

            foreach ($periodes as $periode) {
                $periodeTotalHonor = $periode->alokasiPetugas->sum('total_honor');
                $currentSisaPagu = $currentSisaPagu - $periodeTotalHonor;

                $periode->update(['sisa_pagu' => $currentSisaPagu]);
            }

            ActivityLog::log(
                'Ubah Kegiatan',
                'kegiatan',
                "Berhasil mengubah data kegiatan: {$kegiatan->nama_kegiatan}, pagu diperbarui dan sisa pagu periode terkait dihitung ulang.",
                'success',
                [
                    'kegiatan_id' => $kegiatan->id,
                    'kode_kegiatan' => $kegiatan->kode_kegiatan,
                    'perubahan' => $logChanges,
                ]
            );

            return redirect()->route('kegiatan.index')
                ->with('success', 'Kegiatan dan sisa pagu periode berhasil diperbarui.');
        }

        // Normal update (no pagu change)
        $changedFieldsDesc = count($logChanges) > 0
            ? implode(', ', array_keys($logChanges))
            : 'tidak ada perubahan terdeteksi';

        ActivityLog::log(
            'Ubah Kegiatan',
            'kegiatan',
            "Berhasil mengubah data kegiatan: {$kegiatan->nama_kegiatan}. Field yang berubah: {$changedFieldsDesc}.",
            'success',
            [
                'kegiatan_id' => $kegiatan->id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'perubahan' => $logChanges,
            ]
        );

        return redirect()->route('kegiatan.index')
            ->with('success', 'Perubahan data kegiatan sudah berhasil disimpan.');
    }

    public function exportFrameSampelTemplate(Request $request): BinaryFileResponse
    {
        $metadata = $this->extractFrameSampelMetadata($request);
        $unitSampelList = $this->extractFrameSampelUnitSampel($request);

        return Excel::download(
            new FrameSampelDetailTemplateExport($metadata, $unitSampelList),
            'detail-frame-sampel-template.xlsx'
        );
    }

    public function importFrameSampelPreview(Request $request): JsonResponse
    {
        $metadata = $this->extractFrameSampelMetadata($request);
        $unitSampelList = $this->extractFrameSampelUnitSampel($request);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ], [
            'file.required' => 'File harus diupload.',
            'file.mimes' => 'File harus berupa Excel (.xlsx, .xls) atau CSV.',
        ]);

        $spreadsheet = IOFactory::load($validated['file']->getRealPath());
        $rows = $spreadsheet->getSheet(0)->toArray(null, true, true, false);

        $metadataCount = count($metadata);
        $unitSampelCount = max(count($unitSampelList), 1);
        $expectedColumnCount = ($metadataCount * 2) + $unitSampelCount;
        $importedRows = [];
        $errors = [];

        foreach (array_slice($rows, 1) as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2;
            $normalizedRow = array_slice(array_pad($row, $expectedColumnCount, null), 0, $expectedColumnCount);

            $identitasTambahan = [];
            $hasMetadataValue = false;

            foreach ($metadata as $metadataIndex => $column) {
                $codeKey = trim((string) $column['code']);
                $codeValue = trim((string) ($normalizedRow[$metadataIndex * 2] ?? ''));
                $labelValue = trim((string) ($normalizedRow[($metadataIndex * 2) + 1] ?? ''));

                if ($codeValue !== '') {
                    $identitasTambahan[$codeKey] = $codeValue;
                    $hasMetadataValue = true;
                }

                if ($labelValue !== '') {
                    $identitasTambahan[$codeKey.'_label'] = $labelValue;
                    $hasMetadataValue = true;
                }
            }

            // Parse per-unit-sampel counts
            $targetUnits = [];
            $hasAnyTarget = false;

            if (! empty($unitSampelList)) {
                foreach ($unitSampelList as $usIndex => $unitSampel) {
                    $colIndex = ($metadataCount * 2) + $usIndex;
                    $countStr = trim((string) ($normalizedRow[$colIndex] ?? ''));

                    if ($countStr !== '' && ctype_digit($countStr) && (int) $countStr >= 0) {
                        $targetUnits[(string) $unitSampel['id']] = $countStr;

                        if ((int) $countStr > 0) {
                            $hasAnyTarget = true;
                        }
                    }
                }
            } else {
                // Fallback: single generic count column
                $countStr = trim((string) ($normalizedRow[$expectedColumnCount - 1] ?? ''));

                if ($countStr !== '' && ctype_digit($countStr) && (int) $countStr > 0) {
                    $targetUnits['0'] = $countStr;
                    $hasAnyTarget = true;
                }
            }

            if (! $hasMetadataValue && ! $hasAnyTarget) {
                continue;
            }

            if (! $hasAnyTarget) {
                $nama = ! empty($unitSampelList) ? $unitSampelList[0]['nama'] : 'Sampel';
                $errors[] = "Baris {$rowNumber}: Jumlah {$nama} Dalam Frame harus berupa angka bulat minimal 1.";

                continue;
            }

            $importedRows[] = [
                'target_unit_sampel' => $targetUnits,
                'identitas_tambahan' => $identitasTambahan,
            ];
        }

        return response()->json([
            'rows' => $importedRows,
            'errors' => $errors,
            'summary' => [
                'total_rows' => max(count($rows) - 1, 0),
                'valid_rows' => count($importedRows),
                'error_count' => count($errors),
            ],
        ]);
    }

    /**
     * @param  array<int, array{tahapan:string,target_unit_sampel:array<string,int>,identitas_tambahan?:array<string,mixed>}>  $rows
     */
    private function syncKegiatanFrameSampelRows(
        Kegiatan $kegiatan,
        array $rows,
        ?array $frameSampelByTahapanOverride = null
    ): void {
        $normalizedRows = collect($rows)
            ->map(function (array $row): ?array {
                $tahapan = $row['tahapan'] ?? null;
                $targetUnits = isset($row['target_unit_sampel']) && is_array($row['target_unit_sampel'])
                    ? $row['target_unit_sampel']
                    : [];
                $totalTarget = array_sum($targetUnits);
                $identitasTambahan = isset($row['identitas_tambahan']) && is_array($row['identitas_tambahan'])
                    ? $row['identitas_tambahan']
                    : null;

                if (! in_array($tahapan, ['listing', 'pencacahan'], true) || $totalTarget < 1) {
                    return null;
                }

                // Normalize values to integers
                $normalizedTargets = array_map('intval', $targetUnits);

                return [
                    'tahapan' => $tahapan,
                    'target_unit_sampel' => $normalizedTargets,
                    'identitas_tambahan' => $identitasTambahan,
                ];
            })
            ->filter()
            ->values();

        $listingEnabled = $kegiatan->jenis_kegiatan !== 'sensus' && (bool) $kegiatan->has_listing_updating;
        if (! $listingEnabled) {
            $normalizedRows = $normalizedRows
                ->filter(fn (array $row) => $row['tahapan'] !== 'listing')
                ->values();
        }

        $frameSampelByTahapan = $frameSampelByTahapanOverride ?? [
            'listing' => $kegiatan->frame_sampel_listing_id,
            'pencacahan' => $kegiatan->frame_sampel_pencacahan_id,
        ];

        $missingFrameSampelByTahapan = $normalizedRows
            ->pluck('tahapan')
            ->unique()
            ->filter(fn (string $tahapan): bool => empty($frameSampelByTahapan[$tahapan]))
            ->values();

        if ($missingFrameSampelByTahapan->isNotEmpty()) {
            $messages = [];

            if ($missingFrameSampelByTahapan->contains('listing')) {
                $messages['frame_sampel_listing_id'] = 'Pilih Frame Sampel Listing sebelum menyimpan detail frame untuk tahapan listing.';
            }

            if ($missingFrameSampelByTahapan->contains('pencacahan')) {
                $messages['frame_sampel_pencacahan_id'] = 'Pilih Frame Sampel Pencacahan sebelum menyimpan detail frame untuk tahapan pencacahan.';
            }

            $messages['kegiatan_frame_sampel'] = 'Detail frame sampel belum bisa disimpan karena pilihan frame sampel per tahapan belum lengkap.';

            throw ValidationException::withMessages($messages);
        }

        $payload = $normalizedRows
            ->map(function (array $row) use ($frameSampelByTahapan): array {
                $frameSampelId = $frameSampelByTahapan[$row['tahapan']] ?? null;

                $identitasTambahan = $row['identitas_tambahan'] ?? null;
                $namaFrame = $this->resolveIdentitasString(
                    $identitasTambahan,
                    ['nama_frame', 'nama', 'frame', 'kdkec_label', 'kddes_label', 'kdsegmen_label']
                );

                return [
                    'frame_sampel_id' => $frameSampelId,
                    'tahapan' => $row['tahapan'],
                    'nama_frame' => $namaFrame,
                    'target_unit_sampel' => $row['target_unit_sampel'],
                    'kode_kecamatan' => $this->resolveIdentitasString(
                        $identitasTambahan,
                        ['kdkec', 'kode_kecamatan', 'kecamatan']
                    ),
                    'kode_desa' => $this->resolveIdentitasString(
                        $identitasTambahan,
                        ['kddes', 'kode_desa', 'desa', 'kelurahan']
                    ),
                    'kode_sls' => $this->resolveIdentitasString(
                        $identitasTambahan,
                        ['kdsls', 'kode_sls', 'sls']
                    ),
                    'kode_sub_sls' => $this->resolveIdentitasString(
                        $identitasTambahan,
                        ['kdsubsls', 'kd_sub_sls', 'kode_sub_sls', 'sub_sls']
                    ),
                    'kode_segmen' => $this->resolveIdentitasString(
                        $identitasTambahan,
                        ['kdsegmen', 'kode_segmen', 'segmen']
                    ),
                    'identitas_tambahan' => ! empty($identitasTambahan)
                        ? $identitasTambahan
                        : null,
                ];
            })
            ->values()
            ->all();

        $kegiatan->kegiatanFrameSampel()->delete();

        if (! empty($payload)) {
            $kegiatan->kegiatanFrameSampel()->createMany($payload);
        }
    }

    /**
     * @param  array<string,mixed>|null  $identitas
     * @param  array<int,string>  $candidateKeys
     */
    private function resolveIdentitasString(?array $identitas, array $candidateKeys): ?string
    {
        if (! is_array($identitas) || empty($identitas)) {
            return null;
        }

        foreach ($candidateKeys as $candidateKey) {
            foreach ($identitas as $actualKey => $actualValue) {
                if (! is_scalar($actualValue)) {
                    continue;
                }

                if (mb_strtolower((string) $actualKey) === mb_strtolower($candidateKey)) {
                    $value = trim((string) $actualValue);

                    return $value === '' ? null : $value;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, array{code:string,label:string,description:string}>
     */
    private function extractFrameSampelMetadata(Request $request): array
    {
        $rawMetadata = $request->input('metadata', []);

        if (is_string($rawMetadata)) {
            $decodedMetadata = json_decode($rawMetadata, true);
            $rawMetadata = is_array($decodedMetadata) ? $decodedMetadata : [];
        }

        $validator = Validator::make([
            'metadata' => $rawMetadata,
        ], [
            'metadata' => ['required', 'array', 'min:1'],
            'metadata.*.code' => ['required', 'string', 'max:100'],
            'metadata.*.label' => ['required', 'string', 'max:255'],
            'metadata.*.description' => ['required', 'string', 'max:255'],
        ], [
            'metadata.required' => 'Metadata frame sampel wajib disimpan terlebih dahulu.',
            'metadata.array' => 'Metadata frame sampel harus berupa daftar.',
            'metadata.min' => 'Metadata frame sampel minimal satu kolom.',
            'metadata.*.code.required' => 'Kode metadata wajib diisi.',
            'metadata.*.label.required' => 'Label metadata wajib diisi.',
            'metadata.*.description.required' => 'Deskripsi metadata wajib diisi.',
        ]);

        $validated = $validator->validate();

        $metadata = collect($validated['metadata'])
            ->map(fn (array $item) => [
                'code' => trim((string) $item['code']),
                'label' => trim((string) $item['label']),
                'description' => trim((string) $item['description']),
            ])
            ->values()
            ->all();

        $duplicateCodes = collect($metadata)
            ->groupBy(fn (array $item) => mb_strtolower($item['code']))
            ->filter(fn ($group) => $group->count() > 1)
            ->keys()
            ->all();

        if (! empty($duplicateCodes)) {
            abort(response()->json([
                'message' => 'Kode metadata frame sampel harus unik.',
                'errors' => [
                    'metadata' => ['Kode metadata frame sampel harus unik.'],
                ],
            ], 422));
        }

        return $metadata;
    }

    /**
     * Extract and validate the unit sampel list from the request (for frame sampel template/import).
     *
     * @return array<int, array{id:int,nama:string}>
     */
    private function extractFrameSampelUnitSampel(Request $request): array
    {
        $rawUnitSampel = $request->input('unit_sampel', []);

        if (is_string($rawUnitSampel)) {
            $decoded = json_decode($rawUnitSampel, true);
            $rawUnitSampel = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($rawUnitSampel)) {
            return [];
        }

        return collect($rawUnitSampel)
            ->filter(fn ($item) => is_array($item) && isset($item['id'], $item['nama']))
            ->map(fn ($item) => [
                'id' => (int) $item['id'],
                'nama' => trim((string) $item['nama']),
            ])
            ->values()
            ->all();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        // Authorization via policy
        $this->authorize('delete', $kegiatan);

        $isBelumDikirim = $kegiatan->status === 'draft';
        $hasPeriodeAlokasi = $kegiatan->periodeAlokasi()
            ->where('status', '!=', 'dihapus')
            ->exists();
        $hasNoPeriodeAlokasi = ! $hasPeriodeAlokasi;

        if (! $isBelumDikirim && ! $hasNoPeriodeAlokasi) {
            return back()->with('error', 'Kegiatan hanya dapat dihapus jika belum dikirim atau belum memiliki alokasi kegiatan.');
        }

        ActivityLog::log(
            'Hapus Kegiatan',
            'kegiatan',
            "Berhasil menghapus kegiatan: {$kegiatan->nama_kegiatan}",
            'success',
            ['kegiatan_id' => $kegiatan->id, 'kode_kegiatan' => $kegiatan->kode_kegiatan]
        );

        $kegiatan->delete();

        return redirect()->route('kegiatan.index')
            ->with('success', 'Data kegiatan sudah berhasil dihapus dari sistem.');
    }

    /**
     * Show form to manage rate honors for a kegiatan
     */
    public function manageRateHonor(Kegiatan $kegiatan): Response|RedirectResponse
    {
        // Check if kegiatan is approved
        if (! in_array($kegiatan->status, ['divalidasi', 'aktif'])) {
            return back()->with('error', 'Rate honor hanya bisa dikelola untuk kegiatan yang sudah divalidasi.');
        }

        // Load existing rate honors for this kegiatan
        $kegiatan->load(['rateHonors' => function ($query) {
            $query->orderBy('status_kepegawaian')
                ->orderBy('jenis_penugasan');
        }, 'rateHonors.satuan', 'rateHonors.satuanListing']);

        // Get all available satuan
        $satuans = Satuan::where('status', 'aktif')->get();

        return Inertia::render('Kegiatan/ManageRateHonor', [
            'kegiatan' => $kegiatan,
            'satuans' => $satuans,
        ]);
    }

    /**
     * Bulk update rate honors for a kegiatan
     */
    public function bulkUpdateRateHonor(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        // Check if kegiatan is approved
        if (! in_array($kegiatan->status, ['divalidasi', 'aktif'])) {
            return back()->with('error', 'Rate honor hanya bisa dikelola untuk kegiatan yang sudah divalidasi.');
        }
        $request->validate([
            'rate_honors' => ['required', 'array'],
            'rate_honors.*.status_kepegawaian' => ['required', 'in:organik,non_organik'],
            'rate_honors.*.jenis_penugasan' => ['required', 'in:pcl_ppl,pml,koseka,pengolahan,pengawas_pengolahan'],
            'rate_honors.*.rate' => ['required', 'numeric', 'min:0'],
            'rate_honors.*.satuan_id' => ['nullable', 'exists:satuan,id'],
            'rate_honors.*.satuan_listing_id' => ['nullable', 'exists:satuan,id'],
            'satuan_id' => ['nullable', 'exists:satuan,id'],
            'satuan_listing_id' => ['nullable', 'exists:satuan,id'],
            'satuan_pengolahan_pencacahan_id' => ['nullable', 'exists:satuan,id'],
            'satuan_pengolahan_listing_id' => ['nullable', 'exists:satuan,id'],
            'rate_honors.*.rate_listing' => ['nullable', 'numeric', 'min:0'],
            'kode_coa' => ['nullable', 'string', 'max:100'],
        ]);

        $isSensus = $kegiatan->jenis_kegiatan === 'sensus';
        $obSatuanId = $isSensus ? $this->resolveObSatuanId() : null;
        if ($isSensus && $obSatuanId === null) {
            return back()->withErrors([
                'satuan_id' => 'Satuan O-B (Orang/Bulan) belum tersedia. Hubungi admin untuk menambahkan satuan O-B.',
            ])->withInput();
        }

        $selectedSatuanId = $this->resolveSubmittedSatuanId($request, 'satuan_id');
        $selectedSatuanListingId = $this->resolveSubmittedSatuanId($request, 'satuan_listing_id');
        $selectedSatuanPengolahanPencacahanId = $this->resolveSubmittedSatuanId($request, 'satuan_pengolahan_pencacahan_id');
        $selectedSatuanPengolahanListingId = $this->resolveSubmittedSatuanId($request, 'satuan_pengolahan_listing_id');
        $hasPengolahanPenugasan = collect($request->input('rate_honors', []))
            ->pluck('jenis_penugasan')
            ->contains(fn ($jenisPenugasan) => in_array($jenisPenugasan, ['pengolahan', 'pengawas_pengolahan'], true));

        if (! $isSensus && $selectedSatuanId === null) {
            return back()->withErrors([
                'satuan_id' => 'Satuan pencacahan wajib dipilih untuk kegiatan survei.',
            ])->withInput();
        }

        if ($kegiatan->has_listing_updating && ! $isSensus && $selectedSatuanListingId === null) {
            return back()->withErrors([
                'satuan_listing_id' => 'Satuan listing/updating wajib dipilih untuk kegiatan survei.',
            ])->withInput();
        }

        if ($hasPengolahanPenugasan && ! $isSensus && $selectedSatuanPengolahanPencacahanId === null) {
            return back()->withErrors([
                'satuan_pengolahan_pencacahan_id' => 'Satuan pengolahan dokumen pencacahan wajib dipilih.',
            ])->withInput();
        }

        if (
            $hasPengolahanPenugasan
            && $kegiatan->has_listing_updating
            && ! $isSensus
            && $selectedSatuanPengolahanListingId === null
        ) {
            return back()->withErrors([
                'satuan_pengolahan_listing_id' => 'Satuan pengolahan dokumen listing/updating wajib dipilih.',
            ])->withInput();
        }

        $isFasihOnly = $this->isFasihOnly($kegiatan);

        // Update kode_coa di kegiatan
        $kegiatan->update([
            'kode_coa' => $request->kode_coa,
        ]);

        // Delete existing rate honors for this kegiatan
        RateHonor::where('kegiatan_id', $kegiatan->id)->delete();

        $createdRateCount = 0;

        // Create new rate honors
        foreach ($request->rate_honors as $rateHonorData) {
            if (
                $isFasihOnly &&
                in_array($rateHonorData['jenis_penugasan'], ['pengolahan', 'pengawas_pengolahan'], true)
            ) {
                continue;
            }

            $rateSatuanId = $isSensus
                ? $obSatuanId
                : $this->resolveRateHonorSatuanId(
                    $rateHonorData,
                    'satuan_id',
                    in_array($rateHonorData['jenis_penugasan'], ['pengolahan', 'pengawas_pengolahan'], true)
                        ? $selectedSatuanPengolahanPencacahanId
                        : $selectedSatuanId
                );
            $rateSatuanListingId = $isSensus
                ? $obSatuanId
                : $this->resolveRateHonorSatuanId(
                    $rateHonorData,
                    'satuan_listing_id',
                    in_array($rateHonorData['jenis_penugasan'], ['pengolahan', 'pengawas_pengolahan'], true)
                        ? $selectedSatuanPengolahanListingId
                        : $selectedSatuanListingId
                );

            // Generate posisi label
            $statusLabel = $rateHonorData['status_kepegawaian'] === 'organik'
                ? 'Organik (PNS/PPPK)'
                : 'Non-Organik';

            $penugasanLabels = [
                'pcl_ppl' => 'PCL/PPL',
                'pml' => 'PML',
                'koseka' => 'Koseka (Koordinator Sensus Kecamatan)',
                'pengolahan' => 'Pengolahan',
                'pengawas_pengolahan' => 'Pengawas Pengolahan',
            ];
            $penugasanLabel = $penugasanLabels[$rateHonorData['jenis_penugasan']] ?? $rateHonorData['jenis_penugasan'];

            $data = [
                'kegiatan_id' => $kegiatan->id,
                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                'posisi' => "{$kegiatan->nama_kegiatan} - {$statusLabel} - {$penugasanLabel}",
                'jenis_penugasan' => $rateHonorData['jenis_penugasan'],
                'status_kepegawaian' => $rateHonorData['status_kepegawaian'],
                'deskripsi' => "Rate honor untuk kegiatan {$kegiatan->kode_kegiatan}",
                'rate' => $rateHonorData['rate'],
                'tahun_berlaku' => $kegiatan->tahun_anggaran,
                'status' => 'aktif',
                'satuan_id' => $rateSatuanId,
            ];

            // Simpan rate_listing dan satuan_listing_id jika ada (untuk tahapan listing/updating)
            if (array_key_exists('rate_listing', $rateHonorData)) {
                $data['rate_listing'] = $rateHonorData['rate_listing'] ?? null;
                $data['satuan_listing_id'] = $rateSatuanListingId;
            }
            RateHonor::create($data);
            $createdRateCount++;
        }

        ActivityLog::log(
            'Kelola Rate Honor',
            'kegiatan',
            "Berhasil memperbarui rate honor untuk kegiatan: {$kegiatan->nama_kegiatan}, mengatur rate honor untuk {$createdRateCount} posisi.",
            'success',
            ['kegiatan_id' => $kegiatan->id, 'kode_kegiatan' => $kegiatan->kode_kegiatan]
        );

        return redirect()->route('kegiatan.show', $kegiatan->hashed_id)
            ->with('success', 'Rate honor berhasil disimpan.');
    }

    /**
     * Approve kegiatan (change status from diajukan to divalidasi)
     */
    public function approve(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        // Authorization check via policy
        $this->authorize('approve', $kegiatan);

        // Validate that kegiatan is in correct status
        if ($kegiatan->status !== 'diajukan') {
            return redirect()->back()
                ->with('error', 'Kegiatan dengan status '.$kegiatan->status.' tidak dapat disetujui.');
        }

        // Update status to divalidasi
        $kegiatan->update([
            'status' => 'divalidasi',
            'tanggal_validasi' => now(),
        ]);

        ActivityLog::log(
            'Setujui Kegiatan',
            'kegiatan',
            "Berhasil menyetujui kegiatan: {$kegiatan->nama_kegiatan}",
            'success',
            ['kegiatan_id' => $kegiatan->id, 'kode_kegiatan' => $kegiatan->kode_kegiatan, 'status' => 'divalidasi']
        );

        return redirect()->back()
            ->with('success', 'Kegiatan berhasil disetujui dan divalidasi.');
    }

    /**
     * Reject kegiatan (change status back to draft with notes)
     */
    public function reject(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        // Authorization check via policy
        $this->authorize('reject', $kegiatan);

        // Validate request
        $request->validate([
            'catatan' => 'required|string|max:1000',
        ]);

        // Validate that kegiatan is in correct status
        if ($kegiatan->status !== 'diajukan') {
            return redirect()->back()
                ->with('error', 'Kegiatan dengan status '.$kegiatan->status.' tidak dapat ditolak.');
        }

        // Update status back to draft
        $kegiatan->update([
            'status' => 'draft',
            'catatan' => $request->catatan,
        ]);

        ActivityLog::log(
            'Tolak Kegiatan',
            'kegiatan',
            "Kegiatan ditolak: {$kegiatan->nama_kegiatan} (Kode: {$kegiatan->kode_kegiatan}). Catatan: {$request->catatan}",
            'warning',
            ['kegiatan_id' => $kegiatan->id, 'kode_kegiatan' => $kegiatan->kode_kegiatan, 'catatan' => $request->catatan]
        );

        return redirect()->back()
            ->with('success', 'Kegiatan ditolak dan dikembalikan ke status draft.');
    }

    /**
     * Submit kegiatan for approval (change status from draft to diajukan)
     */
    public function submit(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        // Only the ketua tim or admin/operator can submit
        $effectiveUser = effectiveUser($request);
        if (
            ! in_array($effectiveUser->active_role, ['admin', 'operator']) &&
            $kegiatan->ketua_tim_user_id !== $effectiveUser->id
        ) {
            abort(403, 'Anda tidak memiliki akses untuk mengajukan kegiatan ini.');
        }

        // Validate that kegiatan is draft
        if ($kegiatan->status !== 'draft') {
            return redirect()->back()
                ->with('error', 'Hanya kegiatan dengan status draft yang dapat diajukan.');
        }

        // Update status to diajukan and clear any previous rejection notes
        $kegiatan->update([
            'status' => 'diajukan',
            'catatan' => null,
        ]);

        ActivityLog::log(
            'Ajukan Kegiatan',
            'kegiatan',
            "Berhasil mengajukan kegiatan untuk persetujuan: {$kegiatan->nama_kegiatan}",
            'success',
            ['kegiatan_id' => $kegiatan->id, 'kode_kegiatan' => $kegiatan->kode_kegiatan, 'status' => 'diajukan']
        );

        return redirect()->back()
            ->with('success', 'Kegiatan berhasil diajukan untuk persetujuan.');
    }

    /**
     * Build a snapshot of key kegiatan fields for activity log comparisons.
     *
     * @return array<string, mixed>
     */
    private function buildKegiatanSnapshot(Kegiatan $kegiatan): array
    {
        return [
            'nama_kegiatan' => $kegiatan->nama_kegiatan,
            'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
            'tahun_anggaran' => (int) $kegiatan->tahun_anggaran,
            'tanggal_mulai' => $kegiatan->tanggal_mulai?->format('d-m-Y'),
            'tanggal_selesai' => $kegiatan->tanggal_selesai?->format('d-m-Y'),
            'pagu_pencacahan' => (float) ($kegiatan->pagu_pencacahan ?? 0),
            'pagu_listing' => (float) ($kegiatan->pagu_listing ?? 0),
            'has_listing_updating' => (bool) $kegiatan->has_listing_updating,
            'metode_pendataan_pencacahan' => $kegiatan->metode_pendataan_pencacahan,
            'metode_pendataan_listing' => $kegiatan->metode_pendataan_listing,
            'metode_pelatihan' => $kegiatan->metode_pelatihan,
            'bulan_pelatihan' => $kegiatan->bulan_pelatihan,
            'ketua_tim_user_id' => $kegiatan->ketua_tim_user_id,
            'pj_lainnya_id' => $kegiatan->pj_lainnya_id,
            'status' => $kegiatan->status,
        ];
    }

    /**
     * Compute which fields changed between two kegiatan snapshots.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{before: mixed, after: mixed}>
     */
    private function computeKegiatanChanges(array $before, array $after): array
    {
        $changes = [];
        foreach ($before as $field => $oldValue) {
            if (array_key_exists($field, $after) && $oldValue !== $after[$field]) {
                $changes[$field] = ['before' => $oldValue, 'after' => $after[$field]];
            }
        }

        return $changes;
    }

    private function resolveObSatuanId(): ?int
    {
        $obSatuan = Satuan::query()
            ->where('status', 'aktif')
            ->where(function ($query) {
                $query->whereRaw('LOWER(kode) = ?', ['ob'])
                    ->orWhereRaw('LOWER(kode) = ?', ['o-b'])
                    ->orWhereRaw('LOWER(REPLACE(REPLACE(kode, "-", ""), " ", "")) = ?', ['ob'])
                    ->orWhereRaw('LOWER(nama) = ?', ['ob'])
                    ->orWhereRaw('LOWER(REPLACE(nama, "-", "")) = ?', ['orangbulan'])
                    ->orWhereRaw('LOWER(REPLACE(nama, " ", "")) = ?', ['orangbulan'])
                    ->orWhereRaw('LOWER(REPLACE(REPLACE(nama, "-", ""), " ", "")) = ?', ['ob']);
            })
            ->first();

        return $obSatuan?->id;
    }

    private function resolveSubmittedSatuanId(Request $request, string $field): ?int
    {
        $directValue = $request->input($field);
        if (filled($directValue)) {
            return (int) $directValue;
        }

        $rowValue = collect($request->input('rate_honors', []))
            ->pluck($field)
            ->first(fn ($value) => filled($value));

        return filled($rowValue) ? (int) $rowValue : null;
    }

    /**
     * @param  array<string, mixed>  $rateHonorData
     */
    private function resolveRateHonorSatuanId(array $rateHonorData, string $field, ?int $fallback): ?int
    {
        $value = $rateHonorData[$field] ?? null;

        return filled($value) ? (int) $value : $fallback;
    }

    private function isFasihOnly(Kegiatan $kegiatan): bool
    {
        return $kegiatan->usesFasihPendataan();
    }
}
