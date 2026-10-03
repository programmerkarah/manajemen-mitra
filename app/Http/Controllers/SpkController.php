<?php

namespace App\Http\Controllers;

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
use App\Services\SensusEkonomiReplacementReadService;
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
use App\Http\Controllers\Concerns\SpkPublicPreviewSupport;
use App\Http\Controllers\Concerns\SpkAddendumSupport;
use App\Http\Controllers\Concerns\SpkPdfSupport;
use App\Http\Controllers\Concerns\SpkDownloadSupport;
use App\Http\Controllers\Concerns\SpkScopeSupport;
use App\Http\Controllers\Concerns\SpkSensusSupport;
use App\Http\Controllers\Concerns\SpkChangeDetectionSupport;
use App\Http\Controllers\Concerns\SpkIndexActions;
use App\Http\Controllers\Concerns\SpkGenerationActions;

class SpkController extends Controller
{
    use SpkGenerationActions;
    use SpkIndexActions;
    use SpkChangeDetectionSupport;
    use SpkSensusSupport;
    use SpkScopeSupport;
    use SpkDownloadSupport;
    use SpkPdfSupport;
    use SpkAddendumSupport;
    use SpkPublicPreviewSupport;
    private readonly SpkActionDecisionService $spkActionDecisionService;

    public function __construct(
        ?SpkActionDecisionService $spkActionDecisionService = null,
    ) {
        $this->spkActionDecisionService = $spkActionDecisionService
            ?? app(SpkActionDecisionService::class);
    }

    /**
     * Display a listing of the resource.
     */


    /**
     * Display list of SPKs for a specific month
     */


    /**
     * Show SPK for a specific month with petugas list (GET version)
     */


    /**
     * Show SPK for a specific month with petugas list (POST version)
     */


    /**
     * Internal method to render ShowByMonth view
     */


    /**
     * Download all SPK files in a month as ZIP
     */


    /**
     * Download all SPK files for a specific kegiatan in a periode as ZIP
     */


    /**
     * Download all SPK files for a specific kegiatan in a specific month as ZIP
     * Used by ketua tim to download all SPK for their activity
     */


    /**
     * Upload signed SPK document
     */


    /**
     * Regenerate the same SPK document in place after a technical application error.
     * This updates only the current record and does not create a new database row.
     * If a signed PDF was already uploaded, the document must be re-scanned and re-uploaded.
     */


    /**
     * Get next nomor urut for SPK based on year
     */


    /**
     * Extract nomor urut from SPK number.
     */


    /**
     * Public page for petugas to find and preview/download SPK draft document.
     */


    /**
     * Public preview/download action for SPK.
     */


    /**
     * Serve a manually uploaded replacement document using the same preview/download
     * contract as the regular /mitra documents.
     *
     * @param array<string,mixed> $validated
     */


    /**
     * @return array{filename:string,content?:string,cache_key:string,is_protected:bool,protected_path?:string}|null
     */


    /**
     * @param  array<int,string>  $absolutePdfPaths
     */


    /**
     * @return array{survei_periods:array<int,array{value:string,label:string}>,sensus_kegiatans:array<int,array{value:string,label:string}>}
     */


    /**
     * @param  Collection<int,AlokasiPetugas>  $alokasiCollection
     * @return array<string,string>
     */


    /**
     * @param  array<string,mixed>  $validated
     */


    /**
     * @param  array<string,mixed>  $validated
     */


    /**
     * Common PDF protection and serving logic for public preview.
     *
     * @param  array<string,mixed>  $validated
     */


    /**
     * Show the form to generate SPKs for a periode
     */


    /**
     * Show the form to generate Addendum SPKs for a periode with allocation changes.
     */


    /**
     * Determine whether two allocation snapshots differ on any kegiatan that exists in both snapshots.
     *
     * New kegiatan are intentionally ignored here so they can be handled by regenerate logic.
     *
     * @param  array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>  $referenceSnapshot
     * @param  array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>  $currentSnapshot
     */


    /**
     * Preview addendum SPK PDF
     */


    /**
     * Generate and save addendum SPK
     */


    /**
     * Display the specified SPK
     */


    /**
     * Download all SPK previews in a periode as ZIP without persisting generated documents.
     */


    /**
     * Merge main SPK PDFs for selected petugas into a single PDF, sorted alphabetically.
     */


    /**
     * Merge lampiran PDFs for selected petugas into a single PDF, sorted alphabetically.
     */


    /**
     * Decode and sort preview items from JSON by petugas_nama, falling back to nomor_spk.
     *
     * @return Collection<int, array{petugas_hashed_id:string,nomor_spk:string,petugas_nama?:string}>
     */


    /**
     * Build SPK main (Pasal-based) PDF binary for a single petugas.
     */


    /**
     * Build SPK lampiran PDF binary for a single petugas.
     */


    /**
     * Preview SPK for a petugas in a periode
     */


    /**
     * @return array{filename:string,content:string}|null
     */


    /**
     * Preview SPK Main only
     */


    /**
     * Preview SPK Lampiran only
     */


    /**
     * Generate SPK PDF and save to database
     */


    /**
     * Helper: Get bulan label
     */


    /**
     * @param  Collection<int,AlokasiPetugas>  $alokasiGroup
     * @return array{alokasi_id:int,alokasi_ids:array<int,int>,alokasi_details:array<int,array{alokasi_id:int,kegiatan_id:int,kegiatan_kode:string,kegiatan_nama:string,peran:string}>,alokasi_hashed_id:string,petugas:array{id:int,hashed_id:string,nama:string,nik:string,jenis_petugas:string},jumlah_kegiatan:int,kegiatan_list:array<int,array{kegiatan_id:int,kegiatan_kode:string,kegiatan_nama:string,peran:string}>,perubahan:array<int,string>,total_honor:float}
     */


    /**
     * @param  array{petugas:array{id:int},alokasi_ids:array<int,int>,alokasi_details:array<int,array{alokasi_id:int,kegiatan_id:int,kegiatan_kode:string,kegiatan_nama:string,peran:string}>,kegiatan_list:array<int,array{kegiatan_id:int,kegiatan_kode:string,kegiatan_nama:string,peran:string}>,total_honor:float}  $currentItem
     * @param  array<int, array{nomor_spk:string,kegiatan_list?:array<int,array{kegiatan_id:int,kegiatan_kode:string,kegiatan_nama:string,peran:string}>,total_honor?:float}>  $existingSpkRecords
     * @return array<int, string>
     */


    /**
     * Calculate total honor for petugas
     */


    /**
     * @param  Collection<int, PeriodeAlokasi>  $monthPeriodes
     */


    /**
     * @param  array<int, array<string, mixed>>  $uraianTugas
     * @return array<string, mixed>
     */


    /**
     * @param  array<int, array<string, mixed>>  $uraianTugas
     * @return array<string, mixed>
     */


    /**
     * @return array<int, array<string, mixed>>
     */


    /**
     * @return array{selected_rows:int,prelist_total:int}
     */
    /**
     * @param  array<int, int>  $frameMuatanTotals
     * @return array{selected_rows: int}
     */


    /**
     * @param  array<int, int>  $frameMuatanTotals
     */


    /**
     * @return array{selected_rows:int,prelist_total:int,total_volume:int,narrative:string}
     */


    /**
     * Get uraian tugas details
     */


    /**
     * Get peran kegiatan label based on role and phase
     */


    /**
     * Get beban anggaran (MAK)
     */


    /**
     * Get peran label
     */


    /**
     * Detect work type from all allocations
     * Returns: 'lapangan', 'pengolahan', or 'lapangan_pengolahan'
     */


    /**
     * Generate addendum PDF content
     */


    /**
     * Bulk generate SPK for all non-organik petugas in a periode
     */


    /**
     * Check if there are new kegiatan/petugas added after SPK was generated
     */


    /**
     * Check if there are petugas with revisions who don't have addendum yet
     */


    /**
     * Check if there are allocation changes to petugas who already have addendum
     */


    /**
     * Analyze allocation delta for a petugas in a month.
     *
     * @return array{has_new_kegiatan_added:bool,has_allocation_change:bool,has_perubahan_status:bool,is_allocation_incomplete:bool,has_honor_mismatch:bool}
     */


    /**
     * @return array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>
     */


    /**
     * Detect if there's a meaningful change between perubahan and direvisi allocations.
     *
     * A meaningful change means the perubahan allocation has different values
     * (peran, jumlah_satuan, total_honor) compared to the corresponding direvisi
     * allocation for the same kegiatan.
     *
     * This is more accurate than comparing document snapshots because the document
     * may already contain both direvisi and perubahan allocations for the same kegiatan.
     */


    /**
     * Check if two allocation snapshots match (have same effective values).
     *
     * @param  array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>  $snapshot1
     * @param  array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>  $snapshot2
     */


    /**
     * Build latest effective allocation snapshot keyed by kegiatan_id.
     *
     * @return array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>
     */


    /**
     * Get list of petugas names for a specific month (sorted alphabetically)
     */

}
