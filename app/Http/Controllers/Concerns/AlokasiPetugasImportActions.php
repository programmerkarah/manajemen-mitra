<?php

namespace App\Http\Controllers\Concerns;

use App\Exports\AlokasiPetugasTemplateExport;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\StoreAlokasiPetugasRequest;
use App\Http\Requests\UpdateAlokasiPetugasRequest;
use App\Http\Requests\UpdateNonResponseRequest;
use App\Imports\AlokasiPetugasImport;
use App\Imports\AlokasiPetugasPreviewImport;
use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\AlokasiPetugasFrameSampel;
use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\ReviewPetugas;
use App\Models\Sbml;
use App\Models\Spk;
use App\Services\ActiveYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Vinkla\Hashids\Facades\Hashids;

trait AlokasiPetugasImportActions
{
    public function exportTemplateCreate(Request $request, string $type = 'create'): BinaryFileResponse
    {
        $kegiatan = null;
        $tahapan = $request->query('tahapan');

        if ($kegiatanHash = $request->query('kegiatan')) {
            $decoded = Hashids::decode((string) $kegiatanHash);
            if (! empty($decoded)) {
                $kegiatan = Kegiatan::find((int) $decoded[0]);
            }
        }

        return Excel::download(
            new AlokasiPetugasTemplateExport(null, $type, $kegiatan, $tahapan),
            "alokasi-petugas-template-{$type}.xlsx"
        );
    }

    public function exportTemplate(?string $periodeAlokasiHash = null, string $type = 'create'): BinaryFileResponse
    {
        $periodeAlokasiId = null;
        $kegiatan = null;
        $tahapan = null;

        if ($periodeAlokasiHash !== null) {
            $decodedId = Hashids::decode($periodeAlokasiHash)[0] ?? null;
            $periodeAlokasiId = $decodedId !== null ? (int) $decodedId : (is_numeric($periodeAlokasiHash) ? (int) $periodeAlokasiHash : null);
        }

        if ($periodeAlokasiId) {
            $periode = PeriodeAlokasi::find($periodeAlokasiId);
            $kegiatan = $periode?->kegiatan;
            $tahapan = $periode?->tahapan;
        }

        return Excel::download(
            new AlokasiPetugasTemplateExport($periodeAlokasiId, $type, $kegiatan, $tahapan),
            "alokasi-petugas-template-{$type}.xlsx"
        );
    }

    public function import(Request $request, int $periodeAlokasiId): RedirectResponse
    {
        // Get periode alokasi for reference
        $periode = PeriodeAlokasi::findOrFail($periodeAlokasiId);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ], [
            'file.required' => 'File harus diupload',
            'file.mimes' => 'File harus berupa Excel (.xlsx, .xls) atau CSV',
        ]);

        try {
            $isCreate = $request->input('is_create', false) === 'true' || $request->input('is_create') === true;
            $import = new AlokasiPetugasImport($periodeAlokasiId, $isCreate);
            Excel::import($import, $validated['file']);

            ActivityLog::log(
                'Import Alokasi Petugas',
                'alokasi',
                "Berhasil mengimport alokasi petugas untuk {$periode->jenis_kegiatan} bulan {$periode->bulan}/{$periode->tahun} ({$import->getSuccessCount()} petugas)",
                'success',
                [
                    'periode_id' => $periodeAlokasiId,
                    'imported_count' => $import->getSuccessCount(),
                    'kegiatan_id' => $periode->kegiatan_id,
                ]
            );

            $backUrl = '/alokasi/periode/'.$periode->kegiatan->hashed_id.'/'.$periode->tahun.'/'.str_pad($periode->bulan, 2, '0', STR_PAD_LEFT);

            return redirect($backUrl)
                ->with('success', "Berhasil mengimport {$import->getSuccessCount()} data alokasi petugas");
        } catch (ValidationException $e) {
            $failures = $e->failures();
            $errorMessage = 'Gagal mengimport file. Errors: ';
            $errorDetails = [];
            foreach ($failures as $failure) {
                $errorDetails[] = "Baris {$failure->row()}: ".implode('; ', $failure->errors());
            }

            return back()->withErrors(['file' => $errorMessage.implode(' | ', array_slice($errorDetails, 0, 3))])
                ->withInput();
        } catch (\Exception $e) {
            Log::error('AlokasiPetugasImport Error', ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);

            return back()->withErrors(['file' => 'Gagal mengimport file: '.$e->getMessage()])
                ->withInput();
        }
    }

    public function importPreview(Request $request, Kegiatan $kegiatan): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'tahapan' => ['nullable', 'in:both,listing_only,pencacahan_only'],
        ], [
            'file.required' => 'File harus diupload',
            'file.mimes' => 'File harus berupa Excel (.xlsx, .xls) atau CSV',
        ]);

        $tahapan = $validated['tahapan'] ?? ($kegiatan->has_listing_updating ? 'both' : 'pencacahan_only');

        $import = new AlokasiPetugasPreviewImport;
        Excel::import($import, $validated['file']);

        $rows = $import->rows();
        $previewRows = [];
        $errors = [];

        $rateByKey = $kegiatan->rateHonors()
            ->where('status', 'aktif')
            ->get()
            ->keyBy(fn ($rate) => $rate->status_kepegawaian.'|'.$rate->jenis_penugasan);
        $allowDecimalPencacahan = $kegiatan->jenis_kegiatan === 'sensus';
        $frameSampelQuery = $kegiatan->kegiatanFrameSampel()->select('id', 'tahapan', 'target_unit_sampel', 'identitas_tambahan');

        if ($tahapan === 'listing_only') {
            $frameSampelQuery->where('tahapan', 'listing');
        }

        if ($tahapan === 'pencacahan_only') {
            $frameSampelQuery->where('tahapan', 'pencacahan');
        }

        $frameSampelRows = $frameSampelQuery->get()->values();
        $frameMetadataColumns = $this->extractFrameSampelMetadataColumns($frameSampelRows);
        $requiresFrameSampelInput = $frameSampelRows->isNotEmpty();
        $sensusUnitSampleColumnKeys = $this->sensusUnitSampleColumnKeys($kegiatan);

        // NIK is encrypted in the DB — load all petugas and build a decrypted NIK → Petugas map.
        $petugasByNik = Petugas::query()
            ->get()
            ->keyBy(fn (Petugas $p) => $p->getAttribute('nik'));

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if ($this->isReferencePetugasSheetRow($row)) {
                continue;
            }

            $nik = $this->parseImportNik($this->extractImportNikCellValue($row));
            $kodePenugasan = trim((string) $this->extractImportPeranCellValue($row));

            // Skip empty rows and instruction/note rows (NIK must be all digits).
            if ($nik === '' || ! ctype_digit($nik)) {
                continue;
            }

            $petugas = $petugasByNik->get($nik);
            if (! $petugas) {
                $errors[] = "Baris {$rowNumber}: Petugas dengan NIK {$nik} tidak ditemukan.";

                continue;
            }

            $peranCode = $this->normalizeImportPeranCode($kodePenugasan);
            if (! $peranCode) {
                $errors[] = "Baris {$rowNumber}: Kode penugasan '{$kodePenugasan}' tidak valid.";

                continue;
            }

            $statusKepegawaian = $this->resolveStatusKepegawaianFromPetugas($petugas);
            $rate = $rateByKey->get($statusKepegawaian.'|'.$peranCode);

            if (! $rate) {
                $errors[] = "Baris {$rowNumber}: Rate honor tidak ditemukan untuk {$petugas->nama} ({$statusKepegawaian}, {$peranCode}).";

                continue;
            }

            $jumlahSatuanRaw = $row['jumlah_satuan_pencacahan'] ?? $row['jumlah_satuan'] ?? 0;
            $jumlahSatuanPencacahan = $this->parseImportSatuan($jumlahSatuanRaw);
            $hasSensusUnitSampleInput = false;

            if ($kegiatan->jenis_kegiatan === 'sensus' && ! empty($sensusUnitSampleColumnKeys)) {
                $jumlahSatuanPencacahan = 0;
                foreach ($sensusUnitSampleColumnKeys as $columnKey) {
                    $unitValue = $this->parseImportSatuan($row[$columnKey] ?? 0);
                    if ($unitValue > 0) {
                        $hasSensusUnitSampleInput = true;
                    }
                    $jumlahSatuanPencacahan += $unitValue;
                }
            }
            $jumlahSatuanListing = $this->parseImportInteger($row['jumlah_satuan_listing'] ?? 0);
            $metadataValues = $this->extractImportFrameMetadataValues($row, $frameMetadataColumns);
            $hasAnyMetadataValue = collect($metadataValues)
                ->contains(fn (string $value): bool => trim($value) !== '');

            if ($requiresFrameSampelInput && ! $hasAnyMetadataValue) {
                $errors[] = "Baris {$rowNumber}: Kolom metadata frame sampel wajib diisi.";

                continue;
            }

            $validFrameSampelIds = [];
            $jumlahUnitSampel = 0;

            if ($hasAnyMetadataValue) {
                $matchedFrameSampel = $this->resolveFrameSampelByMetadata(
                    $frameSampelRows,
                    $metadataValues,
                    $rowNumber,
                    $errors
                );

                if ($matchedFrameSampel === null) {
                    continue;
                }

                $validFrameSampelIds[] = (int) $matchedFrameSampel->id;
                $jumlahUnitSampel = max(0, array_sum((array) ($matchedFrameSampel->target_unit_sampel ?? [])));
            }

            if ($requiresFrameSampelInput && $kegiatan->jenis_kegiatan === 'survei') {
                $jumlahSatuanPencacahan = (float) $jumlahUnitSampel;
                $jumlahSatuanListing = $jumlahUnitSampel;

                if ($tahapan === 'listing_only') {
                    $jumlahSatuanPencacahan = 0;
                }

                if ($tahapan === 'pencacahan_only') {
                    $jumlahSatuanListing = 0;
                }
            }

            if ($requiresFrameSampelInput && $kegiatan->jenis_kegiatan === 'sensus' && $hasAnyMetadataValue && ! $hasSensusUnitSampleInput) {
                $jumlahSatuanPencacahan = (float) $jumlahUnitSampel;
            }

            if (! $allowDecimalPencacahan && $this->hasDecimalPart($jumlahSatuanPencacahan)) {
                $errors[] = "Baris {$rowNumber}: Jumlah satuan pencacahan desimal hanya diperbolehkan untuk kegiatan sensus.";

                continue;
            }

            if ($tahapan === 'listing_only') {
                $jumlahSatuanPencacahan = 0;
            }

            if ($tahapan === 'pencacahan_only') {
                $jumlahSatuanListing = 0;
            }

            $isPartialPayment = $this->parseImportBoolean($row['pembayaran_parsial'] ?? false);
            $partialJumlahSatuanRaw = $row['jumlah_satuan_parsial_pencacahan'] ?? $row['jumlah_satuan_parsial'] ?? 0;
            $partialJumlahSatuan = $this->parseImportSatuan($partialJumlahSatuanRaw);
            $partialJumlahSatuanListing = $this->parseImportInteger($row['jumlah_satuan_parsial_listing'] ?? 0);

            if ($isPartialPayment && ! $allowDecimalPencacahan && $this->hasDecimalPart($partialJumlahSatuan)) {
                $errors[] = "Baris {$rowNumber}: Jumlah satuan parsial pencacahan desimal hanya diperbolehkan untuk kegiatan sensus.";

                continue;
            }

            if (! $isPartialPayment) {
                $partialJumlahSatuan = 0;
                $partialJumlahSatuanListing = 0;
            }

            if ($partialJumlahSatuan > $jumlahSatuanPencacahan) {
                $errors[] = "Baris {$rowNumber}: Jumlah satuan parsial pencacahan tidak boleh lebih besar dari jumlah satuan pencacahan.";

                continue;
            }

            if ($partialJumlahSatuanListing > $jumlahSatuanListing) {
                $errors[] = "Baris {$rowNumber}: Jumlah satuan parsial listing tidak boleh lebih besar dari jumlah satuan listing.";

                continue;
            }

            $estimasiHonor = (float) ($rate->rate ?? 0) * (float) $jumlahSatuanPencacahan;
            $estimasiHonorListing = (float) ($rate->rate_listing ?? 0) * $jumlahSatuanListing;
            $estimasiHonorPartial = $isPartialPayment
                ? (float) ($rate->rate ?? 0) * (float) $partialJumlahSatuan
                : 0;
            $estimasiHonorPartialListing = $isPartialPayment ? (float) ($rate->rate_listing ?? 0) * $partialJumlahSatuanListing : 0;

            $previewRows[] = [
                'petugas_id' => (string) $petugas->id,
                'petugas_nama' => $petugas->nama,
                'nik' => $petugas->nik,
                'peran' => $this->mapPeranCodeToDisplayLabel($peranCode),
                'jumlah_satuan' => (string) $jumlahSatuanPencacahan,
                'estimasi_honor' => $estimasiHonor,
                'jumlah_satuan_listing' => (string) $jumlahSatuanListing,
                'estimasi_honor_listing' => $estimasiHonorListing,
                'catatan' => '',
                'is_partial_payment' => $isPartialPayment,
                'partial_jumlah_satuan' => $isPartialPayment ? (string) $partialJumlahSatuan : '',
                'estimasi_honor_partial' => $estimasiHonorPartial,
                'is_partial_payment_listing' => $isPartialPayment,
                'partial_jumlah_satuan_listing' => $isPartialPayment ? (string) $partialJumlahSatuanListing : '',
                'estimasi_honor_partial_listing' => $estimasiHonorPartialListing,
                'frame_sampel_ids' => array_values($validFrameSampelIds),
                'jumlah_unit_sampel' => $jumlahUnitSampel,
                'frame_sampel_metadata' => $metadataValues,
            ];
        }

        if (count($previewRows) === 0 && count($errors) === 0 && $rows->count() > 0) {
            $errors[] = 'Tidak ada baris data yang bisa dipreview. Pastikan kolom [Nama - NIK] dan [Kode Penugasan] sudah dipilih dari dropdown template.';
        }

        return response()->json([
            'rows' => $previewRows,
            'errors' => $errors,
            'frame_metadata_columns' => $frameMetadataColumns,
            'summary' => [
                'total_rows' => $rows->count(),
                'valid_rows' => count($previewRows),
                'error_count' => count($errors),
            ],
        ]);
    }

    public function importCreate(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'min:2020', 'max:2099'],
            'tahapan' => ['nullable', 'in:both,listing_only,pencacahan_only'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'tanggal_mulai_listing' => ['nullable', 'date'],
            'tanggal_selesai_listing' => ['nullable', 'date', 'after_or_equal:tanggal_mulai_listing'],
            'jadwal_pengolahan_listing_mulai' => ['nullable', 'date'],
            'jadwal_pengolahan_listing_selesai' => ['nullable', 'date', 'after_or_equal:jadwal_pengolahan_listing_mulai'],
            'jadwal_pengolahan_pencacahan_mulai' => ['nullable', 'date'],
            'jadwal_pengolahan_pencacahan_selesai' => ['nullable', 'date', 'after_or_equal:jadwal_pengolahan_pencacahan_mulai'],
        ], [
            'file.required' => 'File harus diupload',
            'file.mimes' => 'File harus berupa Excel (.xlsx, .xls) atau CSV',
        ]);

        $isSensusKegiatan = $kegiatan->jenis_kegiatan === 'sensus';

        $existingPeriodeQuery = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $validated['tahun'])
            ->whereIn('status', ['draft', 'dikirim', 'perubahan', 'direvisi']);

        if (! $isSensusKegiatan) {
            $existingPeriodeQuery->where('bulan', str_pad((string) $validated['bulan'], 2, '0', STR_PAD_LEFT));
        }

        $existingPeriode = $existingPeriodeQuery->first();

        if ($existingPeriode) {
            return back()->withErrors([
                'file' => $isSensusKegiatan
                    ? 'Untuk kegiatan sensus hanya diperbolehkan satu periode/perjanjian kerja dalam satu tahun. Gunakan mode edit untuk import ulang.'
                    : 'Periode untuk bulan/tahun tersebut sudah ada. Gunakan mode edit untuk import ulang.',
            ])->withInput();
        }

        DB::beginTransaction();

        try {
            $periode = PeriodeAlokasi::create([
                'kegiatan_id' => $kegiatan->id,
                'bulan' => str_pad((string) $validated['bulan'], 2, '0', STR_PAD_LEFT),
                'tahun' => $validated['tahun'],
                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                'status' => 'draft',
                'tahapan' => $validated['tahapan'] ?? ($kegiatan->has_listing_updating ? 'both' : 'pencacahan_only'),
                'tanggal_mulai' => $validated['tanggal_mulai'] ?? null,
                'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
                'tanggal_mulai_listing' => $validated['tanggal_mulai_listing'] ?? null,
                'tanggal_selesai_listing' => $validated['tanggal_selesai_listing'] ?? null,
                'jadwal_pengolahan_listing_mulai' => $validated['jadwal_pengolahan_listing_mulai'] ?? null,
                'jadwal_pengolahan_listing_selesai' => $validated['jadwal_pengolahan_listing_selesai'] ?? null,
                'jadwal_pengolahan_pencacahan_mulai' => $validated['jadwal_pengolahan_pencacahan_mulai'] ?? null,
                'jadwal_pengolahan_pencacahan_selesai' => $validated['jadwal_pengolahan_pencacahan_selesai'] ?? null,
                'revision_number' => 0,
            ]);

            $import = new AlokasiPetugasImport($periode->id, true);
            Excel::import($import, $validated['file']);

            ActivityLog::log(
                'Import Alokasi Petugas (Create)',
                'alokasi',
                "Berhasil mengimport alokasi {$kegiatan->nama_kegiatan} {$periode->bulan}/{$periode->tahun} ({$import->getSuccessCount()} petugas)",
                'success',
                [
                    'kegiatan_id' => $kegiatan->id,
                    'periode_id' => $periode->id,
                    'imported_count' => $import->getSuccessCount(),
                ]
            );

            DB::commit();

            return redirect('/alokasi/periode/'.$kegiatan->hashed_id.'/'.$periode->tahun.'/'.$periode->bulan)
                ->with('success', "Berhasil import {$import->getSuccessCount()} data alokasi petugas");
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('AlokasiPetugas import create gagal', ['error' => $e->getMessage()]);

            return back()->withErrors(['file' => 'Gagal mengimport file: '.$e->getMessage()])
                ->withInput();
        }
    }
}
