<?php

namespace App\Http\Controllers;

use App\Models\PengajuanPulsa;
use App\Services\ActiveYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
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
            ->with([
                'petugas:id,nama,telepon',
                'kegiatan:id,nama_kegiatan',
            ])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('status', 'diterima')
            ->orderBy('petugas_id')
            ->orderBy('id')
            ->get();

        $rows = $approvedItems
            ->groupBy('petugas_id')
            ->map(function ($items) {
                /** @var PengajuanPulsa|null $first */
                $first = $items->first();
                $nomorHp = trim((string) ($first?->petugas?->telepon ?? ''));

                $rincian = $items
                    ->groupBy('kegiatan_id')
                    ->map(function ($kegiatanItems) {
                        /** @var PengajuanPulsa|null $firstKegiatan */
                        $firstKegiatan = $kegiatanItems->first();

                        return [
                            'nama_kegiatan' => $firstKegiatan?->kegiatan?->nama_kegiatan ?? '-',
                            'nominal' => (float) $kegiatanItems->sum(function (PengajuanPulsa $item) {
                                return $item->nominal_disetujui ?? $item->nominal;
                            }),
                        ];
                    })
                    ->sortBy('nama_kegiatan')
                    ->values()
                    ->all();

                return [
                    'nama_petugas' => $first?->petugas?->nama ?? '-',
                    'nomor_hp' => $nomorHp !== '' ? $nomorHp : '-',
                    'provider' => $this->detectProvider($nomorHp),
                    'jumlah_pulsa' => (float) collect($rincian)->sum('nominal'),
                    'rincian' => $rincian,
                ];
            })
            ->sortBy('nama_petugas')
            ->values();

        $judul = 'Rekapitulasi Usulan Pengisian Pulsa/Paket Data Pelatihan/Pendataan Survei/Sensus di Lingkungan Badan Pusat Statistik Kota Sawahlunto hingga Tanggal '.now()->locale('id')->translatedFormat('d F Y');
        $periodeLabel = Carbon::create((int) $tahun, (int) $bulan, 1)
            ->locale('id')
            ->translatedFormat('F Y');
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $tanggalCetak = now()->timezone($timezone)->locale('id')->translatedFormat('d F Y H:i');

        $pdf = Pdf::loadView('monitoring-pulsa-rekap-pdf', [
            'judul' => $judul,
            'tanggal_cetak' => $tanggalCetak,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'periode_label' => $periodeLabel,
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

    /**
     * Determine the Indonesian mobile provider from the first four digits.
     * Numbers using +62/62 or omitting the leading zero are normalized first.
     */
    private function detectProvider(?string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phoneNumber) ?? '';

        if (str_starts_with($digits, '62')) {
            $digits = '0'.substr($digits, 2);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '0'.$digits;
        }

        $prefix = substr($digits, 0, 4);

        $providers = [
            'Telkomsel' => [
                '0811', '0812', '0813',
                '0821', '0822', '0823',
                '0851', '0852', '0853',
            ],
            'Indosat' => [
                '0814', '0815', '0816',
                '0855', '0856', '0857', '0858',
            ],
            'XL' => [
                '0817', '0818', '0819',
                '0859', '0877', '0878',
            ],
            'AXIS' => [
                '0831', '0832', '0833', '0838',
            ],
            'Tri' => [
                '0895', '0896', '0897', '0898', '0899',
            ],
            'Smartfren' => [
                '0880', '0881', '0882', '0883', '0884',
                '0885', '0886', '0887', '0888', '0889',
            ],
            'Ceria' => ['0828'],
        ];

        foreach ($providers as $provider => $prefixes) {
            if (in_array($prefix, $prefixes, true)) {
                return $provider;
            }
        }

        return 'Tidak diketahui';
    }
}
