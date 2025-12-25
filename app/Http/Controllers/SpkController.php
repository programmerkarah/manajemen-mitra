<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
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
    public function index(FilterRequest $request): Response
    {
        $validated = $request->validated();
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
                $q->where('tahun_anggaran', $activeYear)
                    ->where('jenis_kegiatan', 'survei'); // Only survei activities
            })
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->where('tahun', $activeYear);

        // Search filter
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->whereHas('kegiatan', function ($q) use ($search) {
                $q->where('nama_kegiatan', 'like', "%{$search}%")
                    ->orWhere('kode_kegiatan', 'like', "%{$search}%");
            });
        }

        // Filter by bulan
        if (! empty($validated['bulan'])) {
            $query->where('bulan', (int) $validated['bulan']);
        }

        $periodes = $query->latest()->get();

        // Group by month and year
        $groupedByMonth = $periodes->groupBy(function ($periode) {
            return $periode->tahun.'-'.$periode->bulan;
        })->map(function ($monthPeriodes, $key) {
            [$tahun, $bulan] = explode('-', $key);

            // Count unique non-organik petugas across all kegiatan in this month
            $allPetugasIds = collect();
            foreach ($monthPeriodes as $periode) {
                $petugasIds = $periode->alokasiPetugas
                    ->filter(function ($alokasi) {
                        return $alokasi->petugas && $alokasi->petugas->jenis_petugas === 'non-organik';
                    })
                    ->pluck('petugas_id');
                $allPetugasIds = $allPetugasIds->merge($petugasIds);
            }
            $totalPetugasNonOrganik = $allPetugasIds->unique()->count();

            // Count total SPK created
            $totalSpk = $monthPeriodes->sum(function ($periode) {
                return $periode->spk->count();
            });

            // Get unique kegiatan in this month (regardless of periode status)
            $kegiatanList = $monthPeriodes->groupBy('kegiatan_id')
                ->map(function ($periodesByKegiatan) {
                    // Use the first periode for this kegiatan
                    $firstPeriode = $periodesByKegiatan->first();

                    // Collect unique petugas IDs (don't count duplicates from revisions)
                    $uniquePetugasIds = collect();
                    foreach ($periodesByKegiatan as $periode) {
                        $petugasIds = $periode->alokasiPetugas
                            ->filter(function ($alokasi) {
                                return $alokasi->petugas && $alokasi->petugas->jenis_petugas === 'non-organik';
                            })
                            ->pluck('petugas_id');
                        $uniquePetugasIds = $uniquePetugasIds->merge($petugasIds);
                    }
                    $totalPetugasNonOrganik = $uniquePetugasIds->unique()->count();

                    return [
                        'periode_id' => $firstPeriode->id,
                        'periode_hashed_id' => $firstPeriode->hashed_id,
                        'kegiatan_hashed_id' => $firstPeriode->kegiatan->hashed_id,
                        'kode_kegiatan' => $firstPeriode->kegiatan->kode_kegiatan,
                        'nama_kegiatan' => $firstPeriode->kegiatan->nama_kegiatan,
                        'jenis_kegiatan' => $firstPeriode->kegiatan->jenis_kegiatan,
                        'jumlah_petugas_non_organik' => $totalPetugasNonOrganik,
                    ];
                })->values();

            // SPK status for the month
            $spkStatus = $totalSpk > 0 ? 'Sudah Dibuat' : 'Belum Dibuat';
            $spkStatusType = $totalSpk > 0 ? 'created' : 'not_created';

            // Check if there are any revision/perubahan periods
            $hasRevision = $monthPeriodes->contains(function ($periode) {
                return in_array($periode->status, ['direvisi', 'perubahan']);
            });

            // Check if any SPK already has addendum
            $hasAddendum = $monthPeriodes->flatMap(function ($periode) {
                return $periode->spk;
            })->contains(function ($spk) {
                return $spk->addendum_number > 0;
            });

            // Check for new kegiatan/petugas after SPK was generated
            $hasNewKegiatanAfterSpk = $this->hasNewKegiatanAfterSpk($tahun, $bulan, $monthPeriodes);

            // Check for new revisions after addendum was generated
            $hasNewRevisionAfterAddendum = $this->hasNewRevisionAfterAddendum($tahun, $bulan, $monthPeriodes);

            // Check if SPK has been regenerated (regeneration_count > 0)
            $hasBeenRegenerated = $monthPeriodes->flatMap(function ($periode) {
                return $periode->spk;
            })->contains(function ($spk) {
                return ($spk->regeneration_count ?? 0) > 0;
            });

            return [
                'tahun' => (int) $tahun,
                'bulan' => (int) $bulan,
                'bulan_label' => $this->getBulanLabel((int) $bulan),
                'total_petugas_non_organik' => $totalPetugasNonOrganik,
                'total_spk' => $totalSpk,
                'spk_status' => $spkStatus,
                'spk_status_type' => $spkStatusType,
                'has_revision' => $hasRevision,
                'has_addendum' => $hasAddendum,
                'has_new_kegiatan_after_spk' => $hasNewKegiatanAfterSpk,
                'has_new_revision_after_addendum' => $hasNewRevisionAfterAddendum,
                'has_been_regenerated' => $hasBeenRegenerated,
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
     * Display list of SPKs for a specific month
     */
    public function listByMonth(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        if (! $bulan || ! $tahun) {
            return redirect()->route('spk.index');
        }

        // Format bulan with leading zero
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Get all periodes in this month
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'perubahan', 'direvisi'])
            ->whereHas('kegiatan', function ($q) {
                $q->where('jenis_kegiatan', 'survei'); // Only survei activities
            })
            ->pluck('id');

        // Get all SPKs created in this month
        $spkList = Spk::with([
            'alokasiPetugas.petugas',
            'alokasiPetugas.periodeAlokasi.kegiatan',
        ])
            ->whereIn('alokasi_petugas_id', function ($query) use ($allPeriodeInMonth) {
                $query->select('id')
                    ->from('alokasi_petugas')
                    ->whereIn('periode_alokasi_id', $allPeriodeInMonth);
            })
            ->orderBy('nomor_spk')
            ->get()
            ->map(function ($spk) use ($allPeriodeInMonth) {
                $petugas = $spk->alokasiPetugas->petugas;

                // Get all kegiatan for this petugas in this month
                $allAlokasi = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
                    ->where('petugas_id', $petugas->id)
                    ->with(['periodeAlokasi.kegiatan'])
                    ->get();

                $kegiatanList = $allAlokasi->map(function ($alokasi) {
                    return [
                        'kode_kegiatan' => $alokasi->periodeAlokasi->kegiatan->kode_kegiatan,
                        'nama_kegiatan' => $alokasi->periodeAlokasi->kegiatan->nama_kegiatan,
                        'peran' => $alokasi->peran,
                    ];
                })->values()->all();

                return [
                    'id' => $spk->id,
                    'hashed_id' => $spk->hashed_id,
                    'nomor_spk' => $spk->nomor_spk,
                    'tanggal_spk' => $spk->tanggal_spk,
                    'nilai_kontrak' => $spk->nilai_kontrak,
                    'status' => $spk->status,
                    'file_path' => $spk->file_path,
                    'petugas' => [
                        'id' => $petugas->id,
                        'hashed_id' => $petugas->hashed_id,
                        'nama' => $petugas->nama,
                        'nik' => $petugas->nik,
                    ],
                    'jumlah_kegiatan' => count($kegiatanList),
                    'kegiatan_list' => $kegiatanList,
                ];
            });

        return Inertia::render('Spk/List', [
            'spk_list' => $spkList,
            'bulan' => (int) $bulan,
            'tahun' => (int) $tahun,
            'bulan_label' => $this->getBulanLabel((int) $bulan),
        ]);
    }

    /**
     * Show SPK for a specific month with petugas list (GET version)
     */
    public function showByMonthGet(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $bulan = $request->query('bulan');
        $tahun = $request->query('tahun');
        $spkHashedId = $request->query('spk');

        return $this->renderShowByMonth($bulan, $tahun, $spkHashedId);
    }

    /**
     * Show SPK for a specific month with petugas list (POST version)
     */
    public function showByMonth(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $spkHashedId = $request->input('spk');

        return $this->renderShowByMonth($bulan, $tahun, $spkHashedId);
    }

    /**
     * Internal method to render ShowByMonth view
     */
    private function renderShowByMonth($bulan, $tahun, $spkHashedId): Response|\Illuminate\Http\RedirectResponse
    {

        if (! $bulan || ! $tahun) {
            return redirect()->route('spk.index');
        }

        // Format bulan with leading zero
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Get all periodes in this month (include 'direvisi' and 'perubahan')
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->whereHas('kegiatan', function ($q) {
                $q->where('jenis_kegiatan', 'survei'); // Only survei activities
            })
            ->pluck('id');

        // Get all SPKs in this month
        $allSpks = Spk::with(['alokasiPetugas.petugas'])
            ->whereIn('alokasi_petugas_id', function ($query) use ($allPeriodeInMonth) {
                $query->select('id')
                    ->from('alokasi_petugas')
                    ->whereIn('periode_alokasi_id', $allPeriodeInMonth);
            })
            ->orderBy('nomor_spk')
            ->get();

        if ($allSpks->isEmpty()) {
            return redirect()->route('spk.index')->with('error', 'Tidak ada SPK untuk periode ini');
        }

        // Determine which SPK to show
        $spk = null;
        if ($spkHashedId) {
            $spkId = \Vinkla\Hashids\Facades\Hashids::decode($spkHashedId)[0] ?? null;
            $spk = $allSpks->firstWhere('id', $spkId);
        }

        // If not found or not specified, use first SPK
        if (! $spk) {
            $spk = $allSpks->first();
        }

        $periode = $spk->alokasiPetugas->periodeAlokasi;
        $petugas = $spk->alokasiPetugas->petugas;
        $bast = $spk->bast()->latest()->first();

        // Get all kegiatan for this petugas in this month
        $allAlokasi = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->where('petugas_id', $petugas->id)
            ->with(['periodeAlokasi.kegiatan'])
            ->get();

        // Group by kegiatan only (not peran) - consolidate all peran under one row per kegiatan
        $grouped = $allAlokasi->groupBy(function ($alokasi) {
            return $alokasi->periodeAlokasi->kegiatan->id;
        });

        $kegiatanList = $grouped->map(function ($alokasiGroup) {
            // Find the original (non-perubahan) and latest (perubahan if exists) for the entire kegiatan
            $original = $alokasiGroup->first(function ($a) {
                return in_array($a->periodeAlokasi->status, ['dikirim', 'disetujui', 'direvisi']);
            }) ?? $alokasiGroup->first();
            $latest = $alokasiGroup->sortByDesc(function ($a) {
                return $a->periodeAlokasi->id;
            })->first();

            // Calculate total honor for original periode (only from that specific periode)
            $originalTotalHonor = $alokasiGroup->filter(function ($a) {
                return in_array($a->periodeAlokasi->status, ['dikirim', 'disetujui', 'direvisi']);
            })->sum(function ($a) {
                return ($a->total_honor ?? 0) + ($a->total_honor_listing ?? 0);
            });

            if ($originalTotalHonor === 0) {
                // Fallback: use the first allocation's honor
                $originalTotalHonor = ($original->total_honor ?? 0) + ($original->total_honor_listing ?? 0);
            }

            // Calculate total honor for latest periode only (sum all allocations from the latest periode for this kegiatan)
            $latestPeriodeId = $latest->periodeAlokasi->id;
            $latestTotalHonor = $alokasiGroup->filter(function ($a) use ($latestPeriodeId) {
                return $a->periodeAlokasi->id === $latestPeriodeId;
            })->sum(function ($a) {
                return ($a->total_honor ?? 0) + ($a->total_honor_listing ?? 0);
            });

            // Get all peran for this kegiatan
            $peranList = $alokasiGroup->pluck('peran')->unique()->implode(', ');

            $hasChange = $latestTotalHonor != $originalTotalHonor;

            return [
                'id' => $latest->periodeAlokasi->kegiatan->id,
                'hashed_id' => $latest->periodeAlokasi->kegiatan->hashed_id,
                'kode_kegiatan' => $latest->periodeAlokasi->kegiatan->kode_kegiatan,
                'nama_kegiatan' => $latest->periodeAlokasi->kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $latest->periodeAlokasi->kegiatan->jenis_kegiatan,
                'tahun_anggaran' => $latest->periodeAlokasi->kegiatan->tahun_anggaran,
                'peran' => $peranList,
                'total_honor' => $latestTotalHonor,
                'original' => [
                    'total_honor' => $originalTotalHonor,
                    'peran' => $peranList,
                ],
                'latest' => [
                    'total_honor' => $latestTotalHonor,
                    'peran' => $peranList,
                ],
                'has_change' => $hasChange,
            ];
        })->values()->all();

        // Build petugas list for sidebar
        $petugasList = $allSpks->map(function ($s) {
            return [
                'id' => $s->id,
                'hashed_id' => $s->hashed_id,
                'nomor_spk' => $s->nomor_spk,
                'petugas_nama' => $s->alokasiPetugas->petugas->nama,
                'petugas_nik' => $s->alokasiPetugas->petugas->nik,
                'status' => $s->status,
            ];
        })->values()->all();

        // Get all SPK documents for this petugas (original + addendums)
        $originalSpk = $spk->parent_spk_id ? $spk->parentSpk : $spk;
        $allSpkDocuments = Spk::where(function ($q) use ($originalSpk) {
            $q->where('id', $originalSpk->id)
                ->orWhere('parent_spk_id', $originalSpk->id);
        })
            ->orderBy('addendum_number', 'asc')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'hashed_id' => $s->hashed_id,
                    'nomor_spk' => $s->nomor_spk,
                    'tanggal_spk' => $s->tanggal_spk,
                    'addendum_number' => $s->addendum_number,
                    'file_path' => $s->file_path,
                    'status' => $s->status,
                    'created_by' => $s->createdBy->name ?? 'System',
                    'created_at' => $s->created_at->format('d M Y H:i'),
                    'updated_at' => $s->updated_at->format('d M Y H:i'),
                ];
            });

        return Inertia::render('Spk/ShowByMonth', [
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
                'addendum_number' => $spk->addendum_number,
                'parent_spk_id' => $spk->parent_spk_id,
                'created_by' => $spk->createdBy->name ?? 'System',
                'created_at' => $spk->created_at->format('d M Y H:i'),
                'updated_at' => $spk->updated_at->format('d M Y H:i'),
            ],
            'spk_documents' => $allSpkDocuments,
            'petugas' => [
                'id' => $petugas->id,
                'hashed_id' => $petugas->hashed_id,
                'nama' => $petugas->nama,
                'nik' => $petugas->nik,
                'jenis_petugas' => $petugas->jenis_petugas,
                'alamat' => $petugas->alamat,
            ],
            'kegiatan_list' => $kegiatanList,
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
            'petugas_list' => $petugasList,
            'bulan' => (int) $bulan,
            'tahun' => (int) $tahun,
            'bulan_label' => $this->getBulanLabel((int) $bulan),
        ]);
    }

    /**
     * Download all SPK files in a month as ZIP
     */
    public function downloadAll(Request $request)
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        if (! $bulan || ! $tahun) {
            return redirect()->route('spk.index')->with('error', 'Bulan dan tahun harus diisi');
        }

        // Format bulan with leading zero
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Get all periodes in this month
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui'])
            ->whereHas('kegiatan', function ($q) {
                $q->where('jenis_kegiatan', 'survei'); // Only survei activities
            })
            ->pluck('id');

        // Get all SPKs in this month that have files
        $allSpks = Spk::with(['alokasiPetugas.petugas'])
            ->whereNotNull('file_path')
            ->whereIn('alokasi_petugas_id', function ($query) use ($allPeriodeInMonth) {
                $query->select('id')
                    ->from('alokasi_petugas')
                    ->whereIn('periode_alokasi_id', $allPeriodeInMonth);
            })
            ->orderBy('nomor_spk')
            ->get();

        if ($allSpks->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada SPK dengan file untuk diunduh');
        }

        // Create ZIP file
        $zip = new \ZipArchive;
        $bulanLabel = $this->getBulanLabel((int) $bulan);
        $zipFileName = "SPK_{$bulanLabel}_{$tahun}_".time().'.zip';
        $zipPath = storage_path('app/temp/'.$zipFileName);

        // Create temp directory if not exists
        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Gagal membuat file ZIP');
        }

        // Add each SPK file to ZIP
        foreach ($allSpks as $spk) {
            $filePath = public_path($spk->file_path);
            if (file_exists($filePath)) {
                $fileName = basename($spk->file_path);
                $zip->addFile($filePath, $fileName);
            }
        }

        $zip->close();

        // Download and delete temp file
        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Upload signed SPK document
     */
    public function uploadSigned(Request $request, string $spkHashedId)
    {
        $spkId = \Vinkla\Hashids\Facades\Hashids::decode($spkHashedId)[0] ?? null;

        if (! $spkId) {
            abort(404);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $spk = Spk::findOrFail($spkId);

        // Store new signed file
        $file = $request->file('file');
        $periode = $spk->alokasiPetugas->periodeAlokasi;
        $petugas = $spk->alokasiPetugas->petugas;

        // Extract nomor urut
        $nomorParts = explode('/', $spk->nomor_spk);
        $nomorUrut = $nomorParts[2] ?? '0';

        $namaPetugas = preg_replace('/[\/\\\:*?"<>|]/', '', $petugas->nama);
        $bulanLabel = $this->getBulanLabel($periode->bulan);

        $fileName = "SPK_{$nomorUrut}_{$namaPetugas}_{$bulanLabel}_signed.pdf";
        $filePath = 'spk-export/'.date('Y').'/'.date('m').'/'.$fileName;

        // Create directory if not exists
        $publicPath = public_path('spk-export/'.date('Y').'/'.date('m'));
        if (! file_exists($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        // Delete old signed file if exists
        if ($spk->signed_file_path && file_exists(public_path($spk->signed_file_path))) {
            @unlink(public_path($spk->signed_file_path));
        }

        $file->move($publicPath, $fileName);

        // Update SPK - save to signed_file_path, keep file_path as generated SPK
        $spk->update([
            'signed_file_path' => $filePath,
            'status' => 'diterbitkan',
        ]);

        // Redirect back to ShowByMonth with proper payload
        return redirect()->route('spk.show-by-month-get', [
            'bulan' => $periode->bulan,
            'tahun' => $periode->tahun,
            'spk' => $spk->hashed_id,
        ])->with('success', 'Dokumen SPK berhasil diunggah');
    }

    /**
     * Get next nomor urut for SPK based on year
     */
    private function getNextNomorUrut(int $tahun): int
    {
        // Get last SPK number for the given year
        $lastSpk = Spk::where('nomor_spk', 'like', "PPIS/13730/%/K/{$tahun}")
            ->orderByRaw('CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(nomor_spk, "/", 3), "/", -1) AS UNSIGNED) DESC')
            ->first();

        if (! $lastSpk) {
            return 1;
        }

        // Extract nomor urut from format: PPIS/13730/4/K/2025
        $parts = explode('/', $lastSpk->nomor_spk);
        $lastUrut = isset($parts[2]) ? (int) $parts[2] : 0;

        return $lastUrut + 1;
    }

    /**
     * Extract nomor urut from SPK number (e.g., "PPIS/13730/4A/K/2025" -> 4)
     */
    private function extractNomorUrut(string $nomorSpk): int
    {
        $parts = explode('/', $nomorSpk);
        if (! isset($parts[2])) {
            return 0;
        }

        // Remove suffix letters (e.g., "4A" -> "4")
        $nomorWithSuffix = $parts[2];

        return (int) preg_replace('/[^0-9]/', '', $nomorWithSuffix);
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
        // For regenerate SPK (non-addendum): use 'dikirim' and 'direvisi' status
        // For addendum: use 'perubahan' status
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $periode->bulan)
            ->where('tahun', $periode->tahun)
            ->whereIn('status', ['dikirim', 'direvisi'])
            ->pluck('id');

        // Get all unique non-organik petugas from all alokasi in this month
        $allAlokasi = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->with(['petugas', 'periodeAlokasi.kegiatan'])
            ->get()
            ->filter(function ($alokasi) {
                return $alokasi->petugas && $alokasi->petugas->jenis_petugas === 'non-organik';
            });

        // Group by petugas_id and aggregate their data
        $petugasList = $allAlokasi->groupBy('petugas_id')
            ->map(function ($alokasiGroup) {
                $firstAlokasi = $alokasiGroup->first();

                // Calculate total honor across all kegiatan
                $totalHonor = $alokasiGroup->sum(function ($alokasi) {
                    return ($alokasi->total_honor ?? 0) + ($alokasi->total_honor_listing ?? 0);
                });

                // Get all kegiatan with their peran
                $kegiatanList = $alokasiGroup->map(function ($alokasi) {
                    return [
                        'kegiatan_id' => $alokasi->periodeAlokasi->kegiatan->id,
                        'kegiatan_kode' => $alokasi->periodeAlokasi->kegiatan->kode_kegiatan,
                        'kegiatan_nama' => $alokasi->periodeAlokasi->kegiatan->nama_kegiatan,
                        'peran' => $alokasi->peran,
                    ];
                })->values()->all();

                return [
                    'alokasi_id' => $firstAlokasi->id,
                    'alokasi_hashed_id' => $firstAlokasi->hashed_id,
                    'petugas' => [
                        'id' => $firstAlokasi->petugas->id,
                        'hashed_id' => $firstAlokasi->petugas->hashed_id,
                        'nama' => $firstAlokasi->petugas->nama,
                        'nik' => $firstAlokasi->petugas->nik,
                        'jenis_petugas' => $firstAlokasi->petugas->jenis_petugas,
                    ],
                    'jumlah_kegiatan' => $alokasiGroup->count(),
                    'kegiatan_list' => $kegiatanList,
                    'total_honor' => $totalHonor,
                ];
            })
            ->sortBy(function ($item) {
                return $item['petugas']['nama'];
            })
            ->values();

        // Get next nomor urut for this year
        $nextNomorUrut = $this->getNextNomorUrut($periode->tahun);

        // Check if there are existing SPKs in this month (for regenerate mode)
        $existingSpk = Spk::where('addendum_number', 0)
            ->whereYear('tanggal_spk', $periode->tahun)
            ->whereMonth('tanggal_spk', $periode->bulan)
            ->first();

        // If existing SPK found, use its dates and set readonly mode
        $isRegenerate = $existingSpk !== null;
        $defaultTanggalSpk = $isRegenerate ? $existingSpk->tanggal_spk->format('Y-m-d') : null;
        $defaultSampaiTanggal = $isRegenerate ? $existingSpk->tanggal_selesai_kerja->format('Y-m-d') : null;

        // Get all existing SPKs for petugas in this month (map petugas_id => nomor_spk)
        $existingSpkMap = [];
        $lastNomorUrutInMonth = 0;
        $usesSuffixForNewPetugas = false;

        if ($isRegenerate) {
            $petugasIds = $petugasList->pluck('petugas.id')->unique();
            $existingSpks = Spk::whereIn('petugas_id', $petugasIds)
                ->where('addendum_number', 0)
                ->whereYear('tanggal_spk', $periode->tahun)
                ->whereMonth('tanggal_spk', $periode->bulan)
                ->with(['alokasiPetugas.periodeAlokasi.kegiatan'])
                ->get();

            // Track existing kegiatan per petugas for comparison
            $existingKegiatanPerPetugas = [];

            foreach ($existingSpks as $spk) {
                // Get baseline kegiatan: kegiatan yang ada SEBELUM SPK dibuat
                // Compare using SPK created_at timestamp to ensure we only get kegiatan that existed before
                $kegiatanIds = AlokasiPetugas::where('petugas_id', $spk->petugas_id)
                    ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                        $q->where('bulan', $periode->bulan)
                            ->where('tahun', $periode->tahun);
                    })
                    ->where('created_at', '<=', $spk->created_at) // Only alokasi created BEFORE SPK
                    ->with('periodeAlokasi.kegiatan')
                    ->get()
                    ->pluck('periodeAlokasi.kegiatan.id')
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();

                \Log::debug('Baseline kegiatan for petugas', [
                    'petugas_id' => $spk->petugas_id,
                    'petugas_nama' => $spk->petugas->nama ?? 'Unknown',
                    'spk_created_at' => $spk->created_at,
                    'baseline_kegiatan_ids' => $kegiatanIds,
                ]);

                $existingKegiatanPerPetugas[$spk->petugas_id] = $kegiatanIds;

                $existingSpkMap[$spk->petugas_id] = [
                    'nomor_spk' => $spk->nomor_spk,
                    'nomor_urut' => $spk->nomor_urut_base,
                ];

                // Track the last nomor urut in this month
                if ($spk->nomor_urut_base > $lastNomorUrutInMonth) {
                    $lastNomorUrutInMonth = $spk->nomor_urut_base;
                }
            }

            // Filter petugas list for regenerate mode
            // Only show: 1) New petugas (not in existingSpkMap), 2) Petugas with kegiatan additions
            $petugasList = $petugasList->filter(function ($item) use ($existingSpkMap, $existingKegiatanPerPetugas) {
                $petugasId = $item['petugas']['id'];

                // Always include new petugas (never had SPK before)
                if (! isset($existingSpkMap[$petugasId])) {
                    \Log::debug('Regenerate Filter: New petugas included', [
                        'petugas_id' => $petugasId,
                        'petugas_nama' => $item['petugas']['nama'],
                    ]);

                    return true;
                }

                // For existing petugas, check if they have NEW kegiatan (additions only)
                $currentKegiatanIds = collect($item['kegiatan_list'])
                    ->pluck('kegiatan_id')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();

                $oldKegiatanIds = $existingKegiatanPerPetugas[$petugasId] ?? [];

                // Include only if there are NEW kegiatan (current has kegiatan that old doesn't have)
                $hasNewKegiatan = count(array_diff($currentKegiatanIds, $oldKegiatanIds)) > 0;

                \Log::debug('Regenerate Filter: Existing petugas check', [
                    'petugas_id' => $petugasId,
                    'petugas_nama' => $item['petugas']['nama'],
                    'current_kegiatan' => $currentKegiatanIds,
                    'old_kegiatan' => $oldKegiatanIds,
                    'new_kegiatan' => array_diff($currentKegiatanIds, $oldKegiatanIds),
                    'included' => $hasNewKegiatan,
                ]);

                return $hasNewKegiatan;
            })->values();

            // Check if next sequential number is already used in OTHER months
            $nextSequentialNumber = $lastNomorUrutInMonth + 1;
            $nextNumberUsedElsewhere = Spk::where('nomor_urut_base', $nextSequentialNumber)
                ->where('addendum_number', 0)
                ->whereYear('tanggal_spk', $periode->tahun)
                ->where(function ($q) use ($periode) {
                    $q->whereMonth('tanggal_spk', '!=', $periode->bulan);
                })
                ->exists();

            // If next number is used elsewhere, use suffix mode (3A, 3B, etc)
            $usesSuffixForNewPetugas = $nextNumberUsedElsewhere;
        } else {
            // Generate mode: Filter out petugas who already have SPK in this month
            // Only show petugas who DON'T have SPK yet
            $petugasIds = $petugasList->pluck('petugas.id')->unique();
            $existingSpkPetugasIds = Spk::whereIn('petugas_id', $petugasIds)
                ->where('addendum_number', 0)
                ->whereYear('tanggal_spk', $periode->tahun)
                ->whereMonth('tanggal_spk', $periode->bulan)
                ->pluck('petugas_id')
                ->toArray();

            // Filter: only show petugas who don't have SPK yet
            $petugasList = $petugasList->filter(function ($item) use ($existingSpkPetugasIds) {
                $petugasId = $item['petugas']['id'];
                $notHaveSpk = ! in_array($petugasId, $existingSpkPetugasIds);

                \Log::debug('Generate Filter: Petugas check', [
                    'petugas_id' => $petugasId,
                    'petugas_nama' => $item['petugas']['nama'],
                    'has_spk' => in_array($petugasId, $existingSpkPetugasIds),
                    'included' => $notHaveSpk,
                ]);

                return $notHaveSpk;
            })->values();
        }

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
            'next_nomor_urut' => $nextNomorUrut,
            'is_regenerate' => $isRegenerate,
            'default_tanggal_spk' => $defaultTanggalSpk,
            'default_sampai_tanggal' => $defaultSampaiTanggal,
            'existing_spk_map' => $existingSpkMap,
            'last_nomor_urut_in_month' => $lastNomorUrutInMonth,
            'uses_suffix_for_new_petugas' => $usesSuffixForNewPetugas,
        ]);
    }

    /**
     * Show the form to generate Addendum SPKs for a periode with revisions
     */
    public function createAddendum(Request $request, string $periodeHashedId): Response
    {
        $periodeId = \Vinkla\Hashids\Facades\Hashids::decode($periodeHashedId)[0] ?? null;
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        if (! $periodeId || ! $bulan || ! $tahun) {
            abort(404);
        }

        // Format bulan with leading zero
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Get all periode alokasi in the same month with revision status
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
            ->where('tahun', $tahun)
            ->whereIn('status', ['direvisi', 'perubahan'])
            ->pluck('id');

        if ($allPeriodeInMonth->isEmpty()) {
            return redirect()->route('spk.index')->with('error', 'Tidak ada periode dengan status revisi/perubahan');
        }

        // Get all petugas with revisions (those who have alokasi in revision periods)
        $allAlokasi = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->with(['petugas', 'periodeAlokasi.kegiatan'])
            ->get()
            ->filter(function ($alokasi) {
                return $alokasi->petugas && $alokasi->petugas->jenis_petugas === 'non-organik';
            });

        // Group by petugas_id and aggregate their data

        $petugasList = $allAlokasi->groupBy('petugas_id')
            ->map(function ($alokasiGroup) use ($bulanFormatted, $tahun) {
                $firstAlokasi = $alokasiGroup->first();

                // Get existing SPK for this petugas in this month
                $existingSpk = Spk::whereHas('alokasiPetugas', function ($q) use ($firstAlokasi, $bulanFormatted, $tahun) {
                    $q->where('petugas_id', $firstAlokasi->petugas_id)
                        ->whereHas('periodeAlokasi', function ($q2) use ($bulanFormatted, $tahun) {
                            $q2->where('bulan', $bulanFormatted)
                                ->where('tahun', $tahun);
                        });
                })
                    ->where('addendum_number', 0) // Get original SPK only
                    ->first();

                if (! $existingSpk) {
                    return null; // Skip petugas without original SPK
                }

                // Ambil semua alokasi 'perubahan' (bisa lebih dari satu kegiatan)
                $alokasiPerubahanList = $alokasiGroup->filter(function ($alokasi) {
                    return $alokasi->periodeAlokasi->status === 'perubahan';
                });
                if ($alokasiPerubahanList->isEmpty()) {
                    return null;
                }

                $isBerubah = false;
                foreach ($alokasiPerubahanList as $alokasiPerubahan) {
                    // Cari alokasi sebelumnya untuk kegiatan yang sama
                    $alokasiSebelumnya = AlokasiPetugas::where('petugas_id', $firstAlokasi->petugas_id)
                        ->where('periode_alokasi_id', '!=', $alokasiPerubahan->periode_alokasi_id)
                        ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                            $q->where('bulan', $bulanFormatted)
                                ->where('tahun', $tahun)
                                ->whereIn('status', ['disetujui', 'dikirim', 'direvisi']);
                        })
                        ->where('peran', $alokasiPerubahan->peran)
                        ->where('jumlah_satuan', '!=', null)
                        ->orderByDesc('id')
                        ->first();

                    $selisih_jumlah_satuan = (int) ($alokasiPerubahan->jumlah_satuan ?? 0) - (int) ($alokasiSebelumnya->jumlah_satuan ?? 0);
                    $selisih_jumlah_satuan_listing = (int) ($alokasiPerubahan->jumlah_satuan_listing ?? 0) - (int) ($alokasiSebelumnya->jumlah_satuan_listing ?? 0);
                    $selisih_total_honor = (float) ($alokasiPerubahan->total_honor ?? 0) - (float) ($alokasiSebelumnya->total_honor ?? 0);
                    $selisih_total_honor_listing = (float) ($alokasiPerubahan->total_honor_listing ?? 0) - (float) ($alokasiSebelumnya->total_honor_listing ?? 0);

                    \Log::info('DEBUG ADDENDUM', [
                        'petugas_id' => $firstAlokasi->petugas_id,
                        'periode_perubahan' => $alokasiPerubahan->periode_alokasi_id,
                        'periode_sebelumnya' => $alokasiSebelumnya->periode_alokasi_id ?? null,
                        'jumlah_satuan_perubahan' => $alokasiPerubahan->jumlah_satuan,
                        'jumlah_satuan_sebelumnya' => $alokasiSebelumnya->jumlah_satuan ?? null,
                        'jumlah_satuan_listing_perubahan' => $alokasiPerubahan->jumlah_satuan_listing,
                        'jumlah_satuan_listing_sebelumnya' => $alokasiSebelumnya->jumlah_satuan_listing ?? null,
                        'total_honor_perubahan' => $alokasiPerubahan->total_honor,
                        'total_honor_sebelumnya' => $alokasiSebelumnya->total_honor ?? null,
                        'total_honor_listing_perubahan' => $alokasiPerubahan->total_honor_listing,
                        'total_honor_listing_sebelumnya' => $alokasiSebelumnya->total_honor_listing ?? null,
                        'peran_perubahan' => $alokasiPerubahan->peran,
                        'peran_sebelumnya' => $alokasiSebelumnya->peran ?? null,
                        'selisih_jumlah_satuan' => $selisih_jumlah_satuan,
                        'selisih_jumlah_satuan_listing' => $selisih_jumlah_satuan_listing,
                        'selisih_total_honor' => $selisih_total_honor,
                        'selisih_total_honor_listing' => $selisih_total_honor_listing,
                    ]);

                    if (
                        $selisih_jumlah_satuan !== 0 ||
                        $selisih_jumlah_satuan_listing !== 0 ||
                        abs($selisih_total_honor) > 0.01 ||
                        abs($selisih_total_honor_listing) > 0.01 ||
                        $alokasiPerubahan->peran !== ($alokasiSebelumnya->peran ?? null)
                    ) {
                        $isBerubah = true;
                        break;
                    }
                }

                if (! $isBerubah) {
                    return null;
                }

                // Calculate total honor only from 'perubahan' status (latest revision)
                $totalHonor = $alokasiGroup
                    ->filter(function ($alokasi) {
                        return $alokasi->periodeAlokasi->status === 'perubahan';
                    })
                    ->sum(function ($alokasi) {
                        return ($alokasi->total_honor ?? 0) + ($alokasi->total_honor_listing ?? 0);
                    });

                // Get all kegiatan with their peran (only from 'perubahan' status)
                $kegiatanList = $alokasiGroup
                    ->filter(function ($alokasi) {
                        return $alokasi->periodeAlokasi->status === 'perubahan';
                    })
                    ->map(function ($alokasi) {
                        return [
                            'kegiatan_kode' => $alokasi->periodeAlokasi->kegiatan->kode_kegiatan,
                            'kegiatan_nama' => $alokasi->periodeAlokasi->kegiatan->nama_kegiatan,
                            'peran' => $alokasi->peran,
                        ];
                    })->values()->all();

                // Get last addendum number for this petugas
                $lastAddendum = Spk::where('parent_spk_id', $existingSpk->id)
                    ->orderBy('addendum_number', 'desc')
                    ->first();

                $nextAddendumNumber = $lastAddendum ? $lastAddendum->addendum_number + 1 : 1;

                return [
                    'alokasi_id' => $firstAlokasi->id,
                    'alokasi_hashed_id' => $firstAlokasi->hashed_id,
                    'existing_spk_id' => $existingSpk->id,
                    'existing_spk_hashed_id' => $existingSpk->hashed_id,
                    'existing_spk_nomor' => $existingSpk->nomor_spk,
                    'next_addendum_number' => $nextAddendumNumber,
                    'petugas' => [
                        'id' => $firstAlokasi->petugas->id,
                        'hashed_id' => $firstAlokasi->petugas->hashed_id,
                        'nama' => $firstAlokasi->petugas->nama,
                        'nik' => $firstAlokasi->petugas->nik,
                        'jenis_petugas' => $firstAlokasi->petugas->jenis_petugas,
                    ],
                    'jumlah_kegiatan' => $alokasiGroup->count(),
                    'kegiatan_list' => $kegiatanList,
                    'total_honor' => $totalHonor,
                ];
            })
            ->filter() // Remove nulls
            ->sortBy(function ($item) {
                return $item['petugas']['nama'];
            })
            ->values();

        $periode = PeriodeAlokasi::with('kegiatan')->findOrFail($periodeId);

        \Log::info('DEBUG PETUGAS LIST FINAL', ['count' => $petugasList->count(), 'ids' => $petugasList->pluck('petugas.id')]);

        return Inertia::render('Spk/Addendum', [
            'periode' => [
                'id' => $periode->id,
                'hashed_id' => $periode->hashed_id,
                'tahun' => $tahun,
                'bulan' => (int) $bulan,
                'bulan_label' => $this->getBulanLabel((int) $bulan),
                'kegiatan' => [
                    'hashed_id' => $periode->kegiatan->hashed_id,
                    'kode_kegiatan' => $periode->kegiatan->kode_kegiatan,
                    'nama_kegiatan' => $periode->kegiatan->nama_kegiatan,
                    'jenis_kegiatan' => $periode->kegiatan->jenis_kegiatan,
                    'tahun_anggaran' => $periode->kegiatan->tahun_anggaran,
                ],
            ],
            'petugas_list' => $petugasList->values()->all(),
        ]);
    }

    /**
     * Preview addendum SPK PDF
     */
    public function previewAddendum(Request $request, string $periodeHashedId, string $petugasHashedId)
    {
        $periodeId = \Vinkla\Hashids\Facades\Hashids::decode($periodeHashedId)[0] ?? null;
        $petugasId = \Vinkla\Hashids\Facades\Hashids::decode($petugasHashedId)[0] ?? null;

        if (! $periodeId || ! $petugasId) {
            abort(404);
        }

        $validated = $request->validate([
            'tanggal_spk' => 'required|date',
            'sampai_tanggal' => 'required|date',
            'parent_spk_id' => 'required|exists:spk,id',
            'addendum_number' => 'required|integer|min:1',
        ]);

        // Get parent SPK to retrieve original details
        $parentSpk = Spk::with(['alokasiPetugas.periodeAlokasi.kegiatan'])->findOrFail($validated['parent_spk_id']);

        // Get periode alokasi for this addendum
        $periode = PeriodeAlokasi::with(['kegiatan'])->findOrFail($periodeId);

        // Get petugas details
        $petugas = Petugas::findOrFail($petugasId);

        // Get bulan and tahun from periode
        $bulan = $periode->bulan;
        $tahun = $periode->tahun;
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Get all periode in the same month with status 'dikirim' and 'perubahan'
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'perubahan'])
            ->pluck('id');

        // Get all alokasi for this petugas in the same month (from 'dikirim' and 'perubahan')
        $allAlokasi = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->where('petugas_id', $petugasId)
            ->with(['periodeAlokasi.kegiatan.rateHonors.satuan'])
            ->get();

        // Calculate total honor (from both 'dikirim' and 'perubahan' status)
        $totalHonor = $allAlokasi->sum(function ($alokasi) {
            return ($alokasi->total_honor ?? 0) + ($alokasi->total_honor_listing ?? 0);
        });

        // Build kegiatan list
        $kegiatanList = $allAlokasi->map(function ($alokasi) {
            $periode = $alokasi->periodeAlokasi;

            // Get satuan from rate honor
            $rateHonor = $periode->kegiatan->rateHonors->first(function ($rate) use ($alokasi) {
                return $rate->status_kepegawaian === $alokasi->status_kepegawaian
                    && $rate->jenis_penugasan === $alokasi->peran;
            });

            $satuanKode = $rateHonor && $rateHonor->satuan ? $rateHonor->satuan->kode : 'PAKET';

            return [
                'kode_kegiatan' => $periode->kegiatan->kode_kegiatan,
                'kode_coa' => $periode->kegiatan->kode_coa,
                'nama_kegiatan' => $periode->kegiatan->nama_kegiatan,
                'peran' => $alokasi->peran,
                'peran_label' => $this->getPeranLabel($alokasi->peran),
                'jumlah_satuan' => $alokasi->jumlah_satuan ?? 0,
                'jumlah_satuan_listing' => $alokasi->jumlah_satuan_listing ?? 0,
                'total_honor' => $alokasi->total_honor ?? 0,
                'total_honor_listing' => $alokasi->total_honor_listing ?? 0,
                'satuan_kode' => $satuanKode,
                'periode_mulai' => $periode->tanggal_mulai,
                'periode_selesai' => $periode->tanggal_selesai,
                'periode_bulan' => $periode->bulan,
                'periode_tahun' => $periode->tahun,
                'periode_bulan_label' => $this->getBulanLabel((int) $periode->bulan),
            ];
        })->values()->all();

        // Format nomor SPK with addendum suffix
        $nomorSpkParts = explode('/', $parentSpk->nomor_spk);
        $nomorSpkParts[2] = $nomorSpkParts[2].'/ADD-'.$validated['addendum_number'];
        $nomorSpk = implode('/', $nomorSpkParts);

        $data = [
            'nomor_spk' => $nomorSpk,
            'tanggal_spk' => $validated['tanggal_spk'],
            'sampai_tanggal' => $validated['sampai_tanggal'],
            'addendum_number' => $validated['addendum_number'],
            'parent_nomor_spk' => $parentSpk->nomor_spk,
            'petugas' => [
                'nama' => $petugas->nama,
                'nik' => $petugas->nik,
                'tempat_lahir' => $petugas->tempat_lahir,
                'tanggal_lahir' => $petugas->tanggal_lahir,
                'alamat' => $petugas->alamat,
                'no_rekening' => $petugas->no_rekening,
                'nama_bank' => $petugas->nama_bank,
                'npwp' => $petugas->npwp,
            ],
            'kegiatan_list' => $kegiatanList,
            'total_honor' => $totalHonor,
            'periode' => [
                'bulan' => (int) $bulan,
                'tahun' => $tahun,
                'bulan_label' => $this->getBulanLabel((int) $bulan),
            ],
        ];

        try {
            $pdfContent = $this->generateAddendumPdfContent($data);

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="preview-addendum-spk-'.$petugas->nama.'.pdf"',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error generating addendum preview PDF: '.$e->getMessage());

            return back()->with('error', 'Gagal generate preview addendum SPK: '.$e->getMessage());
        }
    }

    /**
     * Generate and save addendum SPK
     */
    public function generateAddendum(Request $request, string $periodeHashedId, string $petugasHashedId)
    {
        $periodeId = \Vinkla\Hashids\Facades\Hashids::decode($periodeHashedId)[0] ?? null;
        $petugasId = \Vinkla\Hashids\Facades\Hashids::decode($petugasHashedId)[0] ?? null;

        if (! $periodeId || ! $petugasId) {
            abort(404);
        }

        $validated = $request->validate([
            'tanggal_spk' => 'required|date',
            'sampai_tanggal' => 'required|date',
            'parent_spk_id' => 'required|exists:spk,id',
            'addendum_number' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // Get parent SPK
            $parentSpk = Spk::findOrFail($validated['parent_spk_id']);

            // Get periode alokasi
            $periode = PeriodeAlokasi::with(['kegiatan'])->findOrFail($periodeId);

            // Get petugas details
            $petugas = Petugas::findOrFail($petugasId);

            $bulan = $periode->bulan;
            $tahun = $periode->tahun;
            $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

            // Get all periode in the same month with status 'dikirim' and 'perubahan'
            $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
                ->where('tahun', $tahun)
                ->whereIn('status', ['dikirim', 'perubahan'])
                ->pluck('id');

            // Get all alokasi for this petugas in the same month
            $allAlokasi = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
                ->where('petugas_id', $petugasId)
                ->with(['periodeAlokasi.kegiatan.rateHonors.satuan'])
                ->get();

            // Use first alokasi as the main reference (we'll store this in alokasi_petugas_id)
            $mainAlokasi = $allAlokasi->first();

            if (! $mainAlokasi) {
                throw new \Exception('Tidak ditemukan alokasi untuk petugas ini');
            }

            // Calculate total honor (from 'dikirim' and 'perubahan' status)
            $totalHonor = $allAlokasi->sum(function ($alokasi) {
                return ($alokasi->total_honor ?? 0) + ($alokasi->total_honor_listing ?? 0);
            });

            // Build kegiatan list
            $kegiatanList = $allAlokasi->map(function ($alokasi) {
                $periode = $alokasi->periodeAlokasi;

                // Get satuan from rate honor
                $rateHonor = $periode->kegiatan->rateHonors->first(function ($rate) use ($alokasi) {
                    return $rate->status_kepegawaian === $alokasi->status_kepegawaian
                        && $rate->jenis_penugasan === $alokasi->peran;
                });

                $satuanKode = $rateHonor && $rateHonor->satuan ? $rateHonor->satuan->kode : 'PAKET';

                return [
                    'kode_kegiatan' => $periode->kegiatan->kode_kegiatan,
                    'kode_coa' => $periode->kegiatan->kode_coa,
                    'nama_kegiatan' => $periode->kegiatan->nama_kegiatan,
                    'peran' => $alokasi->peran,
                    'peran_label' => $this->getPeranLabel($alokasi->peran),
                    'jumlah_satuan' => $alokasi->jumlah_satuan ?? 0,
                    'jumlah_satuan_listing' => $alokasi->jumlah_satuan_listing ?? 0,
                    'total_honor' => $alokasi->total_honor ?? 0,
                    'total_honor_listing' => $alokasi->total_honor_listing ?? 0,
                    'satuan_kode' => $satuanKode,
                    'periode_mulai' => $periode->tanggal_mulai,
                    'periode_selesai' => $periode->tanggal_selesai,
                    'periode_bulan' => $periode->bulan,
                    'periode_tahun' => $periode->tahun,
                    'periode_bulan_label' => $this->getBulanLabel((int) $periode->bulan),
                ];
            })->values()->all();

            // Format nomor SPK addendum: nomor urut parent + /ADD-x
            $nomorSpkParts = explode('/', $parentSpk->nomor_spk);
            // Pastikan tidak double /ADD-x jika parent sudah addendum
            $baseNomorUrut = $nomorSpkParts[2];
            if (str_contains($baseNomorUrut, '/ADD-')) {
                $baseNomorUrut = explode('/ADD-', $baseNomorUrut)[0];
            }
            $nomorSpkParts[2] = $baseNomorUrut.'/ADD-'.$validated['addendum_number'];
            $nomorSpk = implode('/', $nomorSpkParts);

            $data = [
                'nomor_spk' => $nomorSpk,
                'tanggal_spk' => $validated['tanggal_spk'],
                'sampai_tanggal' => $validated['sampai_tanggal'],
                'addendum_number' => $validated['addendum_number'],
                'parent_nomor_spk' => $parentSpk->nomor_spk,
                'petugas' => [
                    'nama' => $petugas->nama,
                    'nik' => $petugas->nik,
                    'tempat_lahir' => $petugas->tempat_lahir,
                    'tanggal_lahir' => $petugas->tanggal_lahir,
                    'alamat' => $petugas->alamat,
                    'no_rekening' => $petugas->no_rekening,
                    'nama_bank' => $petugas->nama_bank,
                    'npwp' => $petugas->npwp,
                ],
                'kegiatan_list' => $kegiatanList,
                'total_honor' => $totalHonor,
                'periode' => [
                    'bulan' => (int) $bulan,
                    'tahun' => $tahun,
                    'bulan_label' => $this->getBulanLabel((int) $bulan),
                ],
            ];

            // Generate addendum PDF content
            $pdfContent = $this->generateAddendumPdfContent($data);

            // Save to public directory (same as regular SPK)
            $sanitizedNamaPetugas = preg_replace('/[\/\\\\:*?"<>|]/', '', $petugas->nama);
            $fileName = 'SPK-ADDENDUM-'.$validated['addendum_number'].'-'.$sanitizedNamaPetugas.'-'.$bulanFormatted.'-'.$tahun.'.pdf';
            $filePath = "spk-export/{$tahun}/{$bulanFormatted}/{$fileName}";

            // Create directory if not exists
            $publicPath = public_path("spk-export/{$tahun}/{$bulanFormatted}");
            if (! file_exists($publicPath)) {
                mkdir($publicPath, 0755, true);
            }

            // Save PDF to public directory
            file_put_contents(public_path($filePath), $pdfContent);

            // Create SPK record with addendum data
            $spk = Spk::create([
                'petugas_id' => $petugasId,
                'alokasi_petugas_id' => $mainAlokasi->id,
                'nomor_spk' => $nomorSpk,
                'tanggal_spk' => $validated['tanggal_spk'],
                'tanggal_mulai_kerja' => $parentSpk->tanggal_mulai_kerja,
                'tanggal_selesai_kerja' => $parentSpk->tanggal_selesai_kerja,
                'nilai_kontrak' => $totalHonor,
                'nama_ppk' => $parentSpk->nama_ppk,
                'nip_ppk' => $parentSpk->nip_ppk,
                'file_path' => $filePath,
                'status' => 'draft',
                'parent_spk_id' => $validated['parent_spk_id'],
                'addendum_number' => $validated['addendum_number'],
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Addendum SPK berhasil di-generate',
                ]);
            }

            return redirect()->route('spk.index')->with('success', 'Addendum SPK berhasil di-generate');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error generating addendum SPK: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate addendum SPK: '.$e->getMessage(),
            ], 500);
        }
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
            'addendums.alokasiPetugas.periodeAlokasi.kegiatan',
            'addendums.createdBy',
        ])->findOrFail($spkId);

        $periode = $spk->alokasiPetugas->periodeAlokasi;
        $petugas = $spk->alokasiPetugas->petugas;
        $bast = $spk->bast->first();

        // Get all addendums (ordered)
        $addendums = $spk->addendums->sortBy('addendum_number')->values()->map(function ($addendum) {
            return [
                'id' => $addendum->id,
                'hashed_id' => $addendum->hashed_id,
                'nomor_spk' => $addendum->nomor_spk,
                'tanggal_spk' => $addendum->tanggal_spk,
                'tanggal_mulai_kerja' => $addendum->tanggal_mulai_kerja,
                'tanggal_selesai_kerja' => $addendum->tanggal_selesai_kerja,
                'nilai_kontrak' => $addendum->nilai_kontrak,
                'status' => $addendum->status,
                'file_path' => $addendum->file_path,
                'addendum_number' => $addendum->addendum_number,
                'created_by' => $addendum->createdBy->name ?? 'System',
                'created_at' => $addendum->created_at->format('d M Y H:i'),
            ];
        });

        // Get all alokasi for this petugas in the same month (all kegiatan, all statuses)
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $periode->bulan)
            ->where('tahun', $periode->tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->pluck('id');

        $allAlokasi = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->where('petugas_id', $petugas->id)
            ->with(['periodeAlokasi.kegiatan'])
            ->get();

        // Group by kegiatan only (not peran) - consolidate all peran under one row per kegiatan
        $grouped = $allAlokasi->groupBy(function ($alokasi) {
            return $alokasi->periodeAlokasi->kegiatan->id;
        });

        $mergedKegiatanList = $grouped->map(function ($alokasiGroup) {
            // Find the original (non-perubahan) and latest (perubahan if exists) for the entire kegiatan
            $original = $alokasiGroup->first(function ($a) {
                return in_array($a->periodeAlokasi->status, ['dikirim', 'disetujui', 'direvisi']);
            }) ?? $alokasiGroup->first();
            $latest = $alokasiGroup->sortByDesc(function ($a) {
                return $a->periodeAlokasi->id;
            })->first();

            // Calculate total honor across all peran for this kegiatan
            $originalTotalHonor = $alokasiGroup->filter(function ($a) {
                return in_array($a->periodeAlokasi->status, ['dikirim', 'disetujui', 'direvisi']);
            })->sum(function ($a) {
                return ($a->total_honor ?? 0) + ($a->total_honor_listing ?? 0);
            });

            if ($originalTotalHonor === 0) {
                $originalTotalHonor = $alokasiGroup->sum(function ($a) {
                    return ($a->total_honor ?? 0) + ($a->total_honor_listing ?? 0);
                });
            }

            $latestTotalHonor = $alokasiGroup->sum(function ($a) {
                return ($a->total_honor ?? 0) + ($a->total_honor_listing ?? 0);
            });

            // Get all peran for this kegiatan
            $peranList = $alokasiGroup->pluck('peran')->unique()->implode(', ');

            $hasChange = $latestTotalHonor != $originalTotalHonor;

            return [
                'id' => $latest->periodeAlokasi->kegiatan->id,
                'hashed_id' => $latest->periodeAlokasi->kegiatan->hashed_id,
                'kode_kegiatan' => $latest->periodeAlokasi->kegiatan->kode_kegiatan,
                'nama_kegiatan' => $latest->periodeAlokasi->kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $latest->periodeAlokasi->kegiatan->jenis_kegiatan,
                'tahun_anggaran' => $latest->periodeAlokasi->kegiatan->tahun_anggaran,
                'peran' => $peranList,
                'total_honor' => $latestTotalHonor,
                'original' => [
                    'total_honor' => $originalTotalHonor,
                    'peran' => $peranList,
                ],
                'latest' => [
                    'total_honor' => $latestTotalHonor,
                    'peran' => $peranList,
                ],
                'has_change' => $hasChange,
            ];
        })->values()->all();

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
                'signed_file_path' => $spk->signed_file_path,
                'previous_file_path' => $spk->previous_file_path,
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
            'kegiatan_list' => $mergedKegiatanList,
            'addendums' => $addendums,
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
        // For regular SPK (non-addendum): use 'dikirim' and 'direvisi' status
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun)
                    ->whereIn('status', ['dikirim', 'direvisi']);
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
            'tanggalPerpanjangan' => null,
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

        // Fallback: Use combined template if merge failed
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-petugas', $data)
            ->setPaper('a4', 'portrait');

        return response($pdf->output())
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
        // For regular SPK (non-addendum): use 'dikirim' and 'direvisi' status
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun)
                    ->whereIn('status', ['dikirim', 'direvisi']);
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
            'tanggalPerpanjangan' => null,
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
        // For regular SPK (non-addendum): use 'dikirim' and 'direvisi' status
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun)
                    ->whereIn('status', ['dikirim', 'direvisi']);
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
            'tanggalPerpanjangan' => null,
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
            'tanggal_spk' => ['required', 'date'],
            'sampai_tanggal' => ['required', 'date', 'after_or_equal:tanggal_spk'],
        ]);

        $periode = PeriodeAlokasi::with(['kegiatan', 'alokasiPetugas.petugas'])->findOrFail($periodeId);

        // Generate nomor_spk using next available urut for this year
        $tahun = $periode->tahun;
        $nextNomorUrut = $this->getNextNomorUrut($tahun);
        // Format: PPIS/13730/{urut}/K/{tahun}
        $nomorSpk = "PPIS/13730/{$nextNomorUrut}/K/{$tahun}";

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
            'nomorSpk' => $nomorSpk,
            'tanggalSpk' => \Carbon\Carbon::parse($validated['tanggal_spk']),
            'sampaiTanggal' => \Carbon\Carbon::parse($validated['sampai_tanggal']),
            'tanggalPerpanjangan' => null,
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

            // Save PDF file to public/spk-export
            // Extract nomor urut from nomor_spk format: PPIS/13730/4/K/2025 -> get "4"
            $nomorParts = explode('/', $data['nomorSpk']);
            $nomorUrut = $nomorParts[2] ?? '0'; // Index 2 is the sequential number

            // Clean filename - remove special characters that are invalid for filenames
            $namaPetugas = preg_replace('/[\/\\\\:*?"<>|]/', '', $petugas->nama);
            $bulanLabel = $this->getBulanLabel($periode->bulan);

            $fileName = "SPK_{$nomorUrut}_{$namaPetugas}_{$bulanLabel}.pdf";
            $filePath = 'spk-export/'.date('Y').'/'.date('m').'/'.$fileName;

            // Create directory if not exists
            $publicPath = public_path('spk-export/'.date('Y').'/'.date('m'));
            if (! file_exists($publicPath)) {
                mkdir($publicPath, 0755, true);
            }

            // Save to public directory
            file_put_contents(public_path($filePath), $pdfOutput);

            // Save to database
            $spk = Spk::create([
                'nomor_spk' => $nomorSpk,
                'alokasi_petugas_id' => $allAlokasi->first()->id,
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

            return redirect()->route('spk.index')->with('success', 'SPK berhasil dibuat');
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

    /**
     * Generate addendum PDF content
     */
    private function generateAddendumPdfContent(array $data): string
    {
        // Get active Kepala BPS
        $penandatangan = Penandatangan::active()->ppk()->firstOrFail();

        // Check if any kegiatan contains 'Ubinan'
        $hasUbinanKegiatan = collect($data['kegiatan_list'])->contains(function ($kegiatan) {
            return stripos($kegiatan['nama_kegiatan'], 'Ubinan') !== false;
        });

        $pdfData = [
            'nomorSpk' => $data['nomor_spk'],
            'tanggalSpk' => \Carbon\Carbon::parse($data['tanggal_spk']),
            'sampaiTanggal' => \Carbon\Carbon::parse($data['sampai_tanggal']),
            'addendum_number' => $data['addendum_number'],
            'parent_nomor_spk' => $data['parent_nomor_spk'],
            'petugas' => (object) $data['petugas'],
            'kegiatan_list' => $data['kegiatan_list'],
            'total_honor' => $data['total_honor'],
            'penandatangan' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'kepalaBps' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'bulan_label' => $data['periode']['bulan_label'],
            'tahun' => $data['periode']['tahun'],
            'hasUbinanKegiatan' => $hasUbinanKegiatan,
        ];

        // Generate addendum main PDF
        $pdfMain = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-addendum-main', $pdfData)
            ->setPaper('a4', 'portrait');

        // Generate addendum lampiran PDF
        $pdfLampiran = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-addendum-lampiran', $pdfData)
            ->setPaper('a4', 'landscape');

        // Save temporary PDFs
        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        $timestamp = time().'_'.uniqid();
        $mainPath = $tempPath.'/spk_addendum_main_'.$timestamp.'.pdf';
        $lampiranPath = $tempPath.'/spk_addendum_lampiran_'.$timestamp.'.pdf';
        $mergedPath = $tempPath.'/spk_addendum_merged_'.$timestamp.'.pdf';

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
            // Fallback to main PDF only if merge failed
            $pdfOutput = $pdfMain->output();
        }

        // Cleanup temporary files
        @unlink($mainPath);
        @unlink($lampiranPath);
        @unlink($mergedPath);

        return $pdfOutput;
    }

    /**
     * Bulk generate SPK for all non-organik petugas in a periode
     */
    public function generateAllSpk(Request $request, string $periodeHashedId)
    {
        $periodeId = \Vinkla\Hashids\Facades\Hashids::decode($periodeHashedId)[0] ?? null;
        if (! $periodeId) {
            abort(404);
        }

        $validated = $request->validate([
            'tanggal_spk' => ['required', 'date'],
            'sampai_tanggal' => ['required', 'date', 'after_or_equal:tanggal_spk'],
            'petugas_ids' => ['nullable', 'array'], // Array of petugas hashed IDs
        ]);

        // Decode petugas_ids if provided
        $selectedPetugasIds = [];
        if (! empty($validated['petugas_ids'])) {
            foreach ($validated['petugas_ids'] as $hashedId) {
                $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hashedId);
                if (! empty($decoded)) {
                    $selectedPetugasIds[] = $decoded[0];
                }
            }
        }

        $periode = PeriodeAlokasi::with(['kegiatan'])->findOrFail($periodeId);

        // Get all periode alokasi in the same month and year
        // For SPK generation (non-addendum): use 'dikirim' and 'direvisi' status only
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $periode->bulan)
            ->where('tahun', $periode->tahun)
            ->whereIn('status', ['dikirim', 'direvisi'])
            ->pluck('id');

        // Get all unique non-organik petugas from all alokasi in this month
        $allAlokasi = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->with(['petugas', 'periodeAlokasi.kegiatan'])
            ->get()
            ->filter(function ($alokasi) {
                return $alokasi->petugas && $alokasi->petugas->jenis_petugas === 'non-organik';
            });

        // Group by petugas_id and aggregate their data
        $petugasList = $allAlokasi->groupBy('petugas_id')->sortKeys();

        // Sort by petugas name for consistent nomor urut
        $sortedPetugas = $petugasList->sortBy(function ($group) {
            return $group->first()->petugas->nama;
        });

        // Filter by selected petugas if provided
        if (! empty($selectedPetugasIds)) {
            $sortedPetugas = $sortedPetugas->filter(function ($group, $petugasId) use ($selectedPetugasIds) {
                return in_array($petugasId, $selectedPetugasIds);
            });
        }

        $tahun = $periode->tahun;
        $bulan = $periode->bulan;

        // Get all existing SPKs for this month (one SPK per petugas)
        // Now we can query directly by petugas_id since SPK table has it
        $allPeriodeIds = PeriodeAlokasi::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->pluck('id');

        // Get unique petugas_ids that have alokasi in this month
        $petugasIdsInMonth = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeIds)
            ->pluck('petugas_id')
            ->unique();

        // Get existing SPKs for these petugas by checking tanggal_spk (the official date)
        $existingSpks = Spk::whereIn('petugas_id', $petugasIdsInMonth)
            ->where('addendum_number', 0)
            ->whereYear('tanggal_spk', $tahun)
            ->whereMonth('tanggal_spk', $bulan)
            ->with('petugas')
            ->get()
            ->keyBy('petugas_id');

        // Check if this is a regenerate (existing SPKs present) or first time generate
        $isRegenerate = $existingSpks->isNotEmpty();

        // Determine the last nomor_urut_base from existing SPKs in this month
        $lastNomorUrutBase = null;
        if ($isRegenerate) {
            // Get the highest nomor_urut_base from existing SPKs in THIS MONTH only
            foreach ($existingSpks as $spk) {
                $baseNumber = $spk->nomor_urut_base ?? $this->extractNomorUrut($spk->nomor_spk);
                if ($lastNomorUrutBase === null || $baseNumber > $lastNomorUrutBase) {
                    $lastNomorUrutBase = $baseNumber;
                }
            }
        }

        // For first time generation, get next sequential nomor
        $nextNomorUrut = $this->getNextNomorUrut($tahun);
        $nomorUrutCounter = 0;

        $nextSuffix = 'A';
        $results = [];

        // Log petugas yang akan diproses
        \Log::info('SPK Generation Start:', [
            'periode' => "{$periode->bulan}/{$periode->tahun}",
            'is_regenerate' => $isRegenerate,
            'petugas_count' => $sortedPetugas->count(),
            'petugas_list' => $sortedPetugas->map(fn ($group) => $group->first()->petugas->nama)->values()->all(),
        ]);

        foreach ($sortedPetugas as $petugasId => $alokasiGroup) {
            $petugas = $alokasiGroup->first()->petugas;
            $petugasHashedId = $petugas->hashed_id;

            // Check if this petugas already has an SPK
            $existingSpk = $existingSpks->get($petugasId);

            if ($existingSpk) {
                // Use existing nomor for updates
                $nomorSpk = $existingSpk->nomor_spk;
                $noUrut = $existingSpk->nomor_urut_base ?? $this->extractNomorUrut($nomorSpk);
            } else {
                // New petugas
                if ($isRegenerate) {
                    // Regenerate mode: Check if next sequential number is available
                    $nextSequential = ($lastNomorUrutBase ?? $nextNomorUrut) + 1;

                    // Check if this number is already used in ANY month this year
                    $numberUsed = Spk::where('nomor_urut_base', $nextSequential)
                        ->where('addendum_number', 0)
                        ->whereYear('tanggal_spk', $tahun)
                        ->exists();

                    if ($numberUsed) {
                        // Use suffix mode
                        $noUrut = $lastNomorUrutBase ?? $nextNomorUrut;
                        $nomorSpk = "PPIS/13730/{$noUrut}{$nextSuffix}/K/{$tahun}";
                    } else {
                        // Use sequential mode
                        $noUrut = $nextSequential;
                        $nomorSpk = "PPIS/13730/{$noUrut}/K/{$tahun}";
                        // Update lastNomorUrutBase for next iteration
                        $lastNomorUrutBase = $noUrut;
                    }
                } else {
                    // First time generation: use sequential numbering
                    $noUrut = $nextNomorUrut + $nomorUrutCounter;
                    $nomorSpk = "PPIS/13730/{$noUrut}/K/{$tahun}";
                    $nomorUrutCounter++;
                }
            }

            // Call the same logic as generateSpk, but inline to avoid HTTP call
            // IMPORTANT: Only get alokasi from periode with 'dikirim' and 'direvisi' status
            $allAlokasiPetugas = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
                ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                    $q->where('bulan', $periode->bulan)
                        ->where('tahun', $periode->tahun)
                        ->whereIn('status', ['dikirim', 'direvisi']);
                })
                ->where('petugas_id', $petugasId)
                ->get();

            if ($allAlokasiPetugas->isEmpty()) {
                $results[] = [
                    'petugas_id' => $petugasId,
                    'status' => 'failed',
                    'message' => 'Tidak ada alokasi untuk petugas ini',
                ];

                continue;
            }

            $penandatangan = \App\Models\Penandatangan::active()->ppk()->first();
            if (! $penandatangan) {
                $results[] = [
                    'petugas_id' => $petugasId,
                    'status' => 'failed',
                    'message' => 'Penandatangan (PPK) tidak ditemukan',
                ];

                continue;
            }

            $totalHonor = 0;
            $uraianTugas = [];
            $bebanAnggaran = '';
            foreach ($allAlokasiPetugas as $alokasi) {
                $kegiatan = $alokasi->periodeAlokasi->kegiatan;
                $totalHonor += $this->calculateTotalHonor($kegiatan, $alokasi);
                $uraianTugas = array_merge($uraianTugas, $this->getUraianTugas($kegiatan, $alokasi));
                if (empty($bebanAnggaran)) {
                    $bebanAnggaran = $this->getBebanAnggaran($kegiatan);
                }
            }

            $data = [
                'periode' => $periode,
                'alokasi' => $allAlokasiPetugas->first(),
                'petugas' => $petugas,
                'kegiatan' => $periode->kegiatan,
                'nomorSpk' => $nomorSpk,
                'tanggalSpk' => \Carbon\Carbon::parse($validated['tanggal_spk']),
                'sampaiTanggal' => \Carbon\Carbon::parse($validated['sampai_tanggal']),
                'tanggalPerpanjangan' => null,
                'penandatangan' => preg_replace('/,.*$/', '', $penandatangan->nama),
                'kepalaBps' => preg_replace('/,.*$/', '', $penandatangan->nama),
                'peranLabel' => $this->getPeranLabel($allAlokasiPetugas->first()->peran),
                'totalHonor' => $totalHonor,
                'uraianTugas' => $uraianTugas,
                'bebanAnggaran' => $bebanAnggaran,
            ];

            // Use the same PDF/database logic as generateSpk
            \DB::beginTransaction();
            try {
                $pdfMain = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-main', $data)
                    ->setPaper('a4', 'portrait');
                $pdfLampiran = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-lampiran', $data)
                    ->setPaper('a4', 'landscape');
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
                $merged = \App\Services\PdfMergerService::mergePdfFiles(
                    [$mainPath, $lampiranPath],
                    $mergedPath
                );
                $pdfOutput = null;
                if ($merged && file_exists($mergedPath)) {
                    $pdfOutput = file_get_contents($mergedPath);
                } else {
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('spk-petugas', $data)
                        ->setPaper('a4', 'portrait');
                    $pdfOutput = $pdf->output();
                }
                @unlink($mainPath);
                @unlink($lampiranPath);
                @unlink($mergedPath);
                $nomorParts = explode('/', $data['nomorSpk']);
                $nomorUrut = $nomorParts[2] ?? '0';

                // Check if SPK already exists for this petugas (use existing SPK from map)
                $existingSpkRecord = $existingSpk;
                $bulanLabel = $this->getBulanLabel($periode->bulan);
                $namaPetugas = preg_replace('/[\/\\\\:*?"<>|]/', '', $petugas->nama);
                $nomorUrut = $noUrut.(($isRegenerate && ! $existingSpk) ? $nextSuffix : '');
                $fileName = "SPK_{$nomorUrut}_{$namaPetugas}_{$bulanLabel}.pdf";
                $filePath = 'spk-export/'.date('Y').'/'.date('m').'/'.$fileName;
                $publicPath = public_path('spk-export/'.date('Y').'/'.date('m'));
                if (! file_exists($publicPath)) {
                    mkdir($publicPath, 0755, true);
                }
                file_put_contents(public_path($filePath), $pdfOutput);

                if ($existingSpkRecord) {
                    // Update existing SPK with new data
                    // Save previous file_path and signed_file_path before updating
                    $updateData = [
                        'nomor_urut_base' => $noUrut, // Populate base number if NULL
                        'tanggal_spk' => $validated['tanggal_spk'],
                        'tanggal_mulai_kerja' => \Carbon\Carbon::create($periode->tahun, $periode->bulan, 1),
                        'tanggal_selesai_kerja' => \Carbon\Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth(),
                        'nilai_kontrak' => $totalHonor,
                        'nama_ppk' => preg_replace('/,.*$/', '', $penandatangan->nama),
                        'nip_ppk' => $penandatangan->nip ?? null,
                        'file_path' => $filePath, // Update file_path with new regenerated SPK
                        'regeneration_count' => ($existingSpkRecord->regeneration_count ?? 0) + 1, // Increment count
                    ];

                    // If there was a signed file, move it to previous_file_path and reset signed_file_path
                    if ($existingSpkRecord->signed_file_path) {
                        $updateData['previous_file_path'] = $existingSpkRecord->signed_file_path;
                        $updateData['signed_file_path'] = null; // Reset signed file for new regenerated SPK
                    }

                    $existingSpkRecord->update($updateData);

                    \DB::commit();
                    $results[] = [
                        'petugas_id' => $petugasId,
                        'status' => 'updated',
                        'spk_id' => $existingSpkRecord->id,
                    ];
                } else {
                    // Create new SPK
                    $spk = Spk::create([
                        'nomor_spk' => $nomorSpk,
                        'nomor_urut_suffix' => (strpos($nomorSpk, $noUrut) !== false && preg_match('/[A-Z]/', $nomorSpk)) ? $nextSuffix : null,
                        'nomor_urut_base' => $noUrut,
                        'petugas_id' => $petugasId,
                        'alokasi_petugas_id' => $allAlokasiPetugas->first()->id,
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

                    \DB::commit();
                    $results[] = [
                        'petugas_id' => $petugasId,
                        'status' => 'created',
                        'spk_id' => $spk->id,
                    ];

                    // Increment suffix for next new petugas (only in suffix mode)
                    if (strpos($nomorSpk, $nextSuffix) !== false) {
                        $nextSuffix++;
                    }
                }
            } catch (\Exception $e) {
                \DB::rollBack();
                $results[] = [
                    'petugas_id' => $petugasId,
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }

        $successCount = collect($results)->where('status', 'created')->count();
        $updatedCount = collect($results)->where('status', 'updated')->count();
        $failedCount = collect($results)->where('status', 'failed')->count();

        // Log detailed results for debugging
        if ($failedCount > 0) {
            \Log::info('SPK Generation Failures:', [
                'failed_results' => collect($results)->where('status', 'failed')->values()->all(),
            ]);
        }

        // Adjust message based on regenerate mode
        if ($isRegenerate) {
            $message = 'SPK berhasil ';
            if ($successCount > 0) {
                $message .= "ditambahkan: {$successCount} baru";
                if ($updatedCount > 0) {
                    $message .= ", {$updatedCount} diperbarui";
                }
            } elseif ($updatedCount > 0) {
                $message .= "diperbarui: {$updatedCount} SPK";
            } else {
                $message .= 'diproses';
            }
            $message .= '.';
        } else {
            $message = "SPK berhasil dibuat: {$successCount} baru, {$updatedCount} diperbarui.";
        }

        if ($failedCount > 0) {
            // Get failure details
            $failedMessages = collect($results)
                ->where('status', 'failed')
                ->pluck('message')
                ->filter()
                ->unique()
                ->join('; ');

            $message .= " {$failedCount} gagal";
            if ($failedMessages) {
                $message .= ": {$failedMessages}";
            } else {
                $message .= '.';
            }
        }

        return redirect()->route('spk.index')->with('success', $message);
    }

    /**
     * Check if there are new kegiatan/petugas added after SPK was generated
     */
    private function hasNewKegiatanAfterSpk(int $tahun, int $bulan, $monthPeriodes): bool
    {
        // Get the latest SPK creation timestamp for non-addendum SPKs in this month
        $latestSpkCreatedAt = null;
        foreach ($monthPeriodes as $periode) {
            $latestSpk = $periode->spk()
                ->where('addendum_number', 0)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestSpk && (! $latestSpkCreatedAt || $latestSpk->created_at > $latestSpkCreatedAt)) {
                $latestSpkCreatedAt = $latestSpk->created_at;
            }
        }

        // If no SPK exists, return false (should use normal "Generate SPK" button)
        if (! $latestSpkCreatedAt) {
            return false;
        }

        // Check if there are any alokasi petugas (non-organik) that don't have SPK yet
        // OR were updated/created after the latest SPK generation
        $hasNewKegiatan = false;
        foreach ($monthPeriodes as $periode) {
            // Only check dikirim or perubahan status
            if (! in_array($periode->status, ['dikirim', 'perubahan'])) {
                continue;
            }

            // Get non-organik petugas from this periode
            $nonOrganikAlokasi = $periode->alokasiPetugas()
                ->whereHas('petugas', function ($q) {
                    $q->where('jenis_petugas', 'non-organik');
                })
                ->get();

            foreach ($nonOrganikAlokasi as $alokasi) {
                // Check if this alokasi has SPK
                $hasSpk = Spk::where('alokasi_petugas_id', $alokasi->id)
                    ->where('addendum_number', 0)
                    ->exists();

                // If no SPK or periode was updated after latest SPK
                if (! $hasSpk || $periode->updated_at > $latestSpkCreatedAt) {
                    $hasNewKegiatan = true;
                    break 2;
                }
            }
        }

        return $hasNewKegiatan;
    }

    /**
     * Check if there are new revisions after addendum was generated
     */
    private function hasNewRevisionAfterAddendum(int $tahun, int $bulan, $monthPeriodes): bool
    {
        // Get the latest Addendum (SPK with addendum_number > 0) creation timestamp in this month
        $latestAddendumCreatedAt = null;
        foreach ($monthPeriodes as $periode) {
            $latestAddendum = $periode->spk()
                ->where('addendum_number', '>', 0)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestAddendum && (! $latestAddendumCreatedAt || $latestAddendum->created_at > $latestAddendumCreatedAt)) {
                $latestAddendumCreatedAt = $latestAddendum->created_at;
            }
        }

        // If no Addendum exists, return false (should use normal "Addendum SPK" button)
        if (! $latestAddendumCreatedAt) {
            return false;
        }

        // Check if there are any revision periodes (status: perubahan or direvisi)
        // that were updated after the latest addendum generation
        $hasNewRevision = false;
        foreach ($monthPeriodes as $periode) {
            // Only check perubahan or direvisi status
            if (! in_array($periode->status, ['perubahan', 'direvisi'])) {
                continue;
            }

            // Get non-organik petugas from this revision periode
            $nonOrganikAlokasi = $periode->alokasiPetugas()
                ->whereHas('petugas', function ($q) {
                    $q->where('jenis_petugas', 'non-organik');
                })
                ->get();

            foreach ($nonOrganikAlokasi as $alokasi) {
                // Check if this revision alokasi has addendum
                $hasAddendum = Spk::where('alokasi_petugas_id', $alokasi->id)
                    ->where('addendum_number', '>', 0)
                    ->exists();

                // If no addendum or periode was updated after latest addendum
                if (! $hasAddendum || $periode->updated_at > $latestAddendumCreatedAt) {
                    $hasNewRevision = true;
                    break 2;
                }
            }
        }

        return $hasNewRevision;
    }
}
