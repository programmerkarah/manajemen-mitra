<?php

namespace App\Http\Controllers\Analisis;

use App\Http\Controllers\Analisis\Concerns\BuildsAnalisisQueries;
use App\Http\Controllers\Controller;
use App\Models\Kegiatan;
use App\Models\SkKpa;
use App\Models\Spk;
use App\Services\SensusEkonomiReplacementReadService;
use App\Traits\EffectivePeriodeScope;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DokumenAnalysisController extends Controller
{
    use BuildsAnalisisQueries;
    use EffectivePeriodeScope;

    public function __invoke(): Response
    {
        $currentYear = (int) date('Y');

        // ── SK per bulan ──────────────────────────────────────────────────────
        $skPerBulan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $data = SkKpa::query()
                ->where('bulan', $bulan)
                ->where('tahun', $currentYear)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft")
                ->selectRaw("SUM(CASE WHEN status = 'diterbitkan' AND (is_signed = 0 OR is_signed IS NULL) THEN 1 ELSE 0 END) as diterbitkan")
                ->selectRaw('SUM(CASE WHEN is_signed = 1 THEN 1 ELSE 0 END) as ditandatangani')
                ->first();

            $skPerBulan[] = [
                'bulan' => $bulan,
                'total' => (int) $data->total,
                'draft' => (int) $data->draft,
                'diterbitkan' => (int) $data->diterbitkan,
                'ditandatangani' => (int) $data->ditandatangani,
            ];
        }

        // ── PK per bulan ──────────────────────────────────────────────────────
        // Gunakan periode alokasi sebagai sumbu bulan, sama seperti menu
        // Perjanjian Kerja. tanggal_spk dapat berbeda dari bulan alokasinya dan
        // sebelumnya membuat angka analisis (mis. Agustus) tidak sama dengan
        // detail/menu PK.
        $replacementAssignments = app(SensusEkonomiReplacementReadService::class)
            ->assignments($currentYear);

        $spkPerBulan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $bulanCandidates = [
                (string) $bulan,
                str_pad((string) $bulan, 2, '0', STR_PAD_LEFT),
            ];

            $mainSpks = Spk::query()
                ->with('alokasiPetugas.periodeAlokasi.kegiatan:id,nama_kegiatan,kode_kegiatan,jenis_kegiatan')
                ->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($currentYear, $bulanCandidates): void {
                    $query->where('tahun', $currentYear)
                        ->whereIn('bulan', $bulanCandidates)
                        ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan']);
                })
                ->get();

            $regularPublished = 0;
            $sensusMainPublished = 0;
            $mainDraft = 0;

            foreach ($mainSpks as $spk) {
                if ($spk->status === 'draft') {
                    $mainDraft++;

                    continue;
                }

                if ($spk->status !== 'diterbitkan') {
                    continue;
                }

                $kegiatan = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan;
                $activityText = strtolower(trim(
                    (string) ($kegiatan?->nama_kegiatan ?? '').' '.
                    (string) ($kegiatan?->kode_kegiatan ?? '')
                ));
                $isSensusEkonomi = str_contains($activityText, 'sensus ekonomi')
                    || str_contains($activityText, 'se2026')
                    || str_contains($activityText, 'se 2026');

                if ($isSensusEkonomi) {
                    $sensusMainPublished++;
                } else {
                    $regularPublished++;
                }
            }

            // PK petugas pengganti masuk ke bulan berdasarkan tanggal PK
            // yang diinput pada form (tanggal_kontrak), bukan created_at,
            // bulan alokasi SE2026, atau bulan pelaksanaan/honor.
            $replacementForMonth = $replacementAssignments
                ->filter(fn (array $assignment): bool =>
                    (int) ($assignment['pk_year'] ?? 0) === $currentYear
                    && (int) ($assignment['pk_month'] ?? 0) === $bulan
                );

            $sensusReplacementPublished = $replacementForMonth
                ->filter(fn (array $assignment): bool =>
                    (bool) ($assignment['pk_available'] ?? false)
                )
                ->count();
            $replacementDraft = $replacementForMonth
                ->reject(fn (array $assignment): bool =>
                    (bool) ($assignment['pk_available'] ?? false)
                )
                ->count();

            $published = $regularPublished
                + $sensusMainPublished
                + $sensusReplacementPublished;
            $draft = $mainDraft + $replacementDraft;

            $spkPerBulan[] = [
                'bulan' => $bulan,
                'total' => $published + $draft,
                'draft' => $draft,
                'diterbitkan' => $published,
                'reguler_diterbitkan' => $regularPublished,
                'sensus_utama_diterbitkan' => $sensusMainPublished,
                'sensus_pengganti_diterbitkan' => $sensusReplacementPublished,
            ];
        }

        // ── Summary KPI ───────────────────────────────────────────────────────
        $skTotal = SkKpa::query()->where('tahun', $currentYear)->count();
        $skDiterbitkan = SkKpa::query()->where('tahun', $currentYear)->where('status', 'diterbitkan')->count();
        $skDraft = SkKpa::query()->where('tahun', $currentYear)->where('status', 'draft')->count();
        $spkTotal = (int) collect($spkPerBulan)->sum('total');
        $spkDiterbitkan = (int) collect($spkPerBulan)->sum('diterbitkan');
        $spkDraft = (int) collect($spkPerBulan)->sum('draft');

        // ── SK progress per kegiatan ──────────────────────────────────────────
        $kegiatanAktif = Kegiatan::query()
            ->where('tahun_anggaran', $currentYear)
            ->whereNotIn('status', ['dibatalkan'])
            // SK baru menjadi kewajiban setelah minimal satu periode alokasi
            // benar-benar dikirim. Draft yang belum pernah dikirim tidak boleh
            // muncul sebagai "Belum Ada SK" di Analisis Dokumen.
            ->whereHas('periodeAlokasi', function ($query) use ($currentYear): void {
                $query->where('tahun', $currentYear)
                    ->whereNotNull('submitted_at')
                    ->whereIn('status', [
                        'dikirim',
                        'perubahan',
                        'direvisi',
                        'disetujui',
                    ]);
            })
            ->select('id', 'nama_kegiatan', 'kode_kegiatan', 'jenis_kegiatan')
            ->orderBy('nama_kegiatan')
            ->get();

        $skStatsByKegiatan = DB::table('sk_kpa')
            ->where('tahun', $currentYear)
            ->whereNull('deleted_at')
            ->selectRaw("kegiatan_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = 'diterbitkan' THEN 1 ELSE 0 END) as diterbitkan,
                SUM(CASE WHEN is_signed = 1 THEN 1 ELSE 0 END) as ditandatangani")
            ->groupBy('kegiatan_id')
            ->get()
            ->keyBy('kegiatan_id');

        $kelengkapanSKPerKegiatan = $kegiatanAktif->map(function ($kegiatan) use ($skStatsByKegiatan) {
            $sk = $skStatsByKegiatan->get($kegiatan->id);
            $total = $sk ? (int) $sk->total : 0;
            $draft = $sk ? (int) $sk->draft : 0;
            $diterbitkan = $sk ? (int) $sk->diterbitkan : 0;
            $ditandatangani = $sk ? (int) $sk->ditandatangani : 0;

            $statusDokumen = 'belum';
            if ($total > 0) {
                $statusDokumen = ($draft === 0 && $total > 0) ? 'lengkap' : 'sebagian';
            }

            return [
                'kegiatan_id' => $kegiatan->id,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                'total_sk' => $total,
                'sk_draft' => $draft,
                'sk_diterbitkan' => $diterbitkan,
                'sk_ditandatangani' => $ditandatangani,
                'status_dokumen' => $statusDokumen,
            ];
        })->sortBy(function ($item) {
            return match ($item['status_dokumen']) {
                'belum' => 0,
                'sebagian' => 1,
                'lengkap' => 2,
            };
        })->values()->all();

        // ── SK draft yang sudah lama (> 14 hari) ─────────────────────────────
        $skDraftLama = SkKpa::query()
            ->where('tahun', $currentYear)
            ->where('status', 'draft')
            ->where('created_at', '<', now()->subDays(14))
            ->with('kegiatan:id,nama_kegiatan,kode_kegiatan')
            ->orderBy('created_at')
            ->limit(20)
            ->get()
            ->map(function ($sk) {
                return [
                    'id' => $sk->id,
                    'kegiatan_nama' => $sk->kegiatan?->nama_kegiatan ?? '-',
                    'kegiatan_kode' => $sk->kegiatan?->kode_kegiatan ?? '-',
                    'bulan' => (int) $sk->bulan,
                    'tahun' => (int) $sk->tahun,
                    'umur_hari' => (int) now()->diffInDays($sk->created_at),
                ];
            })
            ->all();

        return Inertia::render('Analisis/Dokumen', [
            'skPerBulan' => $skPerBulan,
            'spkPerBulan' => $spkPerBulan,
            'skTotal' => $skTotal,
            'skDiterbitkan' => $skDiterbitkan,
            'skDraft' => $skDraft,
            'spkTotal' => $spkTotal,
            'spkDiterbitkan' => $spkDiterbitkan,
            'spkDraft' => $spkDraft,
            'kelengkapanSKPerKegiatan' => $kelengkapanSKPerKegiatan,
            'skDraftLama' => $skDraftLama,
            'currentYear' => $currentYear,
        ]);
    }
}
