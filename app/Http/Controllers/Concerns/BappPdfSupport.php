<?php

namespace App\Http\Controllers\Concerns;

use App\Exports\BappSeRealisasiTemplateExport;
use App\Imports\BappSeRealisasiImport;
use App\Models\AlokasiPetugas;
use App\Models\BappSeTermin;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use App\Models\Penandatangan;
use App\Models\Petugas;
use App\Models\SensusEkonomiPetugasReplacement;
use App\Models\SensusEkonomiPkppContract;
use App\Models\Spk;
use App\Services\ActiveYearService;
use App\Services\SensusEkonomiBappNumberService;
use App\Services\SensusEkonomiPkNumberService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use setasign\Fpdi\Tcpdf\Fpdi;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Vinkla\Hashids\Facades\Hashids;

trait BappPdfSupport
{
    private function buildPdfViewData(BappSeTermin $bapp, Spk $spk, string $peran, ?Petugas $petugas, array $config): array
    {
        $jenisPihakKedua = $this->getJenisPihakKedua($peran);

        return [
            'nomor_bapp' => $bapp->nomor_bapp,
            'tanggal_bapp' => $bapp->tanggal_bapp,
            'termin_roman' => $config['roman'],
            'termin_number' => $bapp->termin,
            'persentase' => $config['persentase'],
            'jenis_pihak_kedua' => $jenisPihakKedua,
            'is_usaha_besar' => false,
            'nama_petugas' => $petugas?->nama,
            'nik_petugas' => $petugas?->nik ?? '',
            'nama_ketua_tim' => $bapp->nama_ketua_tim,
            'nip_ketua_tim' => $bapp->nip_ketua_tim ?: $this->getNipKetuaTim($this->getSensusEkonomiKegiatan()),
            'nama_ppk' => $bapp->nama_ppk ? $this->stripGelar($bapp->nama_ppk) : null,
            'nip_ppk' => $bapp->nip_ppk,
            'jabatan_ppk' => $bapp->jabatan_ppk,
            'nama_kabkota' => $bapp->nama_kabkota ?: config('app.instansi_kabupaten', ''),
            'nomor_spk' => $spk->nomor_spk,
            'target_sls' => $bapp->target_sls,
            'target_unit_sampel' => $bapp->target_unit_sampel ?? [],
            'realisasi_sls' => $bapp->realisasi_sls,
            'realisasi_unit_sampel' => $bapp->realisasi_unit_sampel ?? [],
            'nilai_perjanjian' => (float) ($bapp->nilai_perjanjian ?? 0),
            'fasih_screenshot_path' => $bapp->fasih_screenshot_path,
        ];
    }

    private function buildMergedPdf(array $viewData): string
    {
        $portraitPdf = Pdf::loadView('bapp-se', array_merge($viewData, ['page_number_offset' => 0]))->setPaper('A4', 'portrait')->output();
        $landscapePdf = Pdf::loadView('bapp-se-lampiran-table', array_merge($viewData, ['page_number_offset' => 2]))->setPaper('A4', 'landscape')->output();
        $screenshotPdf = Pdf::loadView('bapp-se-lampiran-screenshot', array_merge($viewData, ['page_number_offset' => 3]))->setPaper('A4', 'portrait')->output();

        $tmpPortrait = tempnam(sys_get_temp_dir(), 'bapp_').'.pdf';
        $tmpLandscape = tempnam(sys_get_temp_dir(), 'bapp_').'.pdf';
        $tmpScreenshot = tempnam(sys_get_temp_dir(), 'bapp_').'.pdf';

        file_put_contents($tmpPortrait, $portraitPdf);
        file_put_contents($tmpLandscape, $landscapePdf);
        file_put_contents($tmpScreenshot, $screenshotPdf);

        $fpdi = new Fpdi;
        $fpdi->setPrintHeader(false);
        $fpdi->setPrintFooter(false);
        $fpdi->SetMargins(0, 0, 0);
        $fpdi->SetAutoPageBreak(false, 0);

        foreach ([$tmpPortrait, $tmpLandscape, $tmpScreenshot] as $tmpFile) {
            $pageCount = $fpdi->setSourceFile($tmpFile);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplIdx = $fpdi->importPage($pageNo);
                $size = $fpdi->getTemplateSize($tplIdx);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($tplIdx, 0, 0, $size['width'], $size['height'], true);
            }
        }

        $merged = $fpdi->Output('merged.pdf', 'S');

        @unlink($tmpPortrait);
        @unlink($tmpLandscape);
        @unlink($tmpScreenshot);

        return $merged;
    }
}
