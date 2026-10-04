<?php

namespace App\Services\Analysis;

use App\Models\Kegiatan;
use App\Models\SkKpa;
use App\Models\Spk;
use Illuminate\Support\Facades\DB;

class DocumentAnalysisService
{
    /**
     * Build the canonical dataset used by both the Inertia page and PDF export.
     *
     * @return array<string, mixed>
     */
    public function build(int $year): array
    {
        $skPerBulan = [];
        $spkPerBulan = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $sk = SkKpa::query()
                ->where('bulan', $bulan)
                ->where('tahun', $year)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft")
                ->selectRaw("SUM(CASE WHEN status = 'diterbitkan' AND (is_signed = 0 OR is_signed IS NULL) THEN 1 ELSE 0 END) as diterbitkan")
                ->selectRaw('SUM(CASE WHEN is_signed = 1 THEN 1 ELSE 0 END) as ditandatangani')
                ->first();

            $skPerBulan[] = [
                'bulan' => $bulan,
                'total' => (int) ($sk->total ?? 0),
                'draft' => (int) ($sk->draft ?? 0),
                'diterbitkan' => (int) ($sk->diterbitkan ?? 0),
                'ditandatangani' => (int) ($sk->ditandatangani ?? 0),
            ];

            $bulanCandidates = [
                (string) $bulan,
                str_pad((string) $bulan, 2, '0', STR_PAD_LEFT),
            ];

            $mainSpks = Spk::query()
                ->with('alokasiPetugas.periodeAlokasi.kegiatan:id,nama_kegiatan,kode_kegiatan,jenis_kegiatan')
                ->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($year, $bulanCandidates): void {
                    $query->where('tahun', $year)
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

            $published = $regularPublished + $sensusMainPublished;

            $spkPerBulan[] = [
                'bulan' => $bulan,
                'total' => $published + $mainDraft,
                'draft' => $mainDraft,
                'diterbitkan' => $published,
                'reguler_diterbitkan' => $regularPublished,
                'sensus_utama_diterbitkan' => $sensusMainPublished,
            ];
        }

        $skTotal = SkKpa::query()->where('tahun', $year)->count();
        $skDiterbitkan = SkKpa::query()
            ->where('tahun', $year)
            ->where('status', 'diterbitkan')
            ->count();
        $skDraft = SkKpa::query()
            ->where('tahun', $year)
            ->where('status', 'draft')
            ->count();
        $spkTotal = (int) collect($spkPerBulan)->sum('total');
        $spkDiterbitkan = (int) collect($spkPerBulan)->sum('diterbitkan');
        $spkDraft = (int) collect($spkPerBulan)->sum('draft');

        $kegiatanAktif = Kegiatan::query()
            ->where('tahun_anggaran', $year)
            ->whereNotIn('status', ['dibatalkan'])
            ->whereHas('periodeAlokasi', function ($query) use ($year): void {
                $query->where('tahun', $year)
                    ->whereNotNull('submitted_at')
                    ->whereIn('status', ['dikirim', 'perubahan', 'direvisi', 'disetujui']);
            })
            ->select('id', 'nama_kegiatan', 'kode_kegiatan', 'jenis_kegiatan')
            ->orderBy('nama_kegiatan')
            ->get();

        $skStatsByKegiatan = DB::table('sk_kpa')
            ->where('tahun', $year)
            ->whereNull('deleted_at')
            ->selectRaw("kegiatan_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = 'diterbitkan' THEN 1 ELSE 0 END) as diterbitkan,
                SUM(CASE WHEN is_signed = 1 THEN 1 ELSE 0 END) as ditandatangani")
            ->groupBy('kegiatan_id')
            ->get()
            ->keyBy('kegiatan_id');

        $kelengkapanSKPerKegiatan = $kegiatanAktif
            ->map(function ($kegiatan) use ($skStatsByKegiatan) {
                $sk = $skStatsByKegiatan->get($kegiatan->id);
                $total = $sk ? (int) $sk->total : 0;
                $draft = $sk ? (int) $sk->draft : 0;
                $diterbitkan = $sk ? (int) $sk->diterbitkan : 0;
                $ditandatangani = $sk ? (int) $sk->ditandatangani : 0;

                $statusDokumen = 'belum';
                if ($total > 0) {
                    $statusDokumen = $draft === 0 ? 'lengkap' : 'sebagian';
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
            })
            ->sortBy(fn (array $item) => match ($item['status_dokumen']) {
                'belum' => 0,
                'sebagian' => 1,
                default => 2,
            })
            ->values()
            ->all();

        $skDraftLama = SkKpa::query()
            ->where('tahun', $year)
            ->where('status', 'draft')
            ->where('created_at', '<', now()->subDays(14))
            ->with('kegiatan:id,nama_kegiatan,kode_kegiatan')
            ->orderBy('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($sk) => [
                'id' => $sk->id,
                'kegiatan_nama' => $sk->kegiatan?->nama_kegiatan ?? '-',
                'kegiatan_kode' => $sk->kegiatan?->kode_kegiatan ?? '-',
                'bulan' => (int) $sk->bulan,
                'tahun' => (int) $sk->tahun,
                'umur_hari' => (int) now()->diffInDays($sk->created_at),
            ])
            ->all();

        $availableYears = Kegiatan::query()
            ->whereNotNull('tahun_anggaran')
            ->distinct()
            ->orderByDesc('tahun_anggaran')
            ->pluck('tahun_anggaran')
            ->map(fn ($item) => (int) $item)
            ->values()
            ->all();

        if (! in_array($year, $availableYears, true)) {
            array_unshift($availableYears, $year);
        }

        return [
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
            'currentYear' => $year,
            'availableYears' => $availableYears,
        ];
    }
}
