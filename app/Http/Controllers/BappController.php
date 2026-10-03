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
use App\Http\Controllers\Concerns\BappFileActions;

class BappController extends Controller
{
    use BappFileActions;
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


    /**
     * Download the Excel template for realisasi input.
     */


    /**
     * Import realisasi from Excel.
     */


    /**
     * Upload Fasih screenshot for a specific BAPP.
     */


    /**
     * Show detail page for a specific termin: list all petugas with BAPP status.
     */


    /**
     * Upload BAPP SE2026 sebagai dokumen manual.
     */


    /**
     * Upload signed PDF for a specific BAPP record.
     */


    /**
     * Download signed BAPP file.
     */


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
