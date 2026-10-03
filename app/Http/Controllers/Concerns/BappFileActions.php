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

trait BappFileActions
{
    public function download(BappSeTermin $bapp): \Illuminate\Http\Response|BinaryFileResponse
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

        $filename = sprintf(
            'BAPP-Termin%s-%s.pdf',
            $config['roman'],
            preg_replace('/[\/\\\\:*?"<>|]/', '-', $bapp->nomor_bapp ?? $spk->nomor_spk)
        );

        return response($this->buildMergedPdf($viewData), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function downloadTemplate(Request $request): BinaryFileResponse
    {
        $tahun = ActiveYearService::get();
        $documentType = $this->resolveDocumentType($request);
        $terminNumber = (int) $request->input('termin', 1);
        if (! isset(self::TERMIN_CONFIG[$terminNumber])) {
            $terminNumber = 1;
        }

        $kegiatan = $this->getSensusEkonomiKegiatan();
        $unitSampelItems = $this->getUnitSampelItems($kegiatan);
        $spks = $this->getSpksForBappContext($tahun, $terminNumber, $documentType);

        $rows = $spks->map(function (Spk $spk): array {
            return [
                'nomor_spk' => $spk->nomor_spk,
                'nik_petugas' => $spk->petugas?->nik ?? '',
                'nama_petugas' => $spk->petugas?->nama ?? '',
            ];
        })->all();

        return Excel::download(
            new BappSeRealisasiTemplateExport($rows, $unitSampelItems),
            'Template-Realisasi-BAPP-SE2026.xlsx'
        );
    }

    public function importRealisasi(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'termin' => ['required', 'integer', 'in:1,2'],
        ]);

        $terminNumber = (int) $request->input('termin');
        $tahun = ActiveYearService::get();
        $kegiatan = $this->getSensusEkonomiKegiatan();
        $unitSampelItems = $this->getUnitSampelItems($kegiatan);

        $import = new BappSeRealisasiImport;
        Excel::import($import, $request->file('file'));
        $rows = $import->rows();

        if (empty($rows)) {
            return back()->with('error', 'File Excel tidak memiliki data valid.');
        }

        $previewRows = [];
        $unmatchedSpks = [];

        foreach ($rows as $row) {
            $nomorSpk = $row['nomor_spk'] ?? '';
            $nikPetugas = $row['nik_petugas'] ?? '';

            $spk = Spk::query()
                ->where('nomor_spk', $nomorSpk)
                ->orWhereHas('petugas', function ($q) use ($nikPetugas): void {
                    if ($nikPetugas) {
                        $q->where('nik', 'like', '%'.$nikPetugas.'%');
                    }
                })
                ->where('lampiran_template', 'sensus_ekonomi')
                ->where('addendum_number', 0)
                ->first();

            if (! $spk && $nomorSpk) {
                $spk = Spk::query()->where('nomor_spk', $nomorSpk)->first();
            }

            if (! $spk) {
                if ($nomorSpk) {
                    $unmatchedSpks[] = $nomorSpk;
                }

                continue;
            }

            $targetData = $this->buildTargetForTermin($spk, $terminNumber);
            $realisasiUnitSampel = [];

            if (! empty($row['realisasi_unit_sampel'])) {
                foreach ($row['realisasi_unit_sampel'] as $unitKey => $count) {
                    $realisasiUnitSampel[$unitKey] = max(0, (int) $count);
                }
            }

            $previewRows[] = [
                'spk_id' => $spk->id,
                'spk_hashed_id' => Hashids::encode($spk->id),
                'nomor_spk' => $spk->nomor_spk,
                'petugas_nama' => $spk->petugas?->nama,
                'realisasi_sls' => $row['realisasi_sls'] !== null ? max(0, (int) $row['realisasi_sls']) : null,
                'realisasi_unit_sampel' => $realisasiUnitSampel,
                'target_sls' => $targetData['target_sls'],
                'target_unit_sampel' => $targetData['target_unit_sampel'],
            ];
        }

        if (empty($previewRows)) {
            $message = 'Tidak ada SPK yang cocok dalam file Excel.';
            if (! empty($unmatchedSpks)) {
                $message .= ' SPK tidak ditemukan: '.implode(', ', array_slice($unmatchedSpks, 0, 5));
            }

            return back()->with('error', $message);
        }

        /** @var array{preview_rows: array<int, array<string, mixed>>, unmatched_spks: string[]} $importPreview */
        $importPreview = [
            'preview_rows' => $previewRows,
            'unmatched_spks' => $unmatchedSpks,
        ];

        return back()->with('import_preview', $importPreview);
    }

    public function uploadFasihScreenshot(BappSeTermin $bapp, Request $request): RedirectResponse
    {
        $request->validate([
            'screenshot' => ['required', 'file', 'image', 'max:5120'],
        ]);

        $file = $request->file('screenshot');
        $path = $file->store('bapp-se/screenshots/'.$bapp->tahun, 'public');

        BappSeTermin::query()->where('id', $bapp->id)->update([
            'fasih_screenshot_path' => $path,
        ]);

        return back()->with('success', 'Screenshot berhasil diupload.');
    }

    public function uploadManual(Request $request): RedirectResponse
    {
        if (! $this->userCanAccessBapp($request)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses.');
        }

        $validated = $request->validate([
            'spk_hashed_id' => ['required', 'string'],
            'termin' => ['required', 'integer', 'in:1,2'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'nomor_bapp' => ['required', 'string', 'regex:/^\d+$/', 'max:12'],
            'tanggal_bapp' => ['nullable', 'date'],
            'document_type' => ['required', 'in:regular,stopped_petugas,replacement_pkpp'],
            'replacement_termin_count' => ['nullable', 'integer', 'in:0,1,2'],
        ]);

        $spkId = Hashids::decode((string) $validated['spk_hashed_id'])[0] ?? null;
        if (! $spkId) {
            return back()->with('error', 'Perjanjian Kerja tidak valid.');
        }

        $tahun = ActiveYearService::get();
        $termin = (int) $validated['termin'];
        $config = self::TERMIN_CONFIG[$termin];
        $documentType = $this->resolveDocumentType($request);
        $replacementTerminCount = $this->resolveReplacementTerminCount($request);
        $contextReplacementTerminCount = $this->getContextReplacementTerminCount(
            $documentType,
            $replacementTerminCount,
        );

        if ($documentType === 'replacement_pkpp' && $contextReplacementTerminCount === 1 && $termin === 2) {
            return back()->with('error', 'PKPP ini hanya memerlukan satu BAPP.');
        }
        $spk = Spk::query()
            ->with(['petugas', 'alokasiPetugas.periodeAlokasi.kegiatan.ketuaTim'])
            ->find($spkId);

        if (! $spk || ! $this->getSpksForBappContext(
            $tahun,
            $termin,
            $documentType,
            $contextReplacementTerminCount,
        )->contains('id', $spk->id)) {
            return back()->with('error', 'Perjanjian Kerja tidak termasuk alur BAPP SE2026 yang dipilih.');
        }

        $existing = $this->applyBappDocumentContextScope(
            BappSeTermin::query()
                ->where('spk_id', $spk->id)
                ->where('termin', $termin)
                ->where('tahun', $tahun),
            $documentType,
            $contextReplacementTerminCount,
        )->first();

        if ($existing && filled($existing->signed_file_path)) {
            Storage::disk('public')->delete($existing->signed_file_path);
        }

        $replacementId = $documentType === 'replacement_pkpp'
            ? ($this->getReplacementIdByReplacementSpkId($contextReplacementTerminCount)[$spk->id] ?? null)
            : null;
        $replacement = $replacementId
            ? SensusEkonomiPetugasReplacement::query()
                ->with('petugasPengganti')
                ->find($replacementId)
            : null;
        $petugas = $documentType === 'replacement_pkpp'
            ? $replacement?->petugasPengganti
            : $spk->petugas;
        $kegiatan = $this->getSensusEkonomiKegiatan();
        $ppk = $this->getPpk();
        $nomorBapp = $this->formatManualBappNumber(
            trim((string) $validated['nomor_bapp']),
            $termin,
            $tahun,
            $documentType,
            $contextReplacementTerminCount,
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nomorBapp);
        $path = $request->file('file')->storeAs(
            'bapp-se/manual/'.$tahun.'/termin-'.$termin,
            'BAPP_'.$safeName.'_'.time().'.pdf',
            'public'
        );

        $bapp = $existing ?? new BappSeTermin();
        $bapp->spk_id = $spk->id;
        $bapp->petugas_id = $petugas?->id;
        $bapp->termin = $termin;
        $bapp->document_type = $documentType;
        $bapp->replacement_termin_count = $contextReplacementTerminCount;
        $bapp->replacement_id = $documentType === 'stopped_petugas'
            ? ($this->getStoppedReplacementIdBySpkId($termin, $tahun)[$spk->id] ?? null)
            : ($documentType === 'replacement_pkpp' ? $replacementId : null);
        $bapp->bulan = $config['bulan'];
        $bapp->tahun = $tahun;
        $bapp->persentase = $config['persentase'];
        $bapp->nomor_bapp = $nomorBapp;
        $bapp->tanggal_bapp = $validated['tanggal_bapp'] ?? ($existing?->tanggal_bapp ?? now()->toDateString());
        $bapp->nama_ketua_tim = $kegiatan?->ketuaTim?->name;
        $bapp->nip_ketua_tim = $this->getNipKetuaTim($kegiatan);
        $bapp->nama_ppk = $ppk ? $this->stripGelar($ppk->nama) : null;
        $bapp->nip_ppk = $ppk?->nip;
        $bapp->jabatan_ppk = $ppk?->jabatan;
        $bapp->nama_kabkota = config('app.instansi_kabupaten', '');
        $bapp->signed_file_path = $path;
        $bapp->signed_uploaded_at = now();
        $bapp->created_by = $bapp->created_by ?: Auth::id();
        $bapp->save();

        $successLabel = $documentType === 'replacement_pkpp'
            && $contextReplacementTerminCount === 1
                ? 'BAPP petugas pengganti berhasil diunggah.'
                : 'BAPP Termin '.$config['roman'].' berhasil diunggah manual.';

        return back()->with('success', $successLabel);
    }

    public function uploadSignedBapp(BappSeTermin $bapp, Request $request): RedirectResponse
    {
        if (! $this->userCanAccessBapp($request)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        if (filled($bapp->signed_file_path)) {
            Storage::disk('public')->delete($bapp->signed_file_path);
        }

        $file = $request->file('file');
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($bapp->nomor_bapp ?? 'BAPP'));
        $filename = 'BAPP_SIGNED_'.$safeName.'_'.time().'.pdf';
        $path = $file->storeAs('bapp-se/signed/'.$bapp->tahun, $filename, 'public');

        $bapp->update([
            'signed_file_path' => $path,
            'signed_uploaded_at' => now(),
        ]);

        return back()->with('success', 'BAPP bertanda tangan berhasil diunggah.');
    }

    public function downloadSigned(BappSeTermin $bapp): BinaryFileResponse|\Illuminate\Http\Response
    {
        $absolutePath = Storage::disk('public')->path($bapp->signed_file_path ?? '');

        if (! $bapp->signed_file_path || ! file_exists($absolutePath)) {
            abort(404, 'File bertanda tangan belum tersedia.');
        }

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($bapp->nomor_bapp ?? 'BAPP'));

        return response()->download($absolutePath, 'BAPP_SIGNED_'.$safeName.'.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
