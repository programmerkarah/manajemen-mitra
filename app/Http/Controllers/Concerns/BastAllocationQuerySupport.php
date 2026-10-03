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

trait BastAllocationQuerySupport
{
    private function hasPendataan(array $petugas): bool
    {
        return collect($petugas)->contains(function ($p) {
            return in_array($p['peran'] ?? null, self::PENDATAAN_ROLES, true)
                && (int) ($p['hasil_pendataan_lapangan'] ?? 0) > 0;
        });
    }

    private function hasListing(array $petugas): bool
    {
        return collect($petugas)->contains(function ($p) {
            return in_array($p['peran'] ?? null, self::PENDATAAN_ROLES, true)
                && (int) ($p['hasil_listing'] ?? 0) > 0;
        });
    }

    private function isMenggunakanFasih(iterable $allAlokasi): bool
    {
        foreach ($allAlokasi as $alokasi) {
            if (in_array($alokasi->peran, self::PENGOLAHAN_ROLES, true)) {
                continue;
            }

            $kegiatan = $alokasi->periodeAlokasi?->kegiatan;

            if (! $kegiatan) {
                continue;
            }

            if (Kegiatan::isFasihMetodePendataan($kegiatan->metode_pendataan_pencacahan)) {
                return true;
            }

            if ($kegiatan->has_listing_updating && Kegiatan::isFasihMetodePendataan($kegiatan->metode_pendataan_listing)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeDateForCompare(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Exception $exception) {
            return null;
        }
    }

    private function resolveBulanCandidates(int|string $bulan): array
    {
        $normalizedMonth = (int) $bulan;

        if ($normalizedMonth < 1 || $normalizedMonth > 12) {
            return [(string) $bulan];
        }

        return array_values(array_unique([
            str_pad((string) $normalizedMonth, 2, '0', STR_PAD_LEFT),
            (string) $normalizedMonth,
        ]));
    }

    private function applyBastNomorModeFilter($query, bool $isSensusEkonomiMode, string $column = 'nomor_bast')
    {
        if ($isSensusEkonomiMode) {
            return $query->where($column, 'like', '%BAST-SE2026%');
        }

        return $query->where($column, 'not like', '%BAST-SE2026%');
    }

    private function resolveBastLampiranSpkView(array $viewData): string
    {
        $firstKegiatan = data_get($viewData, 'bast.kegiatan_list.0');
        $isSensusEkonomi = (bool) data_get($viewData, 'bast.is_sensus_ekonomi', false)
            || str_contains(mb_strtolower((string) ($firstKegiatan['nama_kegiatan'] ?? '')), 'sensus ekonomi')
            || (string) ($firstKegiatan['jenis_kegiatan'] ?? '') === 'sensus'
            || str_contains((string) data_get($viewData, 'bast.nomor_bast', ''), 'BAST-SE2026');

        return $isSensusEkonomi
            ? 'bast-lampiran-spk-sensus-ekonomi'
            : 'bast-lampiran-spk';
    }

    private function getAlokasiLatestTanggalSelesai(AlokasiPetugas $alokasi): ?string
    {
        $periode = $alokasi->periodeAlokasi;
        $isPengolahanRole = in_array($alokasi->peran, self::PENGOLAHAN_ROLES, true);
        $hasListing = (int) ($alokasi->jumlah_satuan_listing ?? 0) > 0;
        $hasPencacahan = (int) ($alokasi->jumlah_satuan ?? 0) > 0;

        $candidates = [];

        if ($isPengolahanRole) {
            if ($hasListing) {
                $candidates[] = $this->normalizeDateForCompare($periode?->jadwal_pengolahan_listing_selesai);
            }

            if ($hasPencacahan) {
                $candidates[] = $this->normalizeDateForCompare($periode?->jadwal_pengolahan_pencacahan_selesai);
            }

            if (empty(array_filter($candidates))) {
                $candidates[] = $this->normalizeDateForCompare($periode?->jadwal_pengolahan_listing_selesai);
                $candidates[] = $this->normalizeDateForCompare($periode?->jadwal_pengolahan_pencacahan_selesai);

                // Backward-compatible fallback for kegiatan that do not fill jadwal_pengolahan_* fields.
                $candidates[] = $this->normalizeDateForCompare($periode?->tanggal_selesai_listing);
                $candidates[] = $this->normalizeDateForCompare($periode?->tanggal_selesai);
            }
        } else {
            if ($hasListing) {
                $candidates[] = $this->normalizeDateForCompare($periode?->tanggal_selesai_listing);
            }

            if ($hasPencacahan) {
                $candidates[] = $this->normalizeDateForCompare($periode?->tanggal_selesai);
            }

            if (empty(array_filter($candidates))) {
                $candidates[] = $this->normalizeDateForCompare($periode?->tanggal_selesai_listing);
                $candidates[] = $this->normalizeDateForCompare($periode?->tanggal_selesai);
            }
        }

        return collect($candidates)->filter()->max();
    }

    private function getEffectiveAlokasiByKegiatan(Collection $alokasiGroup): Collection
    {
        return $alokasiGroup
            ->groupBy(function ($alokasi) {
                return $alokasi->periodeAlokasi->kegiatan_id;
            })
            ->map(function ($kegiatanGroup) {
                // Priority: perubahan > direvisi > disetujui > dikirim
                $perubahan = $kegiatanGroup->first(fn ($a) => $a->periodeAlokasi->status === 'perubahan');
                if ($perubahan) {
                    return $perubahan;
                }

                $direvisi = $kegiatanGroup->first(fn ($a) => $a->periodeAlokasi->status === 'direvisi');
                if ($direvisi) {
                    return $direvisi;
                }

                $disetujui = $kegiatanGroup->first(fn ($a) => $a->periodeAlokasi->status === 'disetujui');
                if ($disetujui) {
                    return $disetujui;
                }

                return $kegiatanGroup->first(fn ($a) => $a->periodeAlokasi->status === 'dikirim');
            })
            ->filter();
    }

    private function getEffectiveAlokasiForPetugasInMonth(int $petugasId, string $bulanFormatted, int $tahun): Collection
    {
        $allAlokasi = AlokasiPetugas::query()
            ->where('petugas_id', $petugasId)
            ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                $q->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                    ->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan']);
            })
            ->with('periodeAlokasi:id,kegiatan_id,status,created_at')
            ->get();

        return $this->getEffectiveAlokasiByKegiatan($allAlokasi)->values();
    }

    private function getBappSeTerminDataForSpk(int $spkId): array
    {
        $termins = BappSeTermin::query()
            ->where('spk_id', $spkId)
            ->whereIn('termin', [1, 2])
            ->get()
            ->keyBy('termin');

        /** @var BappSeTermin|null $terminI */
        $terminI = $termins->get(1);
        /** @var BappSeTermin|null $terminII */
        $terminII = $termins->get(2);

        $realisasiUnitSampel = [];

        foreach ([$terminI, $terminII] as $termin) {
            if (! $termin) {
                continue;
            }

            $unitSampel = is_array($termin->realisasi_unit_sampel) ? $termin->realisasi_unit_sampel : [];

            foreach ($unitSampel as $key => $value) {
                $key = (string) $key;
                $realisasiUnitSampel[$key] = ($realisasiUnitSampel[$key] ?? 0) + max(0, (int) $value);
            }
        }

        $targetSls = $terminI?->target_sls ?? $terminII?->target_sls ?? null;
        $fasihScreenshotPath = $terminII?->fasih_screenshot_path;
        $terminIIComplete = $terminII !== null
            && ! empty($terminII->realisasi_unit_sampel)
            && filled($terminII->fasih_screenshot_path);

        return [
            'realisasi_unit_sampel' => $realisasiUnitSampel,
            'target_sls' => $targetSls !== null ? (int) $targetSls : null,
            'fasih_screenshot_path' => filled($fasihScreenshotPath) ? $fasihScreenshotPath : null,
            'termin_ii_complete' => $terminIIComplete,
            'termin_ii_has_screenshot' => filled($fasihScreenshotPath),
        ];
    }

    private function hasPositiveBastAttachmentPayloadForPetugas(int $petugasId, string $bulanFormatted, int $tahun): bool
    {
        // Get latest document (SPK or addendum) for this petugas in this month
        $latestDocument = Spk::query()
            ->where('petugas_id', $petugasId)
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                $q->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                    ->where('tahun', $tahun);
            })
            ->orderBy('addendum_number', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        // If no SPK found for this month, petugas shouldn't be in BAST
        if (! $latestDocument) {
            return false;
        }

        // Get alokasi_petugas_ids from latest document
        $latestAlokasIds = $latestDocument->alokasi_petugas_ids ?? [$latestDocument->alokasi_petugas_id];

        // Get periode_alokasi_ids from those alokasi
        $latestPeriodeIds = AlokasiPetugas::whereIn('id', $latestAlokasIds)
            ->pluck('periode_alokasi_id')
            ->sort()
            ->values()
            ->toArray();

        // Get all allocations for this petugas in this month
        $allAlokasi = AlokasiPetugas::query()
            ->where('petugas_id', $petugasId)
            ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                $q->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                    ->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan']);
            })
            ->with('periodeAlokasi')
            ->get();

        if ($allAlokasi->isEmpty()) {
            return false;
        }

        // Get all kegiatan IDs that this petugas is involved in (from any status)
        $kegiatanIds = $allAlokasi->pluck('periodeAlokasi.kegiatan_id')->unique();

        // For each kegiatan, check if there's a 'perubahan' periode that removes this petugas
        foreach ($kegiatanIds as $kegiatanId) {
            $perubahanPeriode = PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
                ->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                ->where('tahun', $tahun)
                ->where('status', 'perubahan')
                ->first();

            // If there's a perubahan periode for this kegiatan, check if petugas is removed
            if ($perubahanPeriode) {
                $hasAlokasiInPerubahan = AlokasiPetugas::where('petugas_id', $petugasId)
                    ->where('periode_alokasi_id', $perubahanPeriode->id)
                    ->exists();

                // If petugas is NOT in perubahan periode, they are removed - filter out this kegiatan
                if (! $hasAlokasiInPerubahan) {
                    $allAlokasi = $allAlokasi->filter(function ($alokasi) use ($kegiatanId) {
                        return $alokasi->periodeAlokasi->kegiatan_id !== $kegiatanId;
                    });
                }
            }
        }

        // After filtering removed kegiatan, check if there are any allocations left
        if ($allAlokasi->isEmpty()) {
            return false;
        }

        // Get effective allocations using priority: perubahan > direvisi > disetujui > dikirim
        $effectiveAlokasi = $this->getEffectiveAlokasiByKegiatan($allAlokasi);

        // Get current periode_alokasi_ids
        $currentPeriodeIds = $effectiveAlokasi
            ->pluck('periode_alokasi_id')
            ->sort()
            ->values()
            ->toArray();

        // Calculate current total honor
        $currentTotalHonor = $effectiveAlokasi->sum(function ($alokasi) {
            return ($alokasi->total_honor ?? 0) + ($alokasi->total_honor_listing ?? 0);
        });

        // Calculate nilai_kontrak from latest document
        $latestNilaiKontrak = (float) $latestDocument->nilai_kontrak;

        // BAST can only be created if:
        // 1. NO change in daftar kegiatan (periode_alokasi_ids unchanged), AND
        // 2. NO change in total honor (nilai_kontrak unchanged), AND
        // 3. Current total honor is POSITIVE (has actual work/payment)
        $periodeIdsChanged = $latestPeriodeIds !== $currentPeriodeIds;
        $nilaiKontrakChanged = abs($latestNilaiKontrak - $currentTotalHonor) > 0.01;

        // If there are changes, addendum is needed first - cannot create BAST
        if ($periodeIdsChanged || $nilaiKontrakChanged) {
            return false;
        }

        // If current total honor is 0 or negative, no BAST attachment - cannot create BAST
        if ($currentTotalHonor <= 0) {
            return false;
        }

        // Verify that there's at least one allocation with positive values
        return $effectiveAlokasi->contains(function ($alokasi) {
            return
                (int) ($alokasi->jumlah_satuan ?? 0) > 0 ||
                (int) ($alokasi->jumlah_satuan_listing ?? 0) > 0 ||
                (float) ($alokasi->total_honor ?? 0) > 0 ||
                (float) ($alokasi->total_honor_listing ?? 0) > 0;
        });
    }

    private function isLegacyBastAttachmentMode(string $bulanFormatted, int $tahun): bool
    {
        $bulan = (int) ltrim($bulanFormatted, '0');

        return $tahun < 2026 || ($tahun === 2026 && $bulan < 4);
    }

    private function hasPositiveEffectiveAlokasiForPetugasInMonth(int $petugasId, string $bulanFormatted, int $tahun): bool
    {
        $effectiveAlokasi = $this->getEffectiveAlokasiForPetugasInMonth($petugasId, $bulanFormatted, $tahun);

        if ($effectiveAlokasi->isEmpty()) {
            return false;
        }

        return $effectiveAlokasi->contains(function ($alokasi) {
            return
                (int) ($alokasi->jumlah_satuan ?? 0) > 0 ||
                (int) ($alokasi->jumlah_satuan_listing ?? 0) > 0 ||
                (float) ($alokasi->total_honor ?? 0) > 0 ||
                (float) ($alokasi->total_honor_listing ?? 0) > 0;
        });
    }

    private function buildBastListForPeriod(
        PeriodeAlokasi $periode,
        bool $canManageMain,
        bool $isKetuaTim,
        Request $request,
        ?Bast $currentBast = null,
        bool $isSensusEkonomiMode = false,
    ): Collection {
        $user = $this->getRequestUser($request);

        return Bast::with([
            'spk.alokasiPetugas.petugas',
            'createdBy:id,name',
            'bastKegiatan.kegiatan:id,ketua_tim_user_id,pj_lainnya_id',
        ])
            ->whereHas('periodeAlokasi', function ($query) use ($periode) {
                $bulanFormatted = str_pad((string) $periode->bulan, 2, '0', STR_PAD_LEFT);

                $query->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                    ->where('tahun', $periode->tahun);
            })
            ->when($isSensusEkonomiMode, function ($query) {
                $this->applyBastNomorModeFilter($query, true);
            }, function ($query) {
                $this->applyBastNomorModeFilter($query, false);
            })
            ->when($isKetuaTim, function ($query) use ($user) {
                $query->whereHas('bastKegiatan.kegiatan', function ($q) use ($user) {
                    $q->where(function ($sub) use ($user) {
                        $sub->where('ketua_tim_user_id', $user?->id)
                            ->orWhere('pj_lainnya_id', $user?->id);
                    });
                });
            })
            ->orderBy('nomor_bast')
            ->get()
            ->filter(function (Bast $item) use ($canManageMain, $isKetuaTim, $request) {
                if ($canManageMain || $isKetuaTim) {
                    return true;
                }

                return $this->userCanAccessBast($request, $item);
            })
            ->map(function (Bast $bast) use ($currentBast, $periode) {
                $petugasNama = $bast->spk?->alokasiPetugas?->petugas?->nama ?? 'Unknown';
                $petugasId = $bast->spk?->alokasiPetugas?->petugas?->id;
                $allLampiranSigned = $bast->bastKegiatan->isNotEmpty()
                    && $bast->bastKegiatan->every(
                        fn (BastKegiatan $item) => filled($item->signed_file_path)
                    );
                $isLegacyMode = (int) $periode->tahun < 2026
                    || ((int) $periode->tahun === 2026 && (int) $periode->bulan < 4);

                if (! $isLegacyMode
                    && filled($bast->main_signed_file_path)
                    && $allLampiranSigned
                    && (blank($bast->signed_file_path) || $bast->status !== 'diserahkan')) {
                    $this->syncCompiledBastFiles($bast);
                    $bast->refresh();
                }

                $finalSignedReady = filled($bast->signed_file_path);

                return [
                    'id' => $bast->id,
                    'hashed_id' => $bast->hashed_id,
                    'nomor_bast' => $bast->nomor_bast,
                    'petugas_nama' => $petugasNama,
                    'petugas_id' => $petugasId,
                    'file_path' => $bast->file_path,
                    'compiled_file_path' => $bast->compiled_file_path,
                    'main_signed_file_path' => $bast->main_signed_file_path,
                    'signed_file_path' => $bast->signed_file_path,
                    'status' => $bast->status,
                    'final_signed_ready' => $finalSignedReady,
                    'is_current' => $currentBast?->id === $bast->id,
                ];
            });
    }

    private function getTargetPeriode(int $kegiatanId): ?PeriodeAlokasi
    {
        $perubahanWithSpk = PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
            ->where('status', 'perubahan')
            ->whereHas('spk')
            ->orderByDesc('id')
            ->first();

        if ($perubahanWithSpk) {
            return $perubahanWithSpk;
        }

        $dikirimWithSpk = PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
            ->where('status', 'dikirim')
            ->whereHas('spk')
            ->orderByDesc('id')
            ->first();

        if ($dikirimWithSpk) {
            return $dikirimWithSpk;
        }

        $perubahan = PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
            ->where('status', 'perubahan')
            ->orderByDesc('id')
            ->first();

        if ($perubahan) {
            return $perubahan;
        }

        return PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
            ->where('status', 'dikirim')
            ->orderByDesc('id')
            ->first();
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
}
