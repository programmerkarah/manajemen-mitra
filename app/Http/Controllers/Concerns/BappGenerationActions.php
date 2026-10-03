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

trait BappGenerationActions
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $this->userCanAccessBapp($request)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman BAPP SE2026.');
        }

        $tahun = ActiveYearService::get();
        $terminHashed = (string) $request->query('termin', '');
        $decoded = Hashids::decode($terminHashed);
        $terminNumber = isset($decoded[0]) ? (int) $decoded[0] : null;

        if ($terminNumber === null || ! isset(self::TERMIN_CONFIG[$terminNumber])) {
            return redirect()->route('bapp.index')->with('error', 'Termin tidak valid.');
        }

        $config = self::TERMIN_CONFIG[$terminNumber];
        $currentMonth = (int) now()->format('m');
        $hasBappTerminTable = $this->hasBappTerminTable();
        $documentType = $this->resolveDocumentType($request);
        $replacementTerminCount = $this->resolveReplacementTerminCount($request);
        $contextReplacementTerminCount = $this->getContextReplacementTerminCount(
            $documentType,
            $replacementTerminCount,
        );

        if ($documentType === 'replacement_pkpp' && $contextReplacementTerminCount === 1 && $terminNumber === 2) {
            return redirect()->route('bapp.index')
                ->with('error', 'PKPP ini hanya memerlukan satu BAPP.');
        }

        $kegiatan = $this->getSensusEkonomiKegiatan();
        if (! $kegiatan) {
            return redirect()->route('bapp.index')->with('error', 'Kegiatan Sensus Ekonomi tidak ditemukan.');
        }

        $unitSampelItems = $this->getUnitSampelItems($kegiatan);
        $spks = $this->getSpksForBappContext($tahun, $terminNumber, $documentType, $contextReplacementTerminCount);
        $ppk = $this->getPpk();
        $nomorBappMap = $this->generateNomorBappMap(
            $spks,
            $config['roman'],
            $tahun,
            $documentType,
            $contextReplacementTerminCount,
        );

        $replacementBySourceSpkId = $documentType === 'replacement_pkpp'
            ? SensusEkonomiPkppContract::query()
                ->where('termin_count', $contextReplacementTerminCount)
                ->whereHas('replacement', fn ($query) => $query->whereNotNull('spk_lama_id'))
                ->with(['replacement.petugasPengganti'])
                ->get()
                ->mapWithKeys(fn (SensusEkonomiPkppContract $contract) => [
                    (int) $contract->replacement?->spk_lama_id => $contract,
                ])
            : collect();

        $spkList = $spks->map(function (Spk $spk) use ($terminNumber, $nomorBappMap, $documentType, $contextReplacementTerminCount, $hasBappTerminTable, $replacementBySourceSpkId): array {
            $targetData = $this->buildTargetForTermin($spk, $terminNumber);
            $replacementContract = $documentType === 'replacement_pkpp'
                ? $replacementBySourceSpkId->get((int) $spk->id)
                : null;
            $petugas = $replacementContract?->replacement?->petugasPengganti ?? $spk->petugas;
            $alokasi = $spk->alokasiPetugas;
            $peran = $alokasi?->peran ?? 'pcl_ppl';

            /** @var BappSeTermin|null $existing */
            $existing = null;
            if ($hasBappTerminTable) {
                $existing = $this->applyBappDocumentContextScope(
                    BappSeTermin::query()
                        ->where('spk_id', $spk->id)
                        ->where('termin', $terminNumber),
                    $documentType,
                    $contextReplacementTerminCount,
                )->first();
            }

            return [
                'spk_id' => $spk->id,
                'spk_hashed_id' => $spk->hashed_id,
                'nomor_spk' => $replacementContract?->nomor_pkpp ?? $spk->nomor_spk,
                'nilai_kontrak' => (float) $spk->nilai_kontrak,
                'peran' => $peran,
                'jenis_pihak_kedua' => $this->getJenisPihakKedua($peran),
                'petugas' => [
                    'id' => $petugas?->id,
                    'nama' => $petugas?->nama,
                    'nik' => $petugas?->nik ?? '',
                ],
                'target_sls' => $targetData['target_sls'],
                'target_unit_sampel' => $targetData['target_unit_sampel'],
                'nomor_bapp_auto' => $nomorBappMap[$spk->id] ?? '',
                'has_bapp' => $existing !== null,
                'bapp_hashed_id' => $existing?->hashed_id,
                'tanggal_bapp' => $existing?->tanggal_bapp?->format('Y-m-d'),
                'bapp_preview_url' => $existing ? route('bapp.preview', $existing) : null,
                'bapp_download_url' => $existing ? route('bapp.download', $existing) : null,
                'realisasi_sls' => $existing?->realisasi_sls,
                'realisasi_unit_sampel' => $existing?->realisasi_unit_sampel ?? [],
                'file_path' => $existing?->file_path,
                'signed_file_path' => $existing?->signed_file_path,
                'signed_uploaded_at' => $existing?->signed_uploaded_at?->toIso8601String(),
                'nomor_bapp' => $existing?->nomor_bapp,
                'nomor_bapp_urut' => $this->extractManualDocumentSequence($existing?->nomor_bapp),
                'fasih_screenshot_path' => $existing?->fasih_screenshot_path,
            ];
        })->values()->all();

        // Shared tanggal_bapp: pick from first existing BAPP record or use default
        $existingBapp = null;
        if ($hasBappTerminTable) {
            $existingBapp = $this->applyBappDocumentContextScope(
                BappSeTermin::query()
                    ->where('termin', $terminNumber)
                    ->where('tahun', $tahun),
                $documentType,
                $contextReplacementTerminCount,
            )
                ->whereNotNull('tanggal_bapp')
                ->first();
        }

        $tahunStr = (string) $tahun;
        $tanggalDefault = $config['tanggal_default']
            ? sprintf($config['tanggal_default'], $tahunStr)
            : null;
        $tanggalBapp = $existingBapp?->tanggal_bapp?->format('Y-m-d') ?? $tanggalDefault;

        $activeRoleName = effectiveUser($request)?->getActiveRole()?->name;
        $tanggalMin = sprintf($config['tanggal_min'], $tahunStr);
        $canInputRealisasi = $activeRoleName !== 'ketua_tim' || now()->format('Y-m-d') >= $tanggalMin;

        return Inertia::render('Bapp/Manual', [
            'tahun' => $tahun,
            'termin' => $terminNumber,
            'termin_hashed' => Hashids::encode($terminNumber),
            'termin_roman' => $config['roman'],
            'bulan' => $config['bulan'],
            'bulan_label' => $config['bulan_label'],
            'persentase' => $config['persentase'],
            'can_generate' => $currentMonth >= $config['bulan'],
            'can_input_realisasi' => $canInputRealisasi,
            'tanggal_bapp' => $tanggalBapp,
            'tanggal_min' => $tanggalMin,
            'tanggal_max' => sprintf($config['tanggal_max'], $tahunStr),
            'tanggal_fixed' => $config['tanggal_default'] !== null,
            'spk_list' => $spkList,
            'document_type' => $documentType,
            'replacement_termin_count' => $contextReplacementTerminCount,
            'nomor_bapp_suffix' => $documentType === 'replacement_pkpp' && $contextReplacementTerminCount === 1
                ? '/BAPP-SE2026/1373/PL.200/'.$tahun
                : '/BAPP-'.$config['roman'].'-SE2026/1373/PL.200/'.$tahun,
            'unit_sampel_items' => $unitSampelItems,
            'ketua_tim' => [
                'nama' => $kegiatan->ketuaTim?->name,
                'nip' => $kegiatan->ketuaTim?->nip,
            ],
            'ppk' => [
                'nama' => $ppk?->nama,
                'nip' => $ppk?->nip,
                'jabatan' => $ppk?->jabatan,
            ],
            'import_preview' => session('import_preview'),
        ]);
    }

    public function storeRealisasi(Request $request): RedirectResponse
    {
        if (! $this->hasBappTerminTable()) {
            return back()->with('error', 'Tabel BAPP belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $tahun = ActiveYearService::get();
        $terminNumber = (int) $request->input('termin', 1);
        $documentType = $this->resolveDocumentType($request);
        $replacementTerminCount = $this->resolveReplacementTerminCount($request);
        $contextReplacementTerminCount = $this->getContextReplacementTerminCount($documentType, $replacementTerminCount);

        if (! isset(self::TERMIN_CONFIG[$terminNumber])) {
            return back()->with('error', 'Termin tidak valid.');
        }

        $config = self::TERMIN_CONFIG[$terminNumber];
        $entries = $request->input('entries', []);
        $sharedTanggalBapp = $request->input('tanggal_bapp');

        if (empty($entries)) {
            return back()->with('error', 'Tidak ada data realisasi yang dikirim.');
        }

        $saved = 0;
        $kegiatan = $this->getSensusEkonomiKegiatan();
        $ppk = $this->getPpk();
        $unitSampelItems = $this->getUnitSampelItems($kegiatan);
        $allSpks = $this->getSpksForBappContext($tahun, $terminNumber, $documentType);
        $allowedSpkIds = $allSpks->pluck('id')->map(fn ($id) => (int) $id)->all();
        $stoppedReplacementIdBySpkId = $documentType === 'stopped_petugas'
            ? $this->getStoppedReplacementIdBySpkId($terminNumber, $tahun)
            : [];
        $nomorBappMap = $this->generateNomorBappMap(
            $allSpks,
            $config['roman'],
            $tahun,
            $documentType,
            $contextReplacementTerminCount,
        );

        foreach ($entries as $entry) {
            $spkId = null;
            if (! empty($entry['spk_hashed_id'])) {
                $spkId = Hashids::decode((string) $entry['spk_hashed_id'])[0] ?? null;
            } elseif (! empty($entry['spk_id'])) {
                $spkId = (int) $entry['spk_id'];
            }
            if (! $spkId) {
                continue;
            }

            $spk = Spk::query()->find($spkId);
            if (! $spk || ! in_array($spkId, $allowedSpkIds, true)) {
                continue;
            }

            $realisasiSls = isset($entry['realisasi_sls']) && $entry['realisasi_sls'] !== '' && $entry['realisasi_sls'] !== null
                ? max(0, (int) $entry['realisasi_sls'])
                : null;

            $realisasiUnitSampel = [];
            foreach ($unitSampelItems as $unit) {
                $unitKey = strtolower($unit['nama']);
                $val = $entry['realisasi_unit_sampel'][$unitKey] ?? null;
                if ($val !== null && $val !== '') {
                    $realisasiUnitSampel[$unitKey] = max(0, (int) $val);
                }
            }

            $targetData = $this->buildTargetForTermin($spk, $terminNumber);
            $nilaiPerjanjian = round((float) $spk->nilai_kontrak * $config['persentase'] / 100, 2);
            $entryTanggalBapp = $this->resolveEntryTanggalBapp($entry, $sharedTanggalBapp);

            BappSeTermin::query()->updateOrCreate(
                $this->withBappDocumentContextAttributes([
                    'spk_id' => $spkId,
                    'termin' => $terminNumber,
                ], $documentType, $contextReplacementTerminCount),
                $this->withBappDocumentContextAttributes([
                    'replacement_id' => $documentType === 'stopped_petugas'
                        ? ($stoppedReplacementIdBySpkId[$spkId] ?? null)
                        : null,
                    'petugas_id' => $spk->petugas_id,
                    'bulan' => $config['bulan'],
                    'tahun' => $tahun,
                    'nomor_bapp' => $nomorBappMap[$spkId] ?? null,
                    'tanggal_bapp' => $entryTanggalBapp ?: null,
                    'nama_ketua_tim' => $kegiatan?->ketuaTim?->name,
                    'nip_ketua_tim' => $this->getNipKetuaTim($kegiatan),
                    'nama_ppk' => $ppk ? $this->stripGelar($ppk->nama) : null,
                    'nip_ppk' => $ppk?->nip,
                    'jabatan_ppk' => $ppk?->jabatan,
                    'nama_kabkota' => config('app.instansi_kabupaten', ''),
                    'target_sls' => $targetData['target_sls'],
                    'target_unit_sampel' => empty($targetData['target_unit_sampel']) ? null : $targetData['target_unit_sampel'],
                    'realisasi_sls' => $realisasiSls,
                    'realisasi_unit_sampel' => empty($realisasiUnitSampel) ? null : $realisasiUnitSampel,
                    'persentase' => $config['persentase'],
                    'nilai_perjanjian' => $nilaiPerjanjian,
                    'created_by' => Auth::id(),
                ], $documentType, $contextReplacementTerminCount)
            );

            $saved++;
        }

        return back()->with('success', "Data realisasi berhasil disimpan untuk {$saved} SPK.");
    }

    public function generate(Request $request): BinaryFileResponse|\Illuminate\Http\Response
    {
        $documentType = $this->resolveDocumentType($request);
        $replacementTerminCount = $this->resolveReplacementTerminCount($request);
        $contextReplacementTerminCount = $this->getContextReplacementTerminCount($documentType, $replacementTerminCount);

        $spkHashedId = $request->input('spk_hashed_id') ?? $request->input('spk_id');
        $spkId = Hashids::decode((string) ($spkHashedId ?? ''))[0] ?? null;
        if (! $spkId) {
            abort(422, 'SPK tidak valid.');
        }
        $terminNumber = (int) $request->input('termin', 1);

        if (! isset(self::TERMIN_CONFIG[$terminNumber])) {
            abort(422, 'Termin tidak valid.');
        }

        if (! $this->hasBappTerminTable()) {
            abort(503, 'Tabel BAPP belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $spk = Spk::query()
            ->with(['petugas', 'alokasiPetugas.periodeAlokasi.kegiatan'])
            ->findOrFail($spkId);

        /** @var BappSeTermin $bapp */
        $bapp = $this->applyBappDocumentContextScope(
            BappSeTermin::query()
                ->where('spk_id', $spkId)
                ->where('termin', $terminNumber),
            $documentType,
            $contextReplacementTerminCount,
        )->firstOrFail();

        $config = self::TERMIN_CONFIG[$terminNumber];
        $alokasi = $spk->alokasiPetugas;
        $peran = $alokasi?->peran ?? 'pcl_ppl';
        $petugas = $spk->petugas;

        $viewData = $this->buildPdfViewData($bapp, $spk, $peran, $petugas, $config);

        $pdf = Pdf::loadView('bapp-se', $viewData);
        $pdf->setPaper('A4', 'portrait');

        $nomorBappSafe = preg_replace('/[\/\\\:*?"<>|]/', '-', $bapp->nomor_bapp ?? ('BAPP-'.$config['roman']));
        $filename = sprintf(
            'BAPP-Termin%s-%s-%s.pdf',
            $config['roman'],
            $nomorBappSafe,
            now()->format('YmdHis')
        );

        $merged = $this->buildMergedPdf($viewData);

        // Save to storage
        $storagePath = 'bapp-se/'.$bapp->tahun.'/termin-'.$terminNumber.'/'.$spk->id.'.pdf';
        Storage::put($storagePath, $merged);

        BappSeTermin::query()->where('id', $bapp->id)->update(['file_path' => $storagePath]);

        return response($merged, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function generateBatch(Request $request): RedirectResponse
    {
        if (! $this->hasBappTerminTable()) {
            return back()->with('error', 'Tabel BAPP belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $tahun = ActiveYearService::get();
        $terminNumber = (int) $request->input('termin', 1);
        $documentType = $this->resolveDocumentType($request);
        $replacementTerminCount = $this->resolveReplacementTerminCount($request);
        $contextReplacementTerminCount = $this->getContextReplacementTerminCount($documentType, $replacementTerminCount);

        if (! isset(self::TERMIN_CONFIG[$terminNumber])) {
            return back()->with('error', 'Termin tidak valid.');
        }

        $config = self::TERMIN_CONFIG[$terminNumber];
        $currentMonth = (int) now()->format('m');
        $selectedSpkHashedIds = collect($request->input('spk_hashed_ids', []))
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->values()
            ->all();

        if ($currentMonth < $config['bulan']) {
            return back()->with('error', 'Generate BAPP Termin '.$config['roman'].' hanya dapat dilakukan mulai bulan '.$config['bulan_label'].'.');
        }

        $spks = $this->getSpksForBappContext($tahun, $terminNumber, $documentType);
        if (! empty($selectedSpkHashedIds)) {
            $spks = $spks->filter(fn (Spk $spk) => in_array($spk->hashed_id, $selectedSpkHashedIds, true))->values();
        }

        if ($spks->isEmpty()) {
            return back()->with('error', 'Tidak ada SPK terpilih untuk digenerate.');
        }

        $nomorBappMap = $this->generateNomorBappMap(
            $spks,
            $config['roman'],
            $tahun,
            $documentType,
            $contextReplacementTerminCount,
        );
        $generated = 0;

        foreach ($spks as $spk) {
            $bapp = $this->applyBappDocumentContextScope(
                BappSeTermin::query()
                    ->where('spk_id', $spk->id)
                    ->where('termin', $terminNumber),
                $documentType,
                $contextReplacementTerminCount,
            )->first();

            if (! $bapp || $bapp->realisasi_sls === null) {
                continue;
            }

            try {
                // Ensure nomor_bapp is set
                if (empty($bapp->nomor_bapp) && isset($nomorBappMap[$spk->id])) {
                    BappSeTermin::query()->where('id', $bapp->id)->update(['nomor_bapp' => $nomorBappMap[$spk->id]]);
                    $bapp->nomor_bapp = $nomorBappMap[$spk->id];
                }

                $alokasi = $spk->alokasiPetugas;
                $peran = $alokasi?->peran ?? 'pcl_ppl';
                $petugas = $spk->petugas;

                $viewData = $this->buildPdfViewData($bapp, $spk, $peran, $petugas, $config);

                $storagePath = 'bapp-se/'.$tahun.'/termin-'.$terminNumber.'/'.$spk->id.'.pdf';
                Storage::put($storagePath, $this->buildMergedPdf($viewData));

                BappSeTermin::query()->where('id', $bapp->id)->update(['file_path' => $storagePath]);
                $generated++;
            } catch (\Throwable $e) {
                Log::error('Error generating BAPP for SPK '.$spk->id.': '.$e->getMessage());
            }
        }

        return back()->with('success', "Berhasil generate {$generated} BAPP Termin {$config['roman']}.");
    }

    public function preview(BappSeTermin $bapp): \Illuminate\Http\Response
    {
        $bapp->loadMissing(['spk.petugas', 'spk.alokasiPetugas.periodeAlokasi.kegiatan']);
        $spk = $bapp->spk;

        if (! $spk) {
            abort(404, 'SPK tidak ditemukan.');
        }

        $terminNumber = $bapp->termin;
        $config = self::TERMIN_CONFIG[$terminNumber] ?? self::TERMIN_CONFIG[1];
        $alokasi = $spk->alokasiPetugas;
        $peran = $alokasi?->peran ?? 'pcl_ppl';
        $petugas = $spk->petugas;

        $viewData = $this->buildPdfViewData($bapp, $spk, $peran, $petugas, $config);

        $merged = $this->buildMergedPdf($viewData);

        return response($merged, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.sprintf(
                'BAPP-Termin%s-%s-preview.pdf',
                $config['roman'],
                preg_replace('/[\/\\\\:*?"<>|]/', '-', $bapp->nomor_bapp ?? $spk->nomor_spk)
            ).'"',
        ]);
    }
}
