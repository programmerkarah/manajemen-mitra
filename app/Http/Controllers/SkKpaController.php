<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Models\ActivityLog;
use App\Models\DasarHukum;
use App\Models\Dipa;
use App\Models\Kegiatan;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\RateHonor;
use App\Models\SkKpa;
use App\Services\ActiveYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Vinkla\Hashids\Facades\Hashids;

class SkKpaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(FilterRequest $request): Response
    {
        $validated = $request->validated();
        $activeYear = ActiveYearService::get();

        // Get all kegiatan on active year
        $query = Kegiatan::query()
            ->select('kegiatan.*') // Only select needed columns
            ->with([
                'ketuaTim:id,name',
                'skKpa:id,kegiatan_id,nomor_sk,tanggal_sk,status,file_path,signed_file_path,created_at,bulan,tahun,revision_acknowledged_at',
                'periodeAlokasi' => function ($q) use ($activeYear) {
                    $q->where('tahun', $activeYear)
                        ->whereIn('status', ['dikirim', 'perubahan', 'direvisi'])
                        ->with('alokasiPetugas:id,periode_alokasi_id,petugas_id')
                        ->orderBy('bulan');
                },
            ])
            ->withCount(['skKpa' => function ($q) {
                $q->select(DB::raw('count(*)'));
            }])
            ->whereHas('periodeAlokasi', function ($q) use ($activeYear) {
                $q->where('tahun', $activeYear)
                    ->whereIn('status', ['dikirim', 'perubahan', 'direvisi'])
                    ->whereHas('alokasiPetugas');
            })
            ->where('tahun_anggaran', $activeYear);

        // Search filter
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama_kegiatan', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        // Filter by jenis kegiatan
        if (! empty($validated['jenis_kegiatan'])) {
            $query->where('jenis_kegiatan', $validated['jenis_kegiatan']);
        }

        // Load ALL data for client-side filtering, sorting, and pagination
        $kegiatan = $query->latest()->get();

        // Transform data to include SK status
        // Note: status_sk and revision_number are calculated from skKpa relationship
        // They are not stored in kegiatan table
        $transformedData = $kegiatan->map(function ($keg) {
            $skCount = $keg->sk_kpa_count ?? 0;
            $latestSk = $keg->skKpa->sortByDesc('created_at')->first();

            // Check if there are personnel changes AFTER the latest SK (for SK Perubahan eligibility)
            $hasPersonnelChanges = $this->checkPersonnelChanges($keg->id, $latestSk);

            // Determine SK status label
            if ($skCount === 0) {
                $skStatus = 'Belum Dibuat';
                $skStatusType = 'not_created';
            } elseif ($skCount === 1) {
                $skStatus = 'Sudah Dibuat';
                $skStatusType = 'created';
            } else {
                $skStatus = 'Perubahan ke-'.($skCount - 1);
                $skStatusType = 'revision';
            }

            return [
                'id' => $keg->id,
                'hashed_id' => $keg->hashed_id,
                'kode_kegiatan' => $keg->kode_kegiatan,
                'nama_kegiatan' => $keg->nama_kegiatan,
                'jenis_kegiatan' => $keg->jenis_kegiatan,
                'tahun_anggaran' => $keg->tahun_anggaran,
                'ketua_tim' => $keg->ketuaTim?->name ?? '-',
                'sk_status' => $skStatus,
                'sk_status_type' => $skStatusType,
                'sk_count' => $skCount,
                'has_personnel_changes' => $hasPersonnelChanges,
                'latest_sk' => $latestSk ? [
                    'id' => $latestSk->id,
                    'hashed_id' => $latestSk->hashed_id,
                    'nomor_sk' => $latestSk->nomor_sk,
                    'tanggal_sk' => $latestSk->tanggal_sk,
                    'tahun' => $latestSk->tahun,
                    'status' => $latestSk->status,
                    'file_path' => $latestSk->file_path,
                    'signed_file_path' => $latestSk->signed_file_path,
                    'revision_acknowledged_at' => $latestSk->revision_acknowledged_at,
                ] : null,
            ];
        });

        $summary = [
            'total_kegiatan_aktif' => $transformedData->count(),
            'total_sk_belum_dibuat' => $transformedData->where('sk_count', 0)->count(),
            'total_sk_digenerate' => $transformedData->where('sk_count', '>', 0)->count(),
            'total_sk_disahkan' => $transformedData->filter(fn ($item) => ! empty($item['latest_sk']['signed_file_path'] ?? null))->count(),
        ];

        // Encrypt sensitive data
        $encryptedData = encryptData($transformedData);
        $totalData = $transformedData->count();

        $response = Inertia::render('SkKpa/Index', [
            'kegiatan' => [
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
                'encrypted' => encryptFilters($validated),
                'decrypted' => $validated,
            ],
            'summary' => $summary,
        ]);

        return $response;
    }

    /**
     * List all SK for a specific kegiatan
     */
    public function listByKegiatan(string $kegiatanHashedId): Response
    {
        $kegiatanId = Hashids::decode($kegiatanHashedId)[0] ?? null;

        if (! $kegiatanId) {
            abort(404);
        }

        $kegiatan = Kegiatan::with([
            'skKpa' => function ($q) {
                $q->with('createdBy:id,name')
                    ->orderBy('created_at', 'desc');
            },
        ])->findOrFail($kegiatanId);

        return Inertia::render('SkKpa/List', [
            'kegiatan' => [
                'id' => $kegiatan->id,
                'hashed_id' => $kegiatan->hashed_id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                'tahun_anggaran' => $kegiatan->tahun_anggaran,
            ],
            'sk_list' => $kegiatan->skKpa->map(function ($sk, $index) use ($kegiatan) {
                return [
                    'id' => $sk->id,
                    'hashed_id' => $sk->hashed_id,
                    'nomor_sk' => $sk->nomor_sk,
                    'tanggal_sk' => $sk->tanggal_sk,
                    'nama_kpa' => $sk->nama_kpa,
                    'perihal' => $sk->perihal,
                    'status' => $sk->status,
                    'file_path' => $sk->file_path,
                    'created_by' => $sk->createdBy?->name ?? '-',
                    'created_at' => $sk->created_at,
                    'revision_number' => $kegiatan->skKpa->count() - $index,
                ];
            }),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(string $kegiatanHashedId): Response|RedirectResponse
    {
        $kegiatanId = Hashids::decode($kegiatanHashedId)[0] ?? null;

        if (! $kegiatanId) {
            abort(404);
        }

        $kegiatan = Kegiatan::findOrFail($kegiatanId);

        // If a latest SK exists, check if revision is needed or already acknowledged
        $latestSk = SkKpa::where('kegiatan_id', $kegiatanId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestSk) {
            // Block if SK was acknowledged as not needing revision
            if ($latestSk->revision_acknowledged_at !== null) {
                return redirect()->route('sk-kpa.index')
                    ->with('error', 'SK ini sudah ditandai tidak perlu revisi.');
            }

            // Block if there are no real personnel changes
            if (! $this->checkPersonnelChanges($kegiatanId, $latestSk)) {
                return redirect()->route('sk-kpa.index')
                    ->with('error', 'Tidak ada perubahan personel yang memerlukan SK Perubahan.');
            }
        }

        // Check if there are any approved periodes (dikirim, perubahan, or direvisi)
        $hasApprovedPeriodes = $kegiatan->periodeAlokasi()
            ->whereIn('status', ['dikirim', 'perubahan', 'direvisi'])
            ->exists();

        if (! $hasApprovedPeriodes) {
            return redirect()->route('sk-kpa.index')
                ->with('error', 'Belum ada periode yang dikirim untuk kegiatan ini.');
        }

        // Get active dasar hukum
        $dasarHukum = DasarHukum::where('status', 'aktif')
            ->orderBy('tahun', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'kategori' => $item->kategori,
                    'instansi' => $item->instansi,
                    'nomor' => $item->nomor,
                    'tentang' => $item->tentang,
                    'tahun' => $item->tahun,
                    'status' => $item->status,
                ];
            });

        // Get personnel change information for SK Perubahan
        $personnelChangeInfo = $this->getPersonnelChangeInfo($kegiatanId);

        // Get the earliest eligible periode for first-SK context info
        $firstPeriode = $this->resolveSkTargetPeriode($kegiatanId, false);
        $firstPeriodeInfo = $firstPeriode ? [
            'bulan' => $firstPeriode->bulan,
            'tahun' => $firstPeriode->tahun,
        ] : null;

        return Inertia::render('SkKpa/Create', [
            'kegiatan' => [
                'id' => $kegiatan->id,
                'hashed_id' => $kegiatan->hashed_id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                'tahun_anggaran' => $kegiatan->tahun_anggaran,
                'first_periode' => $firstPeriodeInfo,
            ],
            'dasarHukumList' => $dasarHukum,
            'personnelChangeInfo' => $personnelChangeInfo,
            'oldInput' => old(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $skKpaHashedId): Response
    {
        $skKpaId = Hashids::decode($skKpaHashedId)[0] ?? null;

        if (! $skKpaId) {
            abort(404);
        }

        $skKpa = SkKpa::with(['kegiatan', 'createdBy:id,name', 'signedBy:id,name'])
            ->findOrFail($skKpaId);

        // Get all SK for this kegiatan for history
        $allSk = SkKpa::where('kegiatan_id', $skKpa->kegiatan_id)
            ->with(['createdBy:id,name', 'signedBy:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($sk, $index) use ($skKpa) {
                return [
                    'id' => $sk->id,
                    'hashed_id' => $sk->hashed_id,
                    'nomor_sk' => $sk->nomor_sk,
                    'tanggal_sk' => $sk->tanggal_sk->format('d-m-Y'),
                    'nama_kpa' => $sk->nama_kpa,
                    'perihal' => $sk->perihal,
                    'status' => $sk->status,
                    'file_path' => $sk->file_path,
                    'signed_file_path' => $sk->signed_file_path,
                    'is_signed' => $sk->is_signed,
                    'signed_at' => $sk->signed_at?->format('d-m-Y H:i'),
                    'signed_by' => $sk->signedBy?->name,
                    'created_by' => $sk->createdBy?->name ?? '-',
                    'created_at' => $sk->created_at->format('d-m-Y H:i'),
                    'is_current' => $sk->id === $skKpa->id,
                ];
            });

        // Parse dasar hukum JSON
        $dasarHukumIds = json_decode($skKpa->dasar_hukum, true) ?? [];
        $dasarHukum = DasarHukum::whereIn('id', $dasarHukumIds)
            ->get()
            ->map(function ($dh) {
                $kategoriLabel = match ($dh->kategori) {
                    'undang_undang' => 'Undang-Undang',
                    'peraturan_pemerintah' => 'Peraturan Pemerintah',
                    'peraturan_presiden' => 'Peraturan Presiden',
                    'peraturan_menteri_badan' => 'Peraturan '.($dh->instansi && stripos($dh->instansi, 'badan') === 0 ? 'Badan' : 'Menteri').' '.$dh->instansi,
                    'keputusan_menteri_kepala_badan' => 'Keputusan '.($dh->instansi && stripos($dh->instansi, 'badan') === 0 ? 'Kepala Badan' : 'Menteri').' '.$dh->instansi,
                    default => $dh->kategori,
                };

                return $kategoriLabel.' Nomor '.$dh->nomor.' Tahun '.$dh->tahun.' tentang '.$dh->tentang;
            });

        return Inertia::render('SkKpa/Show', [
            'skKpa' => [
                'id' => $skKpa->id,
                'hashed_id' => $skKpa->hashed_id,
                'nomor_sk' => $skKpa->nomor_sk,
                'tanggal_sk' => $skKpa->tanggal_sk->format('d-m-Y'),
                'nama_kpa' => $skKpa->nama_kpa,
                'perihal' => $skKpa->perihal,
                'bulan' => $skKpa->bulan,
                'tahun' => $skKpa->tahun,
                'status' => $skKpa->status,
                'file_path' => $skKpa->file_path,
                'signed_file_path' => $skKpa->signed_file_path,
                'is_signed' => $skKpa->is_signed,
                'signed_at' => $skKpa->signed_at?->format('d-m-Y H:i'),
                'signed_by' => $skKpa->signedBy?->name,
                'created_by' => $skKpa->createdBy?->name ?? '-',
                'created_at' => $skKpa->created_at->format('d-m-Y H:i'),
                'dasar_hukum' => $dasarHukum->toArray(),
            ],
            'kegiatan' => [
                'id' => $skKpa->kegiatan->id,
                'hashed_id' => $skKpa->kegiatan->hashed_id,
                'kode_kegiatan' => $skKpa->kegiatan->kode_kegiatan,
                'nama_kegiatan' => $skKpa->kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $skKpa->kegiatan->jenis_kegiatan,
                'tahun_anggaran' => $skKpa->kegiatan->tahun_anggaran,
            ],
            'sk_history' => $allSk->values()->toArray(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SkKpa $skKpa)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SkKpa $skKpa)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SkKpa $skKpa)
    {
        //
    }

    /**
     * Acknowledge that the current SK does not need revision despite later personnel changes.
     * This dismisses the "SK perlu perubahan" warning for this SK.
     */
    public function acknowledgeRevision(string $skKpaHashedId): RedirectResponse
    {
        $skKpaId = Hashids::decode($skKpaHashedId)[0] ?? null;

        if (! $skKpaId) {
            abort(404);
        }

        $skKpa = SkKpa::findOrFail($skKpaId);

        $user = Auth::user();

        $skKpa->update([
            'revision_acknowledged_at' => now(),
            'revision_acknowledged_by' => $user?->getAuthIdentifier(),
        ]);

        return back()->with('success', 'SK telah ditandai tidak perlu revisi.');
    }

    /**
    public function uploadSigned(Request $request, string $skKpaHashedId): RedirectResponse
    {
        $skKpaId = Hashids::decode($skKpaHashedId)[0] ?? null;

        if (! $skKpaId) {
            abort(404);
        }

        $skKpa = SkKpa::findOrFail($skKpaId);

        $validated = $request->validate([
            'signed_file' => ['required', 'file', 'mimes:pdf', 'max:10240'], // 10MB
        ]);

        // Delete old signed file if exists
        if ($skKpa->signed_file_path && file_exists(public_path($skKpa->signed_file_path))) {
            unlink(public_path($skKpa->signed_file_path));
        }

        // Upload new signed file
        $file = $request->file('signed_file');
        $sanitizedNamaKegiatan = preg_replace('/[\/\\\:\*\?"<>\|]/', '', $skKpa->kegiatan->nama_kegiatan);
        $filename = 'SK_'.str_replace('/', '-', $skKpa->nomor_sk).'_'.$sanitizedNamaKegiatan.'_'.now()->format('YmdHis').'_signed.pdf';

        // Ensure sk directory exists
        $skDirectory = public_path('sk');
        if (! file_exists($skDirectory)) {
            mkdir($skDirectory, 0755, true);
        }

        $filePath = 'sk/'.$filename;
        $file->move(public_path('sk'), $filename);

        // Update SK record
        $skKpa->update([
            'signed_file_path' => $filePath,
            'is_signed' => true,
            'signed_at' => now(),
            'signed_by' => Auth::id(),
        ]);

        ActivityLog::log(
            'Upload SK Bertanda Tangan',
            'sk_kpa',
            "Berhasil upload SK bertanda tangan untuk: {$skKpa->nomor_sk} - {$skKpa->kegiatan->nama_kegiatan}",
            'success',
            ['sk_kpa_id' => $skKpa->id, 'nomor_sk' => $skKpa->nomor_sk, 'file_path' => $filePath]
        );

        return redirect()->route('sk-kpa.show', ['skKpa' => $skKpa->hashed_id])
            ->with('success', 'SK yang sudah ditandatangani berhasil diupload.');
    }

    /**
     * Preview SK PDF (tidak save ke database)
     */
    public function previewSk(Request $request, string $kegiatanHashedId)
    {
        $kegiatanId = Hashids::decode($kegiatanHashedId)[0] ?? null;

        if (! $kegiatanId) {
            abort(404);
        }

        $validated = $request->validate([
            'nomor_sk' => ['required', 'string', 'max:255'],
            'tanggal_sk' => ['required', 'date'],
            'dasar_hukum_ids' => ['required', 'array', 'min:1'],
            'dasar_hukum_ids.*' => ['required', 'integer', 'exists:dasar_hukum,id'],
        ]);

        // Get tahun from tanggal_sk
        $tahunSk = Carbon::parse($validated['tanggal_sk'])->format('Y');

        // Check if nomor_sk already exists for this year (for preview, just warn)
        $existingSkWithSameNumber = SkKpa::where('nomor_sk', $validated['nomor_sk'])
            ->where('tahun', $tahunSk)
            ->exists();

        if ($existingSkWithSameNumber) {
            return back()
                ->withInput()
                ->withErrors(['nomor_sk' => 'Nomor SK "'.$validated['nomor_sk'].'" sudah digunakan untuk tahun '.$tahunSk.'. Silakan gunakan nomor SK yang berbeda.']);
        }

        $kegiatan = Kegiatan::findOrFail($kegiatanId);

        // Get active Kepala BPS and DIPA from database
        $penandatangan = Penandatangan::active()->kepala()->firstOrFail();
        $dipa = Dipa::active()->firstOrFail();

        // Get dasar hukum
        $dasarHukum = DasarHukum::whereIn('id', $validated['dasar_hukum_ids'])
            ->get()
            ->sortBy(function ($item) {
                // Define category order: UU, PP, Perpres, Perka/Perban, Keputusan
                $categoryOrder = [
                    'undang_undang' => 1,
                    'peraturan_pemerintah' => 2,
                    'peraturan_presiden' => 3,
                    'peraturan_menteri_badan' => 4,
                    'keputusan_menteri_kepala_badan' => 5,
                ];

                // Sort by category order first, then by year (oldest to newest)
                return [$categoryOrder[$item->kategori] ?? 99, $item->tahun];
            })
            ->values()
            ->map(function ($item) {
                $kategoriLabel = match ($item->kategori) {
                    'undang_undang' => 'Undang-Undang',
                    'peraturan_pemerintah' => 'Peraturan Pemerintah',
                    'peraturan_presiden' => 'Peraturan Presiden',
                    'peraturan_menteri_badan' => 'Peraturan '.($item->instansi && stripos($item->instansi, 'badan') === 0 ? 'Badan' : 'Menteri').' '.$item->instansi,
                    'keputusan_menteri_kepala_badan' => 'Keputusan '.($item->instansi && stripos($item->instansi, 'badan') === 0 ? 'Kepala Badan' : 'Menteri').' '.$item->instansi,
                    default => $item->kategori,
                };

                // Build nama lengkap
                $namaLengkap = $kategoriLabel.' Nomor '.$item->nomor.' Tahun '.$item->tahun;

                return (object) [
                    'kategori' => $kategoriLabel,
                    'nomor' => $item->nomor,
                    'tentang' => $item->tentang,
                    'tahun' => $item->tahun,
                    'nama_lengkap' => $namaLengkap,
                    'lembaran' => $item->lembaran,
                ];
            });

        // Check if this is SK Perubahan (ada SK sebelumnya) atau SK pertama
        $allExistingSk = SkKpa::where('kegiatan_id', $kegiatanId)
            ->orderBy('created_at', 'asc')
            ->get();

        $existingSk = $allExistingSk->first();
        $latestSk = $allExistingSk->last();
        $revisionNumber = $allExistingSk->count(); // 0 = SK pertama, 1 = perubahan pertama, 2 = perubahan kedua, dst
        $firstSkNumber = $existingSk ? $existingSk->nomor_sk : null;
        $firstSkYear = $existingSk ? $existingSk->tahun : null;

        // Get periode for petugas data
        // Jika SK Perubahan, ambil periode terbaru (latest)
        // Jika SK pertama, ambil periode pertama
        $periode = $this->resolveSkTargetPeriode($kegiatan->id, $existingSk !== null);

        if (! $periode) {
            abort(404);
        }

        // Get previous SK petugas list for comparison (if this is a revision)
        $deletedPetugas = [];
        $addedPetugas = [];
        $allCurrentPetugas = [];

        if ($revisionNumber > 0 && $existingSk) {
            // Get the previous periode (not the current one)
            $previousPeriode = $this->resolvePreviousSkComparisonPeriode(
                $kegiatan->id,
                $periode->id,
            );

            if ($previousPeriode) {
                $previousPetugasList = $previousPeriode->alokasiPetugas->pluck('petugas.nama', 'petugas_id')->toArray();
                $currentPetugasList = $periode->alokasiPetugas->pluck('petugas.nama', 'petugas_id')->toArray();

                // Find deleted petugas (in previous but not in current)
                $deletedPetugasIds = array_diff(array_keys($previousPetugasList), array_keys($currentPetugasList));
                $deletedPetugas = array_values(array_intersect_key($previousPetugasList, array_flip($deletedPetugasIds)));

                // Find added petugas (in current but not in previous)
                $addedPetugasIds = array_diff(array_keys($currentPetugasList), array_keys($previousPetugasList));
                $addedPetugas = array_values(array_intersect_key($currentPetugasList, array_flip($addedPetugasIds)));

                // All current petugas names for final list
                $allCurrentPetugas = array_values($currentPetugasList);
            }
        }

        // Parse alokasi petugas with rates
        $alokasiList = $periode->alokasiPetugas->map(function ($alokasi) use ($kegiatan) {
            // Make sure petugas relationship is loaded
            if (! isset($alokasi->petugas)) {
                return null;
            }

            // Get all rate honors for this petugas based on their peran
            $rateHonors = RateHonor::where('kegiatan_id', $kegiatan->id)
                ->where('jenis_penugasan', $alokasi->peran)
                ->where('status_kepegawaian', $alokasi->status_kepegawaian ?? ($alokasi->petugas->jenis_petugas === 'organik' ? 'organik' : 'non_organik'))
                ->with(['satuan', 'satuanListing'])
                ->get();

            $roles = [];
            foreach ($rateHonors as $rateHonor) {
                // Add listing rate first if exists
                if ($rateHonor->rate_listing !== null && $rateHonor->satuanListing) {
                    $biayaSatuanListing = number_format($rateHonor->rate_listing, 0, ',', '.');
                    $satuanListing = $rateHonor->satuanListing->nama ?? '';

                    $roles[] = (object) [
                        'peran' => $this->getPeranLabel($alokasi->peran, true),
                        'biaya_satuan' => 'Rp. '."{$biayaSatuanListing},-".' / '."{$satuanListing}",
                    ];
                }

                // Add regular rate
                if ($rateHonor->rate !== null && $rateHonor->satuan) {
                    $biayaSatuan = number_format($rateHonor->rate, 0, ',', '.');
                    $satuan = $rateHonor->satuan->kode ?? '';

                    $roles[] = (object) [
                        'peran' => $this->getPeranLabel($alokasi->peran, false),
                        'biaya_satuan' => 'Rp. '."{$biayaSatuan},-".' / '."{$satuan}",
                    ];
                }
            }

            return (object) [
                'nama' => $alokasi->petugas->nama,
                'nip' => $alokasi->petugas->nik ?? '-',
                'golongan' => $alokasi->petugas->golongan ?? '-',
                'jabatan' => $alokasi->petugas->jabatan ?? '-',
                'roles' => $roles,
            ];
        })->filter(); // Remove null values

        $data = [
            'kegiatan' => $kegiatan,
            'periode' => $periode,
            'nomorSk' => $validated['nomor_sk'],
            'tanggalSk' => Carbon::parse($validated['tanggal_sk'])->format('d-m-Y'),
            'tahunSk' => Carbon::parse($validated['tanggal_sk'])->format('Y'),
            'kategoriKeputusan' => 'KEPUTUSAN',
            'kepalaBps' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'dipa' => $dipa->nomor_dipa,
            'tanggalDipa' => $dipa->tanggal_dipa->format('d-m-Y'),
            'dasarHukum' => $dasarHukum,
            'alokasiList' => $alokasiList,
            'revisionNumber' => $revisionNumber,
            'revisionSkNumber' => $latestSk ? $latestSk->nomor_sk : null,
            'revisionSkYear' => $latestSk ? $latestSk->tahun : null,
            'firstSkNumber' => $firstSkNumber,
            'firstSkYear' => $firstSkYear,
            'deletedPetugas' => $deletedPetugas,
            'addedPetugas' => $addedPetugas,
            'allCurrentPetugas' => $allCurrentPetugas,
        ];

        // Choose template: use perubahan template when there are previous SKs (revisionNumber > 0)
        $view = ($revisionNumber > 0 && $validated['nomor_sk'] !== $firstSkNumber) ? 'sk-petugas-perubahan' : 'sk-petugas';

        // Generate and stream PDF directly (tidak save)
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait');
        $sanitizedNamaKegiatan = preg_replace('/[^A-Za-z0-9_\-]/', '_', $kegiatan->nama_kegiatan);
        $filename = 'Preview_SK_'.$sanitizedNamaKegiatan.'.pdf';

        // Set PDF title metadata
        $pdf->getDomPDF()->set_option('pdfTitle', $filename);

        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }
        $tempFile = $tempPath.'/sk_preview_'.time().'_'.uniqid().'.pdf';
        file_put_contents($tempFile, $pdf->output());

        return response()->file($tempFile, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Transfer-Encoding' => 'binary',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generate SK PDF
     */
    public function generateSk(Request $request, string $kegiatanHashedId)
    {
        $kegiatanId = Hashids::decode($kegiatanHashedId)[0] ?? null;

        if (! $kegiatanId) {
            abort(404);
        }

        $validated = $request->validate([
            'nomor_sk' => ['required', 'string', 'max:255'],
            'tanggal_sk' => ['required', 'date'],
            'dasar_hukum_ids' => ['required', 'array', 'min:1'],
            'dasar_hukum_ids.*' => ['required', 'integer', 'exists:dasar_hukum,id'],
        ]);

        // Get tahun from tanggal_sk
        $tahunSk = Carbon::parse($validated['tanggal_sk'])->format('Y');

        // Check if nomor_sk already exists for this year
        $existingSkWithSameNumber = SkKpa::where('nomor_sk', $validated['nomor_sk'])
            ->where('tahun', $tahunSk)
            ->exists();

        if ($existingSkWithSameNumber) {
            return redirect()->route('sk-kpa.create-for-kegiatan', ['kegiatanHashedId' => $kegiatanHashedId])
                ->withInput()
                ->with('error', 'Nomor SK "'.$validated['nomor_sk'].'" sudah digunakan untuk tahun '.$tahunSk.'. Silakan gunakan nomor SK yang berbeda.');
        }

        $kegiatan = Kegiatan::findOrFail($kegiatanId);

        // Get active Kepala BPS and DIPA from database
        $penandatangan = Penandatangan::active()->kepala()->firstOrFail();
        $dipa = Dipa::active()->firstOrFail();

        // Get dasar hukum
        $dasarHukum = DasarHukum::whereIn('id', $validated['dasar_hukum_ids'])
            ->get()
            ->sortBy(function ($item) {
                // Define category order: UU, PP, Perpres, Perka/Perban, Keputusan
                $categoryOrder = [
                    'undang_undang' => 1,
                    'peraturan_pemerintah' => 2,
                    'peraturan_presiden' => 3,
                    'peraturan_menteri_badan' => 4,
                    'keputusan_menteri_kepala_badan' => 5,
                ];

                // Sort by category order first, then by year (oldest to newest)
                return [$categoryOrder[$item->kategori] ?? 99, $item->tahun];
            })
            ->values()
            ->map(function ($item) {
                $kategoriLabel = match ($item->kategori) {
                    'undang_undang' => 'Undang-Undang',
                    'peraturan_pemerintah' => 'Peraturan Pemerintah',
                    'peraturan_presiden' => 'Peraturan Presiden',
                    'peraturan_menteri_badan' => 'Peraturan '.($item->instansi && stripos($item->instansi, 'badan') === 0 ? 'Badan' : 'Menteri').' '.$item->instansi,
                    'keputusan_menteri_kepala_badan' => 'Keputusan '.($item->instansi && stripos($item->instansi, 'badan') === 0 ? 'Kepala Badan' : 'Menteri').' '.$item->instansi,
                    default => $item->kategori,
                };

                // Build nama lengkap
                $namaLengkap = $kategoriLabel.' Nomor '.$item->nomor.' Tahun '.$item->tahun;

                return (object) [
                    'kategori' => $kategoriLabel,
                    'nomor' => $item->nomor,
                    'tentang' => $item->tentang,
                    'tahun' => $item->tahun,
                    'nama_lengkap' => $namaLengkap,
                    'lembaran' => $item->lembaran,
                ];
            });

        // Check if this is SK Perubahan (ada SK sebelumnya) atau SK pertama
        $allExistingSk = SkKpa::where('kegiatan_id', $kegiatanId)
            ->orderBy('created_at', 'asc')
            ->get();

        $existingSk = $allExistingSk->first();
        $latestSk = $allExistingSk->last();
        $revisionNumber = $allExistingSk->count(); // 0 = SK pertama, 1 = perubahan pertama, 2 = perubahan kedua, dst
        $firstSkNumber = $existingSk ? $existingSk->nomor_sk : null;
        $firstSkYear = $existingSk ? $existingSk->tahun : null;

        // Get periode for petugas data
        // Jika SK Perubahan, ambil periode terbaru (latest)
        // Jika SK pertama, ambil periode pertama
        $periode = $this->resolveSkTargetPeriode($kegiatan->id, $existingSk !== null);

        if (! $periode) {
            abort(404);
        }

        // Get previous SK petugas list for comparison (if this is a revision)
        $deletedPetugas = [];
        $addedPetugas = [];
        $allCurrentPetugas = [];

        if ($revisionNumber > 0 && $existingSk) {
            // Get the previous periode (not the current one)
            $previousPeriode = $this->resolvePreviousSkComparisonPeriode(
                $kegiatan->id,
                $periode->id,
            );

            if ($previousPeriode) {
                $previousPetugasList = $previousPeriode->alokasiPetugas->pluck('petugas.nama', 'petugas_id')->toArray();
                $currentPetugasList = $periode->alokasiPetugas->pluck('petugas.nama', 'petugas_id')->toArray();

                // Find deleted petugas (in previous but not in current)
                $deletedPetugasIds = array_diff(array_keys($previousPetugasList), array_keys($currentPetugasList));
                $deletedPetugas = array_values(array_intersect_key($previousPetugasList, array_flip($deletedPetugasIds)));

                // Find added petugas (in current but not in previous)
                $addedPetugasIds = array_diff(array_keys($currentPetugasList), array_keys($previousPetugasList));
                $addedPetugas = array_values(array_intersect_key($currentPetugasList, array_flip($addedPetugasIds)));

                // All current petugas names for final list
                $allCurrentPetugas = array_values($currentPetugasList);
            }
        }

        // Parse alokasi petugas with rates
        $alokasiList = $periode->alokasiPetugas->map(function ($alokasi) use ($kegiatan) {
            // Make sure petugas relationship is loaded
            if (! isset($alokasi->petugas)) {
                return null;
            }

            // Get all rate honors for this petugas based on their peran
            $rateHonors = RateHonor::where('kegiatan_id', $kegiatan->id)
                ->where('jenis_penugasan', $alokasi->peran)
                ->where('status_kepegawaian', $alokasi->status_kepegawaian ?? ($alokasi->petugas->jenis_petugas === 'organik' ? 'organik' : 'non_organik'))
                ->with(['satuan', 'satuanListing'])
                ->get();

            $roles = [];
            foreach ($rateHonors as $rateHonor) {
                // Add listing rate first if exists
                if ($rateHonor->rate_listing !== null && $rateHonor->satuanListing) {
                    $biayaSatuanListing = number_format($rateHonor->rate_listing, 0, ',', '.');
                    $satuanListing = $rateHonor->satuanListing->nama ?? '';

                    $roles[] = (object) [
                        'peran' => $this->getPeranLabel($alokasi->peran, true),
                        'biaya_satuan' => 'Rp. '."{$biayaSatuanListing},-".' / '."{$satuanListing}",
                    ];
                }

                // Add regular rate
                if ($rateHonor->rate !== null && $rateHonor->satuan) {
                    $biayaSatuan = number_format($rateHonor->rate, 0, ',', '.');
                    $satuan = $rateHonor->satuan->kode ?? '';

                    $roles[] = (object) [
                        'peran' => $this->getPeranLabel($alokasi->peran, false),
                        'biaya_satuan' => 'Rp. '."{$biayaSatuan},-".' / '."{$satuan}",
                    ];
                }
            }

            return (object) [
                'nama' => $alokasi->petugas->nama,
                'nip' => $alokasi->petugas->nik ?? '-',
                'golongan' => $alokasi->petugas->golongan ?? '-',
                'jabatan' => $alokasi->petugas->jabatan ?? '-',
                'roles' => $roles,
            ];
        })->filter(); // Remove null values

        $data = [
            'kegiatan' => $kegiatan,
            'periode' => $periode,
            'nomorSk' => $validated['nomor_sk'],
            'tanggalSk' => Carbon::parse($validated['tanggal_sk'])->format('d-m-Y'),
            'tahunSk' => Carbon::parse($validated['tanggal_sk'])->format('Y'),
            'kategoriKeputusan' => 'KEPUTUSAN',
            'kepalaBps' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'dipa' => $dipa->nomor_dipa,
            'tanggalDipa' => $dipa->tanggal_dipa->format('d-m-Y'),
            'dasarHukum' => $dasarHukum,
            'alokasiList' => $alokasiList,
            'revisionNumber' => $revisionNumber,
            'revisionSkNumber' => $latestSk ? $latestSk->nomor_sk : null,
            'revisionSkYear' => $latestSk ? $latestSk->tahun : null,
            'firstSkNumber' => $firstSkNumber,
            'firstSkYear' => $firstSkYear,
            'deletedPetugas' => $deletedPetugas,
            'addedPetugas' => $addedPetugas,
            'allCurrentPetugas' => $allCurrentPetugas,
        ];

        // Choose template: use perubahan template when there are previous SKs (revisionNumber > 0)
        $view = ($revisionNumber > 0) ? 'sk-petugas-perubahan' : 'sk-petugas';

        // Generate PDF
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait');

        // Create filename
        $sanitizedNamaKegiatan = preg_replace('/[\/\\\:\*\?"<>\|]/', '', $kegiatan->nama_kegiatan);
        $filename = 'SK_'.str_replace('/', '-', $validated['nomor_sk']).'_'.$sanitizedNamaKegiatan.'_'.now()->format('YmdHis').'.pdf';

        // Ensure sk directory exists
        $skDirectory = public_path('sk');
        if (! file_exists($skDirectory)) {
            mkdir($skDirectory, 0755, true);
        }

        // Save PDF to public/sk
        $filePath = 'sk/'.$filename;
        $pdf->save(public_path($filePath));

        // Save SK record to database
        try {
            $skKpa = SkKpa::create([
                'nomor_sk' => $validated['nomor_sk'],
                'kegiatan_id' => $kegiatan->id,
                'tanggal_sk' => $validated['tanggal_sk'],
                'bulan' => (int) Carbon::parse($validated['tanggal_sk'])->format('m'),
                'tahun' => (int) Carbon::parse($validated['tanggal_sk'])->format('Y'),
                'nama_kpa' => $penandatangan->nama,
                'perihal' => 'Petugas '.$kegiatan->nama_kegiatan.' '.$kegiatan->tahun_anggaran,
                'dasar_hukum' => json_encode($validated['dasar_hukum_ids']),
                'file_path' => $filePath,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            ActivityLog::log(
                'Generate SK-KPA',
                'sk_kpa',
                "Berhasil generate SK-KPA: {$validated['nomor_sk']} untuk kegiatan {$kegiatan->nama_kegiatan}",
                'success',
                [
                    'sk_kpa_id' => $skKpa->id,
                    'nomor_sk' => $validated['nomor_sk'],
                    'kegiatan_id' => $kegiatan->id,
                    'kegiatan_nama' => $kegiatan->nama_kegiatan,
                    'revision_number' => $revisionNumber,
                    'file_path' => $filePath,
                ]
            );
        } catch (QueryException $e) {
            // Delete the generated PDF file
            if (file_exists(public_path($filePath))) {
                unlink(public_path($filePath));
            }

            // Check if it's a duplicate entry error
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'Duplicate entry')) {
                ActivityLog::logError(
                    'Generate SK-KPA',
                    'sk_kpa',
                    "Gagal generate SK-KPA: Nomor SK {$validated['nomor_sk']} sudah digunakan",
                    ['nomor_sk' => $validated['nomor_sk'], 'error' => $e->getMessage()]
                );

                return redirect()->route('sk-kpa.create-for-kegiatan', ['kegiatanHashedId' => $kegiatanHashedId])
                    ->withInput()
                    ->with('error', 'Nomor SK "'.$validated['nomor_sk'].'" sudah digunakan untuk tahun '.$periode->tahun.'. Silakan gunakan nomor SK yang berbeda.');
            }

            // For other database errors
            ActivityLog::logError(
                'Generate SK-KPA',
                'sk_kpa',
                'Gagal generate SK-KPA: '.$e->getMessage(),
                ['nomor_sk' => $validated['nomor_sk'], 'error' => $e->getMessage()]
            );

            return redirect()->route('sk-kpa.create-for-kegiatan', ['kegiatanHashedId' => $kegiatanHashedId])
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan SK: '.$e->getMessage());
        }

        return redirect()->route('sk-kpa.index')
            ->with('success', 'SK berhasil digenerate dan disimpan.');
    }

    private function getPeranLabel(string $peran, bool $isListing = false): string
    {
        if ($isListing) {
            return match ($peran) {
                'pcl_ppl' => 'Petugas Listing/Updating',
                'pml' => 'Pemeriksa Listing/Updating',
                'pengolahan' => 'Petugas Pengolahan - Listing',
                'pengawas_pengolahan' => 'Pemeriksa Pengolahan - Listing',
                default => $peran.' - Listing',
            };
        }

        return match ($peran) {
            'pcl_ppl' => 'Petugas Pencacahan',
            'pml' => 'Pemeriksa Lapangan',
            'pengolahan' => 'Petugas Pengolahan',
            'pengawas_pengolahan' => 'Pemeriksa Pengolahan',
            default => $peran,
        };
    }

    /**
     * Check if there are personnel changes between consecutive periods
     * If latestSk is provided, only check changes AFTER that SK's period
     * Returns true ONLY if there are actual personnel changes (added/removed)
     */
    private function checkPersonnelChanges(int $kegiatanId, ?SkKpa $latestSk = null): bool
    {
        $activeYear = ActiveYearService::get();

        // Get all approved/submitted periods for this kegiatan
        $periods = $this->applyPeriodeSkOrdering(
            $this->buildEligiblePeriodeQuery(
                $kegiatanId,
                $activeYear,
                ['alokasiPetugas'],
            ),
        )
            ->get();

        // If no SK exists yet, return false (this is for first SK creation, not changes)
        // The button "Buat SK" will be shown based on sk_count === 0, not this method
        if (! $latestSk) {
            return false;
        }

        // If the SK has already been acknowledged as not needing revision, skip check
        if ($latestSk->revision_acknowledged_at !== null) {
            return false;
        }

        $referencePeriode = $this->resolveReferencePeriodeForStoredSk($latestSk, ['alokasiPetugas']);

        if (! $referencePeriode) {
            return false;
        }

        $periodsAfterSk = $periods->filter(fn ($periode) => $periode->created_at->gt($latestSk->created_at));

        // No periods after SK = no changes possible
        if ($periodsAfterSk->isEmpty()) {
            return false;
        }

        // Reference team = all petugas listed on the SK (regardless of satuan).
        // They are part of the team even if they have 0 work in a specific period.
        $referenceTeam = $referencePeriode->alokasiPetugas->pluck('petugas_id')->flip();

        // Flag only if a post-SK period introduces a NEW active petugas not on the original SK.
        // Petugas with jumlah_satuan = 0 are "not needed this period" — not a team change.
        foreach ($periodsAfterSk as $period) {
            $activePostSkPetugas = $period->alokasiPetugas
                ->filter(fn ($ap) => ($ap->jumlah_satuan ?? 0) > 0 || ($ap->jumlah_satuan_listing ?? 0) > 0)
                ->pluck('petugas_id');

            foreach ($activePostSkPetugas as $petugasId) {
                if (! $referenceTeam->has($petugasId)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getPersonnelChangeInfo(int $kegiatanId): ?array
    {
        $activeYear = ActiveYearService::get();

        // Get the latest SK for this kegiatan
        $latestSk = SkKpa::where('kegiatan_id', $kegiatanId)
            ->orderBy('created_at', 'desc')
            ->first();

        // If no SK exists, this is first SK - no change info needed
        if (! $latestSk) {
            return null;
        }

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        // Get all approved/submitted periods for this kegiatan
        $periods = $this->applyPeriodeSkOrdering(
            $this->buildEligiblePeriodeQuery(
                $kegiatanId,
                $activeYear,
                ['alokasiPetugas.petugas:id,nama,jenis_petugas'],
            ),
        )
            ->get();

        $referencePeriode = $this->resolveReferencePeriodeForStoredSk(
            $latestSk,
            ['alokasiPetugas.petugas:id,nama,jenis_petugas'],
        );

        if (! $referencePeriode) {
            return null;
        }

        // Filter periods that are AFTER or IN THE SAME MONTH as the latest SK
        // This allows SK Perubahan for changes within the same month
        $periodsAfterSk = $periods->filter(function ($periode) use ($latestSk) {
            // Compare year first
            if ($periode->tahun > $latestSk->tahun) {
                return true;
            }

            // Same year - compare month
            if ($periode->tahun === $latestSk->tahun) {
                return $periode->bulan >= $latestSk->bulan;
            }

            return false;
        });

        // If no periods after or in same month as SK, no SK Perubahan needed
        if ($periodsAfterSk->isEmpty()) {
            return null;
        }

        // Build change details
        $changes = [];
        $referencePersonnel = $referencePeriode->alokasiPetugas->pluck('petugas_id')->sort()->values();
        $previousPersonnel = $referencePersonnel;

        foreach ($periodsAfterSk as $periode) {
            $currentPersonnel = $periode->alokasiPetugas->pluck('petugas_id')->sort()->values();

            // Always check for changes compared to previous periode using serialize for proper comparison
            if (serialize($currentPersonnel->toArray()) !== serialize($previousPersonnel->toArray())) {
                // Find added and removed personnel
                $added = $currentPersonnel->diff($previousPersonnel);
                $removed = $previousPersonnel->diff($currentPersonnel);

                $changes[] = [
                    'bulan' => $periode->bulan,
                    'bulan_nama' => $monthNames[$periode->bulan] ?? '',
                    'tahun' => $periode->tahun,
                    'added_count' => $added->count(),
                    'removed_count' => $removed->count(),
                    'total_petugas' => $currentPersonnel->count(),
                ];
            }

            $previousPersonnel = $currentPersonnel;
        }

        // If NO actual personnel changes detected, return null - no SK Perubahan needed
        if (empty($changes)) {
            return null;
        }

        // Determine first and last change months
        $firstChange = collect($changes)->first();
        $lastChange = collect($changes)->last();

        // Estimated SK month is the month after the last change period
        $estimatedSkMonth = '';
        $estimatedSkYear = $lastChange['tahun'];
        if ($lastChange['bulan'] < 12) {
            $estimatedSkMonth = $monthNames[$lastChange['bulan'] + 0] ?? '';
        } else {
            $estimatedSkMonth = $monthNames[1] ?? 'Januari';
            $estimatedSkYear = $lastChange['tahun'] + 1;
        }

        return [
            'has_changes' => true,
            'sk_number' => $latestSk->nomor_sk,
            'sk_date' => $latestSk->tanggal_sk->format('d-m-Y'),
            'sk_month' => $monthNames[$latestSk->bulan] ?? '',
            'sk_year' => $latestSk->tahun,
            'reference_month' => $monthNames[$referencePeriode->bulan] ?? '',
            'reference_year' => $referencePeriode->tahun,
            'first_change_month' => $firstChange['bulan_nama'],
            'last_change_month' => $lastChange['bulan_nama'],
            'change_year' => $firstChange['tahun'],
            'estimated_sk_month' => $estimatedSkMonth,
            'estimated_sk_year' => $estimatedSkYear,
            'total_changes' => count($changes),
            'changes' => $changes,
        ];
    }

    /**
     * @param  array<int, string>|array<string, string>  $relations
     */
    private function buildEligiblePeriodeQuery(int $kegiatanId, ?int $tahun = null, array $relations = []): Builder
    {
        $query = PeriodeAlokasi::query()
            ->where('kegiatan_id', $kegiatanId)
            ->whereIn('status', ['dikirim', 'perubahan', 'direvisi']);

        if ($tahun !== null) {
            $query->where('tahun', $tahun);
        }

        if ($relations !== []) {
            $query->with($relations);
        }

        return $query;
    }

    private function applyPeriodeSkOrdering(Builder $query, string $direction = 'asc'): Builder
    {
        return $query
            ->orderBy('tahun', $direction)
            ->orderByRaw('CAST(bulan AS UNSIGNED) '.$direction)
            ->orderBy('created_at', $direction)
            ->orderBy('id', $direction);
    }

    private function resolveSkTargetPeriode(int $kegiatanId, bool $useLatest): ?PeriodeAlokasi
    {
        return $this->applyPeriodeSkOrdering(
            $this->buildEligiblePeriodeQuery($kegiatanId, null, [
                'alokasiPetugas' => function ($query) {
                    $query->with('petugas');
                },
            ]),
            $useLatest ? 'desc' : 'asc',
        )->first();
    }

    private function resolvePreviousSkComparisonPeriode(int $kegiatanId, int $currentPeriodeId): ?PeriodeAlokasi
    {
        return $this->applyPeriodeSkOrdering(
            $this->buildEligiblePeriodeQuery($kegiatanId, null, [
                'alokasiPetugas' => function ($query) {
                    $query->with('petugas');
                },
            ])->where('id', '!=', $currentPeriodeId),
            'desc',
        )->first();
    }

    private function hasPreviousStoredSk(SkKpa $sk): bool
    {
        return SkKpa::query()
            ->where('kegiatan_id', $sk->kegiatan_id)
            ->where(function (Builder $query) use ($sk) {
                $query->where('created_at', '<', $sk->created_at)
                    ->orWhere(function (Builder $sameTimestampQuery) use ($sk) {
                        $sameTimestampQuery->where('created_at', $sk->created_at)
                            ->where('id', '<', $sk->id);
                    });
            })
            ->exists();
    }

    /**
     * @param  array<int, string>|array<string, string>  $relations
     */
    private function resolveReferencePeriodeForStoredSk(SkKpa $sk, array $relations = []): ?PeriodeAlokasi
    {
        $query = $this->buildEligiblePeriodeQuery($sk->kegiatan_id, null, $relations)
            ->where('created_at', '<=', $sk->created_at);

        return $this->applyPeriodeSkOrdering(
            $query,
            $this->hasPreviousStoredSk($sk) ? 'desc' : 'asc',
        )->first();
    }
}
