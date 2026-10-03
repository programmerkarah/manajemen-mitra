<?php

namespace App\Http\Controllers\Analisis;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPulsa;
use Inertia\Inertia;
use Inertia\Response;

class PulsaAnalysisController extends Controller
{
    public function __invoke(): Response
    {
        $currentYear = (int) date('Y');

        $pulsaPerBulan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $bulanFormatted = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

            $data = PengajuanPulsa::query()
                ->where('bulan', $bulanFormatted)
                ->where('tahun', $currentYear)
                ->where('status', 'diterima')
                ->selectRaw('COUNT(*) as total_pengajuan')
                ->selectRaw('COALESCE(SUM(nominal), 0) as total_nominal')
                ->selectRaw('COALESCE(SUM(nominal_disetujui), 0) as total_disetujui')
                ->selectRaw('COUNT(DISTINCT petugas_id) as jumlah_petugas')
                ->first();

            $pulsaPerBulan[] = [
                'bulan' => $bulan,
                'total_pengajuan' => (int) $data->total_pengajuan,
                'total_nominal' => (float) $data->total_nominal,
                'total_disetujui' => (float) $data->total_disetujui,
                'jumlah_petugas' => (int) $data->jumlah_petugas,
            ];
        }

        $rataRataPulsa = PengajuanPulsa::query()
            ->where('tahun', $currentYear)
            ->where('status', 'diterima')
            ->selectRaw('COALESCE(AVG(nominal_disetujui), 0) as rata_rata')
            ->value('rata_rata');

        $alokasiPulsaPerBulan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $bulanFormatted = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

            $stats = PengajuanPulsa::query()
                ->where('bulan', $bulanFormatted)
                ->where('tahun', $currentYear)
                ->selectRaw('COUNT(*) as jumlah_kegiatan')
                ->selectRaw("SUM(CASE WHEN status = 'diajukan' THEN 1 ELSE 0 END) as diajukan")
                ->selectRaw("SUM(CASE WHEN status = 'diterima' THEN 1 ELSE 0 END) as disetujui")
                ->selectRaw("SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak")
                ->selectRaw("SUM(CASE WHEN status = 'dikirim' THEN 1 ELSE 0 END) as menunggu")
                ->selectRaw('COUNT(DISTINCT petugas_id) as jumlah_petugas')
                ->first();

            $alokasiPulsaPerBulan[] = [
                'bulan' => $bulan,
                'jumlah_petugas' => (int) $stats->jumlah_petugas,
                'jumlah_kegiatan' => (int) $stats->jumlah_kegiatan,
                'diajukan' => (int) $stats->diajukan,
                'disetujui' => (int) $stats->disetujui,
                'ditolak' => (int) $stats->ditolak,
                'menunggu' => (int) $stats->menunggu,
            ];
        }

        $distribusiJenisPulsa = PengajuanPulsa::query()
            ->where('tahun', $currentYear)
            ->where('status', 'diterima')
            ->groupBy('jenis_pulsa')
            ->selectRaw('jenis_pulsa, COUNT(*) as count, COALESCE(SUM(nominal_disetujui), 0) as total')
            ->get()
            ->map(fn ($item): array => [
                'jenis' => $item->jenis_pulsa === 'pelatihan'
                    ? 'Pelatihan'
                    : 'Pendataan',
                'count' => (int) $item->count,
                'total' => (float) $item->total,
            ])
            ->values()
            ->all();

        return Inertia::render('Analisis/Pulsa', [
            'pulsaPerBulan' => $pulsaPerBulan,
            'rataRataPulsa' => round((float) $rataRataPulsa),
            'alokasiPulsaPerBulan' => $alokasiPulsaPerBulan,
            'distribusiJenisPulsa' => $distribusiJenisPulsa,
            'currentYear' => $currentYear,
        ]);
    }
}
