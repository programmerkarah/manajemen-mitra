<?php

namespace App\Http\Controllers\Concerns;

use App\Exports\AlokasiPetugasTemplateExport;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\StoreAlokasiPetugasRequest;
use App\Http\Requests\UpdateAlokasiPetugasRequest;
use App\Http\Requests\UpdateNonResponseRequest;
use App\Imports\AlokasiPetugasImport;
use App\Imports\AlokasiPetugasPreviewImport;
use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\AlokasiPetugasFrameSampel;
use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\ReviewPetugas;
use App\Models\Sbml;
use App\Models\Spk;
use App\Services\ActiveYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Vinkla\Hashids\Facades\Hashids;

trait AlokasiPetugasApprovalActions
{
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
}
