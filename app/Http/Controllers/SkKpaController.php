<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Models\Kegiatan;
use App\Models\Penandatangan;
use App\Models\SkKpa;
use App\Services\ActiveYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SkKpaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(FilterRequest $request): Response
    {
        $validated = $request->validated();
        $activeYear = ActiveYearService::get();

        // Get kegiatan that have validated periods (dikirim status)
        $query = Kegiatan::query()
            ->select('kegiatan.*') // Only select needed columns
            ->with([
                'ketuaTim:id,name',
                'skKpa:id,kegiatan_id,nomor_sk,tanggal_sk,status,file_path,signed_file_path,created_at',
            ])
            ->withCount(['skKpa' => function ($q) {
                $q->select(DB::raw('count(*)'));
            }])
            ->whereHas('periodeAlokasi', function ($q) use ($activeYear) {
                $q->where('tahun', $activeYear)
                    ->whereIn('status', ['dikirim', 'disetujui']);
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

        $kegiatan = $query->latest()->paginate(15)->withQueryString();

        // Transform data to include SK status
        // Note: status_sk and revision_number are calculated from skKpa relationship
        // They are not stored in kegiatan table
        $kegiatan->getCollection()->transform(function ($keg) {
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
                    'status' => $latestSk->status,
                    'file_path' => $latestSk->file_path,
                    'signed_file_path' => $latestSk->signed_file_path,
                ] : null,
            ];
        });

        return Inertia::render('SkKpa/Index', [
            'kegiatan' => $kegiatan,
            'filters' => $request->only(['search', 'jenis_kegiatan']),
        ]);
    }

    /**
     * List all SK for a specific kegiatan
     */
    public function listByKegiatan(string $kegiatanHashedId): Response
    {
        $kegiatanId = \Vinkla\Hashids\Facades\Hashids::decode($kegiatanHashedId)[0] ?? null;

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
        $kegiatanId = \Vinkla\Hashids\Facades\Hashids::decode($kegiatanHashedId)[0] ?? null;

        if (! $kegiatanId) {
            abort(404);
        }

        $kegiatan = Kegiatan::findOrFail($kegiatanId);

        // Check if there are any approved periodes
        $hasApprovedPeriodes = $kegiatan->periodeAlokasi()
            ->where('status', 'dikirim')
            ->exists();

        if (! $hasApprovedPeriodes) {
            return redirect()->route('sk-kpa.index')
                ->with('error', 'Belum ada periode yang dikirim untuk kegiatan ini.');
        }

        // Get active dasar hukum
        $dasarHukum = \App\Models\DasarHukum::where('status', 'aktif')
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

        return Inertia::render('SkKpa/Create', [
            'kegiatan' => [
                'id' => $kegiatan->id,
                'hashed_id' => $kegiatan->hashed_id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                'tahun_anggaran' => $kegiatan->tahun_anggaran,
            ],
            'dasarHukumList' => $dasarHukum,
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
        $skKpaId = \Vinkla\Hashids\Facades\Hashids::decode($skKpaHashedId)[0] ?? null;

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
        $dasarHukum = \App\Models\DasarHukum::whereIn('id', $dasarHukumIds)
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
     * Upload signed SK file
     */
    public function uploadSigned(Request $request, string $skKpaHashedId): RedirectResponse
    {
        $skKpaId = \Vinkla\Hashids\Facades\Hashids::decode($skKpaHashedId)[0] ?? null;

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
        $filename = 'SK_'.str_replace('/', '-', $skKpa->nomor_sk).'_'.$sanitizedNamaKegiatan.'_'.now()->format('YmdHis').'(signed)'.'.pdf';

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

        return redirect()->route('sk-kpa.show', ['skKpa' => $skKpa->hashed_id])
            ->with('success', 'SK yang sudah ditandatangani berhasil diupload.');
    }

    /**
     * Preview SK PDF (tidak save ke database)
     */
    public function previewSk(Request $request, string $kegiatanHashedId)
    {
        $kegiatanId = \Vinkla\Hashids\Facades\Hashids::decode($kegiatanHashedId)[0] ?? null;

        if (! $kegiatanId) {
            abort(404);
        }

        $validated = $request->validate([
            'nomor_sk' => ['required', 'string', 'max:255'],
            'tanggal_sk' => ['required', 'date'],
            'dasar_hukum_ids' => ['required', 'array', 'min:1'],
            'dasar_hukum_ids.*' => ['required', 'integer', 'exists:dasar_hukum,id'],
        ]);

        $kegiatan = Kegiatan::findOrFail($kegiatanId);

        // Get active Kepala BPS and DIPA from database
        $penandatangan = Penandatangan::active()->kepala()->firstOrFail();
        $dipa = \App\Models\Dipa::active()->firstOrFail();

        // Get dasar hukum
        $dasarHukum = \App\Models\DasarHukum::whereIn('id', $validated['dasar_hukum_ids'])
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
        $periodeQuery = $kegiatan->periodeAlokasi()
            ->with(['alokasiPetugas' => function ($query) {
                $query->with('petugas');
            }])
            ->whereIn('status', ['dikirim', 'disetujui'])
            ->orderBy('tahun', $existingSk ? 'desc' : 'asc')
            ->orderBy('bulan', $existingSk ? 'desc' : 'asc');

        $periode = $periodeQuery->firstOrFail();

        // Get previous SK petugas list for comparison (if this is a revision)
        $deletedPetugas = [];
        $addedPetugas = [];
        $allCurrentPetugas = [];

        if ($revisionNumber > 0 && $existingSk) {
            // Get the previous periode (not the current one)
            $previousPeriode = $kegiatan->periodeAlokasi()
                ->with(['alokasiPetugas' => function ($query) {
                    $query->with('petugas');
                }])
                ->whereIn('status', ['dikirim', 'disetujui'])
                ->where('id', '!=', $periode->id) // Exclude current periode
                ->orderBy('created_at', 'desc')
                ->first();

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
            $rateHonors = \App\Models\RateHonor::where('kegiatan_id', $kegiatan->id)
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
            'tanggalSk' => \Carbon\Carbon::parse($validated['tanggal_sk'])->format('d-m-Y'),
            'tahunSk' => \Carbon\Carbon::parse($validated['tanggal_sk'])->format('Y'),
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
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait');
        $sanitizedNamaKegiatan = preg_replace('/[\/\\\:\*\?"<>\|]/', '', $kegiatan->nama_kegiatan);

        return $pdf->stream('Preview_SK_'.$sanitizedNamaKegiatan.'.pdf');
    }

    /**
     * Generate SK PDF
     */
    public function generateSk(Request $request, string $kegiatanHashedId)
    {
        $kegiatanId = \Vinkla\Hashids\Facades\Hashids::decode($kegiatanHashedId)[0] ?? null;

        if (! $kegiatanId) {
            abort(404);
        }

        $validated = $request->validate([
            'nomor_sk' => ['required', 'string', 'max:255'],
            'tanggal_sk' => ['required', 'date'],
            'dasar_hukum_ids' => ['required', 'array', 'min:1'],
            'dasar_hukum_ids.*' => ['required', 'integer', 'exists:dasar_hukum,id'],
        ]);

        $kegiatan = Kegiatan::findOrFail($kegiatanId);

        // Get active Kepala BPS and DIPA from database
        $penandatangan = Penandatangan::active()->kepala()->firstOrFail();
        $dipa = \App\Models\Dipa::active()->firstOrFail();

        // Get dasar hukum
        $dasarHukum = \App\Models\DasarHukum::whereIn('id', $validated['dasar_hukum_ids'])
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
        $periodeQuery = $kegiatan->periodeAlokasi()
            ->with(['alokasiPetugas' => function ($query) {
                $query->with('petugas');
            }])
            ->whereIn('status', ['dikirim', 'disetujui'])
            ->orderBy('tahun', $existingSk ? 'desc' : 'asc')
            ->orderBy('bulan', $existingSk ? 'desc' : 'asc');

        $periode = $periodeQuery->firstOrFail();

        // Get previous SK petugas list for comparison (if this is a revision)
        $deletedPetugas = [];
        $addedPetugas = [];
        $allCurrentPetugas = [];

        if ($revisionNumber > 0 && $existingSk) {
            // Get the previous periode (not the current one)
            $previousPeriode = $kegiatan->periodeAlokasi()
                ->with(['alokasiPetugas' => function ($query) {
                    $query->with('petugas');
                }])
                ->whereIn('status', ['dikirim', 'disetujui'])
                ->where('id', '!=', $periode->id) // Exclude current periode
                ->orderBy('created_at', 'desc')
                ->first();

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
            $rateHonors = \App\Models\RateHonor::where('kegiatan_id', $kegiatan->id)
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
            'tanggalSk' => \Carbon\Carbon::parse($validated['tanggal_sk'])->format('d-m-Y'),
            'tahunSk' => \Carbon\Carbon::parse($validated['tanggal_sk'])->format('Y'),
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
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data)
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
            SkKpa::create([
                'nomor_sk' => $validated['nomor_sk'],
                'kegiatan_id' => $kegiatan->id,
                'bulan' => $periode->bulan,
                'tahun' => $periode->tahun,
                'tanggal_sk' => $validated['tanggal_sk'],
                'nama_kpa' => $penandatangan->nama,
                'perihal' => 'Petugas '.$kegiatan->nama_kegiatan.' '.$kegiatan->tahun_anggaran,
                'dasar_hukum' => json_encode($validated['dasar_hukum_ids']),
                'file_path' => $filePath,
                'status' => 'diterbitkan',
                'created_by' => Auth::id(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Delete the generated PDF file
            if (file_exists(public_path($filePath))) {
                unlink(public_path($filePath));
            }

            // Check if it's a duplicate entry error
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'Duplicate entry')) {
                return redirect()->route('sk-kpa.create-for-kegiatan', ['kegiatanHashedId' => $kegiatanHashedId])
                    ->withInput()
                    ->with('error', 'Nomor SK "'.$validated['nomor_sk'].'" sudah digunakan. Silakan gunakan nomor SK yang berbeda.');
            }

            // For other database errors
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
     */
    private function checkPersonnelChanges(int $kegiatanId, ?\App\Models\SkKpa $latestSk = null): bool
    {
        $activeYear = ActiveYearService::get();

        // Get all approved/submitted periods for this kegiatan
        $periods = \App\Models\PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
            ->where('tahun', $activeYear)
            ->whereIn('status', ['dikirim', 'disetujui'])
            ->with('alokasiPetugas')
            ->orderBy('bulan')
            ->get();

        // If no SK exists yet, check if there are any periods (need at least one for first SK)
        if (! $latestSk) {
            return $periods->count() > 0;
        }

        // Get periods that exist AFTER the latest SK bulan
        $periodsAfterSk = $periods->filter(fn ($p) => $p->bulan > $latestSk->bulan);

        if ($periodsAfterSk->isEmpty()) {
            return false;
        }

        // Find the latest periode that was used when the SK was created
        // This should be the periode with bulan <= latestSk->bulan (the last one before or at SK creation)
        $lastPeriodeBeforeSk = $periods
            ->filter(fn ($p) => $p->bulan <= $latestSk->bulan)
            ->sortByDesc('bulan')
            ->first();

        if (! $lastPeriodeBeforeSk) {
            // If we can't find reference periode, any new periode means changes
            return true;
        }

        // Get personnel from the last periode before/at SK creation
        $referencePersonnel = $lastPeriodeBeforeSk->alokasiPetugas
            ->pluck('petugas_id')
            ->sort()
            ->values()
            ->toArray();

        // Check each periode after SK to see if personnel changed
        // We compare each with the PREVIOUS periode to detect changes
        $previousPersonnel = $referencePersonnel;

        foreach ($periodsAfterSk as $period) {
            $currentPersonnel = $period->alokasiPetugas
                ->pluck('petugas_id')
                ->sort()
                ->values()
                ->toArray();

            // If current periode has different personnel than previous, there's a change
            if ($currentPersonnel !== $previousPersonnel) {
                return true;
            }

            // Update previous for next iteration
            $previousPersonnel = $currentPersonnel;
        }

        // No changes found - all periods after SK have same personnel
        return false;
    }
}
