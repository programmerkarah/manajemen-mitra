<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\BappSeTermin;
use App\Models\Bast;
use App\Models\BastKegiatan;
use App\Models\BastNumberAllocation;
use App\Models\BastPetugas;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use App\Services\PdfMergerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\Tcpdf\Fpdi;
use App\Http\Controllers\Concerns\BastDocumentStorageSupport;
use App\Http\Controllers\Concerns\BastDocumentActions;
use App\Http\Controllers\Concerns\BastSensusSupport;
use App\Http\Controllers\Concerns\BastAccessSupport;
use App\Http\Controllers\Concerns\BastAllocationQuerySupport;
use App\Http\Controllers\Concerns\BastPdfSupport;
use App\Http\Controllers\Concerns\BastIndexActions;
use App\Http\Controllers\Concerns\BastGenerationActions;
use App\Http\Controllers\Concerns\BastCrudActions;

class BastController extends Controller
{
    use BastCrudActions;
    use BastGenerationActions;
    use BastIndexActions;
    use BastPdfSupport;
    use BastAllocationQuerySupport;
    use BastAccessSupport;
    use BastSensusSupport;
    use BastDocumentActions;
    use BastDocumentStorageSupport;
    // Role constants
    private const PENDATAAN_ROLES = ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'];

    private const PENGOLAHAN_ROLES = ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'];


    /**
     * @param  array<int, array<string, mixed>>  $kegiatanList
     * @param  iterable<int, BastKegiatan>  $records
     * @return array<int, array<string, mixed>>
     */


    /**
     * @param  array<int, array<string, mixed>>  $kegiatanList
     * @return array<int, array<string, mixed>>
     */


    /**
     * @param  array<string, mixed>  $values
     * @return array<string, int>
     */


    /**
     * @return Collection<int, array{id:int, nama:string}>
     */


    /**
     * @return array{bulan:int,tahun:int,bulan_label:string}
     */


    /**
     * @return array{file_path:?string,signed_file_path:?string,fasih_screenshot_path:?string,generated_at:?string,signed_uploaded_at:?string,status:string,can_upload_signed:bool}
     */


    /**
     * Sort lampiran by earliest tanggal_selesai and assign sequential lampiran number.
     * When tanggal_selesai is the same, use kode_kegiatan then nama_kegiatan for stable order.
     *
     * @param  array<int, array<string, mixed>>  $kegiatanList
     * @return array<int, array<string, mixed>>
     */


    /**
     * Ensure bast_kegiatan records exist for all kegiatan payload rows.
     * This lets Detail BAST show per-kegiatan lampiran actions even before generation.
     *
     * @param  array<int, array<string, mixed>>  $kegiatanList
     */


    /**
     * Check if any petugas has pendataan allocation with hasil_pendataan_lapangan > 0
     */


    /**
     * Check if any petugas has listing allocation with hasil_listing > 0
     */


    /**
     * Determine if the BAST should use the FASIH clause.
     *
     * Returns true if any non-pengolahan alokasi belongs to a kegiatan
     * that uses CAPI as its metode_pendataan. If all non-pengolahan alokasi
     * use PAPI (or metode is null / not yet set) the clause is omitted.
     *
     * @param  iterable<AlokasiPetugas>  $allAlokasi
     */


    /**
     * @return array<int, string>
     */


    /**
     * Resolve preview-time SE input and require complete keluarga/usaha values.
     *
     * @return array{muatan_input:int|null, muatan_prelist:int|null, realisasi_unit_sampel:array<string,int>}|null
     */


    /**
     * @return array{
     *     target_jumlah_frame_sampel:int|null,
     *     target_muatan_prelist_keluarga:int|null,
     *     target_muatan_prelist_usaha:int|null,
     *     hasil_jumlah_frame_sampel:int|null,
     *     hasil_realisasi_keluarga:int|null,
     *     hasil_realisasi_usaha:int|null
     * }
     */


    /**
     * @return array<int, array{no:int,nama_kecamatan:string,nama_desa:string,jumlah_sls:string,muatan_label:string}>
     */


    /**
     * Get effective allocation by kegiatan using priority:
     * perubahan > direvisi > disetujui > dikirim
     */


    /**
     * Retrieve BAPP SE Termin data for a given SPK, summing realisasi from Termin I + II.
     * Returns the combined realisasi unit sampel, target SLS, screenshot from Termin II,
     * and whether Termin II is complete (has realisasi + screenshot).
     *
     * @return array{
     *     realisasi_unit_sampel: array<string, int>,
     *     target_sls: int|null,
     *     fasih_screenshot_path: string|null,
     *     termin_ii_complete: bool,
     *     termin_ii_has_screenshot: bool,
     * }
     */


    /**
     * @return array{keluarga:int,usaha:int,total:int}
     */


    /**
     * @return Collection<int, Spk>
     */


    /**
     * Display a listing of the resource.
     * Menampilkan periode bulan (Januari-Desember) dengan informasi BAST yang sudah/belum dibuat
     */


    /**
     * List all BAST for a specific month with filter
     */


    /**
     * Upload BAST Sensus Ekonomi 2026 sebagai dokumen manual.
     */


    /**
     * Show form to create BAST for a specific month
     * List all SPK in that month that don't have BAST yet
     */


    /**
     * Generate BAST secara batch untuk multiple SPK
     * Prinsip: 1 SPK = 1 BAST dengan lampiran per kegiatan
     */


    /**
     * Prepare BAST data for export (sama dengan preview)
     */


    /**
     * Prepare BAST data for PDF export
     */


    /**
     * Generate nomor BAST otomatis untuk SPK
     */


    /**
     * Convert month number to Roman numeral
     */


    /**
     * Generate uraian pekerjaan berdasarkan jenis penugasan, tahapan, dan periode
     */


    /**
     * Preview BAST untuk specific SPK
     */


    /**
     * Preview lampiran BAST dari bast-lampiran-spk.blade.php.
     * Admin/operator: semua kegiatan dari SPK.
     * Ketua tim: harus menentukan kegiatan_id dan hanya untuk kegiatan yang dikelola.
     */


    /**
     * Generic lampiran preview route for both stored BAST and preview-only mode.
     */


    /**
     * Generic lampiran download route for both stored BAST and preview-only mode.
     */


    /**
     * Generic lampiran signed upload route for both stored BAST and preview-only mode.
     */


    /**
     * Download PDF BAST yang sudah tersimpan
     */


    /**
     * Download the compiled BAST (main + all lampiran) from storage.
     */


    /**
     * Generate lampiran (if not yet generated) and immediately download it.
     * Combines the generate + download flow into one request.
     */


    /**
     * Download all BAST files in a month as ZIP
     */


    /**
     * Generate preview PDF for BAST
     */


    /**
     * Store a newly created resource in storage.
     */


    /**
     * Display the specified resource.
     */


    /**
     * Upload signed BAST file
     */


    /**
     * Show the form for editing the specified resource.
     */


    /**
     * Update the specified resource in storage.
     */


    /**
     * Remove the specified resource from storage.
     */


    /**
     * Generate nomor BAST
     */


    /**
     * Generate BAST PDF
     */


    /**
     * Merge multiple PDF binary strings into one PDF preserving page orientations.
     */


    /**
     * Get hari in Indonesian
     */


    /**
     * Strip academic titles / honorifics from a full name.
     */


    /**
     * Ambil periode target: prioritas status perubahan (terbaru), jika tidak ada ambil dikirim (terbaru).
     */


    /**
     * Get bulan label (Indonesian month name)
     */

}
