<?php

namespace App\Http\Controllers;

use App\Models\PengajuanPulsa;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringPulsaController extends Controller
{
    /**
     * Display the pulsa monitoring page.
     * Shows all non-draft submissions for a given bulan/tahun,
     * with separate views for submitted vs approved.
     */
    public function index(Request $request): Response
    {
        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = \App\Services\ActiveYearService::get();

        $query = PengajuanPulsa::query()
            ->with([
                'petugas:id,nama',
                'kegiatan:id,kode_kegiatan,nama_kegiatan',
                'submittedBy:id,name',
                'reviewedBy:id,name',
            ])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereNotIn('status', ['draft']);

        $pengajuanList = $query
            ->orderBy('petugas_id')
            ->orderBy('kegiatan_id')
            ->orderBy('jenis_pulsa')
            ->get();

        return Inertia::render('MonitoringPulsa/Index', [
            'pengajuanList' => [
                'encrypted' => encryptData($pengajuanList),
            ],
            'filters' => [
                'bulan' => $bulan,
                'tahun' => (string) $tahun,
            ],
        ]);
    }
}
