<?php

namespace App\Http\Controllers;

use App\Models\Bast;
use App\Models\BastPetugas;
use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Spk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BastController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $activeYear = \App\Services\ActiveYearService::get();

        // Ambil periode yang sudah dikirim/perubahan dan memiliki SPK
        $periodes = PeriodeAlokasi::query()
            ->where('tahun', $activeYear)
            ->whereIn('status', ['dikirim', 'perubahan'])
            ->whereHas('spk')
            ->when($search, function ($query) use ($search) {
                $query->whereHas('kegiatan', function ($kq) use ($search) {
                    $kq->where('nama_kegiatan', 'like', "%{$search}%")
                        ->orWhere('kode_kegiatan', 'like', "%{$search}%");
                });
            })
            ->with([
                'kegiatan:id,kode_kegiatan,nama_kegiatan,ketua_tim_user_id',
                'kegiatan.ketuaTim:id,name',
            ])
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->get();

        $data = [];
        foreach ($periodes as $periode) {
            $bast = Bast::where('kegiatan_id', $periode->kegiatan_id)
                ->where('periode_alokasi_id', $periode->id)
                ->first();

            $bulanInt = (int) $periode->bulan;

            $data[] = [
                'kegiatan' => [
                    'id' => $periode->kegiatan->id,
                    'hashed_id' => $periode->kegiatan->hashed_id,
                    'kode_kegiatan' => $periode->kegiatan->kode_kegiatan,
                    'nama_kegiatan' => $periode->kegiatan->nama_kegiatan,
                    'ketua_tim' => $periode->kegiatan->ketuaTim?->name,
                ],
                'periode' => [
                    'bulan' => $bulanInt,
                    'tahun' => (int) $periode->tahun,
                    'bulan_label' => \Carbon\Carbon::create((int) $periode->tahun, $bulanInt)->isoFormat('MMMM'),
                ],
                'bast' => $bast ? [
                    'id' => $bast->id,
                    'hashed_id' => $bast->hashed_id,
                    'nomor_bast' => $bast->nomor_bast,
                    'tanggal_bast' => $bast->tanggal_bast?->format('Y-m-d'),
                    'status' => $bast->status,
                    'file_path' => $bast->file_path,
                    'jumlah_petugas' => $bast->bastPetugas()->count(),
                ] : null,
                'has_bast' => $bast !== null,
                'sort_key' => sprintf('%04d%02d%s', (int) $periode->tahun, $bulanInt, $periode->kegiatan->kode_kegiatan),
            ];
        }

        return Inertia::render('Bast/Index', [
            'data' => $data,
            'filters' => [
                'search' => $search,
            ],
            'active_year' => $activeYear,
        ]);
    }

    /**
     * Show the form for creating a new resource - Select Kegiatan
     * Only accessible by ketua_tim
     */
    public function create(): Response
    {
        // Get all kegiatan yang memiliki SPK yang sudah diterbitkan
        $kegiatan = Kegiatan::query()
            ->whereHas('periodeAlokasi', function ($query) {
                $query->whereIn('status', ['dikirim', 'perubahan'])
                    ->whereHas('alokasiPetugas', function ($q) {
                        $q->whereHas('spk', function ($sq) {
                            $sq->where('status', 'diterbitkan');
                        });
                    });
            })
            ->withCount([
                'periodeAlokasi as total_petugas' => function ($query) {
                    $query->whereIn('status', ['dikirim', 'perubahan'])
                        ->join('alokasi_petugas', 'periode_alokasi.id', '=', 'alokasi_petugas.periode_alokasi_id')
                        ->whereHas('alokasiPetugas.spk', function ($sq) {
                            $sq->where('status', 'diterbitkan');
                        });
                },
            ])
            ->with('ketuaTim')
            ->orderBy('nama_kegiatan')
            ->get()
            ->map(function ($keg) {
                return [
                    'id' => $keg->id,
                    'hashed_id' => $keg->hashed_id,
                    'kode_kegiatan' => $keg->kode_kegiatan,
                    'nama_kegiatan' => $keg->nama_kegiatan,
                    'ketua_tim' => $keg->ketuaTim?->name,
                    'total_petugas' => $keg->total_petugas,
                ];
            });

        return Inertia::render('Bast/Create', [
            'kegiatan' => $kegiatan,
        ]);
    }

    /**
     * Show form to create BAST for a specific kegiatan
     */
    public function createForKegiatan(string $kegiatanHashedId): Response
    {
        $kegiatanId = \Vinkla\Hashids\Facades\Hashids::decode($kegiatanHashedId)[0] ?? null;

        if (! $kegiatanId) {
            abort(404);
        }

        $kegiatan = Kegiatan::with([
            'ketuaTim',
            'rateHonors.satuan',
            'rateHonors.satuanListing',
        ])->findOrFail($kegiatanId);

        // Get periode with status perubahan if exists, otherwise get with status dikirim
        $periodeWithPerubahan = PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
            ->where('status', 'perubahan')
            ->exists();

        $targetPeriode = $this->getTargetPeriode($kegiatanId);

        $status = $targetPeriode?->status ?? ($periodeWithPerubahan ? 'perubahan' : 'dikirim');

        // Get all petugas untuk periode target, ambil SPK terbaru (original/addendum) tanpa membatasi status
        $alokasiPetugasRaw = AlokasiPetugas::query()
            ->when($targetPeriode, function ($query) use ($targetPeriode) {
                $query->where('periode_alokasi_id', $targetPeriode->id);
            })
            ->when(! $targetPeriode, function ($query) use ($kegiatanId, $status) {
                $query->whereHas('periodeAlokasi', function ($q) use ($kegiatanId, $status) {
                    $q->where('kegiatan_id', $kegiatanId)->where('status', $status);
                });
            })
            ->whereHas('spk')
            ->with([
                'petugas',
                'spk' => function ($query) {
                    $query->orderByDesc('addendum_number')->orderByDesc('id'); // pick latest SPK (original or addendum)
                },
                'periodeAlokasi',
            ])
            ->get();

        $hasListing = ($kegiatan->has_listing_updating ?? false)
            || $alokasiPetugasRaw->contains(function ($alokasi) {
                return (int) ($alokasi->jumlah_satuan_listing ?? 0) > 0;
            });

        $hasPengolahan = $alokasiPetugasRaw->contains(function ($alokasi) {
            return in_array($alokasi->peran, ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'], true);
        });

        $alokasiPetugas = $alokasiPetugasRaw
            ->filter(function ($alokasi) {
                return $alokasi->spk && $alokasi->spk->isNotEmpty();
            })
            ->map(function ($alokasi) use ($kegiatan, $hasListing, $hasPengolahan) {
                $spk = $alokasi->spk->first();

                // Ambil satuan dari rate honor (berdasarkan kegiatan & jenis penugasan)
                $rateHonor = $kegiatan->rateHonors->first(function ($rate) use ($alokasi) {
                    return $rate->status_kepegawaian === $alokasi->status_kepegawaian
                        && $rate->jenis_penugasan === $alokasi->peran;
                });

                $satuanPendataan = $rateHonor?->satuan?->nama;
                $satuanListing = $rateHonor?->satuanListing?->nama;
                $satuanPengolahan = $rateHonor?->satuan?->nama;

                // Roles that represent pendataan/pencacahan (adjust list as needed)
                $pendataanRoles = ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'];
                $isPendataanRole = in_array($alokasi->peran, $pendataanRoles, true);

                // Roles that represent pengolahan
                $pengolahanRoles = ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'];
                $isPengolahanRole = in_array($alokasi->peran, $pengolahanRoles, true);

                return [
                    'id' => $alokasi->id,
                    'petugas_id' => $alokasi->petugas->id,
                    'spk_id' => $spk->id,
                    'nama_petugas' => $alokasi->petugas->nama,
                    'nomor_spk' => $spk->nomor_spk,
                    'peran' => $alokasi->peran,
                    // Pre-fill hasil & satuan dari alokasi / periode
                    'hasil_listing' => $hasListing ? ($alokasi->jumlah_satuan_listing ?? null) : null,
                    'satuan_listing' => $hasListing ? ($satuanListing ?? null) : null,
                    'hasil_pendataan_lapangan' => $isPendataanRole ? ($alokasi->jumlah_satuan ?? null) : null,
                    'satuan_pendataan_lapangan' => $isPendataanRole ? ($satuanPendataan ?? null) : null,
                    'hasil_pengolahan' => $isPengolahanRole ? ($alokasi->jumlah_satuan ?? null) : null,
                    'satuan_pengolahan' => $isPengolahanRole ? ($satuanPengolahan ?? null) : null,
                    'catatan' => $alokasi->catatan,
                ];
            })
            ->values();

        // Get PPK from penandatangan
            // Get active PPK from penandatangan (by jenis_penandatangan + active + valid date range)
            $penandatangan = Penandatangan::ppk()
                ->active()
                ->where(function ($q) {
                    $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('periode_selesai')->orWhere('periode_selesai', '>=', now());
                })
                ->orderByDesc('periode_mulai')
                ->first();

        return Inertia::render('Bast/CreateForKegiatan', [
            'kegiatan' => [
                'id' => $kegiatan->id,
                'hashed_id' => $kegiatan->hashed_id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'ketua_tim_nama' => $kegiatan->ketuaTim?->name,
                'ketua_tim_nip' => $kegiatan->ketuaTim?->nip ?? null,
            ],
            'petugas_list' => $alokasiPetugas,
            'show_listing_columns' => $hasListing,
            'show_pengolahan_columns' => $hasPengolahan,
            'ppk' => $penandatangan ? [
                'nama' => $this->stripGelar($penandatangan->nama),
                'nip' => $penandatangan->nip,
            ] : null,
            'status_periode' => $status,
        ]);
    }

    /**
     * Generate preview PDF for BAST
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'kegiatan_id' => 'required|exists:kegiatan,id',
            'tanggal_bast' => 'required|date',
            'menggunakan_fasih' => 'required|boolean',
            'petugas' => 'required|array|min:1',
            'petugas_index' => 'nullable|integer|min:0',
            'petugas.*.petugas_id' => 'required|exists:petugas,id',
            'petugas.*.spk_id' => 'required|exists:spk,id',
            'petugas.*.nomor_spk' => 'required|string',
            'petugas.*.nama_petugas' => 'required|string',
            'petugas.*.hasil_listing' => 'nullable|numeric',
            'petugas.*.satuan_listing' => 'nullable|string',
            'petugas.*.instrumen_listing' => 'nullable|string',
            'petugas.*.hasil_pendataan_lapangan' => 'nullable|numeric',
            'petugas.*.satuan_pendataan_lapangan' => 'nullable|string',
            'petugas.*.instrumen_pendataan_lapangan' => 'nullable|string',
            'petugas.*.hasil_pengolahan' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan' => 'nullable|string',
            'petugas.*.catatan' => 'nullable|string',
            'instrumen_listing' => 'nullable|string',
            'instrumen_pendataan_lapangan' => 'nullable|string',
            'dokumen_rekap' => 'nullable|array',
            'dokumen_rekap.*.nama' => 'nullable|string',
            'dokumen_rekap.*.kode' => 'nullable|string',
            'dokumen_rekap.*.didata' => 'nullable|numeric',
            'dokumen_rekap.*.non_respon' => 'nullable|numeric',
            'dokumen_rekap.*.keterangan' => 'nullable|string',
        ]);

        $kegiatan = Kegiatan::with('ketuaTim')->findOrFail($validated['kegiatan_id']);

        $petugasData = $validated['petugas'];
        if (isset($validated['petugas_index']) && array_key_exists($validated['petugas_index'], $petugasData)) {
            $petugasData = [$petugasData[$validated['petugas_index']]];
        }

        // Sanitize incoming petugas results based on role to avoid misclassification
        $pendataanRoles = ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'];
        $pengolahanRoles = ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'];
        foreach ($petugasData as $idx => $p) {
            $peran = $p['peran'] ?? null;
            if (! in_array($peran, $pendataanRoles, true)) {
                $petugasData[$idx]['hasil_pendataan_lapangan'] = null;
                $petugasData[$idx]['satuan_pendataan_lapangan'] = null;
                $petugasData[$idx]['instrumen_pendataan_lapangan'] = null;
            }
            if (! in_array($peran, $pengolahanRoles, true)) {
                $petugasData[$idx]['hasil_pengolahan'] = null;
                $petugasData[$idx]['satuan_pengolahan'] = null;
            }
        }

        $targetPeriode = $this->getTargetPeriode($validated['kegiatan_id']);
        $bulanLabel = $targetPeriode?->bulan
            ? \Carbon\Carbon::create((int) $targetPeriode->tahun, (int) $targetPeriode->bulan)->isoFormat('MMMM')
            : \Carbon\Carbon::parse($validated['tanggal_bast'])->isoFormat('MMMM');
        $tahunPeriode = $targetPeriode?->tahun ?? (int) \Carbon\Carbon::parse($validated['tanggal_bast'])->year;

        // Get PPK aktif
                // Get active PPK from penandatangan (by jenis_penandatangan + active + valid date range)
                $penandatangan = Penandatangan::ppk()
                    ->active()
                    ->where(function ($q) {
                        $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('periode_selesai')->orWhere('periode_selesai', '>=', now());
                    })
                    ->orderByDesc('periode_mulai')
                    ->first();

        $tanggalBast = \Carbon\Carbon::parse($validated['tanggal_bast']);
        $hari = $this->getHariIndonesia($tanggalBast->dayOfWeek);
        $tanggalFormatted = $tanggalBast->isoFormat('D MMMM YYYY');

        $hasListing = collect($petugasData)->contains(function ($p) { return ! empty($p['hasil_listing']); });
        $hasPengolahan = collect($petugasData)->contains(function ($p) { return ! empty($p['hasil_pengolahan']); });
        $hasPendataan = collect($petugasData)->contains(function ($p) { return ! empty($p['hasil_pendataan_lapangan']); });

        // Use same nomor format as stored BASTs (does not persist)
        $nomorBast = $this->generateNomorBast($validated['kegiatan_id']);

        $namaPpk = $penandatangan->nama ?? 'N/A';
        $namaPpk = $this->stripGelar($namaPpk) ?: 'N/A';

        $viewData = [
            'nomor_bast' => $nomorBast,
            'hari' => $hari,
            'tanggal_bast' => $tanggalFormatted,
            'bulan_label' => $bulanLabel,
            'tahun' => $tahunPeriode,
            'nama_ppk' => $namaPpk,
            'nip_ppk' => $penandatangan->nip ?? 'N/A',
            'nama_ketua_tim' => $kegiatan->ketuaTim->name ?? 'N/A',
            'nip_ketua_tim' => $kegiatan->ketuaTim->nip ?? null,
            'nama_kegiatan' => $kegiatan->nama_kegiatan,
            'nama_instansi' => config('app.instansi_name', 'Badan Pusat Statistik Kota Sawahlunto'),
            'menggunakan_fasih' => $validated['menggunakan_fasih'],
            'petugas' => $petugasData,
            // petugas entries may include instrument names
            'has_listing' => $hasListing,
            'has_pendataan' => $hasPendataan,
            'has_pengolahan' => $hasPengolahan,
            'dokumen_rekap' => $validated['dokumen_rekap'] ?? [],
            'instrumen_listing' => $validated['instrumen_listing'] ?? null,
            'instrumen_pendataan_lapangan' => $validated['instrumen_pendataan_lapangan'] ?? null,
            // Kepala BPS penandatangan (if available)
            'kepalaBps' => null,
        ];

        // Attach Kepala BPS if available
        $kepala = Penandatangan::kepala()
            ->active()
            ->where(function ($q) {
                $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('periode_selesai')->orWhere('periode_selesai', '>=', now());
            })
            ->orderByDesc('periode_mulai')
            ->first();

        if ($kepala) {
            $viewData['kepalaBps'] = $this->stripGelar($kepala->nama) ?: $kepala->nama;
        }

        $useLandscape = false;
        if (!empty($viewData['dokumen_rekap']) && count($viewData['dokumen_rekap']) > 0) {
            $useLandscape = true;
        }
        if ($viewData['has_listing'] || $viewData['has_pengolahan'] || $viewData['has_pendataan'] ?? false) {
            $useLandscape = true;
        }

        $orientation = $useLandscape ? 'landscape' : 'portrait';

        // Render main (without lampiran) as portrait PDF
        $viewDataMain = $viewData;
        $viewDataMain['render_lampiran'] = false;
        $pdfMain = \Barryvdh\DomPDF\Facade\Pdf::loadView('bast', $viewDataMain)
            ->setPaper('a4', 'portrait');
        $mainContent = $pdfMain->output();

        // If there are lampiran, render lampiran PDF (landscape)
        $hasLampiran = (!empty($viewData['dokumen_rekap']) && count($viewData['dokumen_rekap']) > 0)
            || $viewData['has_listing'] || $viewData['has_pengolahan'] || ($viewData['has_pendataan'] ?? false);

        if (! $hasLampiran) {
            $fileName = 'BAST_PREVIEW_'.str_replace('/', '-', $nomorBast).'.pdf';
            return response($mainContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            ]);
        }

        $viewDataLamp = $viewData;
        $viewDataLamp['render_lampiran'] = true;
        $lampOrientation = (!empty($viewData['dokumen_rekap']) && count($viewData['dokumen_rekap']) > 0)
            || $viewData['has_listing'] || $viewData['has_pengolahan'] ? 'landscape' : 'portrait';
        $pdfLamp = \Barryvdh\DomPDF\Facade\Pdf::loadView('bast', $viewDataLamp)
            ->setPaper('a4', $lampOrientation);
        $lampContent = $pdfLamp->output();

        // Merge PDFs using FPDI-TCPDF preserving orientations
        $merged = $this->mergePdfStrings([$mainContent, $lampContent]);
        $fileName = 'BAST_PREVIEW_'.str_replace('/', '-', $nomorBast).'.pdf';
        return response($merged, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kegiatan_id' => 'required|exists:kegiatan,id',
            'tanggal_bast' => 'required|date',
            'menggunakan_fasih' => 'required|boolean',
            'petugas' => 'required|array|min:1',
            'petugas.*.petugas_id' => 'required|exists:petugas,id',
            'petugas.*.spk_id' => 'required|exists:spk,id',
            'petugas.*.nomor_spk' => 'required|string',
            'petugas.*.nama_petugas' => 'required|string',
            'petugas.*.hasil_listing' => 'nullable|numeric',
            'petugas.*.satuan_listing' => 'nullable|string',
            'petugas.*.instrumen_listing' => 'nullable|string',
            'petugas.*.hasil_pendataan_lapangan' => 'nullable|numeric',
            'petugas.*.satuan_pendataan_lapangan' => 'nullable|string',
            'petugas.*.instrumen_pendataan_lapangan' => 'nullable|string',
            'petugas.*.hasil_pengolahan' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan' => 'nullable|string',
            'petugas.*.catatan' => 'nullable|string',
            'dokumen_rekap' => 'nullable|array',
            'dokumen_rekap.*.nama' => 'nullable|string',
            'dokumen_rekap.*.kode' => 'nullable|string',
            'dokumen_rekap.*.didata' => 'nullable|numeric',
            'dokumen_rekap.*.non_respon' => 'nullable|numeric',
            'dokumen_rekap.*.keterangan' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $kegiatan = Kegiatan::with('ketuaTim')->findOrFail($validated['kegiatan_id']);

            // Get periode alokasi ID
            $targetPeriode = $this->getTargetPeriode($validated['kegiatan_id']);

            if (! $targetPeriode) {
                throw new \RuntimeException('Periode alokasi tidak ditemukan untuk kegiatan ini.');
            }

            $periodeId = $targetPeriode->id;

            // Get PPK
                    // Get active PPK from penandatangan (by jenis_penandatangan + active + valid date range)
                    $penandatangan = Penandatangan::ppk()
                        ->active()
                        ->where(function ($q) {
                            $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', now());
                        })
                        ->where(function ($q) {
                            $q->whereNull('periode_selesai')->orWhere('periode_selesai', '>=', now());
                        })
                        ->orderByDesc('periode_mulai')
                        ->first();

            // Generate nomor BAST
            $nomorBast = $this->generateNomorBast($validated['kegiatan_id']);

            $bulanLabel = $targetPeriode?->bulan
                ? \Carbon\Carbon::create((int) $targetPeriode->tahun, (int) $targetPeriode->bulan)->isoFormat('MMMM')
                : \Carbon\Carbon::parse($validated['tanggal_bast'])->isoFormat('MMMM');
            $tahunPeriode = $targetPeriode?->tahun ?? (int) \Carbon\Carbon::parse($validated['tanggal_bast'])->year;

            // Generate PDF for BAST
            $filePath = $this->generateBastPdf(
                $kegiatan,
                $validated,
                $nomorBast,
                $penandatangan,
                $bulanLabel,
                $tahunPeriode
            );

            // Create BAST
            $bast = Bast::create([
                'nomor_bast' => $nomorBast,
                'kegiatan_id' => $validated['kegiatan_id'],
                'periode_alokasi_id' => $periodeId,
                'spk_id' => $validated['petugas'][0]['spk_id'], // Take first SPK as reference
                'tanggal_bast' => $validated['tanggal_bast'],
                'tanggal_serah_terima' => $validated['tanggal_bast'],
                'menggunakan_fasih' => $validated['menggunakan_fasih'],
                'nama_ketua_tim' => $kegiatan->ketuaTim->name ?? null,
                'nip_ketua_tim' => $kegiatan->ketuaTim->nip ?? null,
                    'nama_ppk' => $this->stripGelar($penandatangan->nama) ?? null,
                'nip_ppk' => $penandatangan->nip ?? null,
                'file_path' => $filePath,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            // Create BAST Petugas records
            foreach ($validated['petugas'] as $petugasData) {
                BastPetugas::create([
                    'bast_id' => $bast->id,
                    'petugas_id' => $petugasData['petugas_id'],
                    'spk_id' => $petugasData['spk_id'],
                    'nomor_spk' => $petugasData['nomor_spk'],
                    'nama_petugas' => $petugasData['nama_petugas'],
                    'hasil_listing' => $petugasData['hasil_listing'],
                    'satuan_listing' => $petugasData['satuan_listing'],
                    'hasil_pendataan_lapangan' => $petugasData['hasil_pendataan_lapangan'],
                    'satuan_pendataan_lapangan' => $petugasData['satuan_pendataan_lapangan'],
                    'hasil_pengolahan' => $petugasData['hasil_pengolahan'],
                    'satuan_pengolahan' => $petugasData['satuan_pengolahan'],
                    'catatan' => $petugasData['catatan'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('bast.show', $bast->hashed_id)
                ->with('success', 'BAST berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Gagal membuat BAST: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Bast $bast)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bast $bast)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bast $bast)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bast $bast)
    {
        //
    }

    /**
     * Generate nomor BAST
     */
    private function generateNomorBast(int $kegiatanId): string
    {
        $year = now()->year;

        // Get last number for this kegiatan (current year)
        $lastBast = Bast::where('kegiatan_id', $kegiatanId)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = 0;
        if ($lastBast) {
            $extracted = $this->extractBastSequence($lastBast->nomor_bast);
            $lastNumber = $extracted ?? 0;
        }

        $nextNumber = $lastNumber + 1;

        // Format: PPIS/13730/{NO_URUT_AUTO_INCREMENT}/BAST/{YEAR}
        return sprintf('PPIS/13730/%d/BAST/%d', $nextNumber, $year);
    }

    private function extractBastSequence(string $nomorBast): ?int
    {
        if (preg_match('/PPIS\/13730\/(\d+)/', $nomorBast, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/BAST\/[^\/]+\/(\d+)/', $nomorBast, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Generate BAST PDF
     */
    private function generateBastPdf(Kegiatan $kegiatan, array $data, string $nomorBast, ?Penandatangan $ppk, ?string $bulanLabel = null, ?int $tahunPeriode = null): string
    {
        $tanggalBast = \Carbon\Carbon::parse($data['tanggal_bast']);
        $hari = $this->getHariIndonesia($tanggalBast->dayOfWeek);
        $tanggalFormatted = $tanggalBast->isoFormat('D MMMM YYYY');

        // Sanitize petugas entries to ensure fields only present for matching roles
        $pendataanRoles = ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'];
        $pengolahanRoles = ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'];
        foreach ($data['petugas'] as $i => $pEntry) {
            $peran = $pEntry['peran'] ?? null;
            if (! in_array($peran, $pendataanRoles, true)) {
                $data['petugas'][$i]['hasil_pendataan_lapangan'] = null;
                $data['petugas'][$i]['satuan_pendataan_lapangan'] = null;
                $data['petugas'][$i]['instrumen_pendataan_lapangan'] = null;
            }
            if (! in_array($peran, $pengolahanRoles, true)) {
                $data['petugas'][$i]['hasil_pengolahan'] = null;
                $data['petugas'][$i]['satuan_pengolahan'] = null;
            }
        }

        // Check if listing, pendataan, or pengolahan exists after sanitization
        $hasListing = collect($data['petugas'])->contains(function ($p) { return ! empty($p['hasil_listing']); });
        $hasPengolahan = collect($data['petugas'])->contains(function ($p) { return ! empty($p['hasil_pengolahan']); });
        $hasPendataan = collect($data['petugas'])->contains(function ($p) { return ! empty($p['hasil_pendataan_lapangan']); });

        $viewData = [
            'nomor_bast' => $nomorBast,
            'hari' => $hari,
            'tanggal_bast' => $tanggalFormatted,
            'bulan_label' => $bulanLabel ?? $tanggalBast->isoFormat('MMMM'),
            'tahun' => $tahunPeriode ?? (int) $tanggalBast->year,
            'nama_ppk' => $ppk->nama ?? 'N/A',
            'nip_ppk' => $ppk->nip ?? 'N/A',
            'nama_ketua_tim' => $kegiatan->ketuaTim->name ?? 'N/A',
            'nip_ketua_tim' => null,
            'nama_kegiatan' => $kegiatan->nama_kegiatan,
            'nama_instansi' => config('app.instansi_name', 'Badan Pusat Statistik Kota Sawahlunto'),
            'menggunakan_fasih' => $data['menggunakan_fasih'],
            'petugas' => $data['petugas'],
            'has_listing' => $hasListing,
            'has_pendataan' => $hasPendataan,
            'has_pengolahan' => $hasPengolahan,
            'dokumen_rekap' => $data['dokumen_rekap'] ?? [],
            'instrumen_listing' => $data['instrumen_listing'] ?? null,
            'instrumen_pendataan_lapangan' => $data['instrumen_pendataan_lapangan'] ?? null,
            'kepalaBps' => null,
        ];

        // Attach Kepala BPS if available
        $kepala = Penandatangan::kepala()
            ->active()
            ->where(function ($q) {
                $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('periode_selesai')->orWhere('periode_selesai', '>=', now());
            })
            ->orderByDesc('periode_mulai')
            ->first();

        if ($kepala) {
            $viewData['kepalaBps'] = $this->stripGelar($kepala->nama) ?: $kepala->nama;
        }

        $useLandscape = false;
        if (!empty($viewData['dokumen_rekap']) && count($viewData['dokumen_rekap']) > 0) {
            $useLandscape = true;
        }
        if ($viewData['has_listing'] || $viewData['has_pengolahan'] || ($viewData['has_pendataan'] ?? false)) {
            $useLandscape = true;
        }

        $orientation = $useLandscape ? 'landscape' : 'portrait';

        // Render main (without lampiran)
        $viewDataMain = $viewData;
        $viewDataMain['render_lampiran'] = false;
        $pdfMain = \Barryvdh\DomPDF\Facade\Pdf::loadView('bast', $viewDataMain)
            ->setPaper('a4', 'portrait');
        $mainContent = $pdfMain->output();

        // If no lampiran, save main PDF directly
        $hasLampiran = (!empty($viewData['dokumen_rekap']) && count($viewData['dokumen_rekap']) > 0)
            || $viewData['has_listing'] || $viewData['has_pengolahan'] || ($viewData['has_pendataan'] ?? false);

        if (! $hasLampiran) {
            $directory = public_path('bast-export/'.now()->year.'/'.now()->month);
            if (! file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            $fileName = 'BAST_'.$kegiatan->nama_kegiatan.'_'.$kegiatan->periodeAlokasi()->bulan.'_'.time().'.pdf';
            $filePath = 'bast-export/'.now()->year.'/'.now()->month.'/'.$fileName;
            $fullPath = public_path($filePath);
            file_put_contents($fullPath, $mainContent);
            return $filePath;
        }

        // Render lampiran only (landscape)
        $viewDataLamp = $viewData;
        $viewDataLamp['render_lampiran'] = true;
        $lampOrientation = (!empty($viewData['dokumen_rekap']) && count($viewData['dokumen_rekap']) > 0)
            || $viewData['has_listing'] || $viewData['has_pengolahan'] ? 'landscape' : 'portrait';
        $pdfLamp = \Barryvdh\DomPDF\Facade\Pdf::loadView('bast', $viewDataLamp)
            ->setPaper('a4', $lampOrientation);
        $lampContent = $pdfLamp->output();

        // Merge and save
        $merged = $this->mergePdfStrings([$mainContent, $lampContent]);

        $directory = public_path('bast-export/'.now()->year.'/'.now()->month);
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $fileName = 'BAST_'.$kegiatan->nama_kegiatan.'_'.$kegiatan->periodeAlokasi()->bulan.'_'.time().'.pdf';
        $filePath = 'bast-export/'.now()->year.'/'.now()->month.'/'.$fileName;
        $fullPath = public_path($filePath);
        file_put_contents($fullPath, $merged);
        return $filePath;
    }

    /**
     * Merge multiple PDF binary strings into one PDF preserving page orientations.
     */
    private function mergePdfStrings(array $pdfStrings): string
    {
        // Use FPDI TCPDF implementation
        $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        foreach ($pdfStrings as $str) {
            if (empty($str)) {
                continue;
            }
            // use StreamReader to feed string directly
            $reader = \setasign\Fpdi\PdfParser\StreamReader::createByString($str);
            $pageCount = $pdf->setSourceFile($reader);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($tplId);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);
            }
        }

        return $pdf->Output('', 'S');
    }

    /**
     * Get hari in Indonesian
     */
    private function getHariIndonesia(int $dayOfWeek): string
    {
        $hari = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        return $hari[$dayOfWeek] ?? 'Senin';
    }

    /**
     * Strip academic titles / honorifics from a full name.
     */
    private function stripGelar(?string $fullName): string
    {
        if (empty($fullName)) {
            return '';
        }

        // Remove anything after the first comma (common suffixes like ", S.Si., M.Sc.")
        $parts = explode(',', $fullName);
        $name = trim($parts[0]);

        // Remove common prefixes like Dr, Drs, Ir, H, Prof (with optional dot)
        $name = preg_replace('/^(Drs?|Ir|H|Prof)\.?\s+/i', '', $name);

        return trim($name);
    }

    /**
     * Ambil periode target: prioritas status perubahan (terbaru), jika tidak ada ambil dikirim (terbaru).
     */
    private function getTargetPeriode(int $kegiatanId): ?PeriodeAlokasi
    {
        $perubahanWithSpk = PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
            ->where('status', 'perubahan')
            ->whereHas('spk')
            ->orderByDesc('id')
            ->first();

        if ($perubahanWithSpk) {
            return $perubahanWithSpk;
        }

        $dikirimWithSpk = PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
            ->where('status', 'dikirim')
            ->whereHas('spk')
            ->orderByDesc('id')
            ->first();

        if ($dikirimWithSpk) {
            return $dikirimWithSpk;
        }

        $perubahan = PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
            ->where('status', 'perubahan')
            ->orderByDesc('id')
            ->first();

        if ($perubahan) {
            return $perubahan;
        }

        return PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
            ->where('status', 'dikirim')
            ->orderByDesc('id')
            ->first();
    }
}
