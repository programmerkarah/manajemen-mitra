<?php

namespace App\Http\Controllers;

use App\Models\PengajuanPulsa;
use App\Services\ActiveYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class MonitoringPulsaController extends Controller
{
    /**
     * Normalize month input to 2-digit numeric format (01-12).
     */
    private function normalizeBulanValue(mixed $bulan): string
    {
        if ($bulan === null || $bulan === '') {
            return now()->format('m');
        }

        $raw = trim((string) $bulan);

        if (is_numeric($raw)) {
            $numericMonth = (int) $raw;
            if ($numericMonth >= 1 && $numericMonth <= 12) {
                return str_pad((string) $numericMonth, 2, '0', STR_PAD_LEFT);
            }
        }

        $monthNames = [
            'january' => '01',
            'february' => '02',
            'march' => '03',
            'april' => '04',
            'may' => '05',
            'june' => '06',
            'july' => '07',
            'august' => '08',
            'september' => '09',
            'october' => '10',
            'november' => '11',
            'december' => '12',
            'januari' => '01',
            'februari' => '02',
            'maret' => '03',
            'april' => '04',
            'mei' => '05',
            'juni' => '06',
            'juli' => '07',
            'agustus' => '08',
            'september' => '09',
            'oktober' => '10',
            'desember' => '12',
        ];

        $lookup = strtolower($raw);
        if (isset($monthNames[$lookup])) {
            return $monthNames[$lookup];
        }

        return now()->format('m');
    }

    /**
     * Display the pulsa monitoring page.
     * Shows all non-draft submissions for a given bulan/tahun,
     * with separate views for submitted vs approved.
     */
    public function index(Request $request): Response
    {
        $bulan = $this->normalizeBulanValue($request->input('bulan', now()->format('m')));
        $tahun = ActiveYearService::get();

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

    public function exportPdf(Request $request): HttpResponse
    {
        $bulan = $this->normalizeBulanValue($request->input('bulan', now()->format('m')));
        $tahun = ActiveYearService::get();

        $approvedItems = PengajuanPulsa::query()
            ->with('petugas:id,nama')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('status', 'diterima')
            ->orderBy('petugas_id')
            ->orderBy('id')
            ->get();

        $rows = $approvedItems
            ->groupBy('petugas_id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'nama_petugas' => $first?->petugas?->nama ?? '-',
                    'jumlah_pulsa' => (float) $items->sum(function (PengajuanPulsa $item) {
                        return $item->nominal_disetujui ?? $item->nominal;
                    }),
                ];
            })
            ->sortBy('nama_petugas')
            ->values();

        $judul = 'Rekapitulasi Usulan Pengisian Pulsa/Paket Data Pelatihan/Pendataan Survei/Sensus di Lingkungan Badan Pusat Statistik Kota Sawahlunto hingga Tanggal '.now()->locale('id')->translatedFormat('d F Y');
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $tanggalCetak = now()->timezone($timezone)->locale('id')->translatedFormat('d F Y H:i');

        $pdf = Pdf::loadView('monitoring-pulsa-rekap-pdf', [
            'judul' => $judul,
            'tanggal_cetak' => $tanggalCetak,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'rows' => $rows,
            'disclaimer' => 'Dokumen ini di-generate secara otomatis oleh SIMANTIK. Data yang ditampilkan sudah diverifikasi oleh PPK dan bisa digunakan sebagai dasar pengajuan pengadaan pulsa/paket data.',
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            'rekap_pengadaan_pulsa_%s_%s_%s_%s.pdf',
            $tahun,
            $bulan,
            now()->format('Ymd_His'),
            Str::lower(Str::random(6)),
        );

        return $pdf->download($filename);
    }
}
