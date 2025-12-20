<?php

namespace App\Http\Controllers;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Spk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SpkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $activeYear = \App\Services\ActiveYearService::get();

        // Get periode alokasi yang sudah validated grouped by month
        $query = PeriodeAlokasi::query()
            ->with([
                'kegiatan:id,kode_kegiatan,nama_kegiatan,jenis_kegiatan,tahun_anggaran',
                'alokasiPetugas.petugas:id,nama,nik,jenis_petugas',
                'spk' => function ($q) {
                    $q->orderBy('created_at', 'desc');
                },
            ])
            ->whereHas('kegiatan', function ($q) use ($activeYear) {
                $q->where('tahun_anggaran', $activeYear);
            })
            ->whereIn('status', ['dikirim', 'disetujui'])
            ->where('tahun', $activeYear);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('kegiatan', function ($q) use ($search) {
                $q->where('nama_kegiatan', 'like', "%{$search}%")
                    ->orWhere('kode_kegiatan', 'like', "%{$search}%");
            });
        }

        // Filter by bulan
        if ($request->filled('bulan')) {
            $query->where('bulan', $request->bulan);
        }

        $periodes = $query->latest()->get();

        // Group by month and year
        $groupedByMonth = $periodes->groupBy(function ($periode) {
            return $periode->tahun.'-'.$periode->bulan;
        })->map(function ($monthPeriodes, $key) {
            [$tahun, $bulan] = explode('-', $key);

            // Count total non-organik petugas across all kegiatan in this month
            $totalPetugasNonOrganik = $monthPeriodes->sum(function ($periode) {
                return $periode->alokasiPetugas->filter(function ($alokasi) {
                    return $alokasi->petugas->jenis_petugas === 'non_organik';
                })->count();
            });

            // Count total SPK created
            $totalSpk = $monthPeriodes->sum(function ($periode) {
                return $periode->spk->count();
            });

            // Get all kegiatan in this month
            $kegiatanList = $monthPeriodes->map(function ($periode) {
                return [
                    'periode_id' => $periode->id,
                    'periode_hashed_id' => $periode->hashed_id,
                    'kegiatan_hashed_id' => $periode->kegiatan->hashed_id,
                    'kode_kegiatan' => $periode->kegiatan->kode_kegiatan,
                    'nama_kegiatan' => $periode->kegiatan->nama_kegiatan,
                    'jenis_kegiatan' => $periode->kegiatan->jenis_kegiatan,
                    'jumlah_petugas_non_organik' => $periode->alokasiPetugas->filter(function ($alokasi) {
                        return $alokasi->petugas->jenis_petugas === 'non_organik';
                    })->count(),
                    'spk_count' => $periode->spk->count(),
                ];
            })->values();

            // SPK status for the month
            $spkStatus = $totalSpk > 0 ? 'Sudah Dibuat' : 'Belum Dibuat';
            $spkStatusType = $totalSpk > 0 ? 'created' : 'not_created';

            return [
                'tahun' => (int) $tahun,
                'bulan' => (int) $bulan,
                'bulan_label' => $this->getBulanLabel((int) $bulan),
                'total_petugas_non_organik' => $totalPetugasNonOrganik,
                'total_spk' => $totalSpk,
                'spk_status' => $spkStatus,
                'spk_status_type' => $spkStatusType,
                'kegiatan_list' => $kegiatanList,
            ];
        })->sortByDesc(function ($item) {
            return $item['tahun'].str_pad($item['bulan'], 2, '0', STR_PAD_LEFT);
        })->values();

        // Paginate manually
        $perPage = 15;
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;

        $paginatedItems = $groupedByMonth->slice($offset, $perPage)->values();
        $total = $groupedByMonth->count();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Spk/Index', [
            'periodeList' => $paginator,
            'filters' => $request->only(['search', 'bulan']),
        ]);
    }

    /**
     * Show the form to generate SPKs for a periode
     */
    public function create(string $periodeHashedId): Response
    {
        $periodeId = \Vinkla\Hashids\Facades\Hashids::decode($periodeHashedId)[0] ?? null;

        if (! $periodeId) {
            abort(404);
        }

        $periode = PeriodeAlokasi::with([
            'kegiatan',
        ])->findOrFail($periodeId);

        // Check if there are any draft periode in the same month
        $hasDraftPeriode = PeriodeAlokasi::where('bulan', $periode->bulan)
            ->where('tahun', $periode->tahun)
            ->where('status', 'draft')
            ->exists();

        // Get all periode alokasi in the same month and year
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $periode->bulan)
            ->where('tahun', $periode->tahun)
            ->whereIn('status', ['dikirim', 'disetujui'])
            ->pluck('id');

        // Get all unique non-organik petugas from all alokasi in this month
        $petugasList = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->with(['petugas'])
            ->get()
            ->filter(function ($alokasi) {
                return $alokasi->petugas && $alokasi->petugas->jenis_petugas === 'non-organik';
            })
            ->unique(function ($alokasi) {
                // Unique by petugas_id to remove duplicates
                return $alokasi->petugas_id;
            })
            ->sortBy(function ($alokasi) {
                return $alokasi->petugas->nama;
            })
            ->values()
            ->map(function ($alokasi) {
                return [
                    'alokasi_id' => $alokasi->id,
                    'alokasi_hashed_id' => $alokasi->hashed_id,
                    'petugas' => [
                        'id' => $alokasi->petugas->id,
                        'hashed_id' => $alokasi->petugas->hashed_id,
                        'nama' => $alokasi->petugas->nama,
                        'nik' => $alokasi->petugas->nik,
                        'jenis_petugas' => $alokasi->petugas->jenis_petugas,
                    ],
                    'peran' => $alokasi->peran,
                    'target_listing' => $alokasi->target_listing,
                    'target_pencacahan' => $alokasi->target_pencacahan,
                ];
            });

        return Inertia::render('Spk/Generate', [
            'periode' => [
                'id' => $periode->id,
                'hashed_id' => $periode->hashed_id,
                'tahun' => $periode->tahun,
                'bulan' => $periode->bulan,
                'bulan_label' => $this->getBulanLabel($periode->bulan),
                'kegiatan' => [
                    'hashed_id' => $periode->kegiatan->hashed_id,
                    'kode_kegiatan' => $periode->kegiatan->kode_kegiatan,
                    'nama_kegiatan' => $periode->kegiatan->nama_kegiatan,
                    'jenis_kegiatan' => $periode->kegiatan->jenis_kegiatan,
                    'tahun_anggaran' => $periode->kegiatan->tahun_anggaran,
                ],
            ],
            'petugas_list' => $petugasList,
            'has_draft_periode' => $hasDraftPeriode,
        ]);
    }

    /**
     * Display the specified SPK
     */
    public function show(string $spkHashedId): Response
    {
        $spkId = \Vinkla\Hashids\Facades\Hashids::decode($spkHashedId)[0] ?? null;

        if (! $spkId) {
            abort(404);
        }

        $spk = Spk::with([
            'alokasiPetugas.petugas',
            'alokasiPetugas.periodeAlokasi.kegiatan',
            'bast' => function ($q) {
                $q->latest();
            },
            'createdBy',
        ])->findOrFail($spkId);

        $periode = $spk->alokasiPetugas->periodeAlokasi;
        $petugas = $spk->alokasiPetugas->petugas;
        $kegiatan = $periode->kegiatan;
        $bast = $spk->bast->first();

        return Inertia::render('Spk/Show', [
            'spk' => [
                'id' => $spk->id,
                'hashed_id' => $spk->hashed_id,
                'nomor_spk' => $spk->nomor_spk,
                'tanggal_spk' => $spk->tanggal_spk,
                'tanggal_mulai_kerja' => $spk->tanggal_mulai_kerja,
                'tanggal_selesai_kerja' => $spk->tanggal_selesai_kerja,
                'nilai_kontrak' => $spk->nilai_kontrak,
                'nama_ppk' => $spk->nama_ppk,
                'nip_ppk' => $spk->nip_ppk,
                'status' => $spk->status,
                'file_path' => $spk->file_path,
                'created_by' => $spk->createdBy->name ?? 'System',
                'created_at' => $spk->created_at->format('d M Y H:i'),
            ],
            'petugas' => [
                'id' => $petugas->id,
                'hashed_id' => $petugas->hashed_id,
                'nama' => $petugas->nama,
                'nik' => $petugas->nik,
                'jenis_petugas' => $petugas->jenis_petugas,
                'alamat' => $petugas->alamat,
            ],
            'kegiatan' => [
                'id' => $kegiatan->id,
                'hashed_id' => $kegiatan->hashed_id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                'tahun_anggaran' => $kegiatan->tahun_anggaran,
            ],
            'periode' => [
                'id' => $periode->id,
                'hashed_id' => $periode->hashed_id,
                'bulan' => $periode->bulan,
                'tahun' => $periode->tahun,
            ],
            'bast' => $bast ? [
                'id' => $bast->id,
                'hashed_id' => $bast->hashed_id,
                'nomor_bast' => $bast->nomor_bast,
                'tanggal_bast' => $bast->tanggal_bast,
                'file_path' => $bast->file_path,
            ] : null,
        ]);
    }

    /**
     * Preview SPK for a petugas in a periode
     */
    public function previewSpk(Request $request, string $periodeHashedId, string $petugasHashedId)
    {
        $periodeId = \Vinkla\Hashids\Facades\Hashids::decode($periodeHashedId)[0] ?? null;
        $petugasId = \Vinkla\Hashids\Facades\Hashids::decode($petugasHashedId)[0] ?? null;

        if (! $periodeId || ! $petugasId) {
            abort(404);
        }

        $validated = $request->validate([
            'nomor_spk' => ['required', 'string', 'max:255'],
            'tanggal_spk' => ['required', 'date'],
            'sampai_tanggal' => ['required', 'date', 'after_or_equal:tanggal_spk'],
        ]);

        $periode = PeriodeAlokasi::with(['kegiatan', 'alokasiPetugas.petugas'])->findOrFail($periodeId);

        // Get all alokasi for this petugas in the same month
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun);
            })
            ->where('petugas_id', $petugasId)
            ->get();

        if ($allAlokasi->isEmpty()) {
            abort(404, 'Tidak ada alokasi untuk petugas ini');
        }

        $petugas = $allAlokasi->first()->petugas;

        // Get active Kepala BPS
        $penandatangan = Penandatangan::active()->ppk()->firstOrFail();

        // Get total honor for this petugas across all kegiatan
        $totalHonor = 0;
        $uraianTugas = [];
        $bebanAnggaran = '';

        foreach ($allAlokasi as $alokasi) {
            $kegiatan = $alokasi->periodeAlokasi->kegiatan;
            $totalHonor += $this->calculateTotalHonor($kegiatan, $alokasi);
            $uraianTugas = array_merge($uraianTugas, $this->getUraianTugas($kegiatan, $alokasi));
            if (empty($bebanAnggaran)) {
                $bebanAnggaran = $this->getBebanAnggaran($kegiatan);
            }
        }

        $data = [
            'periode' => $periode,
            'alokasi' => $allAlokasi->first(),
            'petugas' => $petugas,
            'kegiatan' => $periode->kegiatan,
            'nomorSpk' => $validated['nomor_spk'],
            'tanggalSpk' => \Carbon\Carbon::parse($validated['tanggal_spk']),
            'sampaiTanggal' => \Carbon\Carbon::parse($validated['sampai_tanggal']),
            'penandatangan' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'peranLabel' => $this->getPeranLabel($allAlokasi->first()->peran),
            'totalHonor' => $totalHonor,
            'uraianTugas' => $uraianTugas,
            'bebanAnggaran' => $bebanAnggaran,
        ];

        // Generate 2 separate PDFs and merge them (SPK Main + Lampiran only)
        $pdfMain = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-main', $data)
            ->setPaper('a4', 'portrait');

        $pdfLampiran = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-lampiran', $data)
            ->setPaper('a4', 'landscape');

        // Save temporary PDFs
        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        $timestamp = time().'_'.uniqid();
        $mainPath = $tempPath.'/spk_main_'.$timestamp.'.pdf';
        $lampiranPath = $tempPath.'/spk_lampiran_'.$timestamp.'.pdf';
        $mergedPath = $tempPath.'/spk_merged_'.$timestamp.'.pdf';

        file_put_contents($mainPath, $pdfMain->output());
        file_put_contents($lampiranPath, $pdfLampiran->output());

        // Try to merge PDFs
        $merged = \App\Services\PdfMergerService::mergePdfFiles(
            [$mainPath, $lampiranPath],
            $mergedPath
        );

        if ($merged && file_exists($mergedPath)) {
            $pdfContent = file_get_contents($mergedPath);

            // Cleanup temporary files
            @unlink($mainPath);
            @unlink($lampiranPath);
            @unlink($mergedPath);

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="Preview_SPK_'.$petugas->nama.'.pdf"');
        }

        // Cleanup temporary files
        @unlink($mainPath);
        @unlink($lampiranPath);

        // Fallback: return main PDF only if merge failed
        return response($pdfMain->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Preview_SPK_'.$petugas->nama.'.pdf"');
    }

    /**
     * Preview SPK Main only
     */
    public function previewSpkMain(Request $request, string $periodeHashedId, string $petugasHashedId)
    {
        $periodeId = \Vinkla\Hashids\Facades\Hashids::decode($periodeHashedId)[0] ?? null;
        $petugasId = \Vinkla\Hashids\Facades\Hashids::decode($petugasHashedId)[0] ?? null;

        if (! $periodeId || ! $petugasId) {
            abort(404);
        }

        $validated = $request->validate([
            'nomor_spk' => ['required', 'string', 'max:255'],
            'tanggal_spk' => ['required', 'date'],
            'sampai_tanggal' => ['required', 'date', 'after_or_equal:tanggal_spk'],
        ]);

        $periode = PeriodeAlokasi::with(['kegiatan', 'alokasiPetugas.petugas'])->findOrFail($periodeId);

        // Get all alokasi for this petugas in the same month
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun);
            })
            ->where('petugas_id', $petugasId)
            ->get();

        if ($allAlokasi->isEmpty()) {
            abort(404, 'Tidak ada alokasi untuk petugas ini');
        }

        $petugas = $allAlokasi->first()->petugas;

        // Get active Kepala BPS
        $penandatangan = Penandatangan::active()->ppk()->firstOrFail();

        // Get total honor for this petugas across all kegiatan
        $totalHonor = 0;
        $uraianTugas = [];
        $bebanAnggaran = '';

        foreach ($allAlokasi as $alokasi) {
            $kegiatan = $alokasi->periodeAlokasi->kegiatan;
            $totalHonor += $this->calculateTotalHonor($kegiatan, $alokasi);
            $uraianTugas = array_merge($uraianTugas, $this->getUraianTugas($kegiatan, $alokasi));
            if (empty($bebanAnggaran)) {
                $bebanAnggaran = $this->getBebanAnggaran($kegiatan);
            }
        }

        $data = [
            'periode' => $periode,
            'alokasi' => $allAlokasi->first(),
            'petugas' => $petugas,
            'kegiatan' => $periode->kegiatan,
            'nomorSpk' => $validated['nomor_spk'],
            'tanggalSpk' => \Carbon\Carbon::parse($validated['tanggal_spk']),
            'sampaiTanggal' => \Carbon\Carbon::parse($validated['sampai_tanggal']),
            'penandatangan' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'peranLabel' => $this->getPeranLabel($allAlokasi->first()->peran),
            'totalHonor' => $totalHonor,
            'uraianTugas' => $uraianTugas,
            'bebanAnggaran' => $bebanAnggaran,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-main', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->stream('Preview_SPK_Main_'.$petugas->nama.'.pdf');
    }

    /**
     * Preview SPK Lampiran only
     */
    public function previewSpkLampiran(Request $request, string $periodeHashedId, string $petugasHashedId)
    {
        $periodeId = \Vinkla\Hashids\Facades\Hashids::decode($periodeHashedId)[0] ?? null;
        $petugasId = \Vinkla\Hashids\Facades\Hashids::decode($petugasHashedId)[0] ?? null;

        if (! $periodeId || ! $petugasId) {
            abort(404);
        }

        $validated = $request->validate([
            'nomor_spk' => ['required', 'string', 'max:255'],
            'tanggal_spk' => ['required', 'date'],
            'sampai_tanggal' => ['required', 'date', 'after_or_equal:tanggal_spk'],
        ]);

        $periode = PeriodeAlokasi::with(['kegiatan', 'alokasiPetugas.petugas'])->findOrFail($periodeId);

        // Get all alokasi for this petugas in the same month
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun);
            })
            ->where('petugas_id', $petugasId)
            ->get();

        if ($allAlokasi->isEmpty()) {
            abort(404, 'Tidak ada alokasi untuk petugas ini');
        }

        $petugas = $allAlokasi->first()->petugas;

        // Get active Kepala BPS
        $penandatangan = Penandatangan::active()->ppk()->firstOrFail();

        // Get total honor for this petugas across all kegiatan
        $totalHonor = 0;
        $uraianTugas = [];
        $bebanAnggaran = '';

        foreach ($allAlokasi as $alokasi) {
            $kegiatan = $alokasi->periodeAlokasi->kegiatan;
            $totalHonor += $this->calculateTotalHonor($kegiatan, $alokasi);
            $uraianTugas = array_merge($uraianTugas, $this->getUraianTugas($kegiatan, $alokasi));
            if (empty($bebanAnggaran)) {
                $bebanAnggaran = $this->getBebanAnggaran($kegiatan);
            }
        }

        $data = [
            'periode' => $periode,
            'alokasi' => $allAlokasi->first(),
            'petugas' => $petugas,
            'kegiatan' => $periode->kegiatan,
            'nomorSpk' => $validated['nomor_spk'],
            'tanggalSpk' => \Carbon\Carbon::parse($validated['tanggal_spk']),
            'sampaiTanggal' => \Carbon\Carbon::parse($validated['sampai_tanggal']),
            'penandatangan' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'peranLabel' => $this->getPeranLabel($allAlokasi->first()->peran),
            'totalHonor' => $totalHonor,
            'uraianTugas' => $uraianTugas,
            'bebanAnggaran' => $bebanAnggaran,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-lampiran', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->stream('Preview_SPK_Lampiran_'.$petugas->nama.'.pdf');
    }

    /**
     * Generate SPK PDF and save to database
     */
    public function generateSpk(Request $request, string $periodeHashedId, string $petugasHashedId)
    {
        $periodeId = \Vinkla\Hashids\Facades\Hashids::decode($periodeHashedId)[0] ?? null;
        $petugasId = \Vinkla\Hashids\Facades\Hashids::decode($petugasHashedId)[0] ?? null;

        if (! $periodeId || ! $petugasId) {
            abort(404);
        }

        $validated = $request->validate([
            'nomor_spk' => ['required', 'string', 'max:255', 'unique:spk,nomor_spk'],
            'tanggal_spk' => ['required', 'date'],
            'sampai_tanggal' => ['required', 'date', 'after_or_equal:tanggal_spk'],
        ]);

        $periode = PeriodeAlokasi::with(['kegiatan', 'alokasiPetugas.petugas'])->findOrFail($periodeId);

        // Get all alokasi for this petugas in the same month
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun);
            })
            ->where('petugas_id', $petugasId)
            ->get();

        if ($allAlokasi->isEmpty()) {
            abort(404, 'Tidak ada alokasi untuk petugas ini');
        }

        $petugas = $allAlokasi->first()->petugas;

        // Get active Kepala BPS
        $penandatangan = Penandatangan::active()->ppk()->firstOrFail();

        // Get total honor for this petugas across all kegiatan
        $totalHonor = 0;
        $uraianTugas = [];
        $bebanAnggaran = '';

        foreach ($allAlokasi as $alokasi) {
            $kegiatan = $alokasi->periodeAlokasi->kegiatan;
            $totalHonor += $this->calculateTotalHonor($kegiatan, $alokasi);
            $uraianTugas = array_merge($uraianTugas, $this->getUraianTugas($kegiatan, $alokasi));
            if (empty($bebanAnggaran)) {
                $bebanAnggaran = $this->getBebanAnggaran($kegiatan);
            }
        }

        $data = [
            'periode' => $periode,
            'alokasi' => $allAlokasi->first(),
            'petugas' => $petugas,
            'kegiatan' => $periode->kegiatan,
            'nomorSpk' => $validated['nomor_spk'],
            'tanggalSpk' => \Carbon\Carbon::parse($validated['tanggal_spk']),
            'sampaiTanggal' => \Carbon\Carbon::parse($validated['sampai_tanggal']),
            'penandatangan' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'kepalaBps' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'peranLabel' => $this->getPeranLabel($allAlokasi->first()->peran),
            'totalHonor' => $totalHonor,
            'uraianTugas' => $uraianTugas,
            'bebanAnggaran' => $bebanAnggaran,
        ];

        DB::beginTransaction();
        try {
            // Generate 2 separate PDFs (SPK Main + Lampiran only)
            $pdfMain = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-main', $data)
                ->setPaper('a4', 'portrait');

            $pdfLampiran = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-lampiran', $data)
                ->setPaper('a4', 'landscape');

            // Save temporary PDFs
            $tempPath = storage_path('app/temp');
            if (! file_exists($tempPath)) {
                mkdir($tempPath, 0777, true);
            }

            $timestamp = time().'_'.uniqid();
            $mainPath = $tempPath.'/spk_main_'.$timestamp.'.pdf';
            $lampiranPath = $tempPath.'/spk_lampiran_'.$timestamp.'.pdf';
            $mergedPath = $tempPath.'/spk_merged_'.$timestamp.'.pdf';

            file_put_contents($mainPath, $pdfMain->output());
            file_put_contents($lampiranPath, $pdfLampiran->output());

            // Try to merge PDFs
            $merged = \App\Services\PdfMergerService::mergePdfFiles(
                [$mainPath, $lampiranPath],
                $mergedPath
            );

            $pdfOutput = null;
            if ($merged && file_exists($mergedPath)) {
                $pdfOutput = file_get_contents($mergedPath);
            } else {
                // Fallback to single PDF if merge failed
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-petugas', $data)
                    ->setPaper('a4', 'portrait');
                $pdfOutput = $pdf->output();
            }

            // Cleanup temporary files
            @unlink($mainPath);
            @unlink($lampiranPath);
            @unlink($mergedPath);

            // Save PDF file
            $fileName = $data['nomorSpk'].'_SPK '.$data['kegiatan']->nama_kegiatan.'_'.$petugas->nama.'_'.$periode->bulan.'_'.$periode->tahun.'.pdf';
            $filePath = 'spk/'.date('Y').'/'.date('m').'/'.$fileName;
            \Storage::put($filePath, $pdfOutput);

            // Save to database
            $spk = Spk::create([
                'nomor_spk' => $validated['nomor_spk'],
                'alokasi_mitra_id' => $allAlokasi->first()->id,
                'tanggal_spk' => $validated['tanggal_spk'],
                'tanggal_mulai_kerja' => \Carbon\Carbon::create($periode->tahun, $periode->bulan, 1),
                'tanggal_selesai_kerja' => \Carbon\Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth(),
                'nilai_kontrak' => $totalHonor,
                'nama_ppk' => preg_replace('/,.*$/', '', $penandatangan->nama),
                'nip_ppk' => $penandatangan->nip ?? null,
                'file_path' => $filePath,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'SPK berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Helper: Get bulan label
     */
    private function getBulanLabel(int $bulan): string
    {
        $bulanLabels = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $bulanLabels[$bulan] ?? '';
    }

    /**
     * Calculate total honor for petugas
     */
    private function calculateTotalHonor(Kegiatan $kegiatan, AlokasiPetugas $alokasi): float
    {
        $rateHonor = \App\Models\RateHonor::where('kegiatan_id', $kegiatan->id)
            ->where('jenis_penugasan', $alokasi->peran)
            ->where('status_kepegawaian', $alokasi->status_kepegawaian ?? ($alokasi->petugas->jenis_petugas === 'organik' ? 'organik' : 'non_organik'))
            ->first();

        if (! $rateHonor) {
            return 0;
        }

        $total = 0;

        // Calculate from listing rate
        if ($rateHonor->rate_listing && $alokasi->jumlah_satuan_listing) {
            $total += $rateHonor->rate_listing * $alokasi->jumlah_satuan_listing;
        }

        // Calculate from regular rate (pencacahan)
        if ($rateHonor->rate && $alokasi->jumlah_satuan) {
            $total += $rateHonor->rate * $alokasi->jumlah_satuan;
        }

        return $total;
    }

    /**
     * Get uraian tugas details
     */
    private function getUraianTugas(Kegiatan $kegiatan, AlokasiPetugas $alokasi): array
    {
        $rateHonor = \App\Models\RateHonor::where('kegiatan_id', $kegiatan->id)
            ->where('jenis_penugasan', $alokasi->peran)
            ->where('status_kepegawaian', $alokasi->status_kepegawaian ?? ($alokasi->petugas->jenis_petugas === 'organik' ? 'organik' : 'non_organik'))
            ->with(['satuan', 'satuanListing'])
            ->first();

        $uraian = [];
        $periode = $alokasi->periodeAlokasi;

        if ($rateHonor) {
            // Add listing task if exists
            if ($rateHonor->rate_listing && $alokasi->jumlah_satuan_listing) {
                $peranKegiatan = $this->getPeranKegiatan($alokasi->peran, 'listing');
                $uraian[] = [
                    'uraian' => "Melakukan {$peranKegiatan} {$kegiatan->nama_kegiatan} bulan {$this->getBulanLabel($periode->bulan)} {$periode->tahun} Tahun {$kegiatan->tahun_anggaran} (Listing)",
                    'volume' => $alokasi->jumlah_satuan_listing,
                    'satuan' => $rateHonor->satuanListing->kode ?? 'DOK',
                    'harga_satuan' => $rateHonor->rate_listing,
                    'jumlah' => $rateHonor->rate_listing * $alokasi->jumlah_satuan_listing,
                    'tanggal_mulai' => $periode->tanggal_mulai_listing?->format('Y-m-d'),
                    'tanggal_selesai' => $periode->tanggal_selesai_listing?->format('Y-m-d'),
                    'phase' => 'listing',
                ];
            }

            // Add regular task (pencacahan)
            if ($rateHonor->rate && $alokasi->jumlah_satuan) {
                $peranKegiatan = $this->getPeranKegiatan($alokasi->peran, 'pencacahan');
                $uraian[] = [
                    'uraian' => "Melakukan {$peranKegiatan} {$kegiatan->nama_kegiatan} bulan {$this->getBulanLabel($periode->bulan)} {$periode->tahun} Tahun {$kegiatan->tahun_anggaran}",
                    'volume' => $alokasi->jumlah_satuan,
                    'satuan' => $rateHonor->satuan->kode ?? 'DOK',
                    'harga_satuan' => $rateHonor->rate,
                    'jumlah' => $rateHonor->rate * $alokasi->jumlah_satuan,
                    'tanggal_mulai' => $periode->tanggal_mulai?->format('Y-m-d'),
                    'tanggal_selesai' => $periode->tanggal_selesai?->format('Y-m-d'),
                    'phase' => 'pencacahan',
                ];
            }
        }

        return $uraian;
    }

    /**
     * Get peran kegiatan label based on role and phase
     */
    private function getPeranKegiatan(string $peran, string $phase): string
    {
        if ($phase === 'listing') {
            return match ($peran) {
                'pcl_ppl' => 'Pemutakhiran Lapangan',
                'pml' => 'Pemeriksaan Pemutakhiran Lapangan',
                default => 'Pemutakhiran Lapangan',
            };
        }

        // pencacahan
        return match ($peran) {
            'pcl_ppl' => 'Pendataan Lapangan',
            'pml' => 'Pemeriksaan Lapangan',
            default => 'Pendataan Lapangan',
        };
    }

    /**
     * Get beban anggaran (MAK)
     */
    private function getBebanAnggaran(Kegiatan $kegiatan): string
    {
        // Prioritaskan kode_coa dari kegiatan jika ada
        if (! empty($kegiatan->kode_coa)) {
            return $kegiatan->kode_coa;
        }

        // Fallback ke MAK dari DIPA
        $dipa = \App\Models\Dipa::active()->first();

        return $dipa->mak ?? '2904.BMA.006.005.A.521213';
    }

    /**
     * Get peran label
     */
    private function getPeranLabel(string $peran): string
    {
        return match ($peran) {
            'pcl_ppl' => 'Petugas Pencacahan',
            'pml' => 'Pemeriksa Lapangan/PML',
            'pengolahan' => 'Petugas Pengolahan',
            'pengawas_pengolahan' => 'Pemeriksa Pengolahan',
            default => $peran,
        };
    }
}
