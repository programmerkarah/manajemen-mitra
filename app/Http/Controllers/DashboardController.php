<?php

namespace App\Http\Controllers;

use App\Models\AlokasiMitra;
use App\Models\Bast;
use App\Models\Kegiatan;
use App\Models\Mitra;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $stats = [
            'total_mitra' => Mitra::where('status', 'aktif')->count(),
            'total_kegiatan' => Kegiatan::whereIn('status', ['aktif', 'divalidasi'])->count(),
            'alokasi_pending' => AlokasiMitra::where('status', 'diajukan')->count(),
            'bast_pending' => Bast::where('status', 'draft')->count(),
        ];

        // Get recent activities based on user role
        $recentAlokasi = AlokasiMitra::query()
            ->with(['kegiatan', 'mitra', 'rateHonor.satuan'])
            ->when($user->isOperator(), function ($query) use ($user) {
                $query->where('submitted_by', $user->id);
            })
            ->when($user->isApprover(), function ($query) {
                $query->where('status', 'diajukan');
            })
            ->latest()
            ->limit(5)
            ->get();

        $recentKegiatan = Kegiatan::query()
            ->with('ketuaTim')
            ->when($user->isKetuaTim(), function ($query) use ($user) {
                $query->where('ketua_tim_user_id', $user->id);
            })
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentAlokasi' => $recentAlokasi,
            'recentKegiatan' => $recentKegiatan,
            'userRole' => $user->role,
        ]);
    }
}
