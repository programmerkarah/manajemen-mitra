<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Requests\FilterRequest;
use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\BappSeTermin;
use App\Models\Bast;
use App\Models\Dipa;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use App\Services\PdfMergerService;
use App\Services\SpkActionDecisionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use setasign\Fpdi\Tcpdf\Fpdi;
use Vinkla\Hashids\Facades\Hashids;

trait SpkPdfSupport
{
    private function decodeAndSortPreviewItems(string $json): Collection
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return collect();
        }

        return collect($decoded)
            ->filter(fn ($item) => ! empty($item['petugas_hashed_id']) && ! empty($item['nomor_spk']))
            ->unique('petugas_hashed_id')
            ->sortBy(fn ($item) => mb_strtolower((string) ($item['petugas_nama'] ?? $item['nomor_spk'])))
            ->values();
    }

    private function buildSpkMainPdfBinary(
        PeriodeAlokasi $periode,
        int $petugasId,
        string $nomorSpk,
        string $tanggalSpk,
    ): ?string {
        $scopePeriodeIds = $this->resolveSpkScopePeriodeIds($periode, ['dikirim', 'perubahan']);
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereIn('periode_alokasi_id', $scopePeriodeIds)
            ->where('petugas_id', $petugasId)
            ->get();

        if ($allAlokasi->isEmpty()) {
            return null;
        }

        $latestEndDate = null;

        foreach ($allAlokasi as $alokasiItem) {
            $periodeItem = $alokasiItem->periodeAlokasi;
            $isPengolahanRole = in_array($alokasiItem->peran, ['pengolahan', 'pengawas_pengolahan']);

            $endDates = $isPengolahanRole
                ? array_filter([
                    $periodeItem->jadwal_pengolahan_pencacahan_selesai,
                    $periodeItem->jadwal_pengolahan_listing_selesai,
                ])
                : array_filter([
                    $periodeItem->tanggal_selesai,
                    $periodeItem->tanggal_selesai_listing,
                ]);

            if (! empty($endDates)) {
                $maxEndDate = max($endDates);
                if ($latestEndDate === null || $maxEndDate > $latestEndDate) {
                    $latestEndDate = $maxEndDate;
                }
            }
        }

        if ($latestEndDate === null) {
            $latestEndDate = Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth();
        }

        $calculatedSampaiTanggal = Carbon::parse($latestEndDate)->format('Y-m-d');
        $petugas = $allAlokasi->first()->petugas;
        $penandatangan = Penandatangan::active()->ppk()->firstOrFail();

        $totalHonor = 0;
        $uraianTugas = [];
        $kegiatanData = [];
        $bebanAnggaran = '';

        foreach ($allAlokasi as $alokasi) {
            $kegiatan = $alokasi->periodeAlokasi->kegiatan;
            $totalHonor += $this->calculateTotalHonor($kegiatan, $alokasi);
            $uraianTugas = array_merge($uraianTugas, $this->getUraianTugas($kegiatan, $alokasi));
            $kegiatanData[] = [
                'kegiatan_id' => $kegiatan->id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'kode_coa' => $kegiatan->kode_coa,
                'alokasi_id' => $alokasi->id,
            ];

            if (empty($bebanAnggaran)) {
                $bebanAnggaran = $this->getBebanAnggaran($kegiatan);
            }
        }

        $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $petugas->nama);
        $filename = 'Print_PK_Main_'.$sanitizedName.'.pdf';

        $data = [
            'periode' => $periode,
            'alokasi' => $allAlokasi->first(),
            'allAlokasi' => $allAlokasi,
            'petugas' => $petugas,
            'kegiatan' => $allAlokasi->first()->periodeAlokasi->kegiatan,
            'kegiatanData' => $kegiatanData,
            'nomorSpk' => $nomorSpk,
            'tanggalSpk' => Carbon::parse($tanggalSpk),
            'sampaiTanggal' => Carbon::parse($calculatedSampaiTanggal),
            'tanggalPerpanjangan' => null,
            'penandatangan' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'peran' => $allAlokasi->first()->peran,
            'peranLabel' => $this->getPeranLabel($allAlokasi->first()->peran),
            'totalHonor' => $totalHonor,
            'uraianTugas' => $uraianTugas,
            'bebanAnggaran' => $bebanAnggaran,
            'pdfTitle' => $filename,
            'workType' => $this->detectWorkType($allAlokasi),
        ];

        $pdf = Pdf::loadView('spk-main', $data)->setPaper('a4', 'portrait');
        $pdf->getDomPDF()->set_option('pdfTitle', $filename);

        return $pdf->output() ?: null;
    }

    private function buildSpkLampiranPdfBinary(
        PeriodeAlokasi $periode,
        int $petugasId,
        string $nomorSpk,
        string $tanggalSpk,
    ): ?string {
        $scopePeriodeIds = $this->resolveSpkScopePeriodeIds($periode, ['dikirim', 'perubahan']);
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereIn('periode_alokasi_id', $scopePeriodeIds)
            ->where('petugas_id', $petugasId)
            ->get();

        if ($allAlokasi->isEmpty()) {
            return null;
        }

        $latestEndDate = null;

        foreach ($allAlokasi as $alokasiItem) {
            $periodeItem = $alokasiItem->periodeAlokasi;
            $isPengolahanRole = in_array($alokasiItem->peran, ['pengolahan', 'pengawas_pengolahan']);

            $endDates = $isPengolahanRole
                ? array_filter([
                    $periodeItem->jadwal_pengolahan_pencacahan_selesai,
                    $periodeItem->jadwal_pengolahan_listing_selesai,
                ])
                : array_filter([
                    $periodeItem->tanggal_selesai,
                    $periodeItem->tanggal_selesai_listing,
                ]);

            if (! empty($endDates)) {
                $maxEndDate = max($endDates);
                if ($latestEndDate === null || $maxEndDate > $latestEndDate) {
                    $latestEndDate = $maxEndDate;
                }
            }
        }

        if ($latestEndDate === null) {
            $latestEndDate = Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth();
        }

        $petugas = $allAlokasi->first()->petugas;
        $penandatangan = Penandatangan::active()->ppk()->firstOrFail();

        $totalHonor = 0;
        $uraianTugas = [];
        $kegiatanData = [];
        $bebanAnggaran = '';

        foreach ($allAlokasi as $alokasi) {
            $kegiatan = $alokasi->periodeAlokasi->kegiatan;
            $totalHonor += $this->calculateTotalHonor($kegiatan, $alokasi);
            $uraianTugas = array_merge($uraianTugas, $this->getUraianTugas($kegiatan, $alokasi));
            $kegiatanData[] = [
                'kegiatan_id' => $kegiatan->id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'kode_coa' => $kegiatan->kode_coa,
                'alokasi_id' => $alokasi->id,
            ];

            if (empty($bebanAnggaran)) {
                $bebanAnggaran = $this->getBebanAnggaran($kegiatan);
            }
        }

        $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $petugas->nama);
        $filename = 'Print_Lampiran_'.$sanitizedName.'.pdf';

        $data = [
            'periode' => $periode,
            'alokasi' => $allAlokasi->first(),
            'allAlokasi' => $allAlokasi,
            'petugas' => $petugas,
            'kegiatan' => $allAlokasi->first()->periodeAlokasi->kegiatan,
            'kegiatanData' => $kegiatanData,
            'nomorSpk' => $nomorSpk,
            'tanggalSpk' => Carbon::parse($tanggalSpk),
            'sampaiTanggal' => Carbon::parse(Carbon::parse($latestEndDate)->format('Y-m-d')),
            'tanggalPerpanjangan' => null,
            'penandatangan' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'peran' => $allAlokasi->first()->peran,
            'peranLabel' => $this->getPeranLabel($allAlokasi->first()->peran),
            'totalHonor' => $totalHonor,
            'uraianTugas' => $uraianTugas,
            'bebanAnggaran' => $bebanAnggaran,
            'pdfTitle' => $filename,
            'workType' => $this->detectWorkType($allAlokasi),
        ];

        // Render main first just for page count offset
        $pdfMain = Pdf::loadView('spk-main', $data)->setPaper('a4', 'portrait');
        $pdfMain->output();
        $data['pageNumberOffset'] = max(0, (int) $pdfMain->getDomPDF()->getCanvas()->get_page_count());

        $data = $this->withLampiranContext($data);

        $lampiranView = $this->resolveLampiranView($data['kegiatan'], $data['peran']);
        $lampiranPaper = $this->resolveLampiranPaperOrientation($data['kegiatan'], $data['peran']);

        $pdf = Pdf::loadView($lampiranView, $data)->setPaper('a4', $lampiranPaper);
        $pdf->getDomPDF()->set_option('pdfTitle', $filename);

        return $pdf->output() ?: null;
    }

    private function buildMergedSpkPreviewBinary(
        PeriodeAlokasi $periode,
        int $petugasId,
        string $nomorSpk,
        string $tanggalSpk,
        ?int $kegiatanId = null,
        ?string $jenisKegiatan = null,
    ): ?array {
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($q) use ($periode, $kegiatanId, $jenisKegiatan) {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun)
                    ->whereIn('status', ['dikirim', 'perubahan'])
                    ->when($kegiatanId !== null, function ($periodeQuery) use ($kegiatanId): void {
                        $periodeQuery->where('kegiatan_id', $kegiatanId);
                    })
                    ->when($jenisKegiatan !== null, function ($periodeQuery) use ($jenisKegiatan): void {
                        $periodeQuery->whereHas('kegiatan', function ($kegiatanQuery) use ($jenisKegiatan): void {
                            $kegiatanQuery->where('jenis_kegiatan', $jenisKegiatan);
                        });
                    });
            })
            ->where('petugas_id', $petugasId)
            ->get();

        if ($allAlokasi->isEmpty()) {
            return null;
        }

        $latestEndDate = null;
        foreach ($allAlokasi as $alokasiItem) {
            $periodeItem = $alokasiItem->periodeAlokasi;
            $isPengolahanRole = in_array($alokasiItem->peran, ['pengolahan', 'pengawas_pengolahan']);

            $endDates = $isPengolahanRole
                ? array_filter([
                    $periodeItem->jadwal_pengolahan_pencacahan_selesai,
                    $periodeItem->jadwal_pengolahan_listing_selesai,
                ])
                : array_filter([
                    $periodeItem->tanggal_selesai,
                    $periodeItem->tanggal_selesai_listing,
                ]);

            if (! empty($endDates)) {
                $maxEndDate = max($endDates);
                if ($latestEndDate === null || $maxEndDate > $latestEndDate) {
                    $latestEndDate = $maxEndDate;
                }
            }
        }

        if ($latestEndDate === null) {
            $latestEndDate = Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth();
        }

        $calculatedSampaiTanggal = Carbon::parse($latestEndDate)->format('Y-m-d');
        $petugas = $allAlokasi->first()->petugas;
        $penandatangan = Penandatangan::active()->ppk()->firstOrFail();

        $totalHonor = 0;
        $uraianTugas = [];
        $kegiatanData = [];

        foreach ($allAlokasi as $alokasi) {
            $kegiatan = $alokasi->periodeAlokasi->kegiatan;
            $totalHonor += $this->calculateTotalHonor($kegiatan, $alokasi);
            $uraianTugas = array_merge($uraianTugas, $this->getUraianTugas($kegiatan, $alokasi));

            $kegiatanData[] = [
                'kegiatan_id' => $kegiatan->id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'kode_coa' => $kegiatan->kode_coa,
                'alokasi_id' => $alokasi->id,
            ];
        }

        $bebanAnggaran = $allAlokasi->isNotEmpty()
            ? $this->getBebanAnggaran($allAlokasi->first()->periodeAlokasi->kegiatan)
            : '';

        $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $petugas->nama);
        $filename = 'Preview_SPK_'.$sanitizedName.'.pdf';

        $data = [
            'periode' => $periode,
            'alokasi' => $allAlokasi->first(),
            'allAlokasi' => $allAlokasi,
            'petugas' => $petugas,
            'kegiatan' => $allAlokasi->first()->periodeAlokasi->kegiatan,
            'kegiatanData' => $kegiatanData,
            'nomorSpk' => $nomorSpk,
            'tanggalSpk' => Carbon::parse($tanggalSpk),
            'sampaiTanggal' => Carbon::parse($calculatedSampaiTanggal),
            'tanggalPerpanjangan' => null,
            'penandatangan' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'peran' => $allAlokasi->first()->peran,
            'peranLabel' => $this->getPeranLabel($allAlokasi->first()->peran),
            'totalHonor' => $totalHonor,
            'uraianTugas' => $uraianTugas,
            'bebanAnggaran' => $bebanAnggaran,
            'pdfTitle' => $filename,
            'workType' => $this->detectWorkType($allAlokasi),
        ];
        $data = $this->withLampiranContext($data);

        $lampiranView = $this->resolveLampiranView($data['kegiatan'], $data['peran']);
        $lampiranPaper = $this->resolveLampiranPaperOrientation($data['kegiatan'], $data['peran']);

        $pdfMain = Pdf::loadView('spk-main', $data)
            ->setPaper('a4', 'portrait');
        $pdfMain->getDomPDF()->set_option('pdfTitle', $filename);

        $mainOutput = $pdfMain->output();
        $mainPageCount = max(0, (int) $pdfMain->getDomPDF()->getCanvas()->get_page_count());
        $data['pageNumberOffset'] = $mainPageCount;

        $pdfLampiran = Pdf::loadView($lampiranView, $data)
            ->setPaper('a4', $lampiranPaper);
        $pdfLampiran->getDomPDF()->set_option('pdfTitle', $filename);

        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        $timestamp = time().'_'.uniqid();
        $mainPath = $tempPath.'/spk_main_'.$timestamp.'.pdf';
        $lampiranPath = $tempPath.'/spk_lampiran_'.$timestamp.'.pdf';
        $mergedPath = $tempPath.'/spk_merged_'.$timestamp.'.pdf';

        file_put_contents($mainPath, $mainOutput);
        file_put_contents($lampiranPath, $pdfLampiran->output());

        $merged = PdfMergerService::mergePdfFiles(
            [$mainPath, $lampiranPath],
            $mergedPath,
            $filename
        );

        $pdfOutput = null;
        if ($merged && file_exists($mergedPath)) {
            $pdfOutput = file_get_contents($mergedPath) ?: null;
        }

        @unlink($mainPath);
        @unlink($lampiranPath);
        @unlink($mergedPath);

        if ($pdfOutput === null) {
            $pdf = Pdf::loadView('spk-petugas', $data)
                ->setPaper('a4', 'portrait');
            $pdf->getDomPDF()->set_option('pdfTitle', $filename);
            $pdfOutput = $pdf->output();
        }

        return [
            'filename' => $filename,
            'content' => $pdfOutput,
        ];
    }

    private function getBulanLabel(int $bulan): string
    {
        $bulanLabels = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $bulanLabels[$bulan] ?? '';
    }

    private function withLampiranContext(array $data): array
    {
        $kegiatan = $data['kegiatan'] ?? null;
        $peran = mb_strtolower((string) ($data['peran'] ?? ''));

        if (! $kegiatan instanceof Kegiatan) {
            $data['lampiranTemplate'] = 'default';
            $data['lampiranPayload'] = null;

            return $data;
        }

        if ($this->usesSensusEkonomiLampiranTemplate($kegiatan, $peran)) {
            $data['lampiranTemplate'] = 'sensus_ekonomi';
            $data['lampiranPayload'] = $this->buildSensusEkonomiLampiranPayload(
                $data['periode'] ?? null,
                $data['uraianTugas'] ?? [],
                (float) ($data['totalHonor'] ?? 0),
                $kegiatan,
                $data['allAlokasi'] ?? null,
                $data['alokasi'] ?? null,
            );

            return $data;
        }

        if ($this->isSensusEkonomi2026($kegiatan) && $peran === 'pml') {
            $data['lampiranTemplate'] = 'pml_sensus_ekonomi';
            $data['lampiranPayload'] = $this->buildPmlSensusEkonomiLampiranPayload(
                $data['periode'] ?? null,
                $data['uraianTugas'] ?? [],
                (float) ($data['totalHonor'] ?? 0),
                $kegiatan,
                $data['allAlokasi'] ?? null,
                $data['alokasi'] ?? null,
            );

            return $data;
        }

        $data['lampiranTemplate'] = 'default';
        $data['lampiranPayload'] = null;

        return $data;
    }

    private function resolveLampiranView(Kegiatan $kegiatan, string $peran): string
    {
        if ($this->usesSensusEkonomiLampiranTemplate($kegiatan, $peran)) {
            return 'spk-lampiran-sensus-ekonomi';
        }

        if ($this->isSensusEkonomi2026($kegiatan) && mb_strtolower($peran) === 'pml') {
            return 'spk-lampiran-pml-sensus-ekonomi';
        }

        return 'spk-lampiran';
    }

    private function resolveLampiranPaperOrientation(Kegiatan $kegiatan, string $peran): string
    {
        if ($this->usesSensusEkonomiLampiranTemplate($kegiatan, $peran)) {
            return 'landscape';
        }

        return 'landscape';
    }

    private function usesSensusEkonomiLampiranTemplate(Kegiatan $kegiatan, string $peran): bool
    {
        return $this->isSensusEkonomi2026($kegiatan)
            && in_array(mb_strtolower($peran), ['pcl_ppl', 'pcl', 'ppl'], true);
    }

    private function formatDisplayName(string $name): string
    {
        return ucwords(mb_strtolower(trim($name)));
    }

    private function formatLampiranVolumeNumber(float|int $volume): string
    {
        return floor((float) $volume) === (float) $volume
            ? number_format((float) $volume, 0, ',', '.')
            : rtrim(rtrim(number_format((float) $volume, 2, ',', '.'), '0'), ',');
    }

    private function calculateLampiranMilestoneAmount(float $totalHonor, float $ratio): float
    {
        return round($totalHonor * $ratio, 2);
    }

    private function formatLampiranDate(mixed $date): string
    {
        if (! $date) {
            return '-';
        }

        return Carbon::parse($date)->locale('id')->translatedFormat('d F Y');
    }

    private function formatLampiranDateRange(mixed $startDate, mixed $endDate): string
    {
        if (! $startDate && ! $endDate) {
            return '-';
        }

        if (! $startDate) {
            return $this->formatLampiranDate($endDate);
        }

        if (! $endDate) {
            return $this->formatLampiranDate($startDate);
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->year === $end->year) {
            if ($start->month === $end->month) {
                return $start->translatedFormat('d').'-'.$end->translatedFormat('d F Y');
            }

            return $start->translatedFormat('d F').'-'.$end->translatedFormat('d F Y');
        }

        return $start->translatedFormat('d F Y').'-'.$end->translatedFormat('d F Y');
    }

    private function formatLampiranVolumeLabel(mixed $volume, ?string $unit): string
    {
        if ($volume === null || $volume === '' || (float) $volume <= 0) {
            return '-';
        }

        $formattedVolume = $this->formatLampiranVolumeNumber((float) $volume);

        return trim($formattedVolume.' '.($unit ?? ''));
    }

    private function getUraianTugas(Kegiatan $kegiatan, AlokasiPetugas $alokasi): array
    {
        $rateHonor = RateHonor::where('kegiatan_id', $kegiatan->id)
            ->where('jenis_penugasan', $alokasi->peran)
            ->where('status_kepegawaian', $alokasi->status_kepegawaian ?? ($alokasi->petugas->jenis_petugas === 'organik' ? 'organik' : 'non_organik'))
            ->with(['satuan', 'satuanListing'])
            ->first();

        $uraian = [];
        $periode = $alokasi->periodeAlokasi;
        if ($rateHonor) {
            // Check if this is a pengolahan role
            $isPengolahanRole = in_array($alokasi->peran, ['pengolahan', 'pengawas_pengolahan']);
            $effectiveListingVolume = $alokasi->getEffectiveJumlahSatuanListing();
            $effectiveListingHonor = $alokasi->getEffectiveTotalHonorListing();
            $effectivePencacahanVolume = $alokasi->getEffectiveJumlahSatuan();
            $effectivePencacahanHonor = $alokasi->getEffectiveTotalHonor();
            $unitSampelVolume = (int) ($alokasi->jumlah_unit_sampel ?? 0);

            if ($unitSampelVolume > 0) {
                if ($effectiveListingVolume <= 0) {
                    $effectiveListingVolume = $unitSampelVolume;
                }

                if ($effectivePencacahanVolume <= 0) {
                    $effectivePencacahanVolume = $unitSampelVolume;
                }
            }

            // Add listing task if exists
            if ($effectiveListingVolume > 0 && $effectiveListingHonor > 0) {
                $peranKegiatan = $this->getPeranKegiatan($alokasi->peran, 'listing');

                // Use processing schedule for pengolahan roles, otherwise use regular schedule
                $tanggalMulai = $isPengolahanRole && $periode->jadwal_pengolahan_listing_mulai
                    ? $periode->jadwal_pengolahan_listing_mulai->format('Y-m-d')
                    : $periode->tanggal_mulai_listing?->format('Y-m-d');
                $tanggalSelesai = $isPengolahanRole && $periode->jadwal_pengolahan_listing_selesai
                    ? $periode->jadwal_pengolahan_listing_selesai->format('Y-m-d')
                    : $periode->tanggal_selesai_listing?->format('Y-m-d');

                $uraian[] = [
                    'uraian' => "Melakukan {$peranKegiatan} {$kegiatan->nama_kegiatan} bulan {$this->getBulanLabel($periode->bulan)} Tahun {$kegiatan->tahun_anggaran} (Listing)",
                    'volume' => $effectiveListingVolume,
                    'satuan' => $rateHonor->satuanListing->kode ?? 'DOK',
                    'harga_satuan' => $effectiveListingVolume > 0 ? ($effectiveListingHonor / $effectiveListingVolume) : (float) ($rateHonor->rate_listing ?? 0),
                    'jumlah' => $effectiveListingHonor,
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_selesai' => $tanggalSelesai,
                    'phase' => 'listing',
                    'kode_coa' => $kegiatan->kode_coa, // Add COA per kegiatan
                ];
            }

            // Add regular task (pencacahan)
            if ($effectivePencacahanVolume > 0 && $effectivePencacahanHonor > 0) {
                $peranKegiatan = $this->getPeranKegiatan($alokasi->peran, 'pencacahan');

                // Use processing schedule for pengolahan roles, otherwise use regular schedule
                $tanggalMulai = $isPengolahanRole && $periode->jadwal_pengolahan_pencacahan_mulai
                    ? $periode->jadwal_pengolahan_pencacahan_mulai->format('Y-m-d')
                    : $periode->tanggal_mulai?->format('Y-m-d');
                $tanggalSelesai = $isPengolahanRole && $periode->jadwal_pengolahan_pencacahan_selesai
                    ? $periode->jadwal_pengolahan_pencacahan_selesai->format('Y-m-d')
                    : $periode->tanggal_selesai?->format('Y-m-d');

                $uraian[] = [
                    'uraian' => "Melakukan {$peranKegiatan} {$kegiatan->nama_kegiatan} bulan {$this->getBulanLabel($periode->bulan)} Tahun {$kegiatan->tahun_anggaran}",
                    'volume' => $effectivePencacahanVolume,
                    'satuan' => $rateHonor->satuan->kode ?? 'DOK',
                    'harga_satuan' => $effectivePencacahanVolume > 0 ? ($effectivePencacahanHonor / $effectivePencacahanVolume) : (float) ($rateHonor->rate ?? 0),
                    'jumlah' => $effectivePencacahanHonor,
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_selesai' => $tanggalSelesai,
                    'phase' => 'pencacahan',
                    'kode_coa' => $kegiatan->kode_coa, // Add COA per kegiatan
                ];
            }
        }

        return $uraian;
    }

    private function getPeranKegiatan(string $peran, string $phase): string
    {
        if ($phase === 'listing') {
            return match ($peran) {
                'pcl_ppl' => 'Pemutakhiran Lapangan',
                'pml' => 'Pemeriksaan Pemutakhiran Lapangan',
                'pengolahan' => 'Pengolahan Dokumen Pemutakhiran Lapangan',
                'pengawas_pengolahan' => 'Pemeriksaan Pengolahan Pemutakhiran Lapangan',
                default => 'Pemutakhiran Lapangan',
            };
        }

        // pencacahan
        return match ($peran) {
            'pcl_ppl' => 'Pendataan Lapangan',
            'pml' => 'Pemeriksaan Lapangan',
            'pengolahan' => 'Pengolahan Dokumen Lapangan',
            'pengawas_pengolahan' => 'Pemeriksaan Pengolahan Lapangan',
            default => 'Pendataan Lapangan',
        };
    }

    private function getBebanAnggaran(Kegiatan $kegiatan): string
    {
        // Prioritaskan kode_coa dari kegiatan jika ada
        if (! empty($kegiatan->kode_coa)) {
            return $kegiatan->kode_coa;
        }

        // Fallback ke MAK dari DIPA
        $dipa = Dipa::active()->first();

        return $dipa->mak ?? '2904.BMA.006.005.A.521213';
    }

    private function getPeranLabel(string $peran): string
    {
        return match ($peran) {
            'pcl_ppl' => 'Petugas Pencacahan',
            'pml' => 'Pemeriksa Lapangan/PML',
            'pengolahan' => 'Petugas Pengolahan',
            'pengawas_pengolahan' => 'Pemeriksa Pengolahan',
            default => $peran,
        };
    }

    private function detectWorkType(iterable $allAlokasi): string
    {
        $hasPengolahan = false;
        $hasLapangan = false;

        foreach ($allAlokasi as $alokasi) {
            $peran = $alokasi->peran ?? '';
            if (in_array($peran, ['pengolahan', 'pengawas_pengolahan'])) {
                $hasPengolahan = true;
            } else {
                $hasLapangan = true;
            }
        }

        if ($hasPengolahan && $hasLapangan) {
            return 'lapangan_pengolahan';
        } elseif ($hasPengolahan) {
            return 'pengolahan';
        } else {
            return 'lapangan';
        }
    }
}
