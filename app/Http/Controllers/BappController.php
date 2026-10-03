<?php

namespace App\Http\Controllers;

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
use App\Http\Controllers\Concerns\BappDocumentContextSupport;
use App\Http\Controllers\Concerns\BappNumberingSupport;
use App\Http\Controllers\Concerns\BappFrameSupport;
use App\Http\Controllers\Concerns\BappSensusSupport;
use App\Http\Controllers\Concerns\BappPdfSupport;
use App\Http\Controllers\Concerns\BappIndexActions;
use App\Http\Controllers\Concerns\BappGenerationActions;

class BappController extends Controller
{
    use BappGenerationActions;
    use BappIndexActions;
    use BappPdfSupport;
    use BappSensusSupport;
    use BappFrameSupport;
    use BappNumberingSupport;
    use BappDocumentContextSupport;
    private static ?bool $hasBappTerminTable = null;

    private static bool $hasLoggedMissingBappTerminTable = false;

    private static ?bool $supportsBappDocumentContextColumns = null;

    private static bool $hasLoggedMissingBappDocumentContextColumns = false;

    private static bool $hasLoggedMissingBappSpkSourceTables = false;

    private const TERMIN_CONFIG = [
        1 => ['bulan' => 7, 'bulan_label' => 'Juli', 'persentase' => 40, 'roman' => 'I', 'tanggal_min' => '%d-07-15', 'tanggal_max' => '%d-07-31', 'tanggal_default' => null],
        2 => ['bulan' => 8, 'bulan_label' => 'Agustus', 'persentase' => 60, 'roman' => 'II', 'tanggal_min' => '%d-08-01', 'tanggal_max' => '%d-08-31', 'tanggal_default' => '%d-08-31'],
    ];

    private const SE_PENDATAAN_ROLES = ['pcl_ppl', 'pcl', 'ppl'];

    private const SE_PEMERIKSAAN_ROLES = ['pml'];

    private const DOCUMENT_TYPES = [
        'regular',
        'stopped_petugas',
        'replacement_pkpp',
    ];

    /**
     * Strip academic/professional titles from a name and apply title case.
     */


    /**
     * Get NIP for ketua tim: first from User.nip, then match Petugas by name and read nik (auto-decrypted).
     */


    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */


    /**
     * Resolve the tanggal_bapp for a realisasi entry.
     *
     * @param  array<string, mixed>  $entry
     */


    /**
     * Build a map of spk_id → auto-generated nomor BAPP.
     * SPKs are sorted alphabetically by petugas name.
     * Format: B-{NNN}/BAPP-{roman}-SE2026/1373/PL.200/{tahun}
     *
     * @param  Collection<int, Spk>  $spks
     * @return array<int, string>
     */


    /**
     * Determine the "jenis_pihak_kedua" based on peran.
     */
    private function getJenisPihakKedua(string $peran): string
    {
        if (in_array($peran, self::SE_PEMERIKSAAN_ROLES, true)) {
            return 'pemeriksa_lapangan';
        }

        return 'petugas_lapangan';
    }

    /**
     * Get SE2026 kegiatan.
     */
    /**
     * Check whether the currently authenticated user may access BAPP pages.
     * Admins and operators always can. Ketua_tim can access if they are the
     * assigned ketua tim or pj lainnya for the Sensus Ekonomi kegiatan.
     */
    private function userCanAccessBapp(Request $request): bool
    {
        $user = effectiveUser($request);
        if (! $user) {
            return false;
        }

        $role = $user->getActiveRole()?->name;

        if (in_array($role, ['admin', 'operator'], true)) {
            return true;
        }

        if ($role !== 'ketua_tim') {
            return false;
        }

        $seKegiatan = $this->getSensusEkonomiKegiatan();

        return $seKegiatan !== null
            && (
                $seKegiatan->ketua_tim_user_id === $user->id
                || $seKegiatan->pj_lainnya_id === $user->id
            );
    }


    /**
     * Get all SE2026 SPKs (original, non-addendum) in current active year.
     *
     * @return Collection<int, Spk>
     */


    /**
     * @return Collection<int, SensusEkonomiPetugasReplacement>
     */


    /**
     * @return Collection<int, Spk>
     */


    /**
     * @return array<int, int>
     */


    /**
     * @return array<int, int>
     */


    /**
     * Build target_sls and target_unit_sampel for a given SPK and termin.
     *
     * @return array{target_sls: int, target_unit_sampel: array<string, int>}
     */


    /**
     * Get PPK penandatangan.
     */
    private function getPpk(): ?Penandatangan
    {
        return Penandatangan::query()
            ->where('jenis_penandatangan', 'ppk')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get unit sampel items for SE kegiatan.
     *
     * @return array<int, array{id:int, nama:string}>
     */


    /**
     * @return array<int, int>
     */


    /**
     * @return array<int, array{id:int, nama:string|null}>
     */


    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<int, array{key:string, label:string, value:string}>
     */


    /**
     * @param  array<string, mixed>|null  $targetUnitSampel
     */


    /**
     * @param  Collection<int, Spk>  $spks
     * @return array{
     *   enabled:bool,
     *   default_periode_alokasi_id:int|null,
     *   stopped_petugas_options:array<int, array{id:int, nama:string|null}>,
     *   replacement_petugas_options:array<int, array{id:int, nama:string|null}>,
     *   spk_lama_options_by_stopped_petugas:array<int, array<int, array{id:int, hashed_id:string, nomor_spk:string, periode_alokasi_id:int|null, wilayah_keys:array<int, string>}>>,
     *   pml_cover_options_by_spk_lama_id:array<int, array<int, array{id:int, nama:string|null}>>,
     *   frame_detail_options_by_spk_lama_id:array<int, array<int, array{alokasi_petugas_frame_sampel_id:int, kegiatan_frame_sampel_id:int|null, target_awal:float, metadata_items:array<int, array{key:string, label:string, value:string}>}>>,
     *   spk_options:array<int, array{id:int, hashed_id:string, nomor_spk:string, petugas_id:int|null, petugas_nama:string|null, periode_alokasi_id:int|null}>,
     *   replacement_options:array<int, array{id:int, hashed_id:string, periode_alokasi_id:int, petugas_berhenti_id:int, petugas_berhenti_nama:string|null, petugas_pengganti_id:int|null, petugas_pengganti_nama:string|null, spk_lama_id:int|null, target_sisa:float, status:string}>,
     *   next_pkpp_nomor_preview:string|null
     * }
     */


    /**
     * Index: show BAPP status per termin.
     */


    /**
     * Show the realisasi input form for a specific termin.
     */


    /**
     * Save realisasi data for multiple SPKs.
     */


    /**
     * Generate BAPP PDF for a specific SPK+termin.
     */


    /**
     * Generate BAPP PDFs for all SPKs in a termin (batch).
     */


    /**
     * Preview a BAPP PDF inline in browser.
     */


    /**
     * Download a specific BAPP PDF (re-generate from saved data).
     */
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

    /**
     * Download the Excel template for realisasi input.
     */
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

    /**
     * Import realisasi from Excel.
     */
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

    /**
     * Upload Fasih screenshot for a specific BAPP.
     */
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

    /**
     * Show detail page for a specific termin: list all petugas with BAPP status.
     */


    /**
     * Upload BAPP SE2026 sebagai dokumen manual.
     */
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

    /**
     * Upload signed PDF for a specific BAPP record.
     */
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

    /**
     * Download signed BAPP file.
     */
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

    /**
     * Build the view data array for PDF rendering.
     *
     * @param  array{bulan:int, bulan_label:string, persentase:int, roman:string}  $config
     * @return array<string, mixed>
     */


    /**
     * Generate and merge three PDF parts into one document:
     *   Part 1 — portrait pages 1-2 (main BAPP content + signatures)
     *   Part 2 — landscape page 3 (table: DAFTAR URAIAN PEKERJAAN)
     *   Part 3 — portrait page 4 (bukti pencapaian + final signatures)
     *
     * @param  array<string, mixed>  $viewData
     */

}
