<?php

namespace App\Http\Controllers\Concerns;

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

trait BastPdfSupport
{
    private function prepareBastDataForExport(
        Spk $spk,
        Collection $allSpks,
        string $nomorBast,
        DateTimeInterface|string $tanggalBerakhir,
        object $ppk,
        ?array $seInput = null,
        ?bool $isSensusEkonomi = null
    ): array {
        $petugas = $spk->alokasiPetugas->petugas;
        $bulan = (int) date('n', strtotime($spk->tanggal_mulai_kerja));
        $tahun = (int) date('Y', strtotime($spk->tanggal_mulai_kerja));
        $isSeSpk = $isSensusEkonomi ?? $this->isSensusEkonomiSpk($spk);

        // Ambil semua alokasi untuk petugas yang sama dalam bulan dan tahun yang sama
        // Filter by kegiatan type: SE BAST hanya memuat alokasi SE, non-SE BAST hanya memuat non-SE
        $allAlokasi = AlokasiPetugas::where('petugas_id', $petugas->id)
            ->whereHas('periodeAlokasi', function ($q) use ($bulan, $tahun) {
                $q->whereIn('bulan', $this->resolveBulanCandidates($bulan))
                    ->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan']);
            })
            ->whereHas('petugas', function ($q) {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->where(function ($query) {
                $query->where('total_honor', '>', 0)
                    ->orWhere('total_honor_listing', '>', 0);
            })
            ->whereHas('periodeAlokasi.kegiatan', function ($q) use ($isSeSpk) {
                if ($isSeSpk) {
                    $q->where('jenis_kegiatan', 'sensus')
                        ->where('nama_kegiatan', 'like', '%Sensus Ekonomi%');
                } else {
                    $q->where(function ($inner) {
                        $inner->where('jenis_kegiatan', '!=', 'sensus')
                            ->orWhere('nama_kegiatan', 'not like', '%Sensus Ekonomi%');
                    });
                }
            })
            ->with([
                'periodeAlokasi.kegiatan.rateHonors.satuan',
                'periodeAlokasi.kegiatan.rateHonors.satuanListing',
                'periodeAlokasi.kegiatan.ketuaTim',
                'frameSampelAllocations.kegiatanFrameSampel',
                'spk',
            ])
            ->get();

        if (! $isSeSpk) {
            $allAlokasi = $allAlokasi
                ->reject(function (AlokasiPetugas $alokasi) {
                    return $alokasi->periodeAlokasi?->status === 'direvisi';
                })
                ->values();
        }

        $ketuaTim = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->ketuaTim;

        $sensusNarrativeData = $this->buildSensusEkonomiNarrativeData($allAlokasi, $seInput);

        // Format data untuk BAST
        $bastData = [
            'nomor_bast' => $nomorBast,
            'tanggal_bast' => $tanggalBerakhir,
            'tanggal_pelaksanaan' => $spk->tanggal_mulai_kerja,
            'tanggal_selesai' => $tanggalBerakhir,
            'muatan_input' => $seInput['muatan_input'] ?? null,
            'muatan_prelist' => $seInput['muatan_prelist'] ?? null,
            'realisasi_unit_sampel' => $seInput['realisasi_unit_sampel'] ?? null,
            'target_jumlah_frame_sampel' => $sensusNarrativeData['target_jumlah_frame_sampel'],
            'target_muatan_prelist_keluarga' => $sensusNarrativeData['target_muatan_prelist_keluarga'],
            'target_muatan_prelist_usaha' => $sensusNarrativeData['target_muatan_prelist_usaha'],
            'hasil_jumlah_frame_sampel' => $sensusNarrativeData['hasil_jumlah_frame_sampel'],
            'hasil_realisasi_keluarga' => $sensusNarrativeData['hasil_realisasi_keluarga'],
            'hasil_realisasi_usaha' => $sensusNarrativeData['hasil_realisasi_usaha'],
            'is_sensus_ekonomi' => $isSeSpk,
            'lokasi_kegiatan' => 'Kota Sawahlunto',
            'nama_ppk' => $ppk->nama,
            'nip_ppk' => $ppk->nip ?? '-',
            'petugas' => [
                'nama' => $petugas?->nama,
                'nik' => $petugas?->nik,
                'alamat' => $petugas?->alamat,
            ],
            'ketua_tim' => [
                'nama' => $ketuaTim?->name,
                'nip' => $ketuaTim?->nip,
            ],
            'kegiatan_list' => [],
        ];

        // Build kegiatan list dengan lampiran
        foreach ($allAlokasi as $alokasi) {
            $kegiatan = $alokasi->periodeAlokasi?->kegiatan;
            $periode = $alokasi->periodeAlokasi;
            if (! $kegiatan || ! $periode) {
                continue;
            }

            $rateHonor = $kegiatan->rateHonors->first(function ($rate) use ($alokasi) {
                return $rate->status_kepegawaian === $alokasi->status_kepegawaian
                    && $rate->jenis_penugasan === $alokasi->peran;
            });

            $isPendataanRole = in_array($alokasi->peran, self::PENDATAAN_ROLES, true);
            $isPengolahanRole = in_array($alokasi->peran, self::PENGOLAHAN_ROLES, true);
            $effectiveListingVolume = $alokasi->getEffectiveJumlahSatuanListing();
            $effectivePencacahanVolume = $alokasi->getEffectiveJumlahSatuan();
            $hasListing = ($kegiatan->has_listing_updating ?? false) || $effectiveListingVolume > 0;

            // Cari SPK dari petugas ini saja, bukan per kegiatan
            $spkPetugas = Spk::where('alokasi_petugas_id', $alokasi->id)->first();
            $nomorSpk = $spkPetugas?->nomor_spk ?? 'Belum ada SPK';

            // Kumpulkan semua tanggal selesai yang relevan (listing & pencacahan)
            $tanggalSelesaiArr = [];
            if ($isPengolahanRole) {
                if (! empty($periode->jadwal_pengolahan_listing_selesai)) {
                    $tanggalSelesaiArr[] = $periode->jadwal_pengolahan_listing_selesai;
                }
                if (! empty($periode->jadwal_pengolahan_pencacahan_selesai)) {
                    $tanggalSelesaiArr[] = $periode->jadwal_pengolahan_pencacahan_selesai;
                }
            } elseif ($isPendataanRole) {
                if (! empty($periode->tanggal_selesai_listing)) {
                    $tanggalSelesaiArr[] = $periode->tanggal_selesai_listing;
                }
                if (! empty($periode->tanggal_selesai)) {
                    $tanggalSelesaiArr[] = $periode->tanggal_selesai;
                }
            } else {
                if (! empty($periode->tanggal_selesai)) {
                    $tanggalSelesaiArr[] = $periode->tanggal_selesai;
                }
                if (! empty($periode->tanggal_selesai_listing)) {
                    $tanggalSelesaiArr[] = $periode->tanggal_selesai_listing;
                }
            }

            // Ambil tanggal paling akhir dari semua tahapan
            $tanggalSelesaiKegiatan = null;
            if (! empty($tanggalSelesaiArr)) {
                $tanggalSelesaiKegiatan = collect($tanggalSelesaiArr)->max();
            }

            // Fallback ke tanggal SPK jika tidak ada tanggal dari periode
            if (empty($tanggalSelesaiKegiatan)) {
                $tanggalSelesaiKegiatan = $spkPetugas?->tanggal_selesai_kerja ?? $alokasi->tanggal_selesai ?? 'Belum ada SPK';
            }

            $ketuaTimKegiatan = $kegiatan->ketuaTim;

            // Validasi tanggal sebelum parsing dan adjust ke hari kerja jika weekend
            $tanggalSelesaiFormatted = '-';
            if (! empty($tanggalSelesaiKegiatan) && $tanggalSelesaiKegiatan !== 'Belum ada SPK') {
                try {
                    // Convert to string if it's already a Carbon instance
                    $dateString = $tanggalSelesaiKegiatan instanceof Carbon
                        ? $tanggalSelesaiKegiatan->format('Y-m-d')
                        : $tanggalSelesaiKegiatan;

                    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dateString)) {
                        $carbonDate = Carbon::parse($dateString);

                        // Adjust ke hari kerja terakhir sebelum tanggal tersebut jika weekend
                        while (in_array($carbonDate->dayOfWeekIso, [6, 7])) {
                            $carbonDate->subDay();
                        }

                        $tanggalSelesaiFormatted = $carbonDate->locale('id')->isoFormat('D MMMM YYYY');
                    }
                } catch (\Exception $e) {
                    // Try fallback to tanggal BAST utama
                    if (! empty($tanggalBerakhir)) {
                        try {
                            $dateString = $tanggalBerakhir instanceof Carbon
                                ? $tanggalBerakhir->format('Y-m-d')
                                : $tanggalBerakhir;

                            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dateString)) {
                                $carbonDate = Carbon::parse($dateString);

                                // Adjust ke hari kerja terakhir sebelum tanggal tersebut jika weekend
                                while (in_array($carbonDate->dayOfWeekIso, [6, 7])) {
                                    $carbonDate->subDay();
                                }

                                $tanggalSelesaiFormatted = $carbonDate->locale('id')->isoFormat('D MMMM YYYY');
                            }
                        } catch (\Exception $e2) {
                            $tanggalSelesaiFormatted = '-';
                        }
                    }
                }
            }

            // Generate uraian terpisah untuk listing dan pencacahan
            $uraianListing = null;
            $uraianPencacahan = null;

            if ($hasListing && $isPendataanRole) {
                // Untuk listing: paksa jumlah_satuan = 0 agar generate uraian listing
                $uraianListing = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $kegiatan->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    $effectiveListingVolume,
                    0 // Force 0 untuk listing
                );
            }

            if ($isPendataanRole && $effectivePencacahanVolume > 0) {
                // Untuk pencacahan: paksa jumlah_satuan_listing = 0 agar generate uraian pencacahan
                $uraianPencacahan = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $kegiatan->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    0, // Force 0 untuk pencacahan
                    $effectivePencacahanVolume
                );
            }

            // Generate uraian terpisah untuk pengolahan listing dan pengolahan pencacahan
            $uraianPengolahanListing = null;
            $uraianPengolahanPencacahan = null;

            if ($hasListing && $isPengolahanRole) {
                // Untuk pengolahan listing: paksa jumlah_satuan = 0
                $uraianPengolahanListing = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $kegiatan->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    $effectiveListingVolume,
                    0
                );
            }

            if ($isPengolahanRole && $effectivePencacahanVolume > 0) {
                // Untuk pengolahan pencacahan: paksa jumlah_satuan_listing = 0
                $uraianPengolahanPencacahan = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $kegiatan->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    0,
                    $effectivePencacahanVolume
                );
            }

            // Fallback: use first available uraian
            $uraianPekerjaan = $uraianListing ?? $uraianPencacahan ?? $this->generateUraianPekerjaan(
                $alokasi->peran,
                $kegiatan->nama_kegiatan,
                (int) $periode->bulan,
                $periode->tahun,
                $effectiveListingVolume,
                $effectivePencacahanVolume
            );

            $bastData['kegiatan_list'][] = [
                'kegiatan_id' => $kegiatan->id,
                'periode_alokasi_id' => $periode->id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                'nomor_spk' => $nomorSpk,
                'tanggal_selesai' => $tanggalSelesaiKegiatan,
                // Tampilkan label tanggal selesai jika valid, jika tidak kosongkan string agar tidak tampil "-" di frontend
                'tanggal_selesai_label' => ($tanggalSelesaiFormatted !== '-' && ! empty($tanggalSelesaiKegiatan)) ? $tanggalSelesaiFormatted : '',
                'tanggal_selesai_formatted' => $tanggalSelesaiFormatted,
                'uraian_pekerjaan' => $uraianPekerjaan,
                'uraian_listing' => $uraianListing,
                'uraian_pencacahan' => $uraianPencacahan,
                'uraian_pengolahan_listing' => $uraianPengolahanListing,
                'uraian_pengolahan_pencacahan' => $uraianPengolahanPencacahan,
                'peran' => $alokasi->peran,
                'hasil_listing' => ($hasListing && $isPendataanRole) ? $effectiveListingVolume : null,
                'satuan_listing' => ($hasListing && $isPendataanRole) ? $rateHonor?->satuanListing?->nama : null,
                'non_response_listing' => ($hasListing && $isPendataanRole) ? $alokasi->non_response_listing : null,
                'hasil_pendataan_lapangan' => $isPendataanRole ? $effectivePencacahanVolume : null,
                'satuan_pendataan' => $isPendataanRole ? $rateHonor?->satuan?->nama : null,
                'non_response' => $isPendataanRole ? $alokasi->non_response : null,
                'hasil_pengolahan' => $isPengolahanRole ? $effectivePencacahanVolume : null,
                'hasil_pengolahan_listing' => $isPengolahanRole ? $effectiveListingVolume : null,
                'satuan_pengolahan' => $isPengolahanRole ? $rateHonor?->satuan?->nama : null,
                'satuan_pengolahan_listing' => $isPengolahanRole ? $rateHonor?->satuanListing?->nama : null,
                'nilai_perjanjian' => (float) ($spkPetugas?->nilai_kontrak ?? 0),
                'wilayah_kerja' => $this->buildSensusLampiranWilayahKerja($alokasi),
                'keterangan' => $alokasi->catatan,
                'ketua_tim' => [
                    'nama' => $ketuaTimKegiatan?->name,
                    'nip' => $ketuaTimKegiatan?->nip,
                ],
            ];
        }

        $bastData['kegiatan_list'] = $this->sortAndNumberKegiatanLampiran($bastData['kegiatan_list']);

        $bastObject = (object) $bastData;

        // Get Kepala BPS
        $kepala = Penandatangan::where('jenis_penandatangan', 'kepala')
            ->where('is_active', true)
            ->first();

        return [
            'bast' => $bastObject,
            'nomor_bast' => $bastData['nomor_bast'],
            'tanggal_akhir_kegiatan' => Carbon::parse($tanggalBerakhir)->locale('id')->isoFormat('D MMMM YYYY'),
            'hari' => Carbon::parse($tanggalBerakhir)->locale('id')->isoFormat('dddd'),
            'menggunakan_fasih' => $this->isMenggunakanFasih($allAlokasi),
            'jabatan_ppk' => 'Pejabat Pembuat Komitmen Badan Pusat Statistik Kota Sawahlunto',
            'alamat_unit_kerja' => 'Jl. Bagindo Aziz Chan, Kel. Aur Mulyo, Kec. Lembah Segar, Kota Sawahlunto',
            'nama_kepala' => $kepala?->nama,
        ];
    }

    private function prepareBastData(
        Spk $spk,
        Collection $allSpks,
        string $nomorBast,
        DateTimeInterface|string $tanggalBerakhir,
        Kegiatan $kegiatan,
        PeriodeAlokasi $periodeAlokasi,
        string $uraianPekerjaan,
        ?User $ketuaTim,
        Penandatangan $ppk
    ): array {
        $petugas = $spk->alokasiPetugas->petugas;

        // Build kegiatan list
        $kegiatanList = [];
        foreach ($allSpks as $spkKegiatan) {
            $alokasi = $spkKegiatan->alokasiPetugas;
            $keg = $alokasi?->periodeAlokasi?->kegiatan;
            $periode = $alokasi?->periodeAlokasi;

            if (! $keg || ! $periode) {
                continue;
            }

            $rateHonor = $keg->rateHonors->first(function ($rate) use ($alokasi) {
                return $rate->status_kepegawaian === $alokasi->status_kepegawaian
                    && $rate->jenis_penugasan === $alokasi->peran;
            });

            $isPendataanRole = in_array($alokasi->peran, self::PENDATAAN_ROLES, true);
            $isPengolahanRole = in_array($alokasi->peran, self::PENGOLAHAN_ROLES, true);
            $hasListing = ($keg->has_listing_updating ?? false) || ($alokasi->jumlah_satuan_listing ?? 0) > 0;

            // Cari SPK dari petugas ini saja, bukan per kegiatan
            $spkPetugas = Spk::where('alokasi_petugas_id', $alokasi->id)->first();
            $alokasi = $spkKegiatan->alokasiPetugas;
            $keg = $alokasi?->periodeAlokasi?->kegiatan;
            $periode = $alokasi?->periodeAlokasi;

            if (! $keg || ! $periode) {
                continue;
            }

            $rateHonor = $keg->rateHonors->first(function ($rate) use ($alokasi) {
                return $rate->status_kepegawaian === $alokasi->status_kepegawaian
                    && $rate->jenis_penugasan === $alokasi->peran;
            });

            $isPendataanRole = in_array($alokasi->peran, self::PENDATAAN_ROLES, true);
            $isPengolahanRole = in_array($alokasi->peran, self::PENGOLAHAN_ROLES, true);
            $hasListing = ($keg->has_listing_updating ?? false) || ($alokasi->jumlah_satuan_listing ?? 0) > 0;

            // Cari SPK dari petugas ini saja, bukan per kegiatan
            $spkPetugas = Spk::where('alokasi_petugas_id', $alokasi->id)->first();
            $nomorSpk = $spkPetugas?->nomor_spk ?? 'Belum ada SPK';

            $tanggalSelesaiKegiatan = $periode->tanggal_selesai ?? ($spkPetugas?->tanggal_selesai_kerja ?? ($alokasi->tanggal_selesai ?? 'Belum ada SPK'));
            $ketuaTimKegiatan = $keg->ketuaTim;

            // Validasi tanggal sebelum parsing
            $tanggalSelesaiFormatted = '-';
            if (! empty($tanggalSelesaiKegiatan) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalSelesaiKegiatan)) {
                try {
                    $tanggalSelesaiFormatted = Carbon::parse($tanggalSelesaiKegiatan)->locale('id')->isoFormat('D MMMM YYYY');
                } catch (\Exception $e) {
                    $tanggalSelesaiFormatted = '-';
                }
            } elseif (! empty($tanggalBerakhir) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalBerakhir)) {
                // Fallback ke tanggal BAST utama jika tanggal selesai tidak valid
                try {
                    $tanggalSelesaiFormatted = Carbon::parse($tanggalBerakhir)->locale('id')->isoFormat('D MMMM YYYY');
                } catch (\Exception $e) {
                    $tanggalSelesaiFormatted = '-';
                }
            }

            // Generate uraian terpisah untuk listing dan pencacahan
            $uraianListing = null;
            $uraianPencacahan = null;

            if ($hasListing && $isPendataanRole) {
                // Untuk listing: paksa jumlah_satuan = 0 agar generate uraian listing
                $uraianListing = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $keg->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    $alokasi->jumlah_satuan_listing ?? 0,
                    0 // Force 0 untuk listing
                );
            }

            if ($isPendataanRole && ($alokasi->jumlah_satuan ?? 0) > 0) {
                // Untuk pencacahan: paksa jumlah_satuan_listing = 0 agar generate uraian pencacahan
                $uraianPencacahan = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $keg->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    0, // Force 0 untuk pencacahan
                    $alokasi->jumlah_satuan ?? 0
                );
            }

            // Generate uraian terpisah untuk pengolahan listing dan pengolahan pencacahan
            $uraianPengolahanListing = null;
            $uraianPengolahanPencacahan = null;

            if ($hasListing && $isPengolahanRole) {
                // Untuk pengolahan listing: paksa jumlah_satuan = 0
                $uraianPengolahanListing = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $keg->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    $alokasi->jumlah_satuan_listing ?? 0,
                    0
                );
            }

            if ($isPengolahanRole && ($alokasi->jumlah_satuan ?? 0) > 0) {
                // Untuk pengolahan pencacahan: paksa jumlah_satuan_listing = 0
                $uraianPengolahanPencacahan = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $keg->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    0,
                    $alokasi->jumlah_satuan ?? 0
                );
            }

            // Fallback: use first available uraian
            $uraianPekerjaan = $uraianListing ?? $uraianPencacahan ?? $this->generateUraianPekerjaan(
                $alokasi->peran,
                $keg->nama_kegiatan,
                (int) $periode->bulan,
                $periode->tahun,
                $alokasi->jumlah_satuan_listing ?? 0,
                $alokasi->jumlah_satuan ?? 0
            );

            $kegiatanList[] = [
                'kode_kegiatan' => $keg->kode_kegiatan,
                'nama_kegiatan' => $keg->nama_kegiatan,
                'jenis_kegiatan' => $keg->jenis_kegiatan,
                'nomor_spk' => $nomorSpk,
                'tanggal_selesai' => $tanggalSelesaiKegiatan,
                // Tampilkan label tanggal selesai jika valid, jika tidak kosongkan string agar tidak tampil "-" di frontend
                'tanggal_selesai_label' => ($tanggalSelesaiFormatted !== '-' && ! empty($tanggalSelesaiKegiatan)) ? $tanggalSelesaiFormatted : '',
                'tanggal_selesai_formatted' => $tanggalSelesaiFormatted,
                'uraian_pekerjaan' => $uraianPekerjaan,
                'uraian_listing' => $uraianListing,
                'uraian_pencacahan' => $uraianPencacahan,
                'uraian_pengolahan_listing' => $uraianPengolahanListing,
                'uraian_pengolahan_pencacahan' => $uraianPengolahanPencacahan,
                'peran' => $alokasi->peran,
                'hasil_listing' => ($hasListing && $isPendataanRole) ? $this->resolveLampiranCumulativeVolume($alokasi, 'listing') : null,
                'satuan_listing' => ($hasListing && $isPendataanRole) ? $rateHonor?->satuanListing?->nama : null,
                'non_response_listing' => ($hasListing && $isPendataanRole) ? $alokasi->non_response_listing : null,
                'hasil_pendataan_lapangan' => $isPendataanRole ? $this->resolveLampiranCumulativeVolume($alokasi, 'pencacahan') : null,
                'satuan_pendataan' => $isPendataanRole ? $rateHonor?->satuan?->nama : null,
                'non_response' => $isPendataanRole ? $alokasi->non_response : null,
                'hasil_pengolahan' => $isPengolahanRole ? $this->resolveLampiranCumulativeVolume($alokasi, 'pencacahan') : null,
                'hasil_pengolahan_listing' => $isPengolahanRole ? $this->resolveLampiranCumulativeVolume($alokasi, 'listing') : null,
                'satuan_pengolahan' => $isPengolahanRole ? $rateHonor?->satuan?->nama : null,
                'satuan_pengolahan_listing' => $isPengolahanRole ? $rateHonor?->satuanListing?->nama : null,
                'nilai_perjanjian' => (float) ($spkPetugas?->nilai_kontrak ?? 0),
                'wilayah_kerja' => $this->buildSensusLampiranWilayahKerja($alokasi),
                'keterangan' => $alokasi->catatan,
                'ketua_tim' => [
                    'nama' => $ketuaTimKegiatan?->name,
                    'nip' => $ketuaTimKegiatan?->nip,
                ],
            ];
        }

        $kegiatanList = $this->sortAndNumberKegiatanLampiran($kegiatanList);

        // Get Kepala BPS
        $kepala = Penandatangan::where('jenis_penandatangan', 'kepala')
            ->where('is_active', true)
            ->first();

        $bastObject = (object) [
            'nomor_bast' => $nomorBast,
            'tanggal_bast' => $tanggalBerakhir,
            'lokasi_kegiatan' => 'Kota Sawahlunto',
            'nama_ppk' => $ppk->nama,
            'nip_ppk' => $ppk->nip ?? '-',
            'petugas' => [
                'nama' => $petugas->nama,
                'nik' => $petugas->nik ?? '-',
                'alamat' => $petugas->alamat ?? '-',
            ],
            'ketua_tim' => [
                'nama' => $ketuaTim?->name,
                'nip' => $ketuaTim?->nip,
            ],
            'kegiatan_list' => $kegiatanList,
        ];

        return [
            'bast' => $bastObject,
            'nomor_bast' => $nomorBast,
            'nama_kepala' => $kepala?->nama ?? '-',
        ];
    }

    private function generateNomorBastForSpk(Carbon $tanggalBast, bool $isSensusEkonomi = false): string
    {
        $tahun = $tanggalBast->year;
        $bulan = $tanggalBast->month;

        // Get all BAST in this month and extract the highest number
        $allBast = Bast::whereYear('tanggal_bast', $tahun)
            ->whereMonth('tanggal_bast', $bulan)
            ->pluck('nomor_bast');

        $maxUrut = 0;
        foreach ($allBast as $nomorBast) {
            $urut = $this->extractBastSequenceForScheme($nomorBast, $isSensusEkonomi);
            if ($urut > $maxUrut) {
                $maxUrut = $urut;
            }
        }

        $urut = $maxUrut + 1;

        return $this->formatBastNomor($urut, $tahun, $isSensusEkonomi);
    }

    private function getRomanMonth(int $month): string
    {
        $romans = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        return $romans[$month - 1] ?? 'I';
    }

    private function generateUraianPekerjaan(
        string $jenisPenugasan,
        string $namaKegiatan,
        int $bulan,
        int $tahun,
        int $jumlahSatuanListing = 0,
        int $jumlahSatuan = 0
    ): string {
        $bulanLabel = [
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
        ][$bulan] ?? 'Januari';

        // Tentukan tahapan berdasarkan jumlah satuan
        $isListing = $jumlahSatuanListing > 0;
        $isLapangan = $jumlahSatuan > 0;

        // Generate uraian berdasarkan jenis penugasan dan tahapan
        return match ($jenisPenugasan) {
            'pcl_ppl' => $isListing
                ? "Melakukan pemutakhiran {$namaKegiatan} bulan {$bulanLabel} {$tahun}"
                : "Melakukan pencacahan {$namaKegiatan} bulan {$bulanLabel} {$tahun}",

            'pml' => $isListing && ! $isLapangan
                ? "Melakukan pemeriksaan pemutakhiran {$namaKegiatan} bulan {$bulanLabel} {$tahun}"
                : ($isListing && $isLapangan
                    ? "Melakukan pemeriksaan pemutakhiran dan pencacahan {$namaKegiatan} bulan {$bulanLabel} {$tahun}"
                    : "Melakukan pemeriksaan pencacahan {$namaKegiatan} bulan {$bulanLabel} {$tahun}"),

            'pengolahan' => $isListing
                ? "Melakukan pengolahan dokumen pemutakhiran {$namaKegiatan} bulan {$bulanLabel} {$tahun}"
                : "Melakukan pengolahan dokumen pencacahan lapangan {$namaKegiatan} bulan {$bulanLabel} {$tahun}",

            'pengawas_pengolahan' => $isListing
                ? "Melakukan pemeriksaan pengolahan dokumen pemutakhiran {$namaKegiatan} bulan {$bulanLabel} {$tahun}"
                : "Melakukan pemeriksaan pengolahan dokumen pencacahan lapangan {$namaKegiatan} bulan {$bulanLabel} {$tahun}",
            default => "Melakukan tugas {$namaKegiatan} bulan {$bulanLabel} {$tahun}",
        };
    }

    private function generateNomorBast(int $kegiatanId): string
    {
        $year = now()->year;
        $kegiatan = Kegiatan::find($kegiatanId);
        $isSensusEkonomi = $this->isSensusEkonomiName($kegiatan?->nama_kegiatan);

        // Get last number for this kegiatan (current year)
        $lastBast = Bast::where('kegiatan_id', $kegiatanId)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = 0;
        if ($lastBast) {
            $extracted = $this->extractBastSequence($lastBast->nomor_bast);
            $lastNumber = $extracted ?? 0;
        }

        $nextNumber = $lastNumber + 1;

        return $this->formatBastNomor($nextNumber, $year, $isSensusEkonomi);
    }

    private function generateBastPdf(Kegiatan $kegiatan, array $data, string $nomorBast, ?Penandatangan $ppk, ?string $bulanLabel = null, ?int $tahunPeriode = null): string
    {
        $tanggalBast = Carbon::parse($data['tanggal_bast']);
        $hari = $this->getHariIndonesia($tanggalBast->dayOfWeek);
        $tanggalFormatted = $tanggalBast->isoFormat('D MMMM YYYY');

        // Sanitize petugas entries to ensure fields only present for matching roles
        $pendataanRoles = ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'];
        $pengolahanRoles = ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'];
        foreach ($data['petugas'] as $i => $pEntry) {
            $peran = $pEntry['peran'] ?? null;
            if (! in_array($peran, $pendataanRoles, true)) {
                $data['petugas'][$i]['hasil_pendataan_lapangan'] = null;
                $data['petugas'][$i]['satuan_pendataan_lapangan'] = null;
                $data['petugas'][$i]['instrumen_pendataan_lapangan'] = null;
                // also clear listing values if not a pendataan role
                $data['petugas'][$i]['hasil_listing'] = null;
                $data['petugas'][$i]['satuan_listing'] = null;
                $data['petugas'][$i]['instrumen_listing'] = null;
            }
            if (! in_array($peran, $pengolahanRoles, true)) {
                $data['petugas'][$i]['hasil_pengolahan'] = null;
                $data['petugas'][$i]['hasil_pengolahan_listing'] = null;
                $data['petugas'][$i]['satuan_pengolahan_listing'] = null;
                $data['petugas'][$i]['satuan_pengolahan'] = null;
            }
        }

        // Check if listing, pendataan, or pengolahan exists after sanitization
        $hasListing = collect($data['petugas'])->contains(function ($p) {
            return ! empty($p['hasil_listing']);
        });
        $hasPengolahan = collect($data['petugas'])->contains(function ($p) {
            return ! empty($p['hasil_pengolahan']);
        });
        $hasPengolahanListing = collect($data['petugas'])->contains(function ($p) {
            return ! empty($p['hasil_pengolahan_listing']);
        });
        $hasPendataan = collect($data['petugas'])->contains(function ($p) {
            return ! empty($p['hasil_pendataan_lapangan']);
        });

        // Cari NIP ketua tim dari data petugas dengan nama yang sama
        $namaKetuaTim = $kegiatan->ketuaTim->name ?? 'N/A';
        $nipKetuaTim = null;

        // Prioritas: cari dari data petugas dengan nama yang sama
        if ($namaKetuaTim !== 'N/A') {
            $petugasKetuaTim = Petugas::whereRaw('LOWER(nama) = ?', [strtolower($namaKetuaTim)])->first();
            if ($petugasKetuaTim && $petugasKetuaTim->nip) {
                $nipKetuaTim = $petugasKetuaTim->nip;
            } else {
                // Fallback ke profile ketua tim
                $nipKetuaTim = $kegiatan->ketuaTim->nip ?? null;
            }
        }

        $viewData = [
            'nomor_bast' => $nomorBast,
            'hari' => $hari,
            'tanggal_bast' => $tanggalFormatted,
            'bulan_label' => $bulanLabel ?? $tanggalBast->isoFormat('MMMM'),
            'tahun' => $tahunPeriode ?? (int) $tanggalBast->year,
            'nama_ppk' => $ppk->nama ?? 'N/A',
            'nip_ppk' => $ppk->nip ?? 'N/A',
            'nama_ketua_tim' => $namaKetuaTim,
            'nip_ketua_tim' => $nipKetuaTim,
            'nama_kegiatan' => $kegiatan->nama_kegiatan,
            'nama_instansi' => config('app.instansi_name', 'Badan Pusat Statistik Kota Sawahlunto'),
            'menggunakan_fasih' => $data['menggunakan_fasih'],
            'petugas' => $data['petugas'],
            'has_listing' => $hasListing,
            'has_pendataan' => $hasPendataan,
            'has_pengolahan' => $hasPengolahan,
            'has_pengolahan_listing' => $hasPengolahanListing,
            'dokumen_rekap' => $data['dokumen_rekap'] ?? [],
            'instrumen_listing' => $data['instrumen_listing'] ?? null,
            'instrumen_pendataan_lapangan' => $data['instrumen_pendataan_lapangan'] ?? null,
            'kepalaBps' => null,
        ];

        // Attach Kepala BPS if available
        $kepala = Penandatangan::kepala()
            ->active()
            ->where(function ($q) {
                $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', today());
            })
            ->where(function ($q) {
                $q->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', today());
            })
            ->orderByDesc('periode_mulai')
            ->first();

        if ($kepala) {
            $viewData['kepalaBps'] = $this->stripGelar($kepala->nama) ?: $kepala->nama;
        }

        $useLandscape = false;
        if (! empty($viewData['dokumen_rekap']) && count($viewData['dokumen_rekap']) > 0) {
            $useLandscape = true;
        }
        if ($viewData['has_listing'] || $viewData['has_pengolahan'] || ($viewData['has_pendataan'] ?? false)) {
            $useLandscape = true;
        }

        $orientation = $useLandscape ? 'landscape' : 'portrait';

        // Render main (without lampiran)
        $viewDataMain = $viewData;
        $pdfMain = Pdf::loadView('bast', $viewDataMain)
            ->setPaper('a4', 'portrait');
        $mainContent = $pdfMain->output();

        // If no lampiran, save main PDF directly
        $hasLampiran = (! empty($viewData['dokumen_rekap']) && count($viewData['dokumen_rekap']) > 0)
            || $viewData['has_listing'] || $viewData['has_pengolahan'] || $viewData['has_pengolahan_listing'] || ($viewData['has_pendataan'] ?? false);

        if (! $hasLampiran) {
            $directory = public_path('bast-export/'.now()->year.'/'.now()->month);
            if (! file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            $fileName = 'BAST_'.$kegiatan->nama_kegiatan.'_'.($targetPeriode?->bulan ?? 'unknown').'_'.time().'.pdf';
            $filePath = 'storage/bast-export/'.now()->year.'/'.now()->month.'/'.$fileName;
            $fullPath = public_path($filePath);
            file_put_contents($fullPath, $mainContent);

            return $filePath;
        }

        // Render lampiran only (landscape) using bast-lampiran.blade.php directly
        $viewDataLamp = $viewData;
        $viewDataLamp['pageNumberOffset'] = $this->resolveLampiranPageNumberOffset(null, $mainContent);
        $lampOrientation = (! empty($viewData['dokumen_rekap']) && count($viewData['dokumen_rekap']) > 0)
            || $viewData['has_listing'] || $viewData['has_pengolahan'] || $viewData['has_pengolahan_listing'] || ($viewData['has_pendataan'] ?? false) ? 'landscape' : 'portrait';
        $pdfLamp = Pdf::loadView('bast-lampiran', $viewDataLamp)
            ->setPaper('a4', $lampOrientation);
        $lampContent = $pdfLamp->output();

        // Merge and save
        $merged = $this->mergePdfStrings([$mainContent, $lampContent]);

        $directory = public_path('bast-export/'.now()->year.'/'.now()->month);
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $periodeAlokasi = $kegiatan->periodeAlokasi()->latest('id')->first();
        $bulan = $periodeAlokasi?->bulan ?? 'unknown';
        $fileName = 'BAST_'.$kegiatan->nama_kegiatan.'_'.$bulan.'_'.time().'.pdf';
        $filePath = 'bast-export/'.now()->year.'/'.now()->month.'/'.$fileName;
        $fullPath = public_path($filePath);
        file_put_contents($fullPath, $merged);

        return 'storage/'.$filePath;
    }

    private function mergePdfStrings(array $pdfStrings): string
    {
        // Use FPDI TCPDF implementation
        $pdf = new Fpdi;
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        foreach ($pdfStrings as $str) {
            if (empty($str)) {
                continue;
            }
            // use StreamReader to feed string directly
            $reader = StreamReader::createByString($str);
            $pageCount = $pdf->setSourceFile($reader);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($tplId);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);
            }
        }

        return $pdf->Output('', 'S');
    }

    private function getHariIndonesia(int $dayOfWeek): string
    {
        $hari = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        return $hari[$dayOfWeek] ?? 'Senin';
    }

    private function stripGelar(?string $fullName): string
    {
        if (empty($fullName)) {
            return '';
        }

        // Remove anything after the first comma (common suffixes like ", S.Si., M.Sc.")
        $parts = explode(',', $fullName);
        $name = trim($parts[0]);

        // Remove common prefixes like Dr, Drs, Ir, H, Prof (with optional dot)
        $name = preg_replace('/^(Drs?|Ir|H|Prof)\.?\s+/i', '', $name);

        return trim($name);
    }
}
