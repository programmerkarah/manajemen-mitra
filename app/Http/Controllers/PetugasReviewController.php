<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePetugasReviewRequest;
use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\Petugas;
use App\Models\ReviewPetugas;
use App\Services\ActiveYearService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Vinkla\Hashids\Facades\Hashids;

class PetugasReviewController extends Controller
{
    private const EFFECTIVE_STATUSES = ['dikirim', 'direvisi', 'perubahan'];

    public function index(Request $request): Response|RedirectResponse
    {
        $user = effectiveUser($request);
        $activeRole = $user?->getActiveRole()?->name;

        if (! $user || $activeRole === 'guest') {
            return redirect()->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses ke menu review petugas.');
        }

        $activeYear = ActiveYearService::get();
        $today = Carbon::today();
        $activeMonthIndex = $this->activeMonthIndex($activeYear);

        $reviewerPetugasIds = Petugas::query()
            ->where('nama', $user->name)
            ->pluck('id');

        $reviewableKegiatanIds = collect();
        if ($reviewerPetugasIds->isNotEmpty()) {
            $reviewableKegiatanIds = AlokasiPetugas::query()
                ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
                ->where('pa.tahun', $activeYear)
                ->whereIn('pa.status', self::EFFECTIVE_STATUSES)
                ->whereIn('alokasi_petugas.petugas_id', $reviewerPetugasIds)
                ->distinct()
                ->pluck('pa.kegiatan_id');
        }

        $rows = collect();

        if ($reviewableKegiatanIds->isNotEmpty()) {
            $assignments = AlokasiPetugas::query()
                ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
                ->join('petugas as p', 'p.id', '=', 'alokasi_petugas.petugas_id')
                ->join('kegiatan as k', 'k.id', '=', 'pa.kegiatan_id')
                ->where('pa.tahun', $activeYear)
                ->whereIn('pa.status', self::EFFECTIVE_STATUSES)
                ->whereIn('pa.kegiatan_id', $reviewableKegiatanIds)
                ->where('alokasi_petugas.peran', 'pcl_ppl')
                ->where('alokasi_petugas.status_kepegawaian', 'non_organik')
                ->where(function ($query) {
                    $query->where('alokasi_petugas.jumlah_satuan', '>', 0)
                        ->orWhere('alokasi_petugas.jumlah_satuan_listing', '>', 0)
                        ->orWhere('alokasi_petugas.total_honor', '>', 0)
                        ->orWhere('alokasi_petugas.total_honor_listing', '>', 0);
                })
                ->select([
                    'p.id as petugas_id',
                    'p.nama as petugas_nama',
                    'k.id as kegiatan_id',
                    'pa.id as periode_alokasi_id',
                    'pa.bulan as periode_bulan',
                    'pa.tahun as periode_tahun',
                    'pa.status as periode_status',
                    'k.kode_kegiatan',
                    'k.nama_kegiatan',
                    'k.tanggal_selesai',
                ])
                ->distinct()
                ->orderBy('p.nama')
                ->orderBy('k.nama_kegiatan')
                ->orderBy('pa.tahun')
                ->orderBy('pa.bulan')
                ->get();

            $kegiatanIdsAsPml = AlokasiPetugas::query()
                ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
                ->where('pa.tahun', $activeYear)
                ->whereIn('pa.status', self::EFFECTIVE_STATUSES)
                ->where('alokasi_petugas.peran', 'pml')
                ->whereIn('alokasi_petugas.petugas_id', $reviewerPetugasIds)
                ->distinct()
                ->pluck('pa.kegiatan_id')
                ->map(fn ($value) => (int) $value)
                ->all();

            $existingReviews = ReviewPetugas::query()
                ->where('reviewer_user_id', $user->id)
                ->whereIn('periode_alokasi_id', $assignments->pluck('periode_alokasi_id')->unique())
                ->whereIn('petugas_id', $assignments->pluck('petugas_id')->unique())
                ->get()
                ->keyBy(fn (ReviewPetugas $review) => $review->periode_alokasi_id.'-'.$review->petugas_id);

            $rows = $this->buildReviewRows(
                assignments: $assignments,
                existingReviews: $existingReviews,
                today: $today,
                activeMonthIndex: $activeMonthIndex,
                activeRole: (string) $activeRole,
                kegiatanIdsAsPml: $kegiatanIdsAsPml,
            );
        }

        $petugasOptions = $rows
            ->groupBy('petugas_id')
            ->map(function (Collection $petugasRows) {
                $first = $petugasRows->first();

                return [
                    'petugas_id' => $first['petugas_id'],
                    'petugas_hashed_id' => $first['petugas_hashed_id'],
                    'petugas_nama' => $first['petugas_nama'],
                    'total_review' => $petugasRows->count(),
                ];
            })
            ->sortBy('petugas_nama')
            ->values();

        return Inertia::render('Petugas/Review', [
            'rows' => $rows,
            'petugas_options' => $petugasOptions,
            'active_year' => $activeYear,
            'can_submit_review' => $activeRole === 'ketua_tim' || $rows->contains('user_can_submit', true),
            'active_role' => $activeRole,
        ]);
    }

    public function store(StorePetugasReviewRequest $request): RedirectResponse
    {
        $user = effectiveUser($request);
        $activeRole = $user?->getActiveRole()?->name;

        if (! $user || $activeRole === 'guest') {
            return redirect()->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses untuk mengisi review.');
        }

        $validated = $request->validated();
        $activeYear = ActiveYearService::get();

        $reviewerPetugasIds = Petugas::query()
            ->where('nama', $user->name)
            ->pluck('id');

        $reviewerInKegiatan = false;
        $reviewerAsPmlInKegiatan = false;

        if ($reviewerPetugasIds->isNotEmpty()) {
            $reviewerInKegiatan = AlokasiPetugas::query()
                ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
                ->where('pa.tahun', $activeYear)
                ->whereIn('pa.status', self::EFFECTIVE_STATUSES)
                ->where('pa.kegiatan_id', $validated['kegiatan_id'])
                ->whereIn('alokasi_petugas.petugas_id', $reviewerPetugasIds)
                ->exists();

            $reviewerAsPmlInKegiatan = AlokasiPetugas::query()
                ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
                ->where('pa.tahun', $activeYear)
                ->whereIn('pa.status', self::EFFECTIVE_STATUSES)
                ->where('pa.kegiatan_id', $validated['kegiatan_id'])
                ->where('alokasi_petugas.peran', 'pml')
                ->whereIn('alokasi_petugas.petugas_id', $reviewerPetugasIds)
                ->exists();
        }

        if (! $reviewerInKegiatan) {
            return back()->with('error', 'Anda hanya dapat mereview kegiatan yang menugaskan Anda pada tahun aktif.');
        }

        if (! ($activeRole === 'ketua_tim' || $reviewerAsPmlInKegiatan)) {
            return back()->with('error', 'Review hanya dapat diisi oleh PML atau ketua tim.');
        }

        $targetAssignment = AlokasiPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
            ->join('kegiatan as k', 'k.id', '=', 'pa.kegiatan_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', self::EFFECTIVE_STATUSES)
            ->where('pa.id', $validated['periode_alokasi_id'])
            ->where('pa.kegiatan_id', $validated['kegiatan_id'])
            ->where('alokasi_petugas.petugas_id', $validated['petugas_id'])
            ->where('alokasi_petugas.peran', 'pcl_ppl')
            ->where('alokasi_petugas.status_kepegawaian', 'non_organik')
            ->where(function ($query) {
                $query->where('alokasi_petugas.jumlah_satuan', '>', 0)
                    ->orWhere('alokasi_petugas.jumlah_satuan_listing', '>', 0)
                    ->orWhere('alokasi_petugas.total_honor', '>', 0)
                    ->orWhere('alokasi_petugas.total_honor_listing', '>', 0);
            })
            ->select([
                'pa.id as periode_alokasi_id',
                'pa.bulan as periode_bulan',
                'pa.tahun as periode_tahun',
                'pa.status as periode_status',
                'k.id as kegiatan_id',
                'k.tanggal_selesai',
            ])
            ->first();

        if (! $targetAssignment) {
            return back()->with('error', 'Petugas tidak memiliki penugasan aktif pada kegiatan tersebut.');
        }

        $canonicalPeriodeId = AlokasiPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', self::EFFECTIVE_STATUSES)
            ->where('pa.kegiatan_id', $validated['kegiatan_id'])
            ->where('pa.bulan', $targetAssignment->periode_bulan)
            ->where('alokasi_petugas.petugas_id', $validated['petugas_id'])
            ->where('alokasi_petugas.peran', 'pcl_ppl')
            ->where('alokasi_petugas.status_kepegawaian', 'non_organik')
            ->where(function ($query) {
                $query->where('alokasi_petugas.jumlah_satuan', '>', 0)
                    ->orWhere('alokasi_petugas.jumlah_satuan_listing', '>', 0)
                    ->orWhere('alokasi_petugas.total_honor', '>', 0)
                    ->orWhere('alokasi_petugas.total_honor_listing', '>', 0);
            })
            ->select([
                'pa.id as periode_alokasi_id',
                'pa.status as periode_status',
            ])
            ->get()
            ->sortByDesc(fn ($row) => ($this->statusRank((string) $row->periode_status) * 1000000)
                + (int) $row->periode_alokasi_id)
            ->first()?->periode_alokasi_id;

        if ((int) $validated['periode_alokasi_id'] !== (int) $canonicalPeriodeId) {
            return back()->with('error', 'Periode review tidak valid. Gunakan periode terbaru pada bulan tersebut.');
        }

        $currentMonthIndex = $this->activeMonthIndex($activeYear);
        $targetMonthIndex = $this->monthIndex(
            (int) $targetAssignment->periode_tahun,
            (int) $targetAssignment->periode_bulan,
        );

        $hasNextMonthAssignment = AlokasiPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', self::EFFECTIVE_STATUSES)
            ->where('pa.kegiatan_id', $validated['kegiatan_id'])
            ->where('alokasi_petugas.petugas_id', $validated['petugas_id'])
            ->where('alokasi_petugas.peran', 'pcl_ppl')
            ->where('alokasi_petugas.status_kepegawaian', 'non_organik')
            ->where(function ($query) {
                $query->where('alokasi_petugas.jumlah_satuan', '>', 0)
                    ->orWhere('alokasi_petugas.jumlah_satuan_listing', '>', 0)
                    ->orWhere('alokasi_petugas.total_honor', '>', 0)
                    ->orWhere('alokasi_petugas.total_honor_listing', '>', 0);
            })
            ->whereRaw('((pa.tahun * 12) + pa.bulan) = ?', [$targetMonthIndex + 1])
            ->exists();

        if ($hasNextMonthAssignment) {
            return back()->with('error', 'Review hanya dapat dibuat pada periode terakhir dari episode alokasi.');
        }

        $canReviewByStop = $targetMonthIndex < $currentMonthIndex;
        $canReviewByKegiatanEnd = $targetAssignment->tanggal_selesai
            ? Carbon::parse($targetAssignment->tanggal_selesai)->lte(Carbon::today())
            : false;

        $canReviewNow = $canReviewByStop || $canReviewByKegiatanEnd;

        if (! $canReviewNow) {
            return back()->with('error', 'Review hanya bisa diisi setelah periode/kegiatan dinyatakan selesai.');
        }

        $alreadyReviewed = ReviewPetugas::query()
            ->where('reviewer_user_id', $user->id)
            ->where('periode_alokasi_id', $validated['periode_alokasi_id'])
            ->where('petugas_id', $validated['petugas_id'])
            ->exists();

        if ($alreadyReviewed) {
            return back()->with('error', 'Review untuk periode ini sudah final dan tidak dapat diubah.');
        }

        ReviewPetugas::create([
            'kegiatan_id' => $validated['kegiatan_id'],
            'petugas_id' => $validated['petugas_id'],
            'periode_alokasi_id' => $validated['periode_alokasi_id'],
            'reviewer_user_id' => $user->id,
            'rating' => $validated['rating'],
            'ulasan' => $validated['ulasan'] ?? null,
            'reviewed_at' => now(),
        ]);

        ActivityLog::log(
            'Submit Review Petugas',
            'petugas',
            'User '.$user->name.' mengirim review petugas pada kegiatan ID '.$validated['kegiatan_id'].'.',
            'success',
            [
                'kegiatan_id' => $validated['kegiatan_id'],
                'petugas_id' => $validated['petugas_id'],
                'periode_alokasi_id' => $validated['periode_alokasi_id'],
                'rating' => $validated['rating'],
            ]
        );

        return back()->with('success', 'Review petugas berhasil disimpan.');
    }

    private function buildReviewRows(
        Collection $assignments,
        Collection $existingReviews,
        Carbon $today,
        int $activeMonthIndex,
        string $activeRole,
        array $kegiatanIdsAsPml,
    ): Collection {
        $rows = collect();

        $grouped = $assignments
            ->groupBy(fn ($row) => (int) $row->kegiatan_id.'-'.(int) $row->petugas_id);

        foreach ($grouped as $groupRows) {
            $sorted = $groupRows->sortBy(fn ($row) => [
                (int) $row->periode_tahun,
                (int) $row->periode_bulan,
                (int) $row->periode_alokasi_id,
            ])->values();

            $sorted = $this->collapseToCanonicalMonthlyAssignments($sorted);

            $episodes = collect();
            $currentEpisode = collect();
            $previousMonthIndex = null;

            foreach ($sorted as $row) {
                $monthIndex = $this->monthIndex((int) $row->periode_tahun, (int) $row->periode_bulan);

                if ($previousMonthIndex !== null && $monthIndex !== $previousMonthIndex + 1) {
                    $episodes->push($currentEpisode);
                    $currentEpisode = collect();
                }

                $currentEpisode->push($row);
                $previousMonthIndex = $monthIndex;
            }

            if ($currentEpisode->isNotEmpty()) {
                $episodes->push($currentEpisode);
            }

            foreach ($episodes as $episodeRows) {
                $lastRow = $episodeRows->last();
                $existingReview = $existingReviews->get($lastRow->periode_alokasi_id.'-'.$lastRow->petugas_id);

                $episodeEndMonthIndex = $this->monthIndex(
                    (int) $lastRow->periode_tahun,
                    (int) $lastRow->periode_bulan,
                );
                $canReviewByStop = $episodeEndMonthIndex < $activeMonthIndex;
                $canReviewByKegiatanEnd = $lastRow->tanggal_selesai
                    ? Carbon::parse($lastRow->tanggal_selesai)->lte($today)
                    : false;
                $canReviewNow = $canReviewByStop || $canReviewByKegiatanEnd;

                $userCanSubmit = $activeRole === 'ketua_tim'
                    || in_array((int) $lastRow->kegiatan_id, $kegiatanIdsAsPml, true);

                if (! $canReviewNow && ! $existingReview) {
                    continue;
                }

                $rows->push([
                    'petugas_id' => (int) $lastRow->petugas_id,
                    'petugas_hashed_id' => Hashids::encode((int) $lastRow->petugas_id),
                    'petugas_nama' => $lastRow->petugas_nama,
                    'kegiatan_id' => (int) $lastRow->kegiatan_id,
                    'kegiatan_hashed_id' => Hashids::encode((int) $lastRow->kegiatan_id),
                    'kegiatan_kode' => $lastRow->kode_kegiatan,
                    'kegiatan_nama' => $lastRow->nama_kegiatan,
                    'periode_alokasi_id' => (int) $lastRow->periode_alokasi_id,
                    'periode_tahun' => (int) $lastRow->periode_tahun,
                    'periode_bulan' => (int) $lastRow->periode_bulan,
                    'tanggal_selesai' => $lastRow->tanggal_selesai,
                    'can_review_now' => $canReviewNow,
                    'user_can_submit' => $userCanSubmit,
                    'existing_review' => $existingReview ? [
                        'rating' => $existingReview->rating,
                        'ulasan' => $existingReview->ulasan,
                        'reviewed_at' => $existingReview->reviewed_at?->format('Y-m-d H:i:s'),
                    ] : null,
                ]);
            }
        }

        return $rows
            ->sortBy(['petugas_nama', 'kegiatan_nama', 'periode_tahun', 'periode_bulan'])
            ->values();
    }

    private function monthIndex(int $year, int $month): int
    {
        return ($year * 12) + $month;
    }

    private function activeMonthIndex(int $activeYear): int
    {
        $now = Carbon::now();

        if ($activeYear < (int) $now->year) {
            return $this->monthIndex($activeYear, 12);
        }

        if ($activeYear > (int) $now->year) {
            return $this->monthIndex($activeYear, 0);
        }

        return $this->monthIndex($activeYear, (int) $now->month);
    }

    private function collapseToCanonicalMonthlyAssignments(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn ($row) => $this->monthIndex((int) $row->periode_tahun, (int) $row->periode_bulan))
            ->map(function (Collection $monthRows) {
                return $monthRows
                    ->sortByDesc(fn ($row) => ($this->statusRank((string) $row->periode_status) * 1000000)
                        + (int) $row->periode_alokasi_id)
                    ->first();
            })
            ->sortBy(fn ($row) => [
                (int) $row->periode_tahun,
                (int) $row->periode_bulan,
            ])
            ->values();
    }

    private function statusRank(string $status): int
    {
        return match ($status) {
            'perubahan' => 3,
            'direvisi' => 2,
            'dikirim' => 1,
            default => 0,
        };
    }
}
