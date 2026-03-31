<?php

namespace App\Http\Controllers;

use App\Models\PengajuanPulsa;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
