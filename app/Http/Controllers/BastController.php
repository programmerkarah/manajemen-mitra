<?php

namespace App\Http\Controllers;

use App\Models\AlokasiPetugas;
use App\Models\Bast;
use App\Models\BastKegiatan;
use App\Models\BastPetugas;
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
    // Role constants
    private const PENDATAAN_ROLES = ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'];

    private const PENGOLAHAN_ROLES = ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'];

    /**
     * Check if any petugas has pengolahan allocation with jumlah_satuan > 0
     */
    private function hasPengolahanListing($petugas): bool
    {
        return collect($petugas)->contains(function ($p) {
            return in_array($p['peran'] ?? null, self::PENGOLAHAN_ROLES, true)
                && (int) ($p['hasil_pengolahan'] ?? $p['jumlah_satuan'] ?? 0) > 0;
        });
    }

    /**
     * Check if any petugas has both pengolahan and pendataan allocation with valid hasil
     */
    private function hasPengolahanPendataan($petugas): bool
    {
        return collect($petugas)->contains(function ($p) {
            return in_array($p['peran'] ?? null, self::PENGOLAHAN_ROLES, true)
                && in_array($p['peran'] ?? null, self::PENDATAAN_ROLES, true)
                && (int) ($p['hasil_pengolahan'] ?? 0) > 0
                && (int) ($p['hasil_pendataan_lapangan'] ?? 0) > 0;
        });
    }

    /**
     * Check if any petugas has pendataan allocation with hasil_pendataan_lapangan > 0
     */
    private function hasPendataan($petugas): bool
    {
        return collect($petugas)->contains(function ($p) {
            return in_array($p['peran'] ?? null, self::PENDATAAN_ROLES, true)
                && (int) ($p['hasil_pendataan_lapangan'] ?? 0) > 0;
        });
    }

    /**
     * Check if any petugas has listing allocation with hasil_listing > 0
     */
    private function hasListing($petugas): bool
    {
        return collect($petugas)->contains(function ($p) {
            return in_array($p['peran'] ?? null, self::PENDATAAN_ROLES, true)
                && (int) ($p['hasil_listing'] ?? 0) > 0;
        });
    }

    /**
     * Display a listing of the resource.
     * Menampilkan periode bulan (Januari-Desember) dengan informasi BAST yang sudah/belum dibuat
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $activeYear = \App\Services\ActiveYearService::get();

        $data = [];

        // Generate data untuk 12 bulan
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

            // Hitung total SPK di bulan ini (original SPK saja)
            $totalSpk = Spk::where('addendum_number', 0)
                ->whereYear('tanggal_spk', $activeYear)
                ->whereMonth('tanggal_spk', $bulan)
                ->count();

            // Hitung SPK yang sudah punya BAST
            $spkWithBast = Spk::where('addendum_number', 0)
                ->whereYear('tanggal_spk', $activeYear)
                ->whereMonth('tanggal_spk', $bulan)
                ->whereHas('bast')
                ->count();

            // Hitung SPK yang belum punya BAST
            $spkWithoutBast = $totalSpk - $spkWithBast;

            $data[] = [
                'bulan' => $bulan,
                'bulan_label' => $this->getBulanLabel($bulan),
                'tahun' => $activeYear,
                'total_spk' => $totalSpk,
                'spk_with_bast' => $spkWithBast,
                'spk_without_bast' => $spkWithoutBast,
                'has_spk' => $totalSpk > 0,
                'all_completed' => $totalSpk > 0 && $spkWithoutBast === 0,
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
    /**
     * Show form to create BAST for a specific month
     * List all SPK in that month that don't have BAST yet
     */
    public function create(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun', \App\Services\ActiveYearService::get());

        if (! $bulan) {
            return redirect()->route('bast.index')
                ->with('error', 'Bulan harus diisi');
        }

        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Ambil SPK original yang belum punya BAST di bulan ini
        $spks = Spk::where('addendum_number', 0)
            ->whereYear('tanggal_spk', $tahun)
            ->whereMonth('tanggal_spk', $bulan)
            ->whereDoesntHave('bast')
            ->with([
                'alokasiPetugas.petugas:id,nama,nik,alamat',
                'alokasiPetugas.periodeAlokasi.kegiatan:id,kode_kegiatan,nama_kegiatan,ketua_tim_user_id',
                'alokasiPetugas.periodeAlokasi.kegiatan.ketuaTim:id,name,nip',
            ])
            ->orderBy('nomor_spk')
            ->get();

        if ($spks->isEmpty()) {
            return redirect()->route('bast.index')
                ->with('info', 'Tidak ada SPK yang belum memiliki BAST di bulan ini');
        }

        // Format data SPK dengan detail kegiatan yang diikuti petugas
        $spkList = $spks->map(function ($spk) use ($bulanFormatted, $tahun) {
            $petugas = $spk->alokasiPetugas?->petugas;

            // Ambil SEMUA alokasi petugas untuk bulan ini (semua kegiatan yang diikuti petugas di bulan yang sama)
            $allAlokasi = AlokasiPetugas::with([
                'periodeAlokasi.kegiatan',
                'spk' => function ($query) {
                    $query->orderByDesc('addendum_number'); // prioritas addendum terbaru
                },
            ])
                ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                    $q->where('bulan', $bulanFormatted)
                        ->where('tahun', $tahun)
                        ->whereIn('status', ['dikirim', 'disetujui', 'direvisi']);
                })
                ->where('petugas_id', $petugas?->id)
                // REMOVED ->whereHas('spk') - show ALL allocations, even without SPK
                ->get();

            // Kumpulkan kegiatan unik dengan detail dari semua alokasi petugas
            $kegiatanList = $allAlokasi->map(function ($alokasi) {
                $kegiatan = $alokasi->periodeAlokasi?->kegiatan;
                $spkTerkait = $alokasi->spk?->first(); // ambil SPK terbaru (bisa null)

                return [
                    'kegiatan_id' => $kegiatan?->id,
                    'kode_kegiatan' => $kegiatan?->kode_kegiatan,
                    'nama_kegiatan' => $kegiatan?->nama_kegiatan,
                    'tanggal_selesai' => $spkTerkait?->tanggal_selesai_kerja?->format('Y-m-d') ?? null,
                    'tanggal_selesai_label' => $spkTerkait?->tanggal_selesai_kerja?->format('d/m/Y') ?? 'Belum ada SPK',
                    'peran' => $alokasi?->peran,
                    'hasil_listing' => $alokasi?->jumlah_satuan_listing,
                    'hasil_pendataan_lapangan' => $alokasi?->jumlah_satuan,
                    'hasil_pengolahan' => in_array($alokasi?->peran, self::PENGOLAHAN_ROLES) ? $alokasi?->jumlah_satuan : null,
                    'spk_id' => $spkTerkait?->id,
                    'nomor_spk' => $spkTerkait?->nomor_spk ?? 'Belum ada SPK',
                ];
            })->filter(function ($k) {
                return $k['kegiatan_id'] !== null;
            })->values();

            // Tentukan tanggal berakhir paling akhir dari semua SPK terkait
            $tanggalBerakhirPalingAkhir = $allAlokasi->map(function ($alokasi) {
                return $alokasi->spk?->first()?->tanggal_selesai_kerja;
            })->filter()->max();

            $ketuaTim = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->ketuaTim;

            return [
                'spk_id' => $spk->id,
                'spk_hashed_id' => $spk->hashed_id,
                'nomor_spk' => $spk->nomor_spk,
                'tanggal_spk' => $spk->tanggal_spk?->format('Y-m-d'),
                'tanggal_mulai_kerja' => $spk->tanggal_mulai_kerja?->format('Y-m-d'),
                'tanggal_selesai_kerja_asli' => $spk->tanggal_selesai_kerja?->format('Y-m-d'),
                'tanggal_berakhir_paling_akhir' => $tanggalBerakhirPalingAkhir?->format('Y-m-d'),
                'nama_ppk' => $spk->nama_ppk,
                'nip_ppk' => $spk->nip_ppk,
                'petugas' => [
                    'id' => $petugas?->id,
                    'nama' => $petugas?->nama,
                    'nik' => $petugas?->nik,
                    'alamat' => $petugas?->alamat,
                ],
                'ketua_tim' => [
                    'nama' => $ketuaTim?->name,
                    'nip' => $ketuaTim?->nip,
                ],
                'kegiatan_list' => $kegiatanList,
                'jumlah_kegiatan' => $kegiatanList->count(),
            ];
        })->values();

        return Inertia::render('Bast/CreateForMonth', [
            'bulan' => (int) $bulan,
            'tahun' => $tahun,
            'bulan_label' => $this->getBulanLabel((int) $bulan),
            'spk_list' => $spkList,
        ]);
    }

    /**
     * Generate BAST secara batch untuk multiple SPK
     * Prinsip: 1 SPK = 1 BAST dengan lampiran per kegiatan
     */
    public function generateBatch(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'spk_ids' => 'required|array|min:1',
            'spk_ids.*' => 'required|integer|exists:spk,id',
        ]);

        $successCount = 0;
        $failedSpk = [];

        DB::beginTransaction();
        try {
            foreach ($request->spk_ids as $spkId) {
                try {
                    $spk = Spk::with([
                        'alokasiPetugas.petugas',
                        'alokasiPetugas.periodeAlokasi.kegiatan.ketuaTim',
                    ])->findOrFail($spkId);

                    // Check if BAST already exists for this SPK
                    if ($spk->bast()->exists()) {
                        $failedSpk[] = [
                            'nomor_spk' => $spk->nomor_spk,
                            'reason' => 'BAST sudah ada',
                        ];

                        continue;
                    }

                    // Ambil semua SPK (original + addendum) untuk menentukan tanggal berakhir
                    $allSpks = Spk::where(function ($q) use ($spk) {
                        $q->where('id', $spk->id)
                            ->orWhere('parent_spk_id', $spk->id);
                    })
                        ->with(['alokasiPetugas.periodeAlokasi.kegiatan'])
                        ->get();

                    // Tentukan tanggal berakhir paling akhir dari semua kegiatan
                    $tanggalBerakhirPalingAkhir = $allSpks->max('tanggal_selesai_kerja');

                    if (! $tanggalBerakhirPalingAkhir) {
                        $failedSpk[] = [
                            'nomor_spk' => $spk->nomor_spk,
                            'reason' => 'Tidak ada tanggal selesai kerja',
                        ];

                        continue;
                    }

                    // Create BAST record
                    $bast = Bast::create([
                        'spk_id' => $spk->id,
                        'nomor_bast' => $this->generateNomorBastForSpk($tanggalBerakhirPalingAkhir),
                        'tanggal_bast' => $tanggalBerakhirPalingAkhir,
                        'tanggal_pelaksanaan' => $spk->tanggal_mulai_kerja,
                        'tanggal_selesai' => $tanggalBerakhirPalingAkhir,
                        'lokasi_kegiatan' => 'Kota Sawahlunto',
                        'keterangan' => null,
                        'created_by_user_id' => Auth::id(),
                    ]);

                    // Create BastPetugas records untuk setiap kegiatan
                    foreach ($allSpks as $spkKegiatan) {
                        $alokasi = $spkKegiatan->alokasiPetugas;
                        $kegiatan = $alokasi?->periodeAlokasi?->kegiatan;

                        if (! $kegiatan) {
                            continue;
                        }

                        $isPendataanRole = in_array($alokasi->peran, self::PENDATAAN_ROLES, true);
                        $isPengolahanRole = in_array($alokasi->peran, self::PENGOLAHAN_ROLES, true);

                        // Cek hasil listing
                        $hasListing = ($kegiatan->has_listing_updating ?? false)
                            || ($alokasi->jumlah_satuan_listing ?? 0) > 0;

                        BastPetugas::create([
                            'bast_id' => $bast->id,
                            'petugas_id' => $alokasi->petugas_id,
                            'kegiatan_id' => $kegiatan->id,
                            'spk_id' => $spkKegiatan->id,
                            'nomor_spk' => $spkKegiatan->nomor_spk,
                            'tanggal_selesai' => $spkKegiatan->tanggal_selesai_kerja,
                            'hasil_listing' => ($hasListing && $isPendataanRole) ? $alokasi->jumlah_satuan_listing : null,
                            'hasil_pendataan_lapangan' => $isPendataanRole ? $alokasi->jumlah_satuan : null,
                            'hasil_pengolahan' => $isPengolahanRole ? $alokasi->jumlah_satuan : null,
                            'keterangan' => $alokasi->catatan,
                        ]);
                    }

                    $successCount++;
                } catch (\Exception $e) {
                    $failedSpk[] = [
                        'nomor_spk' => $spk->nomor_spk ?? 'Unknown',
                        'reason' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            $message = "Berhasil generate {$successCount} BAST";
            if (count($failedSpk) > 0) {
                $failedList = collect($failedSpk)->map(fn ($f) => "{$f['nomor_spk']} ({$f['reason']})")->join(', ');
                $message .= ". Gagal: {$failedList}";
            }

            return redirect()->route('bast.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal generate BAST: '.$e->getMessage());
        }
    }

    /**
     * Generate nomor BAST otomatis untuk SPK
     */
    private function generateNomorBastForSpk(\Carbon\Carbon $tanggalBast): string
    {
        $tahun = $tanggalBast->year;
        $bulan = $tanggalBast->month;

        // Get last BAST number for this month
        $lastBast = Bast::whereYear('tanggal_bast', $tahun)
            ->whereMonth('tanggal_bast', $bulan)
            ->orderByDesc('id')
            ->first();

        if ($lastBast && preg_match('/^(\d+)\//', $lastBast->nomor_bast, $matches)) {
            $urut = (int) $matches[1] + 1;
        } else {
            $urut = 1;
        }

        $bulanRomawi = $this->getRomanMonth($bulan);

        return sprintf('PPIS/13730/%d/BAST/%d', $urut, $tahun);
    }

    /**
     * Convert month number to Roman numeral
     */
    private function getRomanMonth(int $month): string
    {
        $romans = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        return $romans[$month - 1] ?? 'I';
    }

    /**
     * Preview BAST untuk specific SPK
     */
    public function previewForSpk(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $request->validate([
            'spk_id' => 'required|integer|exists:spk,id',
        ]);

        $spk = Spk::with([
            'alokasiPetugas.petugas',
            'alokasiPetugas.periodeAlokasi.kegiatan.ketuaTim',
        ])->findOrFail($request->spk_id);

        $petugas = $spk->alokasiPetugas?->petugas;
        $bulan = date('m', strtotime($spk->tanggal_mulai_kerja));
        $tahun = date('Y', strtotime($spk->tanggal_mulai_kerja));

        // Ambil semua alokasi untuk petugas yang sama dalam bulan dan tahun yang sama
        $allAlokasi = AlokasiPetugas::where('petugas_id', $petugas->id)
            ->whereHas('periodeAlokasi', function ($q) use ($bulan, $tahun) {
                $q->where('bulan', $bulan)
                    ->where('tahun', $tahun);
            })
            ->with([
                'periodeAlokasi.kegiatan.rateHonors.satuan',
                'periodeAlokasi.kegiatan.rateHonors.satuanListing',
                'spk',
            ])
            ->get();

        // Tentukan tanggal berakhir paling akhir dari semua SPK
        $tanggalBerakhirPalingAkhir = $allAlokasi->map(function ($alokasi) {
            return $alokasi->spk?->first()?->tanggal_selesai_kerja ?? $alokasi->tanggal_selesai ?? null;
        })->filter()->max();

        // Fallback ke tanggal SPK original jika tidak ada yang lain
        if (! $tanggalBerakhirPalingAkhir) {
            $tanggalBerakhirPalingAkhir = $spk->tanggal_selesai_kerja;
        }

        if (! $tanggalBerakhirPalingAkhir) {
            return back()->with('error', 'Tidak ada tanggal selesai kerja pada SPK ini');
        }

        $ketuaTim = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->ketuaTim;

        // Generate nomor BAST dengan urutan
        $noUrutBAST = $this->generateNomorBastForSpk($tanggalBerakhirPalingAkhir);

        // Format data untuk preview
        $bastData = [
            'nomor_bast' => $noUrutBAST,
            'tanggal_bast' => $tanggalBerakhirPalingAkhir,
            'tanggal_pelaksanaan' => $spk->tanggal_mulai_kerja,
            'tanggal_selesai' => $tanggalBerakhirPalingAkhir,
            'lokasi_kegiatan' => 'Kota Sawahlunto',
            'nama_ppk' => $spk->nama_ppk,
            'nip_ppk' => $spk->nip_ppk,
            'petugas' => [
                'nama' => $petugas?->nama,
                'nik' => $petugas?->nik,
                'alamat' => $petugas?->alamat,
            ],
            'ketua_tim' => [
                'nama' => $ketuaTim?->name,
                'nip' => $ketuaTim?->nip,
            ],
            'kegiatan_list' => [],
        ];

        // Build kegiatan list dengan lampiran
        foreach ($allAlokasi as $alokasi) {
            $kegiatan = $alokasi->periodeAlokasi?->kegiatan;

            if (! $kegiatan) {
                continue;
            }

            $rateHonor = $kegiatan->rateHonors->first(function ($rate) use ($alokasi) {
                return $rate->status_kepegawaian === $alokasi->status_kepegawaian
                    && $rate->jenis_penugasan === $alokasi->peran;
            });

            $isPendataanRole = in_array($alokasi->peran, self::PENDATAAN_ROLES, true);
            $isPengolahanRole = in_array($alokasi->peran, self::PENGOLAHAN_ROLES, true);
            $hasListing = ($kegiatan->has_listing_updating ?? false) || ($alokasi->jumlah_satuan_listing ?? 0) > 0;

            // Tentukan nomor SPK dan tanggal selesai
            $spkFirst = $alokasi->spk?->first();
            $nomorSpk = $spkFirst?->nomor_spk ?? 'Belum ada SPK';
            $tanggalSelesai = $spkFirst?->tanggal_selesai_kerja ?? ($alokasi->tanggal_selesai ?? 'Belum ada SPK');
            $uraianPekerjaan = $spkFirst?->uraian_pekerjaan ?? 'Belum ada uraian';

            $bastData['kegiatan_list'][] = [
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                'nomor_spk' => $nomorSpk,
                'tanggal_selesai' => $tanggalSelesai,
                'uraian_pekerjaan' => $uraianPekerjaan,
                'peran' => $alokasi->peran,
                'hasil_listing' => ($hasListing && $isPendataanRole) ? $alokasi->jumlah_satuan_listing : null,
                'satuan_listing' => ($hasListing && $isPendataanRole) ? $rateHonor?->satuanListing?->nama : null,
                'hasil_pendataan_lapangan' => $isPendataanRole ? $alokasi->jumlah_satuan : null,
                'satuan_pendataan' => $isPendataanRole ? $rateHonor?->satuan?->nama : null,
                'hasil_pengolahan' => $isPengolahanRole ? $alokasi->jumlah_satuan : null,
                'satuan_pengolahan' => $isPengolahanRole ? $rateHonor?->satuan?->nama : null,
                'keterangan' => $alokasi->catatan,
            ];
        }

        // Generate BAST utama dan lampiran gabungan
        $bastObject = (object) $bastData;

        // Kirim variabel tambahan yang dibutuhkan template
        $viewData = [
            'bast' => $bastObject,
            'nomor_bast' => $bastData['nomor_bast'],
            'hari' => \Carbon\Carbon::parse($tanggalBerakhirPalingAkhir)->locale('id')->isoFormat('dddd'),
            'menggunakan_fasih' => false,
            'jabatan_ppk' => 'Pejabat Pembuat Komitmen Badan Pusat Statistik Kota Sawahlunto untuk Program Penyediaan dan Pelayanan Informasi Statistik',
            'alamat_unit_kerja' => 'Jl. Bagindo Aziz Chan, Kel. Aur Mulyo, Kec. Lembah Segar, Kota Sawahlunto',
        ];

        $htmlContent = view('bast', $viewData)->render();
        $htmlContent .= '<div style="page-break-after: always;"></div>';
        $htmlContent .= view('bast-lampiran-spk', ['bast' => $bastObject])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($htmlContent);
        $pdf->setPaper('a4', 'portrait');

        $cleanNomorBast = str_replace(['/', '\\'], '-', $bastData['nomor_bast']);

        return $pdf->stream('preview-bast-'.$cleanNomorBast.'-'.$bastData['petugas']['nama'].'.pdf');
    }

    /**
     * Download PDF BAST yang sudah tersimpan
     */
    public function downloadPdf(Bast $bast): \Symfony\Component\HttpFoundation\Response
    {
        $bast->load([
            'spk.alokasiPetugas.petugas',
            'spk.alokasiPetugas.periodeAlokasi.kegiatan.ketuaTim',
            'bastPetugas.kegiatan',
            'bastPetugas.petugas',
        ]);

        $spk = $bast->spk;
        $petugas = $spk->alokasiPetugas?->petugas;
        $ketuaTim = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->ketuaTim;

        // Build kegiatan list dari BastPetugas
        $kegiatanList = $bast->bastPetugas->map(function ($bp) {
            $alokasi = AlokasiPetugas::where('petugas_id', $bp->petugas_id)
                ->whereHas('periodeAlokasi', fn ($q) => $q->where('kegiatan_id', $bp->kegiatan_id))
                ->first();

            $kegiatan = $bp->kegiatan;
            $rateHonor = $kegiatan->rateHonors->first(function ($rate) use ($alokasi) {
                return $alokasi && $rate->status_kepegawaian === $alokasi->status_kepegawaian
                    && $rate->jenis_penugasan === $alokasi->peran;
            });

            $isPendataanRole = in_array($alokasi?->peran, self::PENDATAAN_ROLES, true);
            $isPengolahanRole = in_array($alokasi?->peran, self::PENGOLAHAN_ROLES, true);

            return [
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                'nomor_spk' => $bp->nomor_spk,
                'tanggal_selesai' => $bp->tanggal_selesai,
                'peran' => $alokasi?->peran,
                'hasil_listing' => $bp->hasil_listing,
                'satuan_listing' => $isPendataanRole ? $rateHonor?->satuanListing?->nama : null,
                'hasil_pendataan_lapangan' => $bp->hasil_pendataan_lapangan,
                'satuan_pendataan' => $isPendataanRole ? $rateHonor?->satuan?->nama : null,
                'hasil_pengolahan' => $bp->hasil_pengolahan,
                'satuan_pengolahan' => $isPengolahanRole ? $rateHonor?->satuan?->nama : null,
                'keterangan' => $bp->keterangan,
            ];
        });

        $bastData = [
            'nomor_bast' => $bast->nomor_bast,
            'tanggal_bast' => $bast->tanggal_bast,
            'tanggal_pelaksanaan' => $bast->tanggal_pelaksanaan,
            'tanggal_selesai' => $bast->tanggal_selesai,
            'lokasi_kegiatan' => $bast->lokasi_kegiatan,
            'nama_ppk' => $spk->nama_ppk,
            'nip_ppk' => $spk->nip_ppk,
            'petugas' => [
                'nama' => $petugas?->nama,
                'nik' => $petugas?->nik,
                'alamat' => $petugas?->alamat,
            ],
            'ketua_tim' => [
                'nama' => $ketuaTim?->name,
                'nip' => $ketuaTim?->nip,
            ],
            'kegiatan_list' => $kegiatanList->toArray(),
        ];

        $bastObject = (object) $bastData;

        // Kirim variabel tambahan yang dibutuhkan template
        $viewData = [
            'bast' => $bastObject,
            'nomor_bast' => $bastData['nomor_bast'],
            'hari' => \Carbon\Carbon::parse($bast->tanggal_bast)->locale('id')->isoFormat('dddd'),
            'menggunakan_fasih' => false,
            'jabatan_ppk' => 'Pejabat Pembuat Komitmen Badan Pusat Statistik Kota Sawahlunto untuk Program Penyediaan dan Pelayanan Informasi Statistik',
            'alamat_unit_kerja' => 'Jl. Bagindo Aziz Chan, Kel. Aur Mulyo, Kec. Lembah Segar, Kota Sawahlunto',
        ];

        // Generate BAST + Lampiran
        $htmlContent = view('bast', $viewData)->render();
        $htmlContent .= '<div style="page-break-after: always;"></div>';
        $htmlContent .= view('bast-lampiran-spk', ['bast' => $bastObject])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($htmlContent);
        $pdf->setPaper('a4', 'portrait');

        $cleanNomorBast = str_replace(['/', '\\'], '-', $bast->nomor_bast);
        $filename = 'BAST-'.$cleanNomorBast.'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Show form to create BAST for a specific kegiatan
     */
    public function createForKegiatan(string $kegiatanHashedId): Response|\Illuminate\Http\RedirectResponse
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

        // Check if BAST already exists for this kegiatan and periode
        if ($targetPeriode) {
            $existingBast = Bast::where('kegiatan_id', $kegiatanId)
                ->where('periode_alokasi_id', $targetPeriode->id)
                ->first();

            if ($existingBast) {
                return redirect()->route('bast.index')
                    ->with('error', 'BAST untuk kegiatan dan periode ini sudah pernah dibuat.');
            }
        }

        $status = $targetPeriode?->status ?? ($periodeWithPerubahan ? 'perubahan' : 'dikirim');

        // Get all petugas untuk periode target, ambil SPK terbaru (original/addendum) tanpa membatasi status
        // IMPORTANT: Get ALL kegiatan allocations for each petugas in the same month, not just this specific kegiatan
        $alokasiPetugasRaw = AlokasiPetugas::query()
            ->when($targetPeriode, function ($query) use ($targetPeriode) {
                // For specific periode: get all kegiatan for same month/year
                $query->whereHas('periodeAlokasi', function ($q) use ($targetPeriode) {
                    $q->where('bulan', $targetPeriode->bulan)
                        ->where('tahun', $targetPeriode->tahun);
                });
            })
            ->when(! $targetPeriode, function ($query) use ($kegiatanId, $status) {
                // For status-based: get all kegiatan for the specific kegiatan's month/year
                $kegiatanPeriode = PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
                    ->where('status', $status)
                    ->first();

                if ($kegiatanPeriode) {
                    $query->whereHas('periodeAlokasi', function ($q) use ($kegiatanPeriode, $status) {
                        $q->where('bulan', $kegiatanPeriode->bulan)
                            ->where('tahun', $kegiatanPeriode->tahun)
                            ->where('status', $status);
                    });
                }
            })
            ->whereHas('spk')
            ->with([
                'petugas',
                'spk' => function ($query) {
                    $query->orderByDesc('addendum_number')->orderByDesc('id'); // pick latest SPK (original or addendum)
                },
                'periodeAlokasi.kegiatan', // Load kegiatan relationship
            ])
            ->get();

        $hasListing = ($kegiatan->has_listing_updating ?? false)
            || $this->hasListing($alokasiPetugasRaw);

        $hasPengolahan = $this->hasPengolahanListing($alokasiPetugasRaw);

        // Group alokasi by petugas and collect all their kegiatan
        $petugasGrouped = $alokasiPetugasRaw
            ->filter(function ($alokasi) {
                return $alokasi->spk?->isNotEmpty();
            })
            ->groupBy('petugas_id');

        $alokasiPetugas = $petugasGrouped->map(function ($alokasiGroup) use ($kegiatan, $hasListing) {
            // Use first alokasi as base (all should have same petugas and SPK)
            $firstAlokasi = $alokasiGroup->first();
            $spk = $firstAlokasi->spk?->first(); // ambil SPK pertama dari relasi HasMany

            // Collect all kegiatan for this petugas
            $kegiatanList = $alokasiGroup->map(function ($alokasi) {
                $kegiatanAlokasi = $alokasi->periodeAlokasi->kegiatan;

                return [
                    'kegiatan_id' => $kegiatanAlokasi->id,
                    'kode_kegiatan' => $kegiatanAlokasi->kode_kegiatan,
                    'nama_kegiatan' => $kegiatanAlokasi->nama_kegiatan,
                    'peran' => $alokasi->peran,
                    'jumlah_satuan' => $alokasi->jumlah_satuan,
                    'jumlah_satuan_listing' => $alokasi->jumlah_satuan_listing,
                    'bulan' => $alokasi->periodeAlokasi->bulan,
                    'tahun' => $alokasi->periodeAlokasi->tahun,
                ];
            })->toArray();

            // Calculate aggregate data for BAST table
            $totalSatuan = $alokasiGroup->sum('jumlah_satuan');
            $totalSatuanListing = $alokasiGroup->sum('jumlah_satuan_listing');

            // Use the main kegiatan (from the form) to get rate honor info
            $rateHonor = $kegiatan->rateHonors->first(function ($rate) use ($firstAlokasi) {
                return $rate->status_kepegawaian === $firstAlokasi->status_kepegawaian
                    && $rate->jenis_penugasan === $firstAlokasi->peran;
            });
            $satuanPendataan = $rateHonor?->satuan?->nama;
            $satuanListing = $rateHonor?->satuanListing?->nama;
            $satuanPengolahan = $rateHonor?->satuan?->nama;
            $isPendataanRole = in_array($firstAlokasi->peran, self::PENDATAAN_ROLES, true);
            $isPengolahanRole = in_array($firstAlokasi->peran, self::PENGOLAHAN_ROLES, true);

            return [
                'id' => $firstAlokasi->id,
                'petugas_id' => $firstAlokasi->petugas->id,
                'spk_id' => $spk->id,
                'nama_petugas' => $firstAlokasi->petugas->nama,
                'nomor_spk' => $spk->nomor_spk,
                'peran' => $firstAlokasi->peran,
                'hasil_listing' => ($hasListing && $isPendataanRole) ? ($totalSatuanListing ?? null) : null,
                'satuan_listing' => ($hasListing && $isPendataanRole) ? ($satuanListing ?? null) : null,
                'hasil_pendataan_lapangan' => $isPendataanRole ? ($totalSatuan ?? null) : null,
                'satuan_pendataan_lapangan' => $isPendataanRole ? ($satuanPendataan ?? null) : null,
                'hasil_pengolahan' => $isPengolahanRole ? ($totalSatuan ?? null) : null,
                'satuan_pengolahan' => $isPengolahanRole ? ($satuanPengolahan ?? null) : null,
                'hasil_pengolahan_listing' => $isPengolahanRole ? ($totalSatuanListing ?? null) : null,
                'satuan_pengolahan_listing' => $isPengolahanRole ? ($satuanListing ?? null) : null,
                'catatan' => $firstAlokasi->catatan,
                'kegiatan_list' => $kegiatanList, // Add kegiatan list for each petugas
            ];
        })
            ->values();

        // Check if there's actual meaningful data in the columns to determine which columns to show
        // Check for listing data (PCL/PPL roles) - treat null as 0
        $hasActualListingData = $alokasiPetugas->some(function ($petugas) {
            return ($petugas['hasil_listing'] ?? 0) > 0;
        });

        // Check for pendataan lapangan data (PCL/PPL roles) - treat null as 0
        $hasActualPendataanData = $alokasiPetugas->some(function ($petugas) {
            return ($petugas['hasil_pendataan_lapangan'] ?? 0) > 0;
        });

        // Check for pengolahan listing data (pengolahan roles) - treat null as 0
        $hasActualPengolahanListingData = $alokasiPetugas->some(function ($petugas) {
            return ($petugas['hasil_pengolahan_listing'] ?? 0) > 0;
        });

        // Check for pengolahan lapangan data (pengolahan roles) - treat null as 0
        $hasActualPengolahanLapanganData = $alokasiPetugas->some(function ($petugas) {
            return ($petugas['hasil_pengolahan'] ?? 0) > 0;
        });

        // Get PPK from penandatangan
        // Get active PPK from penandatangan (by jenis_penandatangan + active + valid date range)
        $penandatangan = Penandatangan::ppk()
            ->active()
            ->where(function ($q) {
                $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', today());
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
            'show_listing_columns' => $hasActualListingData,
            'show_pengolahan_columns' => $hasActualPengolahanListingData || $hasActualPengolahanLapanganData,
            'has_actual_listing_data' => $hasActualListingData,
            'has_actual_pendataan_data' => $hasActualPendataanData,
            'has_actual_pengolahan_listing_data' => $hasActualPengolahanListingData,
            'has_actual_pengolahan_lapangan_data' => $hasActualPengolahanLapanganData,
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
        // First do basic validation
        $basicValidated = $request->validate([
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
            'petugas.*.hasil_pengolahan_listing' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan_listing' => 'nullable|string',
            'petugas.*.catatan' => 'nullable|string',
            'dokumen_rekap' => 'nullable|array',
            'dokumen_rekap.*.nama' => 'nullable|string',
            'dokumen_rekap.*.kode' => 'nullable|string',
            'dokumen_rekap.*.didata' => 'nullable|numeric',
            'dokumen_rekap.*.non_respon' => 'nullable|numeric',
            'dokumen_rekap.*.keterangan' => 'nullable|string',
        ]);

        // Check if we need to validate instruments based on data availability
        $alokasiPetugas = collect($basicValidated['petugas']);

        // Check for listing data (pencacah roles) - treat null as 0
        $hasActualListingData = $alokasiPetugas->some(function ($petugas) {
            return ($petugas['hasil_listing'] ?? 0) > 0;
        });

        // Check for pendataan data (pencacah roles) - treat null as 0
        $hasActualPendataanData = $alokasiPetugas->some(function ($petugas) {
            return ($petugas['hasil_pendataan_lapangan'] ?? 0) > 0;
        });

        // Dynamic validation for instruments based on data
        $instrumentValidation = [];
        if ($hasActualListingData || $hasActualPendataanData) {
            // Only require if we have actual data to report
            if ($hasActualListingData) {
                $instrumentValidation['instrumen_listing'] = 'required|string';
            } else {
                $instrumentValidation['instrumen_listing'] = 'nullable|string';
            }
            if ($hasActualPendataanData) {
                $instrumentValidation['instrumen_pendataan_lapangan'] = 'required|string';
            } else {
                $instrumentValidation['instrumen_pendataan_lapangan'] = 'nullable|string';
            }
        } else {
            // No data, instruments are optional
            $instrumentValidation['instrumen_listing'] = 'nullable|string';
            $instrumentValidation['instrumen_pendataan_lapangan'] = 'nullable|string';
        }

        // Re-validate with conditional instrument rules
        $validated = $request->validate(array_merge([
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
            'petugas.*.hasil_pengolahan_listing' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan_listing' => 'nullable|string',
            'petugas.*.catatan' => 'nullable|string',
            'dokumen_rekap' => 'nullable|array',
            'dokumen_rekap.*.nama' => 'nullable|string',
            'dokumen_rekap.*.kode' => 'nullable|string',
            'dokumen_rekap.*.didata' => 'nullable|numeric',
            'dokumen_rekap.*.non_respon' => 'nullable|numeric',
            'dokumen_rekap.*.keterangan' => 'nullable|string',
        ], $instrumentValidation));

        // Ambil data kegiatan
        $kegiatan = Kegiatan::with('ketuaTim')->findOrFail($validated['kegiatan_id']);

        // Nomor dan tanggal
        $nomorBast = $this->generateNomorBast($validated['kegiatan_id']);
        $tanggalBast = \Carbon\Carbon::parse($validated['tanggal_bast']);
        $hari = $this->getHariIndonesia($tanggalBast->dayOfWeek);
        $tanggalFormatted = $tanggalBast->isoFormat('D MMMM YYYY');

        // PPK
        $penandatangan = Penandatangan::ppk()
            ->active()
            ->where(function ($q) {
                $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', today());
            })
            ->orderByDesc('periode_mulai')
            ->first();
        $namaPpk = $penandatangan ? $this->stripGelar($penandatangan->nama) : 'N/A';

        // Periode
        $targetPeriode = $this->getTargetPeriode($validated['kegiatan_id']);
        $bulanLabel = $targetPeriode?->bulan
            ? \Carbon\Carbon::create((int) $targetPeriode->tahun, (int) $targetPeriode->bulan)->isoFormat('MMMM')
            : $tanggalBast->isoFormat('MMMM');
        $tahunPeriode = $targetPeriode?->tahun ?? (int) $tanggalBast->year;

        // Kepala BPS
        $kepala = Penandatangan::kepala()
            ->active()
            ->where(function ($q) {
                $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', today());
            })
            ->orderByDesc('periode_mulai')
            ->first();
        $kepalaBps = $kepala ? $this->stripGelar($kepala->nama) : null;

        // Cari NIP ketua tim dari data petugas dengan nama yang sama
        $namaKetuaTim = $kegiatan->ketuaTim->name ?? 'N/A';
        $nipKetuaTim = $kegiatan->ketuaTim->nip ?? null;

        // Prioritas: cari dari data petugas dengan nama yang sama
        if ($namaKetuaTim !== 'N/A') {
            $petugasKetuaTim = \App\Models\Petugas::whereRaw('LOWER(nama) = ?', [strtolower($namaKetuaTim)])->first();
            if ($petugasKetuaTim && $petugasKetuaTim->nip) {
                $nipKetuaTim = $petugasKetuaTim->nip;
            }
        }

        // Data utama
        $viewData = [
            'nomor_bast' => $nomorBast,
            'hari' => $hari,
            'tanggal_bast' => $tanggalFormatted,
            'tanggal_angka' => $tanggalBast->day,
            'bulan_label' => $bulanLabel,
            'tahun' => $tahunPeriode,
            'nama_ppk' => $namaPpk,
            'nip_ppk' => $penandatangan->nip ?? 'N/A',
            'nama_ketua_tim' => $namaKetuaTim,
            'nip_ketua_tim' => $nipKetuaTim,
            'nama_kegiatan' => $kegiatan->nama_kegiatan,
            'nama_instansi' => config('app.instansi_name', 'Badan Pusat Statistik Kota Sawahlunto'),
            'menggunakan_fasih' => $validated['menggunakan_fasih'],
            'petugas' => $validated['petugas'],
            'dokumen_rekap' => $validated['dokumen_rekap'] ?? [],
            'instrumen_listing' => $validated['instrumen_listing'] ?? null,
            'instrumen_pendataan_lapangan' => $validated['instrumen_pendataan_lapangan'] ?? null,
            'kepalaBps' => $kepalaBps,
        ];

        // PDF utama
        $pdfMain = \Barryvdh\DomPDF\Facade\Pdf::loadView('bast', $viewData)
            ->setPaper('a4', 'portrait');
        $mainContent = $pdfMain->output();

        // PDF lampiran: selalu render sebagai halaman terpisah
        $pdfLampiran = \Barryvdh\DomPDF\Facade\Pdf::loadView('bast-lampiran', $viewData)
            ->setPaper('a4', 'landscape');
        $lampiranContent = $pdfLampiran->output();

        // Gabungkan PDF
        $merged = $this->mergePdfStrings([$mainContent, $lampiranContent]);
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
        // First do basic validation
        $basicValidated = $request->validate([
            'kegiatan_id' => 'required|exists:kegiatan,id',
            'bulan' => 'nullable|string|min:1|max:2',
            'tahun' => 'nullable|integer',
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
            'petugas.*.hasil_pengolahan_listing' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan_listing' => 'nullable|string',
            'petugas.*.catatan' => 'nullable|string',
            'dokumen_rekap' => 'nullable|array',
            'dokumen_rekap.*.nama' => 'nullable|string',
            'dokumen_rekap.*.kode' => 'nullable|string',
            'dokumen_rekap.*.didata' => 'nullable|numeric',
            'dokumen_rekap.*.non_respon' => 'nullable|numeric',
            'dokumen_rekap.*.keterangan' => 'nullable|string',
        ]);

        // Check if we need to validate instruments based on data availability
        $alokasiPetugas = collect($basicValidated['petugas']);

        // Check for listing data (pencacah roles) - treat null as 0
        $hasActualListingData = $alokasiPetugas->some(function ($petugas) {
            return ($petugas['hasil_listing'] ?? 0) > 0;
        });

        // Check for pendataan data (pencacah roles) - treat null as 0
        $hasActualPendataanData = $alokasiPetugas->some(function ($petugas) {
            return ($petugas['hasil_pendataan_lapangan'] ?? 0) > 0;
        });

        // Dynamic validation for instruments based on data
        $instrumentValidation = [];
        if ($hasActualListingData || $hasActualPendataanData) {
            // Only require if we have actual data to report
            if ($hasActualListingData) {
                $instrumentValidation['instrumen_listing'] = 'required|string';
            } else {
                $instrumentValidation['instrumen_listing'] = 'nullable|string';
            }
            if ($hasActualPendataanData) {
                $instrumentValidation['instrumen_pendataan_lapangan'] = 'required|string';
            } else {
                $instrumentValidation['instrumen_pendataan_lapangan'] = 'nullable|string';
            }
        } else {
            // No data, instruments are optional
            $instrumentValidation['instrumen_listing'] = 'nullable|string';
            $instrumentValidation['instrumen_pendataan_lapangan'] = 'nullable|string';
        }

        // Re-validate with conditional instrument rules
        $validated = $request->validate(array_merge([
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
            'petugas.*.hasil_pengolahan_listing' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan_listing' => 'nullable|string',
            'petugas.*.catatan' => 'nullable|string',
            'dokumen_rekap' => 'nullable|array',
            'dokumen_rekap.*.nama' => 'nullable|string',
            'dokumen_rekap.*.kode' => 'nullable|string',
            'dokumen_rekap.*.didata' => 'nullable|numeric',
            'dokumen_rekap.*.non_respon' => 'nullable|numeric',
            'dokumen_rekap.*.keterangan' => 'nullable|string',
        ], $instrumentValidation));

        DB::beginTransaction();

        try {
            $kegiatan = Kegiatan::with('ketuaTim')->findOrFail($validated['kegiatan_id']);

            // Get periode alokasi ID - use bulan/tahun if provided
            $targetPeriode = null;
            if (! empty($validated['bulan']) && ! empty($validated['tahun'])) {
                $targetPeriode = PeriodeAlokasi::where('kegiatan_id', $validated['kegiatan_id'])
                    ->where('bulan', $validated['bulan'])
                    ->where('tahun', $validated['tahun'])
                    ->whereIn('status', ['dikirim', 'perubahan'])
                    ->orderBy('status', 'desc') // perubahan > dikirim alphabetically
                    ->first();
            }

            // Fallback to getTargetPeriode if not found
            if (! $targetPeriode) {
                $targetPeriode = $this->getTargetPeriode($validated['kegiatan_id']);
            }

            if (! $targetPeriode) {
                throw new \RuntimeException('Periode alokasi tidak ditemukan untuk kegiatan ini.');
            }

            if (! ($targetPeriode instanceof \App\Models\PeriodeAlokasi)) {
                \Illuminate\Support\Facades\Log::error('Invalid targetPeriode type', [
                    'type' => gettype($targetPeriode),
                    'class' => is_object($targetPeriode) ? get_class($targetPeriode) : null,
                ]);
                throw new \RuntimeException('Periode alokasi tidak valid.');
            }

            $periodeId = $targetPeriode->id;

            // Get PPK
            // Get active PPK from penandatangan (by jenis_penandatangan + active + valid date range)
            $penandatangan = Penandatangan::ppk()
                ->active()
                ->where(function ($q) {
                    $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', today());
                })
                ->where(function ($q) {
                    $q->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', today());
                })
                ->orderByDesc('periode_mulai')
                ->first();

            // Generate nomor BAST
            $nomorBast = $this->generateNomorBast($validated['kegiatan_id']);

            $bulanLabel = $targetPeriode?->bulan
                ? \Carbon\Carbon::create((int) $targetPeriode->tahun, (int) $targetPeriode->bulan)->isoFormat('MMMM')
                : \Carbon\Carbon::parse($validated['tanggal_bast'])->isoFormat('MMMM');
            $tahunPeriode = $targetPeriode?->tahun ?? (int) \Carbon\Carbon::parse($validated['tanggal_bast'])->year;

            // Ambil data petugas dari AlokasiPetugas untuk periode ini
            // Fetch ALL allocations for each petugas in the same bulan/tahun across all kegiatan
            $bulanTarget = $targetPeriode->bulan;
            $tahunTarget = $targetPeriode->tahun;

            // Get all periode alokasi in the same bulan/tahun
            $allPeriodeInMonth = \App\Models\PeriodeAlokasi::where('bulan', $bulanTarget)
                ->where('tahun', $tahunTarget)
                ->whereIn('status', ['dikirim', 'perubahan', 'disetujui'])
                ->pluck('id');

            // Get all alokasi petugas in that period
            $allAlokasiPetugas = \App\Models\AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
                ->with([
                    'spk' => function ($query) {
                        $query->orderByDesc('created_at')->limit(1);
                    },
                    'petugas',
                    'periodeAlokasi.kegiatan',
                ])
                ->get();

            // Group by petugas and aggregate their work across all activities
            $petugasGrouped = $allAlokasiPetugas->groupBy('petugas_id');

            // Collect kegiatan details for attachments
            $kegiatanAttachments = $allAlokasiPetugas->map(function ($alokasi) {
                return [
                    'kegiatan_id' => $alokasi->periodeAlokasi->kegiatan->id,
                    'periode_alokasi_id' => $alokasi->periode_alokasi_id,
                    'kode_kegiatan' => $alokasi->periodeAlokasi->kegiatan->kode_kegiatan,
                    'nama_kegiatan' => $alokasi->periodeAlokasi->kegiatan->nama_kegiatan,
                    'bulan' => $alokasi->periodeAlokasi->bulan,
                    'tahun' => $alokasi->periodeAlokasi->tahun,
                    'jenis_kegiatan' => $alokasi->periodeAlokasi->jenis_kegiatan,
                ];
            })->unique(function ($item) {
                return $item['kegiatan_id'].'_'.$item['periode_alokasi_id'];
            })->values();

            $petugas = $petugasGrouped->map(function ($alokasiGroup, $petugasId) {
                // Get the first alokasi to get petugas info and latest SPK
                $firstAlokasi = $alokasiGroup->first();
                $latestSpk = $firstAlokasi->spk?->first(); // relasi HasMany, ambil yang pertama

                // Aggregate results across all activities for this petugas
                $totalHasilListing = 0;
                $totalHasilPendataanLapangan = 0;
                $totalHasilPengolahan = 0;
                $totalHasilPengolahanListing = 0;

                $satuanListing = null;
                $satuanPendataanLapangan = null;
                $satuanPengolahan = null;
                $satuanPengolahanListing = null;

                $peranList = [];
                $catatanList = [];

                foreach ($alokasiGroup as $alokasi) {
                    $isPendataanRole = in_array($alokasi->peran, ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'], true);
                    $isPengolahanRole = in_array($alokasi->peran, ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'], true);

                    if ($isPendataanRole) {
                        $totalHasilListing += $alokasi->jumlah_satuan_listing ?? 0;
                        $totalHasilPendataanLapangan += $alokasi->jumlah_satuan ?? 0;
                        // Get satuan from the first non-null value
                        $satuanListing = $satuanListing ?? $alokasi->satuan_listing;
                        $satuanPendataanLapangan = $satuanPendataanLapangan ?? $alokasi->satuan_pendataan_lapangan;
                    }

                    if ($isPengolahanRole) {
                        $totalHasilPengolahan += $alokasi->jumlah_satuan ?? 0;
                        $totalHasilPengolahanListing += $alokasi->jumlah_satuan_listing ?? 0;
                        // Get satuan from the first non-null value
                        $satuanPengolahan = $satuanPengolahan ?? $alokasi->satuan_pengolahan;
                        $satuanPengolahanListing = $satuanPengolahanListing ?? $alokasi->satuan_listing;
                    }

                    // Collect unique peran
                    if (! in_array($alokasi->peran, $peranList)) {
                        $peranList[] = $alokasi->peran;
                    }

                    // Collect non-empty catatan
                    if (! empty($alokasi->catatan)) {
                        $catatanList[] = $alokasi->catatan;
                    }
                }

                // Determine primary peran (use the first one or most common)
                $primaryPeran = $peranList[0] ?? 'pcl_ppl';
                $isPendataanRole = in_array($primaryPeran, ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'], true);
                $isPengolahanRole = in_array($primaryPeran, ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'], true);

                return [
                    'petugas_id' => $petugasId,
                    'spk_id' => $latestSpk?->id,
                    'nomor_spk' => $latestSpk?->nomor_spk ?? '-',
                    'nama_petugas' => $firstAlokasi->petugas?->nama ?? '-',
                    'peran' => $primaryPeran,
                    'hasil_listing' => $isPendataanRole && $totalHasilListing > 0 ? $totalHasilListing : null,
                    'satuan_listing' => $isPendataanRole ? $satuanListing : null,
                    'instrumen_listing' => $isPendataanRole ? ($firstAlokasi->instrumen_listing ?? null) : null,
                    'hasil_pendataan_lapangan' => $isPendataanRole && $totalHasilPendataanLapangan > 0 ? $totalHasilPendataanLapangan : null,
                    'satuan_pendataan_lapangan' => $isPendataanRole ? $satuanPendataanLapangan : null,
                    'instrumen_pendataan_lapangan' => $isPendataanRole ? ($firstAlokasi->instrumen_pendataan_lapangan ?? null) : null,
                    'hasil_pengolahan' => $isPengolahanRole && $totalHasilPengolahan > 0 ? $totalHasilPengolahan : null,
                    'satuan_pengolahan' => $isPengolahanRole ? $satuanPengolahan : null,
                    'hasil_pengolahan_listing' => $isPengolahanRole && $totalHasilPengolahanListing > 0 ? $totalHasilPengolahanListing : null,
                    'satuan_pengolahan_listing' => $isPengolahanRole ? $satuanPengolahanListing : null,
                    'catatan' => implode('; ', $catatanList),
                ];
            })->values()->toArray();

            // Filter hanya petugas yang punya SPK
            $petugas = array_filter($petugas, function ($p) {
                return ! empty($p['spk_id']);
            });
            $petugas = array_values($petugas); // Re-index array

            // Inject ke $validated agar generateBastPdf menggunakan data petugas dari alokasi
            $validated['petugas'] = $petugas;

            // Cari NIP ketua tim dari data petugas dengan nama yang sama
            $namaKetuaTim = $kegiatan->ketuaTim->name ?? null;
            $nipKetuaTim = $kegiatan->ketuaTim->nip ?? null;

            // Prioritas: cari dari data petugas dengan nama yang sama
            if ($namaKetuaTim) {
                $petugasKetuaTim = \App\Models\Petugas::whereRaw('LOWER(nama) = ?', [strtolower($namaKetuaTim)])->first();
                if ($petugasKetuaTim && $petugasKetuaTim->nip) {
                    $nipKetuaTim = $petugasKetuaTim->nip;
                }
            }

            // Create BAST (without file first)
            $bast = Bast::create([
                'nomor_bast' => $nomorBast,
                'kegiatan_id' => $validated['kegiatan_id'],
                'periode_alokasi_id' => $periodeId,
                'spk_id' => $validated['petugas'][0]['spk_id'], // Take first SPK as reference
                'tanggal_bast' => $validated['tanggal_bast'],
                'tanggal_serah_terima' => $validated['tanggal_bast'],
                'menggunakan_fasih' => $validated['menggunakan_fasih'],
                'uraian_pekerjaan' => $kegiatan->nama_kegiatan.' Bulan '.$bulanLabel.' Tahun '.$tahunPeriode,
                'nama_ketua_tim' => $namaKetuaTim,
                'nip_ketua_tim' => $nipKetuaTim,
                'nama_ppk' => $penandatangan ? $this->stripGelar($penandatangan->nama) : null,
                'nip_ppk' => $penandatangan?->nip ?? null,
                'file_path' => null, // Will be updated after PDF generation
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
                    'instrumen_listing' => $petugasData['instrumen_listing'] ?? null,
                    'hasil_pendataan_lapangan' => $petugasData['hasil_pendataan_lapangan'],
                    'satuan_pendataan_lapangan' => $petugasData['satuan_pendataan_lapangan'],
                    'instrumen_pendataan_lapangan' => $petugasData['instrumen_pendataan_lapangan'] ?? null,
                    'hasil_pengolahan_listing' => $petugasData['hasil_pengolahan_listing'],
                    'satuan_pengolahan_listing' => $petugasData['satuan_pengolahan_listing'],
                    'hasil_pengolahan' => $petugasData['hasil_pengolahan'],
                    'satuan_pengolahan' => $petugasData['satuan_pengolahan'],
                    'catatan' => $petugasData['catatan'] ?? null,
                ]);
            }

            // Create BAST Kegiatan records (activity attachments)
            foreach ($kegiatanAttachments as $attachment) {
                BastKegiatan::create([
                    'bast_id' => $bast->id,
                    'kegiatan_id' => $attachment['kegiatan_id'],
                    'periode_alokasi_id' => $attachment['periode_alokasi_id'],
                    'kode_kegiatan' => $attachment['kode_kegiatan'],
                    'nama_kegiatan' => $attachment['nama_kegiatan'],
                    'bulan' => $attachment['bulan'],
                    'tahun' => $attachment['tahun'],
                    'jenis_kegiatan' => $attachment['jenis_kegiatan'],
                ]);
            }

            // Generate PDF after data saved to database
            $filePath = $this->generateBastPdf(
                $kegiatan,
                $validated,
                $nomorBast,
                $penandatangan,
                $bulanLabel,
                $tahunPeriode
            );

            // Update BAST with file path
            $bast->update(['file_path' => $filePath]);

            DB::commit();

            return redirect()->route('bast.index')
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
    public function show(Bast $bast): Response
    {
        // Load relationships
        $bast->load([
            'kegiatan',
            'periodeAlokasi',
            'bastPetugas.petugas',
            'bastPetugas.spk',
            'createdBy:id,name',
        ]);

        // Get periode info
        $periode = $bast->periodeAlokasi;
        $bulanLabel = $this->getBulanLabel((int) $periode->bulan);

        // Get all BAST for this kegiatan (history)
        $bastHistory = Bast::where('kegiatan_id', $bast->kegiatan_id)
            ->with(['periodeAlokasi', 'createdBy:id,name'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($b) use ($bast) {
                $periodeBast = $b->periodeAlokasi;
                $bulanName = $this->getBulanLabel((int) $periodeBast->bulan);

                return [
                    'id' => $b->id,
                    'hashed_id' => $b->hashed_id,
                    'nomor_bast' => $b->nomor_bast,
                    'tanggal_bast' => $b->tanggal_bast,
                    'tanggal_serah_terima' => $b->tanggal_serah_terima,
                    'periode' => "{$bulanName} {$periodeBast->tahun}",
                    'status' => $b->status,
                    'file_path' => $b->file_path,
                    'created_by' => $b->createdBy?->name ?? 'System',
                    'created_at' => $b->created_at->format('d M Y H:i'),
                    'is_current' => $b->id === $bast->id,
                ];
            });

        // Format bast petugas data
        $bastPetugasList = $bast->bastPetugas->map(function ($bp) {
            return [
                'id' => $bp->id,
                'petugas_id' => $bp->petugas_id,
                'petugas_nama' => $bp->nama_petugas,
                'nomor_spk' => $bp->nomor_spk,
                'hasil_listing' => $bp->hasil_listing,
                'satuan_listing' => $bp->satuan_listing,
                'hasil_pendataan_lapangan' => $bp->hasil_pendataan_lapangan,
                'satuan_pendataan_lapangan' => $bp->satuan_pendataan_lapangan,
                'hasil_pengolahan' => $bp->hasil_pengolahan,
                'satuan_pengolahan' => $bp->satuan_pengolahan,
                'catatan' => $bp->catatan,
            ];
        })->values();

        return Inertia::render('Bast/Show', [
            'bast' => [
                'id' => $bast->id,
                'hashed_id' => $bast->hashed_id,
                'nomor_bast' => $bast->nomor_bast,
                'tanggal_bast' => $bast->tanggal_bast,
                'tanggal_serah_terima' => $bast->tanggal_serah_terima,
                'menggunakan_fasih' => $bast->menggunakan_fasih,
                'uraian_pekerjaan' => $bast->uraian_pekerjaan,
                'nama_ketua_tim' => $bast->nama_ketua_tim,
                'nip_ketua_tim' => $bast->nip_ketua_tim,
                'nama_ppk' => $bast->nama_ppk,
                'nip_ppk' => $bast->nip_ppk,
                'hasil_pekerjaan' => $bast->hasil_pekerjaan,
                'file_path' => $bast->file_path,
                'status' => $bast->status,
                'catatan' => $bast->catatan,
                'created_by' => $bast->createdBy?->name ?? 'System',
                'created_at' => $bast->created_at->format('d M Y H:i'),
            ],
            'kegiatan' => [
                'id' => $bast->kegiatan->id,
                'hashed_id' => $bast->kegiatan->hashed_id,
                'kode_kegiatan' => $bast->kegiatan->kode_kegiatan,
                'nama_kegiatan' => $bast->kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $bast->kegiatan->jenis_kegiatan,
                'tahun_anggaran' => $bast->kegiatan->tahun_anggaran,
            ],
            'periode' => [
                'id' => $periode->id,
                'hashed_id' => $periode->hashed_id,
                'bulan' => (int) $periode->bulan,
                'tahun' => $periode->tahun,
                'bulan_label' => $bulanLabel,
            ],
            'bast_petugas' => $bastPetugasList,
            'bast_history' => $bastHistory->values()->toArray(),
        ]);
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
                // also clear listing values if not a pendataan role
                $data['petugas'][$i]['hasil_listing'] = null;
                $data['petugas'][$i]['satuan_listing'] = null;
                $data['petugas'][$i]['instrumen_listing'] = null;
            }
            if (! in_array($peran, $pengolahanRoles, true)) {
                $data['petugas'][$i]['hasil_pengolahan'] = null;
                $data['petugas'][$i]['satuan_pengolahan'] = null;
            }
        }

        // Check if listing, pendataan, or pengolahan exists after sanitization
        $hasListing = collect($data['petugas'])->contains(function ($p) {
            return ! empty($p['hasil_listing']);
        });
        $hasPengolahan = collect($data['petugas'])->contains(function ($p) {
            return ! empty($p['hasil_pengolahan']);
        });
        $hasPendataan = collect($data['petugas'])->contains(function ($p) {
            return ! empty($p['hasil_pendataan_lapangan']);
        });

        // Cari NIP ketua tim dari data petugas dengan nama yang sama
        $namaKetuaTim = $kegiatan->ketuaTim->name ?? 'N/A';
        $nipKetuaTim = null;

        // Prioritas: cari dari data petugas dengan nama yang sama
        if ($namaKetuaTim !== 'N/A') {
            $petugasKetuaTim = \App\Models\Petugas::whereRaw('LOWER(nama) = ?', [strtolower($namaKetuaTim)])->first();
            if ($petugasKetuaTim && $petugasKetuaTim->nip) {
                $nipKetuaTim = $petugasKetuaTim->nip;
            } else {
                // Fallback ke profile ketua tim
                $nipKetuaTim = $kegiatan->ketuaTim->nip ?? null;
            }
        }

        $viewData = [
            'nomor_bast' => $nomorBast,
            'hari' => $hari,
            'tanggal_bast' => $tanggalFormatted,
            'bulan_label' => $bulanLabel ?? $tanggalBast->isoFormat('MMMM'),
            'tahun' => $tahunPeriode ?? (int) $tanggalBast->year,
            'nama_ppk' => $ppk->nama ?? 'N/A',
            'nip_ppk' => $ppk->nip ?? 'N/A',
            'nama_ketua_tim' => $namaKetuaTim,
            'nip_ketua_tim' => $nipKetuaTim,
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
                $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', today());
            })
            ->where(function ($q) {
                $q->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', today());
            })
            ->orderByDesc('periode_mulai')
            ->first();

        if ($kepala) {
            $viewData['kepalaBps'] = $this->stripGelar($kepala->nama) ?: $kepala->nama;
        }

        $useLandscape = false;
        if (! empty($viewData['dokumen_rekap']) && count($viewData['dokumen_rekap']) > 0) {
            $useLandscape = true;
        }
        if ($viewData['has_listing'] || $viewData['has_pengolahan'] || ($viewData['has_pendataan'] ?? false)) {
            $useLandscape = true;
        }

        $orientation = $useLandscape ? 'landscape' : 'portrait';

        // Render main (without lampiran)
        $viewDataMain = $viewData;
        $pdfMain = \Barryvdh\DomPDF\Facade\Pdf::loadView('bast', $viewDataMain)
            ->setPaper('a4', 'portrait');
        $mainContent = $pdfMain->output();

        // If no lampiran, save main PDF directly
        $hasLampiran = (! empty($viewData['dokumen_rekap']) && count($viewData['dokumen_rekap']) > 0)
            || $viewData['has_listing'] || $viewData['has_pengolahan'] || ($viewData['has_pendataan'] ?? false);

        if (! $hasLampiran) {
            $directory = public_path('bast-export/'.now()->year.'/'.now()->month);
            if (! file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            $fileName = 'BAST_'.$kegiatan->nama_kegiatan.'_'.($targetPeriode?->bulan ?? 'unknown').'_'.time().'.pdf';
            $filePath = 'bast-export/'.now()->year.'/'.now()->month.'/'.$fileName;
            $fullPath = public_path($filePath);
            file_put_contents($fullPath, $mainContent);

            return $filePath;
        }

        // Render lampiran only (landscape) using bast-lampiran.blade.php directly
        $viewDataLamp = $viewData;
        $lampOrientation = (! empty($viewData['dokumen_rekap']) && count($viewData['dokumen_rekap']) > 0)
            || $viewData['has_listing'] || $viewData['has_pengolahan'] ? 'landscape' : 'portrait';
        $pdfLamp = \Barryvdh\DomPDF\Facade\Pdf::loadView('bast-lampiran', $viewDataLamp)
            ->setPaper('a4', $lampOrientation);
        $lampContent = $pdfLamp->output();

        // Merge and save
        $merged = $this->mergePdfStrings([$mainContent, $lampContent]);

        $directory = public_path('bast-export/'.now()->year.'/'.now()->month);
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $periodeAlokasi = $kegiatan->periodeAlokasi()->latest('id')->first();
        $bulan = $periodeAlokasi?->bulan ?? 'unknown';
        $fileName = 'BAST_'.$kegiatan->nama_kegiatan.'_'.$bulan.'_'.time().'.pdf';
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
        $pdf = new \setasign\Fpdi\Tcpdf\Fpdi;
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

    /**
     * Get bulan label (Indonesian month name)
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
}
