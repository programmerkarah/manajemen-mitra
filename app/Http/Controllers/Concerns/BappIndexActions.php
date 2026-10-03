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

trait BappIndexActions
{
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $this->userCanAccessBapp($request)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman BAPP SE2026.');
        }

        $tahun = ActiveYearService::get();
        $currentMonth = (int) now()->format('m');
        $hasBappTerminTable = $this->hasBappTerminTable();
        $kegiatan = $this->getSensusEkonomiKegiatan();
        $unitSampelItems = $this->getUnitSampelItems($kegiatan);

        $workflowContexts = [
            [
                'key' => 'regular',
                'label' => 'Petugas utama',
                'description' => 'BAPP petugas utama yang tidak masuk workflow penggantian.',
                'document_type' => 'regular',
                'replacement_termin_count' => 0,
            ],
            [
                'key' => 'stopped',
                'label' => 'Petugas berhenti',
                'description' => 'BAPP terakhir petugas yang berhenti. Termin yang tidak lagi berlaku tidak diwajibkan.',
                'document_type' => 'stopped_petugas',
                'replacement_termin_count' => 0,
            ],
            [
                'key' => 'replacement_2',
                'label' => 'Petugas pengganti · 2 termin',
                'description' => 'BAPP PKPP skema dua termin.',
                'document_type' => 'replacement_pkpp',
                'replacement_termin_count' => 2,
            ],
            [
                'key' => 'replacement_1',
                'label' => 'Petugas pengganti · 1 BAPP',
                'description' => 'Satu BAPP final untuk PKPP skema satu dokumen.',
                'document_type' => 'replacement_pkpp',
                'replacement_termin_count' => 1,
            ],
        ];

        $workflowData = collect($workflowContexts)->map(function (array $context) use ($tahun, $currentMonth, $hasBappTerminTable): array {
            $documentType = (string) $context['document_type'];
            $replacementTerminCount = (int) $context['replacement_termin_count'];
            $contextReplacementTerminCount = $this->getContextReplacementTerminCount($documentType, $replacementTerminCount);

            $terminData = [];
            foreach (self::TERMIN_CONFIG as $terminNumber => $config) {
                if ($documentType === 'replacement_pkpp' && $replacementTerminCount === 1 && $terminNumber === 2) {
                    continue;
                }

                $contextSpks = $this->getSpksForBappContext(
                    $tahun,
                    $terminNumber,
                    $documentType,
                    $contextReplacementTerminCount,
                );
                $spkIds = $contextSpks->pluck('id')->filter()->values();
                $bappCount = 0;

                if ($hasBappTerminTable && $spkIds->isNotEmpty()) {
                    $bappCount = $this->applyBappDocumentContextScope(
                        BappSeTermin::query()
                            ->where('termin', $terminNumber)
                            ->where('tahun', $tahun)
                            ->whereIn('spk_id', $spkIds)
                            ->whereNotNull('signed_file_path'),
                        $documentType,
                        $contextReplacementTerminCount,
                    )->count();
                }

                $spkCount = $contextSpks->count();

                $terminData[] = [
                    'termin' => $terminNumber,
                    'termin_hashed' => Hashids::encode($terminNumber),
                    'termin_roman' => $config['roman'],
                    'bulan' => $config['bulan'],
                    'bulan_label' => $config['bulan_label'],
                    'persentase' => $config['persentase'],
                    'can_generate' => $currentMonth >= $config['bulan'],
                    'bapp_count' => $bappCount,
                    'spk_count' => $spkCount,
                    'is_complete' => $spkCount > 0 && $bappCount >= $spkCount,
                ];
            }

            return [
                ...$context,
                'termin_data' => $terminData,
                'total_spk' => collect($terminData)->sum('spk_count'),
                'total_uploaded' => collect($terminData)->sum('bapp_count'),
            ];
        })->values()->all();

        return Inertia::render('Bapp/Index', [
            'tahun' => $tahun,
            'workflow_data' => $workflowData,
            'termin_data' => $workflowData[0]['termin_data'] ?? [],
            'document_type' => 'regular',
            'replacement_termin_count' => 0,
            'has_kegiatan' => $kegiatan !== null,
            'unit_sampel_items' => $unitSampelItems,
        ]);
    }

    public function show(Request $request, string $terminHashed): Response|RedirectResponse
    {
        if (! $this->userCanAccessBapp($request)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman BAPP SE2026.');
        }

        $decoded = Hashids::decode($terminHashed);
        $termin = ! empty($decoded) ? (int) $decoded[0] : null;

        if ($termin === null || ! isset(self::TERMIN_CONFIG[$termin])) {
            return redirect()->route('bapp.index')->with('error', 'Termin tidak valid.');
        }

        $tahun = ActiveYearService::get();
        $config = self::TERMIN_CONFIG[$termin];
        $hasBappTerminTable = $this->hasBappTerminTable();
        $documentType = $this->resolveDocumentType($request);
        $replacementTerminCount = $this->resolveReplacementTerminCount($request);
        $contextReplacementTerminCount = $this->getContextReplacementTerminCount($documentType, $replacementTerminCount);
        $tahunStr = (string) $tahun;
        $tanggalMin = sprintf($config['tanggal_min'], $tahunStr);
        $currentMonth = (int) now()->format('m');
        $canGenerate = $currentMonth >= $config['bulan'];
        $activeRoleName = effectiveUser($request)?->getActiveRole()?->name;
        $canInputRealisasi = $activeRoleName !== 'ketua_tim' || now()->format('Y-m-d') >= $tanggalMin;

        $spks = $this->getSpksForBappContext($tahun, $termin, $documentType);
        $nomorBappMap = $this->generateNomorBappMap(
            $spks,
            $config['roman'],
            $tahun,
            $documentType,
            $contextReplacementTerminCount,
        );

        $bappRecords = collect();
        if ($hasBappTerminTable) {
            $bappRecords = $this->applyBappDocumentContextScope(
                BappSeTermin::query()
                    ->where('termin', $termin)
                    ->where('tahun', $tahun),
                $documentType,
                $contextReplacementTerminCount,
            )
                ->get()
                ->keyBy('spk_id');
        }

        $spkList = $spks->map(function (Spk $spk) use ($bappRecords, $nomorBappMap) {
            /** @var BappSeTermin|null $bapp */
            $bapp = $bappRecords->get($spk->id);
            $petugas = $spk->petugas;
            $peran = $spk->alokasiPetugas?->peran ?? 'pcl_ppl';

            return [
                'spk_id' => $spk->id,
                'spk_hashed_id' => $spk->hashed_id,
                'nomor_spk' => $spk->nomor_spk,
                'petugas' => [
                    'id' => $petugas?->id,
                    'nama' => $petugas?->nama,
                    'nik' => $petugas?->nik,
                ],
                'peran' => $peran,
                'nomor_bapp_auto' => $nomorBappMap[$spk->id] ?? '',
                'has_bapp' => $bapp !== null,
                'bapp_hashed_id' => $bapp?->hashed_id,
                'bapp_preview_url' => $bapp ? route('bapp.preview', $bapp) : null,
                'bapp_download_url' => $bapp ? route('bapp.download', $bapp) : null,
                'bapp_download_signed_url' => $bapp ? route('bapp.download-signed', $bapp) : null,
                'nomor_bapp' => $bapp?->nomor_bapp,
                'tanggal_bapp' => $bapp?->tanggal_bapp?->format('Y-m-d'),
                'file_path' => $bapp?->file_path,
                'signed_file_path' => $bapp?->signed_file_path,
                'signed_uploaded_at' => $bapp?->signed_uploaded_at?->format('d M Y H:i'),
                'fasih_screenshot_path' => $bapp?->fasih_screenshot_path,
                'realisasi_sls' => $bapp?->realisasi_sls,
                'realisasi_unit_sampel' => $bapp?->realisasi_unit_sampel ?? [],
            ];
        })->values()->all();

        $totalCount = count($spkList);
        $generatedCount = collect($spkList)->filter(fn ($item) => $item['has_bapp'] && $item['file_path'])->count();
        $signedCount = collect($spkList)->filter(fn ($item) => filled($item['signed_file_path']))->count();

        return Inertia::render('Bapp/Show', [
            'tahun' => $tahun,
            'termin' => $termin,
            'termin_hashed' => Hashids::encode($termin),
            'termin_roman' => $config['roman'],
            'termin_options' => [
                ['termin' => 1, 'termin_hashed' => Hashids::encode(1), 'termin_roman' => self::TERMIN_CONFIG[1]['roman']],
                ['termin' => 2, 'termin_hashed' => Hashids::encode(2), 'termin_roman' => self::TERMIN_CONFIG[2]['roman']],
            ],
            'bulan_label' => $config['bulan_label'],
            'persentase' => $config['persentase'],
            'can_generate' => $canGenerate,
            'can_input_realisasi' => $canInputRealisasi,
            'tanggal_min' => $tanggalMin,
            'document_type' => $documentType,
            'replacement_termin_count' => $contextReplacementTerminCount,
            'spk_list' => $spkList,
            'summary' => [
                'total' => $totalCount,
                'generated' => $generatedCount,
                'signed' => $signedCount,
            ],
        ]);
    }
}
