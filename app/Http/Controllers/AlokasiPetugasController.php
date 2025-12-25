<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Http\Requests\StoreAlokasiPetugasRequest;
use App\Http\Requests\UpdateAlokasiPetugasRequest;
use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Sbml;
use App\Services\ActiveYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Vinkla\Hashids\Facades\Hashids;

class AlokasiPetugasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(FilterRequest $request): Response
    {
        $validated = $request->validated();
        $activeYear = ActiveYearService::get();
        $query = PeriodeAlokasi::query()
            ->with(['kegiatan:id,kode_kegiatan,nama_kegiatan,ketua_tim_user_id,pagu_pencacahan,pagu_listing,has_listing_updating', 'alokasiPetugas'])
            ->withCount('alokasiPetugas as jumlah_petugas')
            ->where('status', '!=', 'dihapus') // Exclude deleted periods
            ->whereIn('status', ['dikirim', 'perubahan', 'direvisi', 'draft']) // Show all relevant statuses
            ->where('tahun', $activeYear);

        // Search by kegiatan
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->whereHas('kegiatan', function ($q) use ($search) {
                $q->where('nama_kegiatan', 'like', "%{$search}%")
                    ->orWhere('kode_kegiatan', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Filter by bulan (gunakan string dengan leading zero agar cocok dengan frontend)
        if (! empty($validated['bulan'])) {
            $query->where('bulan', str_pad($validated['bulan'], 2, '0', STR_PAD_LEFT));
        }

        // Filter for Ketua Tim - only their kegiatan
        $effectiveUser = effectiveUser($request);
        if ($effectiveUser->isKetuaTim()) {
            $query->whereHas('kegiatan', function ($q) use ($effectiveUser) {
                $q->where('ketua_tim_user_id', $effectiveUser->id)
                    ->orWhere('pj_lainnya_id', $effectiveUser->id);
            });
        }

        // Order by tahun and bulan descending (newest first)
        $alokasi = $query->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->paginate(15)
            ->withQueryString();

        // Get latest month for each kegiatan (for revisi button logic)
        // Only show revisi for 'dikirim' or 'perubahan' status
        $latestMonthsByKegiatan = PeriodeAlokasi::query()
            ->select('kegiatan_id', DB::raw('MAX(bulan) as latest_bulan'))
            ->whereIn('status', ['dikirim', 'perubahan'])
            ->where('tahun', $activeYear)
            ->groupBy('kegiatan_id')
            ->pluck('latest_bulan', 'kegiatan_id');

        // Transform the result to include necessary data
        $alokasi->getCollection()->transform(function ($periode) use ($latestMonthsByKegiatan, $activeYear) {
            // Hitung ulang total honor untuk periode ini
            $totalHonorPencacahan = $periode->alokasiPetugas->sum('total_honor');
            $totalHonorListing = $periode->alokasiPetugas->sum('total_honor_listing');
            $estimasiHonor = $totalHonorPencacahan + $totalHonorListing;

            // Ambil pagu dari kegiatan
            $paguPencacahan = $periode->kegiatan->pagu_pencacahan ?? 0;
            $paguListing = $periode->kegiatan->pagu_listing ?? 0;

            // Hitung TOTAL honor yang sudah terpakai dari SEMUA periode sampai periode ini
            // (periode yang lebih lama atau sama dengan periode saat ini)
            $totalHonorTerpakaiPencacahan = AlokasiPetugas::whereHas('periodeAlokasi', function ($q) use ($periode, $activeYear) {
                $q->where('kegiatan_id', $periode->kegiatan_id)
                    ->where('tahun', $activeYear)
                    ->where('bulan', '<=', $periode->bulan)
                    ->whereIn('status', ['dikirim', 'perubahan', 'direvisi', 'draft']);
            })->sum('total_honor');

            $totalHonorTerpakaiListing = AlokasiPetugas::whereHas('periodeAlokasi', function ($q) use ($periode, $activeYear) {
                $q->where('kegiatan_id', $periode->kegiatan_id)
                    ->where('tahun', $activeYear)
                    ->where('bulan', '<=', $periode->bulan)
                    ->whereIn('status', ['dikirim', 'perubahan', 'direvisi', 'draft']);
            })->sum('total_honor_listing');

            // Sisa pagu = pagu total - total honor terpakai (akumulasi semua periode)
            $sisaPaguPencacahan = $paguPencacahan - $totalHonorTerpakaiPencacahan;
            $sisaPaguListing = $paguListing - $totalHonorTerpakaiListing;
            $sisaPagu = $sisaPaguPencacahan + $sisaPaguListing;

            // Pagu terpakai = total honor untuk periode ini saja
            $paguTerpakai = $estimasiHonor;

            $isLatestPeriode = $periode->status === 'dikirim' &&
                isset($latestMonthsByKegiatan[$periode->kegiatan_id]) &&
                $periode->bulan == $latestMonthsByKegiatan[$periode->kegiatan_id];

            return [
                'kegiatan_id' => $periode->kegiatan_id,
                'bulan' => str_pad($periode->bulan, 2, '0', STR_PAD_LEFT),
                'tahun' => $periode->tahun,
                'jenis_kegiatan' => $periode->jenis_kegiatan,
                'status' => $periode->status,
                'jumlah_petugas' => $periode->jumlah_petugas,
                'total_honor' => $estimasiHonor,
                'estimasi_honor' => $estimasiHonor,
                'sisa_pagu' => $sisaPagu,
                'pagu_pencacahan' => $paguPencacahan,
                'pagu_listing' => $paguListing,
                'pagu_terpakai' => $paguTerpakai,
                'latest_created_at' => $periode->created_at,
                'is_latest_periode' => $isLatestPeriode,
                'kegiatan' => [
                    'id' => $periode->kegiatan->id,
                    'hashed_id' => $periode->kegiatan->hashed_id,
                    'nama_kegiatan' => $periode->kegiatan->nama_kegiatan,
                    'kode_kegiatan' => $periode->kegiatan->kode_kegiatan,
                ],
            ];
        });

        // Check if any kegiatan exists
        $hasKegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])
            ->when($effectiveUser->isKetuaTim(), function ($query) use ($effectiveUser) {
                $query->where('ketua_tim_user_id', $effectiveUser->id)
                    ->orWhere('pj_lainnya_id', $effectiveUser->id);
            })
            ->exists();

        return Inertia::render('Alokasi/Index', [
            'alokasi' => $alokasi,
            'filters' => $request->only(['search', 'status', 'bulan']),
            'active_year' => $activeYear,
            'hasKegiatans' => $hasKegiatans,
        ]);
    }

    /**
     * Store multiple alokasi for a kegiatan.
     */
    public function storeMultiple(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        // Check if kegiatan is approved
        if (! in_array($kegiatan->status, ['divalidasi', 'aktif'])) {
            return back()->with('error', 'Alokasi petugas hanya bisa ditambahkan untuk kegiatan yang sudah divalidasi.');
        }

        // Ketua Tim dapat menambah alokasi jika dia adalah ketua_tim_user_id atau pj_lainnya_id
        $effectiveUser = effectiveUser($request);
        if ($effectiveUser->isKetuaTim() && ! ($kegiatan->ketua_tim_user_id === $effectiveUser->id || $kegiatan->pj_lainnya_id === $effectiveUser->id)) {
            abort(403, 'Anda tidak memiliki akses untuk menambahkan alokasi pada kegiatan ini.');
        }
        // Validate that kegiatan has rate honors
        if ($kegiatan->rateHonors()->count() === 0) {
            return back()->withErrors([
                'rate_honor' => 'Kegiatan ini belum memiliki rate honor. Silakan set rate honor pada kegiatan terlebih dahulu.',
            ]);
        }

        $validated = $request->validate([
            'alokasi' => 'required|array|min:1',
            'alokasi.*.petugas_id' => 'required|exists:petugas,id',
            'alokasi.*.peran' => 'required|string|in:PCL,PML,Pengolahan,Petugas Pengolahan,Pengawas Pengolahan',
            'alokasi.*.bulan' => 'required|integer|min:1|max:12',
            'alokasi.*.tahun' => 'required|integer|min:2020|max:2099',
            'alokasi.*.jumlah_satuan' => 'required|integer|min:0',
            'alokasi.*.jumlah_satuan_listing' => 'nullable|integer|min:0',
            'alokasi.*.jenis_kegiatan' => 'required|in:sensus,survei',
            'alokasi.*.tahapan' => 'nullable|in:both,listing_only,pencacahan_only',
            'alokasi.*.catatan' => 'nullable|string',
        ]);

        // Get tahapan from first alokasi item (all should have same tahapan in a batch)
        $tahapan = $validated['alokasi'][0]['tahapan'] ?? 'both';

        // Conditional validation based on tahapan
        if ($tahapan === 'listing_only') {
            $request->validate([
                'tanggal_mulai_listing' => 'required|date',
                'tanggal_selesai_listing' => 'required|date|after_or_equal:tanggal_mulai_listing',
            ], [
                'tanggal_mulai_listing.required' => 'Tanggal mulai listing wajib diisi.',
                'tanggal_selesai_listing.required' => 'Tanggal selesai listing wajib diisi.',
                'tanggal_selesai_listing.after_or_equal' => 'Tanggal selesai listing harus setelah atau sama dengan tanggal mulai listing.',
            ]);
            $validated['tanggal_mulai_listing'] = $request->tanggal_mulai_listing;
            $validated['tanggal_selesai_listing'] = $request->tanggal_selesai_listing;
        } else {
            $request->validate([
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            ], [
                'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
                'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
                'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
            ]);
            $validated['tanggal_mulai'] = $request->tanggal_mulai;
            $validated['tanggal_selesai'] = $request->tanggal_selesai;

            // Also validate listing dates if tahapan is 'both'
            if ($tahapan === 'both') {
                $request->validate([
                    'tanggal_mulai_listing' => 'nullable|date',
                    'tanggal_selesai_listing' => 'nullable|date|after_or_equal:tanggal_mulai_listing',
                ]);
                $validated['tanggal_mulai_listing'] = $request->tanggal_mulai_listing;
                $validated['tanggal_selesai_listing'] = $request->tanggal_selesai_listing;
            }
        }

        DB::beginTransaction();
        $created = 0;
        $errors = [];

        // Group by periode (bulan+tahun+jenis_kegiatan) to create PeriodeAlokasi first
        $periodeGroups = [];
        foreach ($validated['alokasi'] as $index => $alokasiData) {
            // Get petugas to determine jenis_petugas
            $petugas = Petugas::find($alokasiData['petugas_id']);
            if (! $petugas) {
                $errors[] = 'Petugas tidak ditemukan.';

                continue;
            }

            // Map peran to jenis_penugasan
            $jenisPenugasan = match ($alokasiData['peran']) {
                'PCL' => 'pcl_ppl',
                'PML' => 'pml',
                'Pengolahan', 'Petugas Pengolahan' => 'pengolahan',
                'Pengawas Pengolahan' => 'pengawas_pengolahan',
                default => null,
            };

            if (! $jenisPenugasan) {
                $errors[] = $petugas->nama.': Peran tidak valid.';

                continue;
            }

            // Find matching rate honor based on petugas type, jenis_kegiatan, and jenis_penugasan
            $statusKepegawaian = $petugas->jenis_petugas === 'organik' ? 'organik' : 'non_organik';
            $rateHonor = $kegiatan->rateHonors()
                ->where('status_kepegawaian', $statusKepegawaian)
                ->where('jenis_kegiatan', $alokasiData['jenis_kegiatan'])
                ->where('jenis_penugasan', $jenisPenugasan)
                ->where('status', 'aktif')
                ->where('tahun_berlaku', $alokasiData['tahun'])
                ->first();

            if (! $rateHonor) {
                $errors[] = $petugas->nama.': Rate honor untuk '.$alokasiData['peran'].' ('.$statusKepegawaian.', '.$alokasiData['jenis_kegiatan'].') tidak ditemukan.';

                continue;
            }

            $totalHonor = $rateHonor->rate * $alokasiData['jumlah_satuan'];

            // Calculate listing honor if kegiatan has listing phase
            $totalHonorListing = 0;
            $jumlahSatuanListing = null;
            if ($kegiatan->has_listing_updating && isset($alokasiData['jumlah_satuan_listing']) && $alokasiData['jumlah_satuan_listing'] > 0) {
                $jumlahSatuanListing = $alokasiData['jumlah_satuan_listing'];
                if ($rateHonor->rate_listing) {
                    $totalHonorListing = $rateHonor->rate_listing * $jumlahSatuanListing;
                }
            }

            // Check SBML constraint per assignment (skip if honor is 0)
            if ($totalHonor > 0) {
                $constraintError = $this->checkSbmlConstraint(
                    $alokasiData['tahun'],
                    $alokasiData['jenis_kegiatan'],
                    $rateHonor->status_kepegawaian,
                    $rateHonor->jenis_penugasan,
                    $totalHonor
                );

                if ($constraintError) {
                    $errors[] = $petugas->nama.': '.$constraintError;

                    continue;
                }
            }

            // Check petugas total honor in month across all assignments (skip if honor is 0)
            if ($totalHonor > 0) {
                $petugasTotalError = $this->checkPetugasTotalHonorInMonth(
                    $alokasiData['petugas_id'],
                    $alokasiData['tahun'],
                    $alokasiData['bulan'],
                    $totalHonor,
                    null,
                    $jenisPenugasan,
                    $alokasiData['jenis_kegiatan'],
                    $statusKepegawaian
                );

                if ($petugasTotalError) {
                    $errors[] = $petugas->nama.': '.$petugasTotalError;

                    continue;
                }
            }

            // Store data grouped by periode
            $periodeKey = $alokasiData['bulan'].'_'.$alokasiData['tahun'].'_'.$alokasiData['jenis_kegiatan'];
            if (! isset($periodeGroups[$periodeKey])) {
                $periodeGroups[$periodeKey] = [
                    'bulan' => str_pad($alokasiData['bulan'], 2, '0', STR_PAD_LEFT),
                    'tahun' => $alokasiData['tahun'],
                    'jenis_kegiatan' => $alokasiData['jenis_kegiatan'],
                    'tahapan' => $alokasiData['tahapan'] ?? 'both',
                    'alokasi' => [],
                ];
            }

            $periodeGroups[$periodeKey]['alokasi'][] = [
                'petugas_id' => $alokasiData['petugas_id'],
                'jumlah_satuan' => $alokasiData['jumlah_satuan'],
                'jumlah_satuan_listing' => $jumlahSatuanListing,
                'total_honor' => $totalHonor,
                'total_honor_listing' => $totalHonorListing,
                'peran' => $jenisPenugasan,
                'status_kepegawaian' => $rateHonor->status_kepegawaian,
                'catatan' => $alokasiData['catatan'] ?? null,
            ];

            $created++;
        }

        // Create PeriodeAlokasi and AlokasiPetugas
        foreach ($periodeGroups as $periodeData) {
            // Calculate new periode's total honor
            $newPeriodeTotalHonor = collect($periodeData['alokasi'])->sum('total_honor');
            $newPeriodeTotalHonorListing = collect($periodeData['alokasi'])->sum('total_honor_listing');

            // Check budget constraint before creating periode
            $kegiatan->load('periodeAlokasi.alokasiPetugas');
            $paguAnggaran = $kegiatan->pagu_pencacahan ?? 0;
            $paguListing = $kegiatan->has_listing_updating ? ($kegiatan->pagu_listing ?? 0) : 0;

            // Calculate total spent across all active periods
            $totalSpent = $kegiatan->periodeAlokasi
                ->whereIn('status', ['draft', 'dikirim', 'direvisi'])
                ->sum(function ($p) {
                    return $p->alokasiPetugas->sum('total_honor');
                });

            $totalSpentListing = $kegiatan->periodeAlokasi
                ->whereIn('status', ['draft', 'dikirim', 'direvisi'])
                ->sum(function ($p) {
                    return $p->alokasiPetugas->sum('total_honor_listing');
                });

            $sisaPagu = $paguAnggaran - $totalSpent;
            $sisaPaguListing = $paguListing - $totalSpentListing;
            // Validate that sisa pagu is sufficient for new periode
            if ($newPeriodeTotalHonor > $sisaPagu || $newPeriodeTotalHonorListing > $sisaPaguListing) {
                DB::rollBack();

                return back()->withErrors([
                    'budget' => 'Anggaran tidak mencukupi untuk menambahkan periode ini. '.
                        'Sisa pagu: '.number_format($sisaPagu, 0, ',', '.').', '.
                        'Estimasi honor periode baru: '.number_format($newPeriodeTotalHonor, 0, ',', '.'),
                    ' Sisa pagu listing: '.number_format($sisaPaguListing, 0, ',', '.').', '.
                    'Estimasi honor listing periode baru: '.number_format($newPeriodeTotalHonorListing, 0, ',', '.'),
                ]);
            }

            // Calculate sisa_pagu based on previous periods (sequential by month)
            $previousPeriode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where(function ($query) use ($periodeData) {
                    $query->where('tahun', '<', $periodeData['tahun'])
                        ->orWhere(function ($q) use ($periodeData) {
                            $q->where('tahun', $periodeData['tahun'])
                                ->where('bulan', '<', $periodeData['bulan']);
                        });
                })
                ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
                ->orderByDesc('tahun')
                ->orderByDesc('bulan')
                ->first();

            $previousPeriodeListing = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where(function ($query) use ($periodeData) {
                    $query->where('tahun', '<', $periodeData['tahun'])
                        ->orWhere(function ($q) use ($periodeData) {
                            $q->where('tahun', $periodeData['tahun'])
                                ->where('bulan', '<', $periodeData['bulan']);
                        });
                })
                ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
                ->orderByDesc('tahun')
                ->orderByDesc('bulan')
                ->first();

            // Calculate sisa_pagu for this new periode
            $sisaPaguPeriode = $previousPeriode
                ? $previousPeriode->sisa_pagu - $newPeriodeTotalHonor
                : $paguAnggaran - $newPeriodeTotalHonor;

            $sisaPaguPeriodeListing = $previousPeriodeListing
                ? $previousPeriodeListing->sisa_pagu_listing - $newPeriodeTotalHonorListing
                : $paguListing - $newPeriodeTotalHonorListing;

            // Check for existing periode (including dihapus status)
            $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where('bulan', $periodeData['bulan'])
                ->where('tahun', $periodeData['tahun'])
                ->first();

            if ($periode && $periode->status === 'dihapus') {
                // Reuse periode that was marked as deleted
                $periode->update([
                    'jenis_kegiatan' => $periodeData['jenis_kegiatan'],
                    'tahapan' => $periodeData['tahapan'] ?? 'both',
                    'status' => 'draft',
                    'sisa_pagu' => $sisaPaguPeriode,
                    'sisa_pagu_listing' => $sisaPaguPeriodeListing,
                    'tanggal_mulai' => $validated['tanggal_mulai'] ?? null,
                    'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
                    'tanggal_mulai_listing' => $validated['tanggal_mulai_listing'] ?? null,
                    'tanggal_selesai_listing' => $validated['tanggal_selesai_listing'] ?? null,
                ]);
            } elseif (! $periode) {
                // Create new periode
                $periode = PeriodeAlokasi::create([
                    'kegiatan_id' => $kegiatan->id,
                    'bulan' => $periodeData['bulan'],
                    'tahun' => $periodeData['tahun'],
                    'jenis_kegiatan' => $periodeData['jenis_kegiatan'],
                    'tahapan' => $periodeData['tahapan'] ?? 'both',
                    'status' => 'draft',
                    'sisa_pagu' => $sisaPaguPeriode,
                    'sisa_pagu_listing' => $sisaPaguPeriodeListing,
                    'tanggal_mulai' => $validated['tanggal_mulai'] ?? null,
                    'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
                    'tanggal_mulai_listing' => $validated['tanggal_mulai_listing'] ?? null,
                    'tanggal_selesai_listing' => $validated['tanggal_selesai_listing'] ?? null,
                ]);
            }

            // Create AlokasiPetugas for this periode
            foreach ($periodeData['alokasi'] as $alokasiItem) {
                AlokasiPetugas::create([
                    'periode_alokasi_id' => $periode->id,
                    'petugas_id' => $alokasiItem['petugas_id'],
                    'jumlah_satuan' => $alokasiItem['jumlah_satuan'],
                    'jumlah_satuan_listing' => $alokasiItem['jumlah_satuan_listing'],
                    'total_honor' => $alokasiItem['total_honor'],
                    'total_honor_listing' => $alokasiItem['total_honor_listing'],
                    'peran' => $alokasiItem['peran'],
                    'status_kepegawaian' => $alokasiItem['status_kepegawaian'],
                    'catatan' => $alokasiItem['catatan'],
                ]);
            }
        }

        DB::commit();

        if (count($errors) > 0) {
            $errorMessage = implode("\n", $errors);
            if ($created > 0) {
                return back()->withErrors(['sbml_constraint' => $errorMessage])
                    ->with('warning', "{$created} alokasi berhasil ditambahkan.");
            }
            return back()->withErrors(['sbml_constraint' => $errorMessage]);
        }

        return redirect()->route('alokasi.index')
            ->with('success', "{$created} alokasi petugas berhasil ditambahkan.");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $activeYear = ActiveYearService::get();
        $effectiveUser = effectiveUser($request);

        // Check if any kegiatan exists before allowing access
        if ($effectiveUser->isKetuaTim()) {
            $hasKegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])
                ->where(function ($q) use ($effectiveUser) {
                    $q->where('ketua_tim_user_id', $effectiveUser->id)
                        ->orWhere('pj_lainnya_id', $effectiveUser->id);
                })
                ->exists();
        } else {
            $hasKegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])->exists();
        }

        if (! $hasKegiatans) {
            return redirect()->route('alokasi.index')
                ->with('error', 'Tidak ada kegiatan yang tersedia untuk membuat periode alokasi.');
        }

        if ($effectiveUser->isKetuaTim()) {
            $kegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])
                ->where(function ($q) use ($effectiveUser) {
                    $q->where('ketua_tim_user_id', $effectiveUser->id)
                        ->orWhere('pj_lainnya_id', $effectiveUser->id);
                })
                ->with([
                    'rateHonors' => function ($query) use ($activeYear) {
                        $query->where('status', 'aktif')
                            ->where('tahun_berlaku', $activeYear)
                            ->select('id', 'kegiatan_id', 'posisi', 'jenis_kegiatan', 'status_kepegawaian', 'jenis_penugasan', 'rate', 'rate_listing', 'satuan_id', 'satuan_listing_id')
                            ->with([
                                'satuan:id,kode,nama',
                                'satuanListing:id,kode,nama',
                            ]);
                    },
                ])
                ->select('id', 'kode_kegiatan', 'nama_kegiatan', 'deskripsi', 'jenis_kegiatan', 'pagu_pencacahan', 'ketua_tim_user_id', 'pj_lainnya_id', 'has_listing_updating', 'pagu_listing', 'tanggal_mulai', 'tanggal_selesai')
                ->orderBy('created_at', 'desc')
                ->get()
                ->filter(function ($kegiatan) {
                    // Only show kegiatan that has at least one rate honor
                    return $kegiatan->rateHonors->isNotEmpty();
                })
                ->values();
        } else {
            $kegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])
                ->with([
                    'rateHonors' => function ($query) use ($activeYear) {
                        $query->where('status', 'aktif')
                            ->where('tahun_berlaku', $activeYear)
                            ->select('id', 'kegiatan_id', 'posisi', 'jenis_kegiatan', 'status_kepegawaian', 'jenis_penugasan', 'rate', 'rate_listing', 'satuan_id', 'satuan_listing_id')
                            ->with([
                                'satuan:id,kode,nama',
                                'satuanListing:id,kode,nama',
                            ]);
                    },
                ])
                ->select('id', 'kode_kegiatan', 'nama_kegiatan', 'deskripsi', 'jenis_kegiatan', 'pagu_pencacahan', 'ketua_tim_user_id', 'pj_lainnya_id', 'has_listing_updating', 'pagu_listing', 'tanggal_mulai', 'tanggal_selesai')
                ->orderBy('created_at', 'desc')
                ->get()
                ->filter(function ($kegiatan) {
                    // Only show kegiatan that has at least one rate honor
                    return $kegiatan->rateHonors->isNotEmpty();
                })
                ->values();
        }

        // Pastikan field rate_listing dan satuan_listing_id selalu ada di setiap rateHonors
        foreach ($kegiatans as $kegiatan) {
            foreach ($kegiatan->rateHonors as $rateHonor) {
                // Pastikan field rate_listing dan satuan_listing_id selalu ada
                if (! array_key_exists('rate_listing', $rateHonor->getAttributes())) {
                    $rateHonor->rate_listing = null;
                }
                if (! array_key_exists('satuan_listing_id', $rateHonor->getAttributes())) {
                    $rateHonor->satuan_listing_id = null;
                }
            }
        }
        // Calculate budget info for all kegiatans
        $budgetInfo = [];
        $usedMonthsInfo = [];
        foreach ($kegiatans as $kegiatan) {
            $totalSpent = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where('tahun', $activeYear)
                ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
                ->with('alokasiPetugas')
                ->get()
                ->sum(function ($p) {
                    return $p->alokasiPetugas->sum('total_honor');
                });

            $totalSpentListing = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where('tahun', $activeYear)
                ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
                ->with('alokasiPetugas')
                ->get()
                ->sum(function ($p) {
                    return $p->alokasiPetugas->sum('total_honor_listing');
                });

            $budgetInfo[$kegiatan->id] = [
                'pagu_pencacahan' => $kegiatan->pagu_pencacahan ?? 0,
                'current_total_spent' => $totalSpent,
                'pagu_listing' => $kegiatan->pagu_listing ?? 0,
                'current_total_spent_listing' => $totalSpentListing,
            ];

            // Calculate used months for this kegiatan
            $usedMonthsInfo[$kegiatan->id] = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where('tahun', $activeYear)
                ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
                ->pluck('bulan')
                ->map(fn ($b) => (int) $b)
                ->toArray();
        }

        $petugas = Petugas::where('status', 'aktif')
            ->select('id', 'nama', 'nik', 'email', 'jenis_petugas', 'jabatan')
            ->get();

        // Handle pre-selected kegiatan from query string
        $selectedKegiatan = null;
        if ($request->filled('kegiatan_id')) {
            try {
                $decodedId = Hashids::decode($request->kegiatan_id)[0] ?? null;
                if ($decodedId) {
                    $selectedKegiatan = Kegiatan::where('id', $decodedId)
                        ->whereIn('status', ['divalidasi', 'aktif'])
                        ->with([
                            'rateHonors' => function ($query) use ($activeYear) {
                                $query->where('status', 'aktif')
                                    ->where('tahun_berlaku', $activeYear)
                                    ->select('id', 'kegiatan_id', 'posisi', 'jenis_kegiatan', 'status_kepegawaian', 'jenis_penugasan', 'rate', 'satuan_id')
                                    ->with('satuan:id,kode,nama');
                            },
                        ])
                        ->select('id', 'kode_kegiatan', 'nama_kegiatan', 'deskripsi', 'jenis_kegiatan', 'ketua_tim_user_id', 'tanggal_mulai', 'tanggal_selesai')
                        ->first();
                }
            } catch (\Exception $e) {
                // Invalid hashed_id, just ignore
            }
        }

        // Handle copy from existing periode
        $copiedAlokasi = null;
        $sourcePeriode = null;

        if ($request->filled(['kegiatan_id', 'copy_from_bulan', 'copy_from_tahun'])) {
            try {
                $decodedId = Hashids::decode($request->kegiatan_id)[0] ?? null;
                if ($decodedId) {
                    $kegiatan = Kegiatan::find($decodedId);
                    if ($kegiatan) {
                        // Ketua Tim can only copy from their own kegiatan
                        $effectiveUser = effectiveUser($request);
                        if ($effectiveUser->isKetuaTim() && $kegiatan->ketua_tim_user_id !== $effectiveUser->id) {
                            // Don't copy data if ketua_tim tries to copy from other's kegiatan
                            $copiedAlokasi = null;
                            $sourcePeriode = null;
                        } else {
                            $sourcePeriodeData = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                                ->where('tahun', $request->copy_from_tahun)
                                ->where('bulan', $request->copy_from_bulan)
                                ->with(['alokasiPetugas.petugas'])
                                ->first();

                            if ($sourcePeriodeData && $sourcePeriodeData->alokasiPetugas->isNotEmpty()) {
                                // Calculate kegiatan duration
                                $tanggalMulai = \Carbon\Carbon::parse($kegiatan->tanggal_mulai);
                                $tanggalSelesai = \Carbon\Carbon::parse($kegiatan->tanggal_selesai);
                                $durationMonths = $tanggalMulai->diffInMonths($tanggalSelesai) + 1;

                                // Only allow copy if kegiatan spans multiple months
                                if ($durationMonths > 1) {
                                    $copiedAlokasi = $sourcePeriodeData->alokasiPetugas->map(function ($alokasi) {
                                        return [
                                            'petugas_id' => $alokasi->petugas_id,
                                            'petugas_nama' => $alokasi->petugas->nama,
                                            'status_kepegawaian' => $alokasi->status_kepegawaian,
                                            'peran' => $alokasi->peran,
                                            'jumlah_satuan' => $alokasi->jumlah_satuan,
                                            'total_honor' => $alokasi->total_honor,
                                            'catatan' => $alokasi->catatan,
                                        ];
                                    });

                                    $sourcePeriode = [
                                        'bulan' => str_pad($request->copy_from_bulan, 2, '0', STR_PAD_LEFT),
                                        'tahun' => $request->copy_from_tahun,
                                        'tahapan' => $sourcePeriodeData->tahapan ?? 'both',
                                    ];
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Invalid data, just ignore
            }
        }

        // Calculate budget info for selected kegiatan
        $paguAnggaran = $selectedKegiatan ? $selectedKegiatan->anggaran : 0;
        $currentTotalSpent = $selectedKegiatan ? PeriodeAlokasi::where('kegiatan_id', $decodedId)
            ->where('tahun', $activeYear)
            ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
            ->with('alokasiPetugas')
            ->get()
            ->sum(function ($p) {
                return $p->alokasiPetugas->sum('total_honor');
            }) : 0;

        return Inertia::render('Alokasi/Create', [
            'kegiatans' => $kegiatans,
            'petugas' => $petugas,
            'selectedKegiatan' => $selectedKegiatan,
            'active_year' => $activeYear,
            'copiedAlokasi' => $copiedAlokasi,
            'sourcePeriode' => $sourcePeriode,
            'budget_info' => $budgetInfo,
            'used_months_info' => $usedMonthsInfo,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAlokasiPetugasRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $kegiatan = Kegiatan::findOrFail($data['kegiatan_id']);
        $hasListing = $kegiatan->has_listing_updating;

        // Calculate total honor for pencacahan
        $rateHonor = RateHonor::findOrFail($data['rate_honor_id']);
        $totalHonor = $rateHonor->rate * $data['jumlah_satuan'];

        // Calculate total honor for listing if present
        $jumlahSatuanListing = $data['jumlah_satuan_listing'] ?? null;
        $totalHonorListing = null;
        if ($hasListing && $jumlahSatuanListing !== null) {
            $totalHonorListing = ($rateHonor->rate_listing ?? 0) * $jumlahSatuanListing;
        }

        // Check SBML constraint for pencacahan
        $constraintError = $this->checkSbmlConstraint(
            $data['tahun'],
            $data['jenis_kegiatan'],
            $rateHonor->status_kepegawaian,
            $rateHonor->jenis_penugasan,
            $totalHonor
        );
        if ($constraintError) {
            return back()->withErrors(['sbml_constraint' => $constraintError])->withInput();
        }

        // Optionally check SBML for listing phase if needed

        $data['total_honor'] = $totalHonor;
        $data['total_honor_listing'] = $totalHonorListing;
        $data['jumlah_satuan_listing'] = $jumlahSatuanListing;
        $data['peran'] = $rateHonor->posisi;
        $data['status_kepegawaian'] = $rateHonor->status_kepegawaian;
        $data['submitted_by'] = effectiveUser($request)->id;

        AlokasiPetugas::create($data);

        return redirect()->route('alokasi.index')
            ->with('success', 'alokasi petugas berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AlokasiPetugas $alokasi): Response
    {
        $alokasi->load([
            'kegiatan.ketuaTim',
            'kegiatan.rateHonor.satuan',
            'petugas',
            'submittedBy',
            'approvedBy',
        ]);

        return Inertia::render('Alokasi/Show', [
            'alokasi' => $alokasi,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AlokasiPetugas $alokasi): Response
    {
        $kegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])
            ->select('id', 'kode_kegiatan', 'nama_kegiatan')
            ->get();

        $petugas = Petugas::where('status', 'aktif')
            ->select('id', 'nama', 'nik', 'email', 'jenis_petugas', 'jabatan', 'golongan')
            ->get();

        $rateHonors = RateHonor::with('satuan')
            ->where('status', 'aktif')
            ->where('tahun_berlaku', now()->year)
            ->get();

        return Inertia::render('Alokasi/Edit', [
            'alokasi' => $alokasi,
            'kegiatans' => $kegiatans,
            'petugas' => $petugas,
            'rateHonors' => $rateHonors,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAlokasiPetugasRequest $request, AlokasiPetugas $alokasi): RedirectResponse
    {
        $data = $request->validated();
        $kegiatan = Kegiatan::findOrFail($data['kegiatan_id']);
        $hasListing = $kegiatan->has_listing_updating;

        // Calculate total honor for pencacahan
        $rateHonor = RateHonor::findOrFail($data['rate_honor_id']);
        $totalHonor = $rateHonor->rate * $data['jumlah_satuan'];

        // Calculate total honor for listing if present
        $jumlahSatuanListing = $data['jumlah_satuan_listing'] ?? null;
        $totalHonorListing = null;
        if ($hasListing && $jumlahSatuanListing !== null) {
            $totalHonorListing = ($rateHonor->rate_listing ?? 0) * $jumlahSatuanListing;
        }

        // Check SBML constraint for pencacahan
        $constraintError = $this->checkSbmlConstraint(
            $data['tahun'],
            $data['jenis_kegiatan'],
            $rateHonor->status_kepegawaian,
            $rateHonor->jenis_penugasan,
            $totalHonor
        );
        if ($constraintError) {
            return back()->withErrors(['sbml_constraint' => $constraintError])->withInput();
        }

        $data['total_honor'] = $totalHonor;
        $data['total_honor_listing'] = $totalHonorListing;
        $data['jumlah_satuan_listing'] = $jumlahSatuanListing;
        $data['peran'] = $rateHonor->posisi;
        $data['status_kepegawaian'] = $rateHonor->status_kepegawaian;

        $alokasi->update($data);

        return redirect()->route('alokasi.index')
            ->with('success', 'alokasi petugas berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AlokasiPetugas $alokasi): RedirectResponse
    {
        $alokasi->delete();

        return redirect()->route('alokasi.index')
            ->with('success', 'alokasi petugas berhasil dihapus.');
    }

    /**
     * Submit alokasi for approval.
     */
    public function submit(Request $request, AlokasiPetugas $alokasi): RedirectResponse
    {
        if ($alokasi->status !== 'draft') {
            return back()->with('error', 'Hanya alokasi dengan status draft yang dapat diajukan.');
        }

        $alokasi->update([
            'status' => 'diajukan',
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Alokasi berhasil diajukan untuk persetujuan.');
    }

    /**
     * Approve alokasi.
     */
    public function approve(Request $request, AlokasiPetugas $alokasi): RedirectResponse
    {
        $effectiveUser = effectiveUser($request);
        if (! $effectiveUser->hasActiveRole('approver')) {
            return back()->with('error', 'Anda tidak memiliki akses untuk menyetujui alokasi.');
        }

        if (! in_array($alokasi->status, ['diajukan', 'disetujui_pj'])) {
            return back()->with('error', 'Hanya alokasi yang diajukan yang dapat disetujui.');
        }

        $validated = $request->validate([
            'catatan_approval' => 'nullable|string',
        ]);

        $alokasi->update([
            'status' => 'disetujui',
            'approved_by' => $effectiveUser->id,
            'approved_at' => now(),
            'catatan_approval' => $validated['catatan_approval'] ?? null,
        ]);

        return back()->with('success', 'Alokasi berhasil disetujui.');
    }

    /**
     * Reject alokasi.
     */
    public function reject(Request $request, AlokasiPetugas $alokasi): RedirectResponse
    {
        $effectiveUser = effectiveUser($request);
        if (! $effectiveUser->hasActiveRole('approver')) {
            return back()->with('error', 'Anda tidak memiliki akses untuk menolak alokasi.');
        }

        if (! in_array($alokasi->status, ['diajukan', 'disetujui_pj'])) {
            return back()->with('error', 'Hanya alokasi yang diajukan yang dapat ditolak.');
        }

        $validated = $request->validate([
            'catatan_approval' => 'required|string',
        ]);

        $alokasi->update([
            'status' => 'ditolak',
            'approved_by' => $effectiveUser->id,
            'approved_at' => now(),
            'catatan_approval' => $validated['catatan_approval'],
        ]);

        return back()->with('success', 'Alokasi ditolak.');
    }

    /**
     * Approve alokasi by Ketua Tim.
     */
    public function approvePj(Request $request, AlokasiPetugas $alokasi): RedirectResponse
    {
        $effectiveUser = effectiveUser($request);
        if (! $effectiveUser->hasActiveRole('ketua_tim')) {
            return back()->with('error', 'Anda tidak memiliki akses untuk menyetujui alokasi.');
        }

        // Check if user is the Ketua Tim of the kegiatan
        if ($alokasi->kegiatan->ketua_tim_user_id !== $effectiveUser->id) {
            return back()->with('error', 'Anda bukan ketua tim kegiatan ini.');
        }

        if ($alokasi->status !== 'diajukan') {
            return back()->with('error', 'Hanya alokasi yang diajukan yang dapat disetujui.');
        }

        $validated = $request->validate([
            'catatan_approval' => 'nullable|string',
        ]);

        $alokasi->update([
            'status' => 'disetujui_pj',
            'catatan_approval' => $validated['catatan_approval'] ?? null,
        ]);

        return back()->with('success', 'Alokasi berhasil disetujui. Menunggu persetujuan final dari Approver.');
    }

    /**
     * Submit all alokasi in a periode (kegiatan + bulan)
     */
    public function submitPeriode(Request $request, Kegiatan $kegiatan, int $tahun, string $bulan): RedirectResponse
    {
        // Allow submitting 'draft' or re-submitting 'perubahan'
        $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->whereIn('status', ['draft', 'perubahan'])
            ->firstOrFail();

        // If this is a revision (has parent_periode_id), keep status as 'perubahan'
        // Otherwise, set to 'dikirim' for first submission
        $newStatus = $periode->parent_periode_id ? 'perubahan' : 'dikirim';

        $periode->update([
            'status' => $newStatus,
            'submitted_by' => effectiveUser($request)->id,
            'submitted_at' => now(),
        ]);

        return redirect()->route('alokasi.index')
            ->with('success', 'Alokasi periode berhasil dikirim untuk pembuatan SK KPA dan SPK.');
    }

    /**
     * Show detail of a specific periode with all its alokasi
     */
    public function showPeriode(Kegiatan $kegiatan, string $tahun, string $bulan): Response
    {
        $tahun = (int) $tahun;

        // Get the latest periode
        $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
            ->orderByDesc('revision_number')
            ->with([
                'alokasiPetugas.petugas',
                'submittedBy:id,name',
            ])
            ->firstOrFail();

        // Calculate totals
        $totalEstimasiPencacahan = $periode->alokasiPetugas->sum('total_honor');
        $totalEstimasiListing = $periode->alokasiPetugas->sum('total_honor_listing');
        $totalEstimasi = $totalEstimasiPencacahan + $totalEstimasiListing;
        $jumlahPetugas = $periode->alokasiPetugas->count();

        // Format periode data
        $periodeData = [
            'id' => $periode->id,
            'kegiatan_id' => $periode->kegiatan_id,
            'bulan' => $periode->bulan,
            'tahun' => $periode->tahun,
            'jenis_kegiatan' => $periode->jenis_kegiatan,
            'status' => $periode->status,
            'revision_number' => $periode->revision_number,
            'parent_periode_id' => $periode->parent_periode_id,
            'submitted_at' => $periode->submitted_at,
            'submitted_by_name' => $periode->submittedBy?->name,
            'kegiatan' => [
                'id' => $kegiatan->id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'hashed_id' => $kegiatan->hashed_id,
                'has_listing_updating' => $kegiatan->has_listing_updating ?? false,
            ],
            'alokasi_petugas' => $periode->alokasiPetugas->map(function ($alokasi) {
                return [
                    'id' => $alokasi->id,
                    'petugas' => [
                        'id' => $alokasi->petugas->id,
                        'nama' => $alokasi->petugas->nama,
                        'jenis_petugas' => $alokasi->petugas->jenis_petugas,
                    ],
                    'peran' => $alokasi->peran,
                    'jumlah_satuan' => $alokasi->jumlah_satuan,
                    'jumlah_satuan_listing' => $alokasi->jumlah_satuan_listing,
                    'total_honor' => $alokasi->total_honor,
                    'total_honor_listing' => $alokasi->total_honor_listing,
                    'rate_pencacahan' => $alokasi->jumlah_satuan > 0
                        ? $alokasi->total_honor / $alokasi->jumlah_satuan
                        : 0,
                    'rate_listing' => ($alokasi->jumlah_satuan_listing ?? 0) > 0
                        ? ($alokasi->total_honor_listing ?? 0) / $alokasi->jumlah_satuan_listing
                        : 0,
                    'catatan' => $alokasi->catatan,
                ];
            }),
            'total_estimasi' => $totalEstimasi,
            'total_estimasi_pencacahan' => $totalEstimasiPencacahan,
            'total_estimasi_listing' => $totalEstimasiListing,
            'jumlah_petugas' => $jumlahPetugas,
        ];

        // Get revision history - include all previous versions (status 'direvisi' and older active versions)
        $revisions = [];

        // Get all periode with same kegiatan, tahun, bulan but different revision numbers or status 'direvisi'
        $allRevisions = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->where(function ($query) use ($periode) {
                // Get older revision numbers OR status 'direvisi'
                $query->where('revision_number', '<', $periode->revision_number)
                    ->orWhere('status', 'direvisi');
            })
            ->orderByDesc('revision_number')
            ->with([
                'alokasiPetugas.petugas',
                'submittedBy:id,name',
            ])
            ->get()
            ->map(function ($rev) {
                $totalPencacahan = $rev->alokasiPetugas->sum('total_honor');
                $totalListing = $rev->alokasiPetugas->sum('total_honor_listing');

                return [
                    'id' => $rev->id,
                    'revision_number' => $rev->revision_number,
                    'status' => $rev->status,
                    'submitted_at' => $rev->submitted_at,
                    'submitted_by_name' => $rev->submittedBy?->name,
                    'alokasi_petugas' => $rev->alokasiPetugas->map(function ($alokasi) {
                        return [
                            'id' => $alokasi->id,
                            'petugas' => [
                                'id' => $alokasi->petugas->id,
                                'nama' => $alokasi->petugas->nama,
                                'jenis_petugas' => $alokasi->petugas->jenis_petugas,
                            ],
                            'peran' => $alokasi->peran,
                            'jumlah_satuan' => $alokasi->jumlah_satuan,
                            'jumlah_satuan_listing' => $alokasi->jumlah_satuan_listing,
                            'total_honor' => $alokasi->total_honor,
                            'total_honor_listing' => $alokasi->total_honor_listing,
                            'rate_pencacahan' => $alokasi->jumlah_satuan > 0
                                ? $alokasi->total_honor / $alokasi->jumlah_satuan
                                : 0,
                            'rate_listing' => ($alokasi->jumlah_satuan_listing ?? 0) > 0
                                ? ($alokasi->total_honor_listing ?? 0) / $alokasi->jumlah_satuan_listing
                                : 0,
                            'catatan' => $alokasi->catatan,
                        ];
                    }),
                    'total_estimasi' => $totalPencacahan + $totalListing,
                    'total_estimasi_pencacahan' => $totalPencacahan,
                    'total_estimasi_listing' => $totalListing,
                    'jumlah_petugas' => $rev->alokasiPetugas->count(),
                ];
            })
            ->toArray();

        $revisions = $allRevisions;

        return Inertia::render('Alokasi/ShowPeriode', [
            'periode' => $periodeData,
            'revisions' => $revisions,
        ]);
    }

    /**
     * Edit all alokasi in a periode
     */
    public function editPeriode(Request $request, Kegiatan $kegiatan, int $tahun, string $bulan): Response|RedirectResponse
    {
        // Check if this is revisi mode from session
        $isRevisiMode = $request->session()->get('is_revisi_mode', false);

        if ($isRevisiMode) {
            // Load data from parent periode for revision
            $parentPeriodeId = $request->session()->get('revisi_parent_periode_id');
            $periode = PeriodeAlokasi::with(['alokasiPetugas.petugas'])->findOrFail($parentPeriodeId);
        } else {
            // Load existing draft/perubahan periode for editing
            $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->whereIn('status', ['draft', 'perubahan'])
                ->orderByDesc('revision_number')
                ->with(['alokasiPetugas.petugas'])
                ->firstOrFail();
        }

        if ($periode->alokasiPetugas->isEmpty()) {
            return redirect()->route('alokasi.index')
                ->with('error', 'Tidak ada alokasi untuk periode ini.');
        }

        // Load kegiatan with rate honors and satuan
        $kegiatanWithRates = Kegiatan::with(['rateHonors.satuan', 'rateHonors.satuanListing'])->findOrFail($kegiatan->id);

        // Load all petugas
        $petugas = Petugas::select('id', 'nama', 'jenis_petugas', 'golongan', 'jabatan')
            ->orderBy('nama')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'jenis_petugas' => $p->jenis_petugas,
                    'jabatan' => $p->jabatan,
                ];
            });

        // Convert existing alokasi to format expected by Manage view
        $existingAlokasi = $periode->alokasiPetugas->map(function ($alok) {
            return [
                'petugas_id' => $alok->petugas_id,
                'petugas_nama' => $alok->petugas->nama,
                'status_kepegawaian' => $alok->petugas->jenis_petugas,
                'peran' => $alok->peran,
                'jumlah_satuan' => $alok->jumlah_satuan,
                'jumlah_satuan_listing' => $alok->jumlah_satuan_listing,
                'total_honor' => $alok->total_honor,
                'total_honor_listing' => $alok->total_honor_listing,
                'catatan' => $alok->catatan,
            ];
        });

        // Get used months for this kegiatan to prevent duplicates (exclude current month being edited)
        $usedMonths = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->where('bulan', '!=', $bulan)
            ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
            ->pluck('bulan')
            ->map(fn ($b) => (int) $b)
            ->toArray();

        // Get active year
        $activeYear = ActiveYearService::get();

        // Calculate budget info - exclude current periode being edited
        $totalSpent = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('id', '!=', $periode->id) // Exclude current periode
            ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
            ->with('alokasiPetugas')
            ->get()
            ->sum(function ($p) {
                return $p->alokasiPetugas->sum('total_honor');
            });

        $totalSpentListing = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('id', '!=', $periode->id) // Exclude current periode
            ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
            ->with('alokasiPetugas')
            ->get()
            ->sum(function ($p) {
                return $p->alokasiPetugas->sum('total_honor_listing');
            });

        $budgetInfo = [
            $kegiatan->id => [
                'pagu_pencacahan' => $kegiatan->pagu_pencacahan ?? 0,
                'current_total_spent' => $totalSpent,
                'pagu_listing' => $kegiatan->pagu_listing ?? 0,
                'current_total_spent_listing' => $totalSpentListing,
            ],
        ];

        // Used months info
        $usedMonthsInfo = [
            $kegiatan->id => $usedMonths,
        ];

        // Keep revisi mode in session for updatePeriode
        // Don't forget it here, will be handled in updatePeriode

        return Inertia::render('Alokasi/Create', [
            'kegiatans' => [$kegiatanWithRates],
            'petugas' => $petugas,
            'selectedKegiatan' => $kegiatanWithRates,
            'active_year' => $activeYear,
            'copiedAlokasi' => $existingAlokasi,
            'sourcePeriode' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'tahapan' => $periode->tahapan ?? 'both',
                'tanggal_mulai' => $periode->tanggal_mulai?->format('Y-m-d'),
                'tanggal_selesai' => $periode->tanggal_selesai?->format('Y-m-d'),
                'tanggal_mulai_listing' => $periode->tanggal_mulai_listing?->format('Y-m-d'),
                'tanggal_selesai_listing' => $periode->tanggal_selesai_listing?->format('Y-m-d'),
            ],
            'budget_info' => $budgetInfo,
            'used_months_info' => $usedMonthsInfo,
            'isEditMode' => true,
            'isRevisiMode' => $isRevisiMode,
        ]);
    }

    /**
     * Update alokasi periode - replaces all alokasi for the periode
     */
    public function updatePeriode(Request $request, Kegiatan $kegiatan, string $tahun, string $bulan): RedirectResponse
    {
        // Convert tahun to int for consistency
        $tahun = (int) $tahun;

        // Check if kegiatan is approved
        if (! in_array($kegiatan->status, ['divalidasi', 'aktif'])) {
            return back()->with('error', 'Alokasi petugas hanya bisa diperbarui untuk kegiatan yang sudah divalidasi.');
        }

        // Ketua Tim can only update alokasi for their own kegiatan
        $effectiveUser = effectiveUser($request);
        if ($effectiveUser->isKetuaTim() && $kegiatan->ketua_tim_user_id !== $effectiveUser->id) {
            abort(403, 'Anda tidak memiliki akses untuk memperbarui alokasi kegiatan ini.');
        }

        // Validate that kegiatan has rate honors
        if ($kegiatan->rateHonors()->count() === 0) {
            return back()->withErrors([
                'rate_honor' => 'Kegiatan ini belum memiliki rate honor. Silakan set rate honor pada kegiatan terlebih dahulu.',
            ]);
        }

        $validated = $request->validate([
            'alokasi' => 'required|array|min:1',
            'alokasi.*.petugas_id' => 'required|exists:petugas,id',
            'alokasi.*.peran' => 'required|string|in:PCL,PML,Pengolahan,Pengawas Pengolahan',
            'alokasi.*.bulan' => 'required|integer|min:1|max:12',
            'alokasi.*.tahun' => 'required|integer|min:2020|max:2099',
            'alokasi.*.jumlah_satuan' => 'required|integer|min:0',
            'alokasi.*.jumlah_satuan_listing' => 'nullable|integer|min:0',
            'alokasi.*.jenis_kegiatan' => 'required|in:sensus,survei',
            'alokasi.*.tahapan' => 'nullable|in:both,listing_only,pencacahan_only',
            'alokasi.*.catatan' => 'nullable|string',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'tanggal_mulai_listing' => 'nullable|date',
            'tanggal_selesai_listing' => 'nullable|date|after_or_equal:tanggal_mulai_listing',
        ]);

        DB::beginTransaction();
        try {
            // Check if this is a revision from session
            $isRevision = $request->session()->get('is_revisi_mode', false);
            $parentPeriodeId = $request->session()->get('revisi_parent_periode_id');

            if ($isRevision && $parentPeriodeId) {
                $parentPeriode = PeriodeAlokasi::with('alokasiPetugas')->findOrFail($parentPeriodeId);

                // Get original alokasi from parent periode
                $originalAlokasi = $parentPeriode->alokasiPetugas->map(function ($a) {
                    return [
                        'petugas_id' => (int) $a->petugas_id,
                        'peran' => $a->peran,
                        'jumlah_satuan' => (int) $a->jumlah_satuan,
                    ];
                })->sortBy('petugas_id')->values()->all();

                // Format new alokasi for comparison
                $newAlokasi = collect($validated['alokasi'])->map(function ($a) {
                    return [
                        'petugas_id' => (int) $a['petugas_id'],
                        'peran' => match ($a['peran']) {
                            'PCL' => 'pcl_ppl',
                            'PML' => 'pml',
                            'Pengolahan' => 'pengolahan',
                            'Pengawas Pengolahan' => 'pengawas_pengolahan',
                            default => null,
                        },
                        'jumlah_satuan' => (int) $a['jumlah_satuan'],
                    ];
                })->sortBy('petugas_id')->values()->all();

                // Check if there are changes
                $hasChanges = json_encode($originalAlokasi) !== json_encode($newAlokasi);
                // If no changes, just redirect without creating anything
                if (! $hasChanges) {
                    // Clear session
                    $request->session()->forget(['is_revisi_mode', 'revisi_parent_periode_id', 'revisi_kegiatan_id', 'revisi_tahun', 'revisi_bulan']);

                    DB::commit();

                    return redirect()->route('alokasi.index')
                        ->with('info', 'Tidak ada perubahan data. Revisi dibatalkan.');
                }

                // If there are changes, create new periode with 'perubahan' status
                $revisionNumber = ($parentPeriode->revision_number ?? 0) + 1;

                $periode = PeriodeAlokasi::create([
                    'kegiatan_id' => $parentPeriode->kegiatan_id,
                    'parent_periode_id' => $parentPeriode->parent_periode_id ?? $parentPeriode->id,
                    'revision_number' => $revisionNumber,
                    'bulan' => $parentPeriode->bulan,
                    'tahun' => $parentPeriode->tahun,
                    'jenis_kegiatan' => $parentPeriode->jenis_kegiatan,
                    'tahapan' => $validated['alokasi'][0]['tahapan'] ?? $parentPeriode->tahapan ?? 'both',
                    'status' => 'perubahan',
                ]);

                // Mark parent as 'direvisi'
                $parentPeriode->update(['status' => 'direvisi']);

                // Clear session
                $request->session()->forget(['is_revisi_mode', 'revisi_parent_periode_id', 'revisi_kegiatan_id', 'revisi_tahun', 'revisi_bulan']);

                // Now create alokasi for new periode (continue to loop below)
            } else {
                // Normal edit - find existing periode

                $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                    ->where('tahun', $tahun)
                    ->where('bulan', $bulan)
                    ->whereIn('status', ['draft', 'perubahan'])
                    ->orderByDesc('revision_number')
                    ->first();

                if (! $periode) {
                    DB::rollBack();

                    return back()->withErrors(['periode' => 'Periode tidak ditemukan atau tidak dapat diedit.']);
                }

                // Update tahapan field
                $periode->update([
                    'tahapan' => $validated['alokasi'][0]['tahapan'] ?? 'both',
                ]);

                // Delete existing alokasi for update
                AlokasiPetugas::where('periode_alokasi_id', $periode->id)->delete();
            }

            // Create new alokasi entries (only executed if not early return above)

            $errors = [];
            $created = 0;

            foreach ($validated['alokasi'] as $index => $alokasiData) {
                // Get petugas to determine jenis_petugas
                $petugas = Petugas::find($alokasiData['petugas_id']);
                if (! $petugas) {
                    $errors[] = 'Petugas tidak ditemukan.';

                    continue;
                }

                // Map peran to jenis_penugasan
                $jenisPenugasan = match ($alokasiData['peran']) {
                    'PCL' => 'pcl_ppl',
                    'PML' => 'pml',
                    'Pengolahan' => 'pengolahan',
                    'Pengawas Pengolahan' => 'pengawas_pengolahan',
                    default => null,
                };

                if (! $jenisPenugasan) {
                    $errors[] = $petugas->nama.': Peran tidak valid.';

                    continue;
                }

                // Get rate honor for this petugas
                $petugasType = $petugas->jenis_petugas === 'organik' ? 'organik' : 'non_organik';
                $rateHonor = $kegiatan->rateHonors()
                    ->where('status_kepegawaian', $petugasType)
                    ->where('jenis_penugasan', $jenisPenugasan)
                    ->first();

                if (! $rateHonor) {
                    $errors[] = $petugas->nama.': Rate honor tidak ditemukan untuk ('.$petugasType.') sebagai '.$alokasiData['peran'];

                    continue;
                }

                // Calculate pencacahan honor (can be 0 if listing_only)
                $totalHonor = $rateHonor->rate * $alokasiData['jumlah_satuan'];

                // Calculate listing honor if kegiatan has listing phase
                $totalHonorListing = 0;
                $jumlahSatuanListing = 0;
                if ($kegiatan->has_listing_updating) {
                    $jumlahSatuanListing = $alokasiData['jumlah_satuan_listing'] ?? 0;
                    if ($jumlahSatuanListing > 0 && $rateHonor->rate_listing) {
                        $totalHonorListing = $rateHonor->rate_listing * $jumlahSatuanListing;
                    }
                }

                // Check SBML constraint per assignment (skip if honor is 0)
                if ($totalHonor > 0) {
                    $constraintError = $this->checkSbmlConstraint(
                        (int) $tahun,
                        $kegiatan->jenis_kegiatan,
                        $petugasType,
                        $jenisPenugasan,
                        $totalHonor
                    );

                    if ($constraintError) {
                        $errors[] = $petugas->nama.': '.$constraintError;

                        continue;
                    }
                }

                // Check petugas total honor in month across all assignments (skip if honor is 0)
                // For edit/revision, exclude current periode from calculation
                if ($totalHonor > 0) {
                    $petugasTotalError = $this->checkPetugasTotalHonorInMonth(
                        $alokasiData['petugas_id'],
                        (int) $tahun,
                        (int) $bulan,
                        $totalHonor,
                        $periode->id,
                        $jenisPenugasan,
                        $kegiatan->jenis_kegiatan,
                        $petugasType
                    );

                    if ($petugasTotalError) {
                        $errors[] = $petugas->nama.': '.$petugasTotalError;

                        continue;
                    }
                }

                // Create new alokasi
                AlokasiPetugas::create([
                    'periode_alokasi_id' => $periode->id,
                    'petugas_id' => $alokasiData['petugas_id'],
                    'jumlah_satuan' => $alokasiData['jumlah_satuan'],
                    'jumlah_satuan_listing' => $jumlahSatuanListing,
                    'total_honor' => $totalHonor,
                    'total_honor_listing' => $totalHonorListing,
                    'peran' => $jenisPenugasan,
                    'status_kepegawaian' => $petugasType,
                    'catatan' => $alokasiData['catatan'] ?? null,
                ]);

                $created++;
            }

            // Check if there were any errors during validation
            if (! empty($errors)) {
                DB::rollBack();

                return back()->withErrors([
                    'alokasi' => 'Terdapat kesalahan pada alokasi petugas: '.implode(' | ', $errors),
                ])->withInput();
            }

            // Recalculate periode total and sisa_pagu
            $periode->load('alokasiPetugas');
            $newPeriodeTotalHonor = $periode->alokasiPetugas->sum('total_honor');
            $newPeriodeTotalHonorListing = $periode->alokasiPetugas->sum('total_honor_listing');

            // Calculate sisa_pagu based on previous periods
            $kegiatan->load('periodeAlokasi.alokasiPetugas');
            $paguAnggaran = $kegiatan->pagu_pencacahan ?? 0;
            $paguListing = $kegiatan->pagu_listing ?? 0;

            // For revision, we need to adjust calculation
            if ($isRevision && $parentPeriodeId) {
                // Get parent periode total (old value that was revised)
                $parentPeriode = PeriodeAlokasi::with('alokasiPetugas')->find($parentPeriodeId);
                $oldPeriodeTotalHonor = $parentPeriode ? $parentPeriode->alokasiPetugas->sum('total_honor') : 0;
                $oldPeriodeTotalHonorListing = $parentPeriode ? $parentPeriode->alokasiPetugas->sum('total_honor_listing') : 0;

                // Find periode BEFORE the parent periode (the one that was just revised)
                $previousPeriode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                    ->where(function ($query) use ($tahun, $bulan) {
                        $query->where('tahun', '<', $tahun)
                            ->orWhere(function ($q) use ($tahun, $bulan) {
                                $q->where('tahun', $tahun)
                                    ->where('bulan', '<', $bulan);
                            });
                    })
                    ->where('id', '!=', $parentPeriodeId) // Exclude parent periode
                    ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                    ->orderByDesc('tahun')
                    ->orderByDesc('bulan')
                    ->first();

                // Sisa pagu = (sisa pagu periode sebelumnya) + (total honor lama yang direvisi) - (total honor baru)
                // This way, we "add back" the old allocation and subtract the new one
                $sisaPaguPeriode = $previousPeriode
                    ? $previousPeriode->sisa_pagu + $oldPeriodeTotalHonor - $newPeriodeTotalHonor
                    : $paguAnggaran - $newPeriodeTotalHonor;

                $sisaPaguPeriodeListing = $previousPeriode
                    ? ($previousPeriode->sisa_pagu_listing ?? 0) + $oldPeriodeTotalHonorListing - $newPeriodeTotalHonorListing
                    : $paguListing - $newPeriodeTotalHonorListing;
            } else {
                // Normal edit - use standard calculation
                $previousPeriode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                    ->where(function ($query) use ($tahun, $bulan) {
                        $query->where('tahun', '<', $tahun)
                            ->orWhere(function ($q) use ($tahun, $bulan) {
                                $q->where('tahun', $tahun)
                                    ->where('bulan', '<', $bulan);
                            });
                    })
                    ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                    ->orderByDesc('tahun')
                    ->orderByDesc('bulan')
                    ->first();

                $sisaPaguPeriode = $previousPeriode
                    ? $previousPeriode->sisa_pagu - $newPeriodeTotalHonor
                    : $paguAnggaran - $newPeriodeTotalHonor;

                $sisaPaguPeriodeListing = $previousPeriode
                    ? ($previousPeriode->sisa_pagu_listing ?? 0) - $newPeriodeTotalHonorListing
                    : $paguListing - $newPeriodeTotalHonorListing;
            }

            $periode->update([
                'sisa_pagu' => $sisaPaguPeriode,
                'sisa_pagu_listing' => $sisaPaguPeriodeListing,
                'tanggal_mulai' => $validated['tanggal_mulai'] ?? $periode->tanggal_mulai,
                'tanggal_selesai' => $validated['tanggal_selesai'] ?? $periode->tanggal_selesai,
                'tanggal_mulai_listing' => $validated['tanggal_mulai_listing'] ?? $periode->tanggal_mulai_listing,
                'tanggal_selesai_listing' => $validated['tanggal_selesai_listing'] ?? $periode->tanggal_selesai_listing,
            ]);

            // Always recalculate sisa_pagu for all subsequent periods when any periode is updated
            // This ensures that changes to any periode cascade correctly to future periods
            $subsequentPeriods = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where(function ($query) use ($tahun, $bulan) {
                    $query->where('tahun', '>', $tahun)
                        ->orWhere(function ($q) use ($tahun, $bulan) {
                            $q->where('tahun', $tahun)
                                ->where('bulan', '>', $bulan);
                        });
                })
                ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                ->orderBy('tahun')
                ->orderBy('bulan')
                ->get();

            // Recalculate sisa_pagu for all subsequent periods sequentially
            if ($subsequentPeriods->isNotEmpty()) {
                $currentSisaPagu = $sisaPaguPeriode;
                $currentSisaPaguListing = $sisaPaguPeriodeListing;

                foreach ($subsequentPeriods as $nextPeriode) {
                    $nextPeriode->load('alokasiPetugas');
                    $nextPeriodeTotal = $nextPeriode->alokasiPetugas->sum('total_honor');
                    $nextPeriodeTotalListing = $nextPeriode->alokasiPetugas->sum('total_honor_listing');

                    $currentSisaPagu = $currentSisaPagu - $nextPeriodeTotal;
                    $currentSisaPaguListing = $currentSisaPaguListing - $nextPeriodeTotalListing;

                    $nextPeriode->update([
                        'sisa_pagu' => $currentSisaPagu,
                        'sisa_pagu_listing' => $currentSisaPaguListing,
                    ]);
                }
            }

            DB::commit();

            if (count($errors) > 0) {
                return back()->withErrors(['validation' => $errors])
                    ->with('warning', "Berhasil memperbarui {$created} alokasi, namun ada beberapa yang gagal.");
            }

            return redirect()->route('alokasi.index')
                ->with('success', 'Alokasi periode berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memperbarui alokasi: '.$e->getMessage());
        }
    }

    /**
     * Mark periode as deleted (status = dihapus)
     */
    public function destroyPeriode(Request $request, Kegiatan $kegiatan, int $tahun, string $bulan): RedirectResponse
    {
        // Allow deleting 'draft' or 'perubahan' that hasn't been submitted yet
        $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->whereIn('status', ['draft', 'perubahan'])
            ->first();

        if (! $periode) {
            return back()->with('error', 'Tidak ada alokasi draft yang dapat dibatalkan.');
        }

        // If this is a revision being deleted, restore the parent periode status
        if ($periode->parent_periode_id) {
            $parentPeriode = PeriodeAlokasi::find($periode->parent_periode_id);
            if ($parentPeriode && $parentPeriode->status === 'direvisi') {
                // Restore parent to 'dikirim' or 'perubahan' based on whether it has parent
                $parentPeriode->update([
                    'status' => $parentPeriode->parent_periode_id ? 'perubahan' : 'dikirim',
                ]);
            }
        }

        // Delete the periode and its alokasi
        $periode->alokasiPetugas()->delete();
        $periode->delete();

        return redirect()->route('alokasi.index')
            ->with('success', 'Alokasi periode berhasil dibatalkan.');
    }

    /**
     * Revisi: Prepare revision data in session without creating database records
     */
    public function revisiPeriode(Request $request, Kegiatan $kegiatan, int $tahun, string $bulan): RedirectResponse
    {
        // Get existing periode (could be original 'dikirim' or previous 'perubahan')
        $oldPeriode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->whereIn('status', ['dikirim', 'perubahan'])
            ->with('alokasiPetugas')
            ->first();

        if (! $oldPeriode) {
            return back()->with('error', 'Tidak ada alokasi terkirim untuk direvisi.');
        }

        // Store parent periode info in session for later comparison
        $request->session()->put('revisi_parent_periode_id', $oldPeriode->id);
        $request->session()->put('revisi_kegiatan_id', $kegiatan->id);
        $request->session()->put('revisi_tahun', $tahun);
        $request->session()->put('revisi_bulan', $bulan);
        $request->session()->put('is_revisi_mode', true);

        // Redirect to edit page - will load data from parent periode
        return redirect('/alokasi/periode/'.$kegiatan->hashed_id.'/'.$tahun.'/'.$bulan.'/edit')
            ->with('success', 'Mode revisi. Silakan edit data sesuai kebutuhan.');
    }

    /**
     * Check if total honor exceeds SBML maximum constraint
     */
    private function checkSbmlConstraint(
        int $tahun,
        string $jenisKegiatan,
        string $statusKepegawaian,
        string $jenisPenugasan,
        float $totalHonor
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

        if ($totalHonor > $sbml->honor_max) {
            return 'Total honor (Rp '.number_format($totalHonor, 0, ',', '.').') melebihi batas maksimal SBML (Rp '.number_format($sbml->honor_max, 0, ',', '.').") untuk tahun {$tahun}.";
        }

        return null;
    }

    /**
     * Check if petugas total honor in a month exceeds their maximum SBML limit
     * across all their assignments (kegiatan)
     * Now checks SBML based on jenis penugasan from allocations
     */
    private function checkPetugasTotalHonorInMonth(
        int $petugasId,
        int $tahun,
        int $bulan,
        float $newHonor,
        ?int $excludePeriodeId = null,
        ?string $newPeran = null,
        ?string $newJenisKegiatan = null,
        ?string $newStatusKepegawaian = null
    ): ?string {
        $petugas = Petugas::find($petugasId);
        if (! $petugas) {
            return 'Petugas tidak ditemukan.';
        }

        // Get all existing allocations for this petugas in this month
        $existingAlokasis = AlokasiPetugas::whereHas('periodeAlokasi', function ($query) use ($tahun, $bulan, $excludePeriodeId) {
            $query->where('tahun', $tahun)
                ->where('bulan', str_pad($bulan, 2, '0', STR_PAD_LEFT))
                ->whereIn('status', ['draft', 'dikirim', 'perubahan']);

            if ($excludePeriodeId) {
                $query->where('id', '!=', $excludePeriodeId);
            }
        })
            ->where('petugas_id', $petugasId)
            ->get();

        // Calculate existing total honor
        $existingTotalHonor = $existingAlokasis->sum('total_honor');
        $totalHonorInMonth = $existingTotalHonor + $newHonor;

        // Collect all jenis penugasan (peran) from existing allocations
        $jenisPenugasanList = $existingAlokasis->pluck('peran')->unique();

        // Add new peran if provided
        if ($newPeran) {
            $jenisPenugasanList->push($newPeran);
            $jenisPenugasanList = $jenisPenugasanList->unique();
        }

        // Map peran ke jenis_penugasan dan ambil honor_max SBML untuk tiap penugasan yang sudah diberikan
        $statusKepegawaian = $petugas->jenis_petugas === 'organik' ? 'organik' : 'non_organik';
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
            $sbml = \App\Models\Sbml::where('tahun_anggaran', $tahun)
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
                \Carbon\Carbon::create()->month($bulan)->translatedFormat('F'),
                $tahun,
                number_format($totalHonorInMonth, 0, ',', '.'),
                number_format($minAllowed, 0, ',', '.'),
                number_format($existingTotalHonor, 0, ',', '.'),
                number_format($newHonor, 0, ',', '.')
            );
        }

        return null;
    }
}
