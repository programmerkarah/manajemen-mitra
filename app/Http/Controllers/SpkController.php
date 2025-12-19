<?php

namespace App\Http\Controllers;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
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

        // Get periode alokasi yang sudah validated
        $query = PeriodeAlokasi::query()
            ->with([
                'kegiatan:id,kode_kegiatan,nama_kegiatan,jenis_kegiatan,tahun_anggaran',
                'alokasiPetugas.petugas:id,nama,nik',
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

        $periodeList = $query->latest()->paginate(15)->withQueryString();

        // Transform data
        $periodeList->getCollection()->transform(function ($periode) {
            $spkCount = $periode->spk->count();
            $latestSpk = $periode->spk->first();

            // SPK status
            $spkStatus = $spkCount > 0 ? 'Sudah Dibuat' : 'Belum Dibuat';
            $spkStatusType = $spkCount > 0 ? 'created' : 'not_created';

            return [
                'id' => $periode->id,
                'hashed_id' => $periode->hashed_id,
                'tahun' => $periode->tahun,
                'bulan' => $periode->bulan,
                'bulan_label' => $this->getBulanLabel($periode->bulan),
                'status' => $periode->status,
                'kegiatan' => [
                    'hashed_id' => $periode->kegiatan->hashed_id,
                    'kode_kegiatan' => $periode->kegiatan->kode_kegiatan,
                    'nama_kegiatan' => $periode->kegiatan->nama_kegiatan,
                    'jenis_kegiatan' => $periode->kegiatan->jenis_kegiatan,
                    'tahun_anggaran' => $periode->kegiatan->tahun_anggaran,
                ],
                'jumlah_petugas' => $periode->alokasiPetugas->count(),
                'spk_status' => $spkStatus,
                'spk_status_type' => $spkStatusType,
                'spk_count' => $spkCount,
                'latest_spk' => $latestSpk ? [
                    'id' => $latestSpk->id,
                    'hashed_id' => $latestSpk->hashed_id,
                    'nomor_spk' => $latestSpk->nomor_spk,
                    'tanggal_spk' => $latestSpk->tanggal_spk,
                    'status' => $latestSpk->status,
                    'file_path' => $latestSpk->file_path,
                ] : null,
            ];
        });

        return Inertia::render('Spk/Index', [
            'periodeList' => $periodeList,
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
            'alokasiPetugas.petugas',
        ])->findOrFail($periodeId);

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
            'petugas_list' => $periode->alokasiPetugas->map(function ($alokasi) {
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
            }),
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
            'nomor_bast' => ['required', 'string', 'max:255'],
            'tanggal_bast' => ['required', 'date'],
        ]);

        $periode = PeriodeAlokasi::with(['kegiatan', 'alokasiPetugas.petugas'])->findOrFail($periodeId);
        $alokasi = AlokasiPetugas::with('petugas')->where('periode_alokasi_id', $periodeId)->where('petugas_id', $petugasId)->firstOrFail();

        // Get active Kepala BPS
        $kepalaBps = \App\Models\KepalaBps::active()->firstOrFail();

        // Get total honor for this petugas
        $totalHonor = $this->calculateTotalHonor($periode->kegiatan, $alokasi);

        // Get uraian tugas details
        $uraianTugas = $this->getUraianTugas($periode->kegiatan, $alokasi);

        // Get beban anggaran (MAK)
        $bebanAnggaran = $this->getBebanAnggaran($periode->kegiatan);

        $data = [
            'periode' => $periode,
            'alokasi' => $alokasi,
            'petugas' => $alokasi->petugas,
            'kegiatan' => $periode->kegiatan,
            'nomorSpk' => $validated['nomor_spk'],
            'tanggalSpk' => \Carbon\Carbon::parse($validated['tanggal_spk']),
            'nomorBast' => $validated['nomor_bast'],
            'tanggalBast' => \Carbon\Carbon::parse($validated['tanggal_bast']),
            'kepalaBps' => preg_replace('/,.*$/', '', $kepalaBps->nama),
            'totalHonor' => $totalHonor,
            'uraianTugas' => $uraianTugas,
            'bebanAnggaran' => $bebanAnggaran,
        ];

        // Generate 3 separate PDFs and merge them
        $pdfMain = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-main', $data)
            ->setPaper('a4', 'portrait');
        
        $pdfLampiran = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-lampiran', $data)
            ->setPaper('a4', 'landscape');
        
        $pdfBast = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-bast', $data)
            ->setPaper('a4', 'portrait');

        // Save temporary PDFs
        $tempPath = storage_path('app/temp');
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        $timestamp = time() . '_' . uniqid();
        $mainPath = $tempPath . '/spk_main_' . $timestamp . '.pdf';
        $lampiranPath = $tempPath . '/spk_lampiran_' . $timestamp . '.pdf';
        $bastPath = $tempPath . '/spk_bast_' . $timestamp . '.pdf';
        $mergedPath = $tempPath . '/spk_merged_' . $timestamp . '.pdf';

        file_put_contents($mainPath, $pdfMain->output());
        file_put_contents($lampiranPath, $pdfLampiran->output());
        file_put_contents($bastPath, $pdfBast->output());

        // Try to merge PDFs
        $merged = \App\Services\PdfMergerService::mergePdfFiles(
            [$mainPath, $lampiranPath, $bastPath],
            $mergedPath
        );

        if ($merged && file_exists($mergedPath)) {
            $pdfContent = file_get_contents($mergedPath);
            
            // Cleanup temporary files
            @unlink($mainPath);
            @unlink($lampiranPath);
            @unlink($bastPath);
            @unlink($mergedPath);

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="Preview_SPK_'.$alokasi->petugas->nama.'.pdf"');
        }

        // Cleanup and fallback to single PDF if merge failed
        @unlink($mainPath);
        @unlink($lampiranPath);
        @unlink($bastPath);
        @unlink($mergedPath);

        // Fallback: use old single-file approach with landscape
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-petugas', $data)
            ->setPaper('a4', 'portrait');
        
        return $pdf->stream('Preview_SPK_'.$alokasi->petugas->nama.'.pdf');
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
            'nomor_bast' => ['required', 'string', 'max:255'],
            'tanggal_bast' => ['required', 'date'],
        ]);

        $periode = PeriodeAlokasi::with(['kegiatan', 'alokasiPetugas.petugas'])->findOrFail($periodeId);
        $alokasi = AlokasiPetugas::with('petugas')->where('periode_alokasi_id', $periodeId)->where('petugas_id', $petugasId)->firstOrFail();

        // Get active Kepala BPS
        $kepalaBps = \App\Models\KepalaBps::active()->firstOrFail();

        // Get total honor for this petugas
        $totalHonor = $this->calculateTotalHonor($periode->kegiatan, $alokasi);

        // Get uraian tugas details
        $uraianTugas = $this->getUraianTugas($periode->kegiatan, $alokasi);

        // Get beban anggaran (MAK)
        $bebanAnggaran = $this->getBebanAnggaran($periode->kegiatan);

        $data = [
            'periode' => $periode,
            'alokasi' => $alokasi,
            'petugas' => $alokasi->petugas,
            'kegiatan' => $periode->kegiatan,
            'nomorSpk' => $validated['nomor_spk'],
            'tanggalSpk' => \Carbon\Carbon::parse($validated['tanggal_spk']),
            'nomorBast' => $validated['nomor_bast'],
            'tanggalBast' => \Carbon\Carbon::parse($validated['tanggal_bast']),
            'kepalaBps' => preg_replace('/,.*$/', '', $kepalaBps->nama),
            'totalHonor' => $totalHonor,
            'uraianTugas' => $uraianTugas,
            'bebanAnggaran' => $bebanAnggaran,
        ];

        DB::beginTransaction();
        try {
            // Generate 3 separate PDFs
            $pdfMain = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-main', $data)
                ->setPaper('a4', 'portrait');
            
            $pdfLampiran = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-lampiran', $data)
                ->setPaper('a4', 'landscape');
            
            $pdfBast = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-bast', $data)
                ->setPaper('a4', 'portrait');

            // Save temporary PDFs
            $tempPath = storage_path('app/temp');
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0777, true);
            }

            $timestamp = time() . '_' . uniqid();
            $mainPath = $tempPath . '/spk_main_' . $timestamp . '.pdf';
            $lampiranPath = $tempPath . '/spk_lampiran_' . $timestamp . '.pdf';
            $bastPath = $tempPath . '/spk_bast_' . $timestamp . '.pdf';
            $mergedPath = $tempPath . '/spk_merged_' . $timestamp . '.pdf';

            file_put_contents($mainPath, $pdfMain->output());
            file_put_contents($lampiranPath, $pdfLampiran->output());
            file_put_contents($bastPath, $pdfBast->output());

            // Try to merge PDFs
            $merged = \App\Services\PdfMergerService::mergePdfFiles(
                [$mainPath, $lampiranPath, $bastPath],
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
            @unlink($bastPath);
            @unlink($mergedPath);

            // Save PDF file
            $fileName = $data['nomorSpk'].'_SPK '.$data['kegiatan']->nama_kegiatan.'_'.$alokasi->petugas->nama.'_'.$periode->bulan.'_'.$periode->tahun.'.pdf';
            $filePath = 'spk/'.date('Y').'/'.date('m').'/'.$fileName;
            \Storage::put($filePath, $pdfOutput);

            // Save to database
            $spk = Spk::create([
                'nomor_spk' => $validated['nomor_spk'],
                'alokasi_mitra_id' => $alokasi->id,
                'tanggal_spk' => $validated['tanggal_spk'],
                'tanggal_mulai_kerja' => \Carbon\Carbon::create($periode->tahun, $periode->bulan, 1),
                'tanggal_selesai_kerja' => \Carbon\Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth(),
                'nilai_kontrak' => $totalHonor,
                'nama_ppk' => preg_replace('/,.*$/', '', $kepalaBps->nama),
                'nip_ppk' => $kepalaBps->nip ?? null,
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
        if ($rateHonor->rate_listing && $alokasi->target_listing) {
            $total += $rateHonor->rate_listing * $alokasi->target_listing;
        }

        // Calculate from regular rate
        if ($rateHonor->rate && $alokasi->target_pencacahan) {
            $total += $rateHonor->rate * $alokasi->target_pencacahan;
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

        if ($rateHonor) {
            $peranLabel = $this->getPeranLabel($alokasi->peran);

            // Add listing task if exists
            if ($rateHonor->rate_listing && $alokasi->target_listing) {
                $uraian[] = [
                    'uraian' => "Honor Petugas {$peranLabel} {$kegiatan->nama_kegiatan} {$kegiatan->tahun_anggaran}",
                    'volume' => $alokasi->target_listing,
                    'satuan' => $rateHonor->satuanListing->kode ?? 'DOK',
                    'harga_satuan' => $rateHonor->rate_listing,
                    'jumlah' => $rateHonor->rate_listing * $alokasi->target_listing,
                ];
            }

            // Add regular task
            if ($rateHonor->rate && $alokasi->target_pencacahan) {
                $uraian[] = [
                    'uraian' => "Honor Petugas {$peranLabel} {$kegiatan->nama_kegiatan} {$kegiatan->tahun_anggaran}",
                    'volume' => $alokasi->target_pencacahan,
                    'satuan' => $rateHonor->satuan->kode ?? 'DOK',
                    'harga_satuan' => $rateHonor->rate,
                    'jumlah' => $rateHonor->rate * $alokasi->target_pencacahan,
                ];
            }
        }

        return $uraian;
    }

    /**
     * Get beban anggaran (MAK)
     */
    private function getBebanAnggaran(Kegiatan $kegiatan): string
    {
        $dipa = \App\Models\Dipa::active()->first();

        return $dipa->mak ?? '2904.BMA.006.005.A.521213';
    }

    /**
     * Get peran label
     */
    private function getPeranLabel(string $peran): string
    {
        return match ($peran) {
            'pencacah' => 'Pendataan Lapangan',
            'pengawas' => 'Pengawas',
            'pemeriksa' => 'Pemeriksa',
            'ketua_tim' => 'Ketua Tim',
            default => $peran,
        };
    }
}
