<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use App\Models\SkKpa;
use App\Services\ActiveYearService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SkKpaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $activeYear = ActiveYearService::get();

        // Get kegiatan that have validated periods (dikirim status)
        $query = Kegiatan::query()
            ->with([
                'ketuaTim:id,name',
                'skKpa' => function ($q) {
                    $q->orderBy('created_at', 'desc');
                },
            ])
            ->whereHas('periodeAlokasi', function ($q) use ($activeYear) {
                $q->where('tahun', $activeYear)
                    ->whereIn('status', ['dikirim', 'disetujui']);
            })
            ->where('tahun_anggaran', $activeYear);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_kegiatan', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        // Filter by jenis kegiatan
        if ($request->filled('jenis_kegiatan')) {
            $query->where('jenis_kegiatan', $request->jenis_kegiatan);
        }

        $kegiatan = $query->latest()->paginate(15)->withQueryString();

        // Transform data to include SK status
        // Note: status_sk and revision_number are calculated from skKpa relationship
        // They are not stored in kegiatan table
        $kegiatan->getCollection()->transform(function ($keg) {
            $skCount = $keg->skKpa->count();
            $latestSk = $keg->skKpa->first();

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
                'latest_sk' => $latestSk ? [
                    'id' => $latestSk->id,
                    'hashed_id' => $latestSk->hashed_id,
                    'nomor_sk' => $latestSk->nomor_sk,
                    'tanggal_sk' => $latestSk->tanggal_sk,
                    'status' => $latestSk->status,
                    'file_path' => $latestSk->file_path,
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
                    'nip_kpa' => $sk->nip_kpa,
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
    public function create()
    {
        //
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
    public function show(SkKpa $skKpa)
    {
        //
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
}
