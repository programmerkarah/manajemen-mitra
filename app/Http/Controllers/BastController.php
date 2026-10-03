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

class BastController extends Controller
{
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


    private function getRequestUser(Request $request)
    {
        return effectiveUser($request) ?? $request->user();
    }

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
    private function resolveBastDetailReferencePeriod(Request $request, Bast $bast): array
    {
        $requestedBulan = (int) $request->input('bulan', 0);
        $requestedTahun = (int) $request->input('tahun', 0);

        if ($requestedBulan >= 1 && $requestedBulan <= 12 && $requestedTahun >= 2000) {
            return [
                'bulan' => $requestedBulan,
                'tahun' => $requestedTahun,
                'bulan_label' => $this->getBulanLabel($requestedBulan),
            ];
        }

        $sessionFilters = $request->session()->get('bast_open_detail_filters');
        if (
            is_array($sessionFilters)
            && isset($sessionFilters['bulan'], $sessionFilters['tahun'])
            && (int) $sessionFilters['bulan'] >= 1
            && (int) $sessionFilters['bulan'] <= 12
            && (int) $sessionFilters['tahun'] >= 2000
        ) {
            return [
                'bulan' => (int) $sessionFilters['bulan'],
                'tahun' => (int) $sessionFilters['tahun'],
                'bulan_label' => $this->getBulanLabel((int) $sessionFilters['bulan']),
            ];
        }

        $periode = $bast->periodeAlokasi;

        return [
            'bulan' => (int) $periode->bulan,
            'tahun' => (int) $periode->tahun,
            'bulan_label' => $this->getBulanLabel((int) $periode->bulan),
        ];
    }

    private function resolveBastFromHashedId(string $hashedId): ?Bast
    {
        return (new Bast)->resolveRouteBinding($hashedId);
    }

    private function userCanManageBastMain(Request $request): bool
    {
        $user = $this->getRequestUser($request);

        return $user && $user->hasAnyRole(['admin', 'operator']);
    }

    private function userCanManageLampiran(Request $request, BastKegiatan $bastKegiatan): bool
    {
        $user = $this->getRequestUser($request);

        if (! $user) {
            return false;
        }

        if (in_array($user->active_role, ['admin', 'operator'], true)) {
            return true;
        }

        if ($user->active_role !== 'ketua_tim') {
            return false;
        }

        return (int) $bastKegiatan->kegiatan?->ketua_tim_user_id === (int) $user->id
            || (int) $bastKegiatan->kegiatan?->pj_lainnya_id === (int) $user->id;
    }

    private function userCanAccessBast(Request $request, Bast $bast): bool
    {
        $user = $this->getRequestUser($request);

        if (! $user) {
            return false;
        }

        if (in_array($user->active_role, ['admin', 'operator'], true)) {
            return true;
        }

        // Ketua tim can open BAST detail across the period.
        // Lampiran actions are still restricted by userCanManageLampiran().
        return $user->active_role === 'ketua_tim';
    }


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


    public function openDetailByPetugas(Request $request): Response|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $decrypted = [];
            if ($request->has('encrypted_filters')) {
                $decrypted = decryptFilters($request->input('encrypted_filters'));
            }

            $request->merge($decrypted);

            $validated = $request->validate([
                'bulan' => 'required|integer|min:1|max:12',
                'tahun' => 'required|integer|min:2000',
                'petugas_id' => 'nullable|integer|exists:petugas,id',
                'mode' => 'nullable|string',
            ]);

            $filters = [
                'bulan' => (int) $validated['bulan'],
                'tahun' => (int) $validated['tahun'],
            ];

            if (filled($validated['mode'] ?? null) && (string) $validated['mode'] !== 'regular') {
                $filters['mode'] = (string) $validated['mode'];
            }

            $request->session()->put('bast_open_detail_filters', $filters);

            $routeParams = [];

            if (filled($validated['petugas_id'] ?? null)) {
                $routeParams['petugas_id'] = (int) $validated['petugas_id'];
            }

            if (filled($validated['mode'] ?? null) && (string) $validated['mode'] !== 'regular') {
                $routeParams['mode'] = (string) $validated['mode'];
            }

            return redirect()->route('bast.open-detail-by-petugas', $routeParams);
        }

        if ($request->filled('state')) {
            $request->merge(decryptFilters((string) $request->query('state')));
        }

        $filters = null;

        if ($request->hasAny(['bulan', 'tahun'])) {
            $validated = $request->validate([
                'bulan' => 'required|integer|min:1|max:12',
                'tahun' => 'required|integer|min:2000',
                'petugas_id' => 'nullable|integer|exists:petugas,id',
                'mode' => 'nullable|string',
            ]);

            $filters = [
                'bulan' => (int) $validated['bulan'],
                'tahun' => (int) $validated['tahun'],
            ];

            if (filled($validated['mode'] ?? null) && (string) $validated['mode'] !== 'regular') {
                $filters['mode'] = (string) $validated['mode'];
            }

            $request->session()->put('bast_open_detail_filters', $filters);
        }

        if (! is_array($filters)) {
            $filters = $request->session()->get('bast_open_detail_filters');
            if (! is_array($filters) || ! isset($filters['bulan'], $filters['tahun'])) {
                return redirect()->route('bast.index')
                    ->with('error', 'Pilih periode terlebih dahulu untuk membuka detail BAST.');
            }
        }

        $selectedPetugasId = $request->input('petugas_id');
        if (filled($selectedPetugasId) && is_numeric((string) $selectedPetugasId)) {
            $filters['petugas_id'] = (int) $selectedPetugasId;
        }

        if (filled($request->input('mode')) && (string) $request->input('mode') !== 'regular') {
            $filters['mode'] = (string) $request->input('mode');
        }

        $request->merge($filters);

        return $this->listByMonth($request);
    }

    /**
     * Check if any petugas has pendataan allocation with hasil_pendataan_lapangan > 0
     */
    private function hasPendataan(array $petugas): bool
    {
        return collect($petugas)->contains(function ($p) {
            return in_array($p['peran'] ?? null, self::PENDATAAN_ROLES, true)
                && (int) ($p['hasil_pendataan_lapangan'] ?? 0) > 0;
        });
    }

    /**
     * Check if any petugas has listing allocation with hasil_listing > 0
     */
    private function hasListing(array $petugas): bool
    {
        return collect($petugas)->contains(function ($p) {
            return in_array($p['peran'] ?? null, self::PENDATAAN_ROLES, true)
                && (int) ($p['hasil_listing'] ?? 0) > 0;
        });
    }

    /**
     * Determine if the BAST should use the FASIH clause.
     *
     * Returns true if any non-pengolahan alokasi belongs to a kegiatan
     * that uses CAPI as its metode_pendataan. If all non-pengolahan alokasi
     * use PAPI (or metode is null / not yet set) the clause is omitted.
     *
     * @param  iterable<AlokasiPetugas>  $allAlokasi
     */
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

    /**
     * @return array<int, string>
     */
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

    /**
     * Get effective allocation by kegiatan using priority:
     * perubahan > direvisi > disetujui > dikirim
     */
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


    /**
     * @return array{keluarga:int,usaha:int,total:int}
     */


    /**
     * @return Collection<int, Spk>
     */


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

    /**
     * Display a listing of the resource.
     * Menampilkan periode bulan (Januari-Desember) dengan informasi BAST yang sudah/belum dibuat
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $activeYear = ActiveYearService::get();
        $user = $this->getRequestUser($request);
        $canAccessSensusMode = $this->canAccessSensusMode($user, $activeYear);
        $requestedMode = (string) $request->session()->get('bast_index_mode', 'regular');
        $mode = $requestedMode === 'sensus-ekonomi' && $canAccessSensusMode
            ? 'sensus-ekonomi'
            : 'regular';

        if ($mode !== $requestedMode) {
            $request->session()->put('bast_index_mode', $mode);
        }
        $isSensusEkonomiMode = $mode === 'sensus-ekonomi';
        $sensusPetugasByMonth = collect();

        if ($isSensusEkonomiMode) {
            $excludedOldPetugasIds = Schema::hasTable('sensus_ekonomi_petugas_replacements')
                ? DB::table('sensus_ekonomi_petugas_replacements')
                    ->where('termin_i_paid', 0)
                    ->where('status', '!=', 'dibatalkan')
                    ->pluck('petugas_berhenti_id')
                : collect();

            $sensusPetugasIds = Spk::query()
                ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($activeYear) {
                    $q->where('tahun', $activeYear);
                })
                ->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($q) {
                    $q->where('nama_kegiatan', 'like', '%Sensus Ekonomi%');
                })
                ->whereHas('alokasiPetugas', function ($q) {
                    $q->where(function ($inner) {
                        $inner->where('jumlah_satuan', '>', 0)
                            ->orWhere('jumlah_satuan_listing', '>', 0)
                            ->orWhere('total_honor', '>', 0)
                            ->orWhere('total_honor_listing', '>', 0);
                    });
                })
                ->pluck('petugas_id')
                ->filter()
                ->unique()
                ->values()
                ->diff($excludedOldPetugasIds)
                ->values();

            // Business rule: all Sensus Ekonomi PK are processed in August BAST batch.
            $sensusPetugasByMonth = collect([
                8 => $sensusPetugasIds,
            ]);
        }

        // Ambil semua SPK yang punya alokasi > 0 pada periode status 'perubahan' (final allocation state) di tahun berjalan
        // Konsisten dengan filtering di create() method
        $eligibleSpks = DB::table('spk')
            ->join('alokasi_petugas as ap', 'ap.petugas_id', '=', 'spk.petugas_id')
            ->join('periode_alokasi as pa', 'ap.periode_alokasi_id', '=', 'pa.id')
            ->join('kegiatan as k', 'pa.kegiatan_id', '=', 'k.id')
            ->where('spk.addendum_number', 0)
            ->where('pa.tahun', $activeYear)
            ->where('pa.status', 'perubahan')
            ->when($isSensusEkonomiMode, function ($query) {
                $query->where('k.nama_kegiatan', 'like', '%Sensus Ekonomi%');
            }, function ($query) {
                $query->where('k.nama_kegiatan', 'not like', '%Sensus Ekonomi%');
            })
            ->where(function ($q) {
                $q->where('ap.jumlah_satuan', '>', 0)
                    ->orWhere('ap.jumlah_satuan_listing', '>', 0)
                    ->orWhere('ap.total_honor', '>', 0)
                    ->orWhere('ap.total_honor_listing', '>', 0);
            })
            ->distinct('spk.petugas_id')
            ->select('spk.*')
            ->get();

        // Untuk setiap SPK, tentukan bulan periode alokasi pertamanya di tahun berjalan (alokasi > 0, status perubahan)
        // Ambil seluruh alokasi_petugas yang join ke periode_alokasi (tahun aktif, status perubahan, jumlah > 0)
        $alokasiRows = DB::table('alokasi_petugas as ap')
            ->join('periode_alokasi as pa', 'ap.periode_alokasi_id', '=', 'pa.id')
            ->join('kegiatan as k', 'pa.kegiatan_id', '=', 'k.id')
            ->where('pa.tahun', $activeYear)
            ->where('pa.status', 'perubahan')
            ->when($isSensusEkonomiMode, function ($query) {
                $query->where('k.nama_kegiatan', 'like', '%Sensus Ekonomi%');
            }, function ($query) {
                $query->where('k.nama_kegiatan', 'not like', '%Sensus Ekonomi%');
            })
            ->where(function ($q) {
                $q->where('ap.jumlah_satuan', '>', 0)
                    ->orWhere('ap.jumlah_satuan_listing', '>', 0)
                    ->orWhere('ap.total_honor', '>', 0)
                    ->orWhere('ap.total_honor_listing', '>', 0);
            })
            ->select('ap.petugas_id', 'pa.bulan')
            ->get();

        // Untuk setiap bulan, kumpulkan petugas unik
        $spkByBulan = [];
        foreach (range(1, 12) as $bulan) {
            $petugasIds = $alokasiRows->filter(function ($row) use ($bulan) {
                return (int) ltrim($row->bulan, '0') === $bulan;
            })->pluck('petugas_id')->unique()->values();
            $spkByBulan[$bulan] = $petugasIds->all();
        }

        $data = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);
            $isLegacyBastMode = $this->isLegacyBastAttachmentMode($bulanFormatted, (int) $activeYear);

            if ($isSensusEkonomiMode) {
                $eligiblePetugasCount = $bulan === 8
                    ? $sensusPetugasByMonth->get($bulan, collect())->count()
                    : 0;
            } else {
                // Get all unique petugas who have SPK (original or addendum) in this month
                $allPetugasIds = Spk::whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($activeYear, $bulanFormatted) {
                    $q->where('tahun', $activeYear)
                        ->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted));
                })
                    ->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($relationQuery) {
                        $relationQuery->where('nama_kegiatan', 'not like', '%Sensus Ekonomi%');
                    })
                    ->distinct()
                    ->pluck('petugas_id')
                    ->filter()
                    ->unique()
                    ->values();

                // Filter petugas using the same logic as create() method
                $eligiblePetugasIds = $allPetugasIds->filter(function ($petugasId) use ($bulanFormatted, $activeYear, $isLegacyBastMode) {
                    if ($isLegacyBastMode) {
                        return $this->hasPositiveBastAttachmentPayloadForPetugas(
                            (int) $petugasId,
                            $bulanFormatted,
                            (int) $activeYear
                        );
                    }

                    return $this->hasPositiveEffectiveAlokasiForPetugasInMonth(
                        (int) $petugasId,
                        $bulanFormatted,
                        (int) $activeYear
                    );
                });

                $eligiblePetugasCount = $eligiblePetugasIds->count();
            }

            // Samakan metrik "BAST dibuat" dengan detail bulan (jumlah petugas pada dokumen BAST bulan tersebut)
            $petugasWithBast = DB::table('bast_petugas as bp')
                ->join('bast as b', 'bp.bast_id', '=', 'b.id')
                ->whereYear('b.tanggal_bast', $activeYear)
                ->whereMonth('b.tanggal_bast', $bulan)
                ->when($isSensusEkonomiMode, function ($query) {
                    $this->applyBastNomorModeFilter($query, true, 'b.nomor_bast');
                }, function ($query) {
                    $this->applyBastNomorModeFilter($query, false, 'b.nomor_bast');
                })
                ->distinct('bp.petugas_id')
                ->count('bp.petugas_id');

            if ($isSensusEkonomiMode && $bulan !== 8) {
                $petugasWithBast = 0;
            }

            $totalPetugas = max($eligiblePetugasCount, $petugasWithBast);
            $petugasWithoutBast = max(0, $totalPetugas - $petugasWithBast);

            $data[] = [
                'bulan' => $bulan,
                'bulan_label' => $this->getBulanLabel($bulan),
                'tahun' => $activeYear,
                'total_spk' => $totalPetugas,
                'spk_with_bast' => $petugasWithBast,
                'spk_without_bast' => $petugasWithoutBast,
                'visible_petugas_count' => $eligiblePetugasCount,
                'has_spk' => $totalPetugas > 0,
                'all_completed' => $totalPetugas > 0 && $petugasWithoutBast === 0,
            ];
        }

        // Encrypt sensitive data
        $encryptedData = encryptData($data);

        return Inertia::render('Bast/Index', [
            'data' => [
                'encrypted' => $encryptedData,
            ],
            'filters' => [
                'search' => $search,
                'mode' => $mode,
            ],
            'active_year' => $activeYear,
            'mode' => $mode,
            'can_access_sensus_mode' => $canAccessSensusMode,
        ]);
    }

    public function switchIndexMode(Request $request): RedirectResponse
    {
        $state = decryptFilters((string) $request->input('encrypted_filters'));
        $mode = (string) ($state['mode'] ?? 'regular');

        if (! in_array($mode, ['regular', 'sensus-ekonomi'], true)) {
            $mode = 'regular';
        }

        $request->session()->put('bast_index_mode', $mode);

        return redirect()->route('bast.index');
    }
    public function openCreate(Request $request): RedirectResponse
    {
        $state = decryptFilters((string) $request->input('encrypted_filters'));
        $bulan = (int) ($state['bulan'] ?? 0);
        $tahun = (int) ($state['tahun'] ?? 0);
        $mode = (string) ($state['mode'] ?? 'regular');

        if ($bulan < 1 || $bulan > 12 || $tahun < 2000) {
            return back()->with('error', 'Periode BAST tidak valid.');
        }

        if (! in_array($mode, ['regular', 'sensus-ekonomi'], true)) {
            $mode = 'regular';
        }

        if ($mode === 'sensus-ekonomi'
            && ! $this->canAccessSensusMode($this->getRequestUser($request), $tahun)) {
            return back()->with('error', 'Anda tidak memiliki akses ke BAST Sensus Ekonomi.');
        }

        $request->session()->put('bast_create_filters', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'mode' => $mode,
        ]);

        return redirect()->route('bast.create');
    }
    private function canAccessSensusMode(?User $user, ?int $tahunAnggaran = null): bool
    {
        if (! $user) {
            return false;
        }

        if (in_array($user->active_role, ['admin', 'operator'], true)) {
            return true;
        }

        if ($user->active_role !== 'ketua_tim') {
            return false;
        }

        $activeYear = $tahunAnggaran ?? ActiveYearService::get();

        return Kegiatan::query()
            ->where('tahun_anggaran', $activeYear)
            ->where('nama_kegiatan', 'like', '%Sensus Ekonomi%')
            ->where(function ($query) use ($user) {
                $query->where('ketua_tim_user_id', $user->id)
                    ->orWhere('pj_lainnya_id', $user->id);
            })
            ->exists();
    }

    /**
     * List all BAST for a specific month with filter
     */
    public function listByMonth(Request $request): Response|RedirectResponse
    {
        $decrypted = [];
        if ($request->has('encrypted_filters')) {
            $decrypted = decryptFilters($request->input('encrypted_filters'));
        }

        $request->merge($decrypted);

        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $selectedPetugasId = (int) $request->input('petugas_id', 0);
        $requestedMode = (string) $request->input('mode', 'regular');
        $user = $this->getRequestUser($request);
        $canAccessSensusMode = $this->canAccessSensusMode($user, (int) $tahun);
        $mode = $requestedMode === 'sensus-ekonomi' && $canAccessSensusMode
            ? 'sensus-ekonomi'
            : 'regular';
        $isSensusEkonomiMode = $mode === 'sensus-ekonomi';
        $activeYear = ActiveYearService::get();

        // Default to current year if no filter
        if (! $tahun) {
            $tahun = $activeYear;
        }

        $bulanFormatted = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

        $isKetuaTim = $user?->active_role === 'ketua_tim';

        // Get first BAST for this month, filtered by kegiatan managed by the current ketua tim so
        // they land on a BAST that actually contains their lampiran (not an unrelated BAST).
        $firstBast = Bast::query()
            ->whereYear('tanggal_bast', (int) $tahun)
            ->when($bulan, function ($query) use ($bulan) {
                $query->whereMonth('tanggal_bast', (int) $bulan);
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
            ->orderBy('created_at', 'desc')
            ->first();

        if ($selectedPetugasId > 0) {
            $selectedPetugasBast = Bast::query()
                ->whereYear('tanggal_bast', (int) $tahun)
                ->whereMonth('tanggal_bast', (int) $bulan)
                ->when($isSensusEkonomiMode, function ($query) {
                    $this->applyBastNomorModeFilter($query, true);
                }, function ($query) {
                    $this->applyBastNomorModeFilter($query, false);
                })
                ->where(function ($query) use ($selectedPetugasId) {
                    $query->whereHas('spk.alokasiPetugas', function ($relationQuery) use ($selectedPetugasId) {
                        $relationQuery->where('petugas_id', $selectedPetugasId);
                    })->orWhereHas('bastPetugas', function ($relationQuery) use ($selectedPetugasId) {
                        $relationQuery->where('petugas_id', $selectedPetugasId);
                    });
                })
                ->latest('created_at')
                ->first();

            if ($selectedPetugasBast) {
                return $this->show($request, $selectedPetugasBast);
            }
        }

        // For April 2026+, when selecting a petugas without BAST (or no BAST exists yet),
        // show same detail layout without generating BAST document.
        if ($selectedPetugasId > 0 || ! $firstBast) {
            $canManageMain = $this->userCanManageBastMain($request);

            $periodeReference = PeriodeAlokasi::query()
                ->where('tahun', $tahun)
                ->when($bulan, function ($query) use ($bulanFormatted) {
                    $query->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted));
                })
                ->latest('id')
                ->first();

            $bastList = $periodeReference
                ? $this->buildBastListForPeriod($periodeReference, $canManageMain, $isKetuaTim, $request, null, $isSensusEkonomiMode)
                : collect();

            $existingBastPetugasIds = Bast::query()
                ->with('spk:id,petugas_id')
                ->whereYear('tanggal_bast', (int) $tahun)
                ->whereMonth('tanggal_bast', (int) $bulan)
                ->when($isSensusEkonomiMode, function ($query) {
                    $this->applyBastNomorModeFilter($query, true);
                }, function ($query) {
                    $this->applyBastNomorModeFilter($query, false);
                })
                ->get()
                ->pluck('spk.petugas_id')
                ->filter()
                ->unique();

            $isLegacyBastMode = $this->isLegacyBastAttachmentMode($bulanFormatted, (int) $tahun);

            $eligibleWithoutBast = Spk::with('alokasiPetugas.petugas')
                ->whereNotIn('petugas_id', $existingBastPetugasIds)
                ->when($isSensusEkonomiMode, function ($query) use ($tahun) {
                    $query->whereHas('alokasiPetugas.periodeAlokasi', function ($periodeQuery) use ($tahun) {
                        $periodeQuery->where('tahun', (int) $tahun);
                    })
                        ->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($kegiatanQuery) {
                            $kegiatanQuery->where('nama_kegiatan', 'like', '%Sensus Ekonomi%');
                        });
                }, function ($query) use ($bulanFormatted, $tahun) {
                    $query->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                        $q->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                            ->where('tahun', $tahun)
                            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan']);
                    })->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($kegiatanQuery) {
                        $kegiatanQuery->where('nama_kegiatan', 'not like', '%Sensus Ekonomi%');
                    });
                })
                ->when($isKetuaTim, function ($query) use ($user, $bulanFormatted, $tahun, $isSensusEkonomiMode) {
                    $alokasiIds = AlokasiPetugas::whereHas('periodeAlokasi', function ($q) use ($user, $bulanFormatted, $tahun, $isSensusEkonomiMode) {
                        $q->where('tahun', $tahun)
                            ->when(! $isSensusEkonomiMode, function ($subQuery) use ($bulanFormatted) {
                                $subQuery->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted));
                            })
                            ->whereHas('kegiatan', function ($qk) use ($user, $isSensusEkonomiMode) {
                                if ($isSensusEkonomiMode) {
                                    $qk->where('nama_kegiatan', 'like', '%Sensus Ekonomi%');
                                }
                                $qk->where(function ($sub) use ($user) {
                                    $sub->where('ketua_tim_user_id', $user?->id)
                                        ->orWhere('pj_lainnya_id', $user?->id);
                                });
                            });
                    })
                        ->pluck('id')
                        ->toArray();

                    if (empty($alokasiIds)) {
                        $query->whereRaw('0 = 1');

                        return;
                    }

                    $query->where(function ($inner) use ($alokasiIds) {
                        foreach ($alokasiIds as $id) {
                            $inner->orWhereJsonContains('alokasi_petugas_ids', $id);
                        }
                    });
                })
                ->get()
                ->map(function ($spk) {
                    $petugas = $spk->alokasiPetugas?->petugas;

                    return [
                        'petugas_nama' => $petugas?->nama ?? 'Petugas tidak diketahui',
                        'petugas_id' => $petugas?->id,
                    ];
                })
                ->filter(function (array $item) use ($isLegacyBastMode, $bulanFormatted, $tahun, $isSensusEkonomiMode) {
                    if ($isSensusEkonomiMode) {
                        return true;
                    }

                    $petugasId = (int) ($item['petugas_id'] ?? 0);
                    if ($petugasId === 0) {
                        return false;
                    }

                    if ($isLegacyBastMode) {
                        return $this->hasPositiveBastAttachmentPayloadForPetugas($petugasId, $bulanFormatted, (int) $tahun);
                    }

                    return $this->hasPositiveEffectiveAlokasiForPetugasInMonth($petugasId, $bulanFormatted, (int) $tahun);
                })
                ->unique('petugas_id')
                ->sortBy('petugas_nama')
                ->values();

            if ($selectedPetugasId === 0 && $eligibleWithoutBast->isNotEmpty()) {
                $selectedPetugasId = (int) ($eligibleWithoutBast->first()['petugas_id'] ?? 0);
            }

            $selectedPetugas = null;
            $lampiranPreview = collect();
            $selectedSpk = null;
            $previewSensusReference = null;

            if ($selectedPetugasId > 0) {
                $selectedSpk = Spk::where('petugas_id', $selectedPetugasId)
                    ->when($isSensusEkonomiMode, function ($query) use ($tahun) {
                        $query->whereHas('alokasiPetugas.periodeAlokasi', function ($periodeQuery) use ($tahun) {
                            $periodeQuery->where('tahun', (int) $tahun);
                        })
                            ->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($kegiatanQuery) {
                                $kegiatanQuery->where('nama_kegiatan', 'like', '%Sensus Ekonomi%');
                            });
                    }, function ($query) use ($bulanFormatted, $tahun) {
                        $query->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                            $q->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                                ->where('tahun', $tahun);
                        })->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($kegiatanQuery) {
                            $kegiatanQuery->where('nama_kegiatan', 'not like', '%Sensus Ekonomi%');
                        });
                    })
                    ->orderByDesc('addendum_number')
                    ->orderByDesc('created_at')
                    ->first();

                $alokasiIdsFromLatestSpk = collect($selectedSpk?->alokasi_petugas_ids ?? [])
                    ->filter()
                    ->values();

                if ($selectedSpk?->alokasi_petugas_id) {
                    $alokasiIdsFromLatestSpk->push($selectedSpk->alokasi_petugas_id);
                }

                $alokasiIdsFromLatestSpk = $alokasiIdsFromLatestSpk->unique()->values();

                if ($isSensusEkonomiMode) {
                    $alokasiPreview = $this->getSensusEkonomiAlokasiForPetugasInYear($selectedPetugasId, (int) $tahun)
                        ->when($isKetuaTim, function ($collection) use ($user) {
                            return $collection->filter(function ($alokasi) use ($user) {
                                $kegiatan = $alokasi->periodeAlokasi?->kegiatan;

                                return (int) ($kegiatan?->ketua_tim_user_id ?? 0) === (int) ($user?->id ?? 0)
                                    || (int) ($kegiatan?->pj_lainnya_id ?? 0) === (int) ($user?->id ?? 0);
                            })->values();
                        });
                } else {
                    $alokasiPreviewQuery = AlokasiPetugas::query();

                    if ($alokasiIdsFromLatestSpk->isNotEmpty()) {
                        $alokasiPreviewQuery->whereIn('id', $alokasiIdsFromLatestSpk->all());
                    } else {
                        $alokasiPreviewQuery->where('petugas_id', $selectedPetugasId)
                            ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                                $q->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                                    ->where('tahun', $tahun)
                                    ->whereIn('status', ['dikirim', 'perubahan']);
                            });
                    }

                    $alokasiPreview = $alokasiPreviewQuery
                        ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                            $q->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                                ->where('tahun', $tahun)
                                ->whereIn('status', ['dikirim', 'perubahan']);
                        })
                        ->whereHas('petugas', function ($q) {
                            $q->where('jenis_petugas', 'non-organik');
                        })
                        ->where(function ($query) {
                            $query->where('total_honor', '>', 0)
                                ->orWhere('total_honor_listing', '>', 0);
                        })
                        ->when($isKetuaTim, function ($query) use ($user) {
                            $query->whereHas('periodeAlokasi.kegiatan', function ($q) use ($user) {
                                $q->where(function ($sub) use ($user) {
                                    $sub->where('ketua_tim_user_id', $user?->id)
                                        ->orWhere('pj_lainnya_id', $user?->id);
                                });
                            });
                        })
                        ->with([
                            'petugas',
                            'periodeAlokasi.kegiatan.ketuaTim',
                        ])
                        ->get();
                }

                $selectedPetugas = $alokasiPreview->first()?->petugas;

                $previewSensusReference = $selectedSpk && $this->isSensusEkonomiSpk($selectedSpk)
                    ? $this->buildSensusReferencePayload($selectedSpk, (int) $bulan, (int) $tahun)
                    : null;
                $sharedPreviewScreenshotPath = $previewSensusReference['fasih_screenshot_path'] ?? null;

                $lampiranPreview = $this->getEffectiveAlokasiByKegiatan($alokasiPreview)
                    ->values()
                    ->map(function (AlokasiPetugas $alokasi, int $index) use ($selectedSpk, $sharedPreviewScreenshotPath) {
                        $kegiatan = $alokasi->periodeAlokasi?->kegiatan;
                        $tanggalSelesai = $this->getAlokasiLatestTanggalSelesai($alokasi);
                        $formatted = '-';

                        if ($tanggalSelesai) {
                            try {
                                $formatted = Carbon::parse($tanggalSelesai)->locale('id')->isoFormat('D MMMM YYYY');
                            } catch (\Exception $e) {
                                $formatted = '-';
                            }
                        }

                        $previewDocumentState = $this->getPreviewLampiranDocumentState(
                            $selectedSpk,
                            (int) ($kegiatan?->id ?? 0),
                            (int) ($alokasi->periode_alokasi_id ?? 0),
                            (string) ($kegiatan?->kode_kegiatan ?? '-'),
                            $sharedPreviewScreenshotPath,
                        );
                        $usesFasihScreenshot = $this->shouldUseLampiranFasihScreenshot($kegiatan?->nama_kegiatan, $alokasi->peran);
                        $readyToGenerate = $this->isLampiranGenerationAllowed([
                            'tanggal_selesai' => $tanggalSelesai,
                            'nama_kegiatan' => $kegiatan?->nama_kegiatan,
                            'peran' => $alokasi->peran,
                            'fasih_screenshot_path' => $sharedPreviewScreenshotPath,
                            'bapp_termin_ii_complete' => $previewSensusReference['bapp_termin_ii_complete'] ?? null,
                        ]);

                        return [
                            'id' => $index + 1,
                            'kegiatan_id' => (int) ($kegiatan?->id ?? 0),
                            'periode_alokasi_id' => (int) ($alokasi->periode_alokasi_id ?? 0),
                            'kode_kegiatan' => $kegiatan?->kode_kegiatan ?? '-',
                            'nama_kegiatan' => $kegiatan?->nama_kegiatan ?? '-',
                            'jenis_kegiatan' => $kegiatan?->jenis_kegiatan ?? 'survei',
                            'peran' => $alokasi->peran,
                            'tanggal_selesai' => $tanggalSelesai,
                            'tanggal_selesai_formatted' => $formatted,
                            'ketua_tim_nama' => $kegiatan?->ketuaTim?->name,
                            'file_path' => $previewDocumentState['file_path'],
                            'signed_file_path' => $previewDocumentState['signed_file_path'],
                            'fasih_screenshot_path' => $previewDocumentState['fasih_screenshot_path'],
                            'generated_at' => $previewDocumentState['generated_at'],
                            'signed_uploaded_at' => $previewDocumentState['signed_uploaded_at'],
                            'status' => $previewDocumentState['status'],
                            'can_download' => $readyToGenerate,
                            'can_generate' => $readyToGenerate,
                            'can_upload_signed' => $previewDocumentState['can_upload_signed'],
                            'can_upload_fasih_screenshot' => false,
                            'can_preview' => $readyToGenerate,
                            'ready_to_generate' => $readyToGenerate,
                            'uses_fasih_screenshot' => $usesFasihScreenshot,
                            'preview_spk_id' => $selectedSpk?->id,
                        ];
                    });
            }

            $generatedLampiranPreviewCount = $lampiranPreview->filter(fn (array $item) => filled($item['file_path']))->count();
            $signedLampiranPreviewCount = $lampiranPreview->filter(fn (array $item) => filled($item['signed_file_path']))->count();
            $allLampiranPreviewGenerated = $lampiranPreview->isNotEmpty() && $generatedLampiranPreviewCount === $lampiranPreview->count();
            $allLampiranPreviewSigned = $lampiranPreview->isNotEmpty() && $signedLampiranPreviewCount === $lampiranPreview->count();
            $previewSensusReferenceForView = $previewSensusReference ?? null;

            return Inertia::render('Bast/Show', [
                'bast' => [
                    'id' => 0,
                    'hashed_id' => '',
                    'nomor_bast' => '-',
                    'tanggal_bast' => '-',
                    'tanggal_serah_terima' => '-',
                    'menggunakan_fasih' => false,
                    'uraian_pekerjaan' => '-',
                    'nama_ketua_tim' => '-',
                    'nip_ketua_tim' => null,
                    'nama_ppk' => '-',
                    'nip_ppk' => null,
                    'hasil_pekerjaan' => null,
                    'file_path' => null,
                    'compiled_file_path' => null,
                    'main_signed_file_path' => null,
                    'signed_file_path' => null,
                    'lokasi_kegiatan' => null,
                    'status' => 'draft',
                    'catatan' => null,
                    'is_sensus_ekonomi' => $selectedSpk ? $this->isSensusEkonomiSpk($selectedSpk) : false,
                    'muatan_input' => $previewSensusReferenceForView['muatan_input'] ?? null,
                    'muatan_prelist' => $previewSensusReferenceForView['muatan_prelist'] ?? null,
                    'realisasi_unit_sampel' => $previewSensusReferenceForView['realisasi_unit_sampel'] ?? null,
                    'fasih_screenshot_path' => $previewSensusReferenceForView['fasih_screenshot_path'] ?? null,
                    'created_by' => '-',
                    'created_at' => '-',
                    'is_legacy_mode' => false,
                ],
                'spk' => $selectedSpk ? [
                    'id' => $selectedSpk->id,
                    'hashed_id' => $selectedSpk->hashed_id,
                    'nomor_spk' => $selectedSpk->nomor_spk,
                    'tanggal_spk' => $selectedSpk->tanggal_spk?->format('Y-m-d') ?? '-',
                    'nilai_kontrak' => (float) ($selectedSpk->nilai_kontrak ?? 0),
                ] : null,
                'petugas' => $selectedPetugas ? [
                    'id' => $selectedPetugas->id,
                    'hashed_id' => $selectedPetugas->hashed_id ?? '',
                    'nama' => $selectedPetugas->nama,
                    'nik' => $selectedPetugas->nik,
                    'alamat' => $selectedPetugas->alamat,
                    'no_hp' => $selectedPetugas->no_hp,
                ] : null,
                'kegiatan' => [
                    'id' => 0,
                    'hashed_id' => '',
                    'kode_kegiatan' => '-',
                    'nama_kegiatan' => 'Belum ada BAST',
                    'jenis_kegiatan' => 'survei',
                    'tahun_anggaran' => (int) $tahun,
                ],
                'lampiran' => $lampiranPreview->values()->toArray(),
                'bast_list' => $bastList->values()->toArray(),
                'eligible_without_bast' => $eligibleWithoutBast
                    ->filter(function ($item) use ($bastList) {
                        $idsWithBast = $bastList->pluck('petugas_id')->filter()->unique()->toArray();

                        return ! in_array($item['petugas_id'] ?? null, $idsWithBast, true);
                    })
                    ->values()
                    ->toArray(),
                'permissions' => [
                    'can_manage_main' => $canManageMain,
                    'is_ketua_tim' => $isKetuaTim,
                    'can_upload_main' => in_array($user?->active_role, ['admin', 'operator'], true),
                ],
                'summary' => [
                    'total_lampiran' => $lampiranPreview->count(),
                    'generated_lampiran' => $generatedLampiranPreviewCount,
                    'signed_lampiran' => $signedLampiranPreviewCount,
                    'all_lampiran_generated' => $allLampiranPreviewGenerated,
                    'all_lampiran_signed' => $allLampiranPreviewSigned,
                    'main_signed_uploaded' => false,
                    'final_signed_ready' => false,
                ],
                'sensus_reference' => $previewSensusReferenceForView,
                'mode' => $mode,
                'bulan' => (int) $bulan,
                'tahun' => (int) $tahun,
                'bulan_label' => $this->getBulanLabel((int) $bulan),
            ]);
        }

        if (! $firstBast) {
            return redirect()->route('bast.index')
                ->with('error', 'Tidak ada BAST untuk periode '.$this->getBulanLabel((int) $bulan).' '.$tahun);
        }

        // Redirect to canonical open-detail page (no hash URL)
        $this->rememberOpenDetailFiltersFromBast($request, $firstBast);

        $routeParams = $mode !== 'regular' ? ['mode' => $mode] : [];

        return redirect()->route('bast.open-detail-by-petugas', $routeParams);
    }

    /**
     * Upload BAST Sensus Ekonomi 2026 sebagai dokumen manual.
     */
    public function uploadManualSensus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'spk_id' => ['required', 'integer', 'exists:spk,id'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'nomor_bast' => ['required', 'string', 'regex:/^\d+$/', 'max:12'],
            'tanggal_bast' => ['nullable', 'date'],
        ]);

        $spk = Spk::query()
            ->with([
                'petugas',
                'alokasiPetugas.periodeAlokasi.kegiatan.ketuaTim',
            ])
            ->findOrFail((int) $validated['spk_id']);

        if (! $this->isSensusEkonomiSpk($spk)) {
            return back()->with('error', 'Upload manual ini hanya untuk BAST Sensus Ekonomi 2026.');
        }

        $user = $this->getRequestUser($request);
        if (! $user || ! in_array($user->getActiveRole()?->name, ['admin', 'operator'], true)) {
            abort(403);
        }

        $periode = $spk->alokasiPetugas?->periodeAlokasi;
        $kegiatan = $periode?->kegiatan;
        if (! $periode || ! $kegiatan) {
            return back()->with('error', 'Data alokasi Sensus Ekonomi tidak lengkap.');
        }

        $existing = Bast::query()
            ->where('spk_id', $spk->id)
            ->whereNull('deleted_at')
            ->first();

        if ($existing && filled($existing->signed_file_path) && str_starts_with((string) $existing->signed_file_path, 'storage/')) {
            Storage::disk('public')->delete(substr((string) $existing->signed_file_path, 8));
        }

        $nomorUrutBast = trim((string) $validated['nomor_bast']);
        $nomorBast = sprintf(
            'B-%s/BAST-SE2026/1373/PL.200/%d',
            preg_replace('/\D+/', '', $nomorUrutBast),
            (int) $periode->tahun,
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nomorBast);
        $stored = $request->file('file')->storeAs(
            'bast-manual/se2026/'.(int) $periode->tahun,
            'BAST_'.$safeName.'_'.time().'.pdf',
            'public'
        );
        $publicPath = 'storage/'.$stored;

        $tanggal = $validated['tanggal_bast']
            ?? $spk->tanggal_selesai_kerja
            ?? $periode->tanggal_selesai
            ?? now()->toDateString();

        $bast = $existing ?? new Bast();
        $bast->nomor_bast = $nomorBast;
        $bast->spk_id = $spk->id;
        $bast->periode_alokasi_id = $periode->id;
        $bast->kegiatan_id = $kegiatan->id;
        $bast->tanggal_bast = $tanggal;
        $bast->tanggal_serah_terima = $tanggal;
        $bast->menggunakan_fasih = false;
        $bast->uraian_pekerjaan = 'BAST Sensus Ekonomi 2026 (dokumen manual)';
        $bast->nama_ketua_tim = $kegiatan->ketuaTim?->name ?: '-';
        $bast->nip_ketua_tim = $kegiatan->ketuaTim?->nip;
        $bast->nama_ppk = $spk->nama_ppk ?: '-';
        $bast->nip_ppk = $spk->nip_ppk ?: '-';
        $bast->hasil_pekerjaan = 'Dokumen BAST SE2026 diunggah manual.';
        $bast->lokasi_kegiatan = 'Kota Sawahlunto';
        $bast->file_path = null;
        $bast->compiled_file_path = null;
        $bast->main_signed_file_path = null;
        $bast->signed_file_path = $publicPath;
        $bast->status = 'draft';
        $bast->created_by = $bast->created_by ?: Auth::id();
        $bast->save();

        return back()->with('success', 'BAST SE2026 berhasil diunggah manual.');
    }

    private function extractSensusBastSequence(?string $number): ?string
    {
        if (blank($number)) {
            return null;
        }

        return preg_match('/^B-(\d+)\/BAST-SE2026\//', (string) $number, $matches) === 1
            ? $matches[1]
            : null;
    }

    /**
     * Show form to create BAST for a specific month
     * List all SPK in that month that don't have BAST yet
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $filters = $request->session()->get('bast_create_filters');

        if (! is_array($filters) || ! isset($filters['bulan'], $filters['tahun'])) {
            return redirect()->route('bast.index')
                ->with('error', 'Pilih periode BAST dari halaman Berita Acara.');
        }

        $bulan = (int) $filters['bulan'];
        $tahun = (int) $filters['tahun'];
        $requestedMode = (string) ($filters['mode'] ?? 'regular');
        $user = $this->getRequestUser($request);
        $canAccessSensusMode = $this->canAccessSensusMode($user, $tahun);
        $mode = $requestedMode === 'sensus-ekonomi' && $canAccessSensusMode
            ? 'sensus-ekonomi'
            : 'regular';
        $isSensusEkonomiMode = $mode === 'sensus-ekonomi';
        $bulanFormatted = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

        if ($isSensusEkonomiMode && $bulan !== 8) {
            $request->session()->put('bast_index_mode', 'sensus-ekonomi');

            return redirect()->route('bast.index')
                ->with('info', 'BAST Sensus Ekonomi dikelola pada periode Agustus sesuai akhir pelaksanaan PK.');
        }

        if ($isSensusEkonomiMode) {
            $allPetugasQuery = Spk::query()
                ->where('addendum_number', 0);

            if (
                Schema::hasTable('sensus_ekonomi_petugas_replacements')
                && Schema::hasColumn('sensus_ekonomi_petugas_replacements', 'termin_i_paid')
            ) {
                $allPetugasQuery->whereNotIn('petugas_id', function ($query): void {
                    $query->select('petugas_berhenti_id')
                        ->from('sensus_ekonomi_petugas_replacements')
                        ->where('termin_i_paid', 0)
                        ->where('status', '!=', 'dibatalkan');
                });
            }

            $allPetugasIds = $allPetugasQuery
                ->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($tahun): void {
                    $query->where('tahun', $tahun);
                })
                ->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($q) {
                    $q->where('nama_kegiatan', 'like', '%Sensus Ekonomi%');
                })
                ->pluck('petugas_id')
                ->filter()
                ->unique();
        } else {
            // Get all unique petugas who have SPK (original or addendum) in this month
            $allPetugasIds = Spk::whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($tahun, $bulanFormatted) {
                $q->where('tahun', $tahun)
                    ->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted));
            })
                ->distinct()
                ->pluck('petugas_id')
                ->filter()
                ->unique();
        }

        $isLegacyBastMode = $this->isLegacyBastAttachmentMode($bulanFormatted, (int) $tahun);

        // Filter petugas who are eligible for BAST (have positive honor and no pending addendum)
        $eligiblePetugasIds = $allPetugasIds->filter(function ($petugasId) use ($bulanFormatted, $tahun, $isLegacyBastMode, $isSensusEkonomiMode) {
            if ($isSensusEkonomiMode) {
                return true;
            }

            if ($isLegacyBastMode) {
                return $this->hasPositiveBastAttachmentPayloadForPetugas(
                    (int) $petugasId,
                    $bulanFormatted,
                    (int) $tahun,
                );
            }

            return $this->hasPositiveEffectiveAlokasiForPetugasInMonth(
                (int) $petugasId,
                $bulanFormatted,
                (int) $tahun,
            );
        });

        // For each eligible petugas, get their latest SPK (highest addendum_number)
        $spks = collect();
        foreach ($eligiblePetugasIds as $petugasId) {
            $latestSpk = Spk::where('petugas_id', $petugasId)
                ->when($isSensusEkonomiMode, function ($query) use ($tahun) {
                    $query->where('addendum_number', 0)
                        ->whereHas('alokasiPetugas.periodeAlokasi', function ($periodeQuery) use ($tahun): void {
                            $periodeQuery->where('tahun', $tahun);
                        })
                        ->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($q) {
                            $q->where('nama_kegiatan', 'like', '%Sensus Ekonomi%');
                        });
                }, function ($query) use ($tahun, $bulanFormatted) {
                    $query->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($tahun, $bulanFormatted) {
                        $q->where('tahun', $tahun)
                            ->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted));
                    });
                })
                ->with(['alokasiPetugas.petugas', 'alokasiPetugas.periodeAlokasi', 'bast'])
                ->orderByDesc('addendum_number')
                ->orderByDesc('created_at')
                ->first();

            if ($latestSpk) {
                $isSensusEkonomiSpk = $this->isSensusEkonomiSpk($latestSpk);
                if ($isSensusEkonomiMode !== $isSensusEkonomiSpk) {
                    continue;
                }
            }

            // Check if this petugas already has BAST for this month
            if ($latestSpk) {
                $hasBastThisMonth = $latestSpk->bast->contains(function ($bast) use ($tahun, $bulan) {
                    if (! $bast->tanggal_bast) {
                        return false;
                    }

                    return (int) $bast->tanggal_bast->format('Y') === (int) $tahun
                        && (int) $bast->tanggal_bast->format('n') === (int) $bulan;
                });

                if ($isSensusEkonomiMode || ! $hasBastThisMonth) {
                    // Workspace SE2026 berfungsi sebagai inventaris, bukan hanya
                    // daftar "belum BAST". Dokumen yang sudah diunggah tetap
                    // ditampilkan agar dapat ditinjau atau diganti dari halaman
                    // yang sama.
                    $spks->push($latestSpk);
                }
            }
        }

        if ($spks->isEmpty()) {
            return redirect()->route('bast.index')
                ->with('info', $isSensusEkonomiMode
                    ? 'Tidak ada PK Sensus Ekonomi yang dapat dikelola.'
                    : 'Tidak ada SPK yang belum memiliki BAST di bulan ini');
        }

        // Get starting nomor urut BAST untuk bulan ini
        $lastBast = Bast::whereYear('tanggal_bast', $tahun)
            ->whereMonth('tanggal_bast', $bulan)
            ->orderByDesc('id')
            ->first();

        if ($lastBast) {
            $nomorUrutStart = $this->extractBastSequence($lastBast->nomor_bast) + 1;
        } else {
            $nomorUrutStart = 1;
        }

        // Tentukan tanggal berakhir paling akhir dari semua alokasi di bulan ini
        // Ambil alokasi di bulan ini dari semua periode (tidak peduli status)
        $allAlokasiBulan = AlokasiPetugas::whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
            $q->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                ->where('tahun', $tahun);
        })->get();
        $tanggalBerakhirPalingAkhir = $allAlokasiBulan->map(function ($alokasi) {
            return $this->getAlokasiLatestTanggalSelesai($alokasi);
        })->filter()->max();

        // SE2026 sebelumnya menjalankan query alokasi berulang di comparator sort dan
        // di setiap card. Ambil seluruh alokasi SE untuk petugas yang tampil satu kali,
        // lalu kelompokkan di memory. Ini menghilangkan pola N+1/O(n log n) query.
        $sensusAlokasiByPetugas = collect();

        if ($isSensusEkonomiMode) {
            $sensusPetugasIds = $spks
                ->map(fn (Spk $spk) => (int) ($spk->alokasiPetugas?->petugas_id ?? $spk->petugas_id ?? 0))
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values();

            if ($sensusPetugasIds->isNotEmpty()) {
                $allSensusAlokasi = AlokasiPetugas::query()
                    ->whereIn('petugas_id', $sensusPetugasIds->all())
                    ->whereHas('periodeAlokasi', function ($query) use ($tahun): void {
                        $query->where('tahun', $tahun)
                            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
                            ->whereHas('kegiatan', function ($kegiatanQuery): void {
                                $kegiatanQuery->where('nama_kegiatan', 'like', '%Sensus Ekonomi%');
                            });
                    })
                    ->where(function ($query): void {
                        $query->where('total_honor', '>', 0)
                            ->orWhere('total_honor_listing', '>', 0)
                            ->orWhere('jumlah_satuan', '>', 0)
                            ->orWhere('jumlah_satuan_listing', '>', 0);
                    })
                    ->with([
                        'periodeAlokasi:id,kegiatan_id,status,created_at,tanggal_selesai,tanggal_selesai_listing,jadwal_pengolahan_listing_selesai,jadwal_pengolahan_pencacahan_selesai',
                        'periodeAlokasi.kegiatan',
                        'frameSampelAllocations.kegiatanFrameSampel',
                        'spk' => fn ($query) => $query->orderByDesc('addendum_number'),
                    ])
                    ->get();

                $sensusAlokasiByPetugas = $allSensusAlokasi
                    ->groupBy('petugas_id')
                    ->map(fn (Collection $items) => $this->getEffectiveAlokasiByKegiatan($items)->values());
            }
        }

        // Urutkan SPKs berdasarkan tanggal_berakhir_paling_akhir kemudian nama petugas (A-Z)
        $spks = $spks->sort(function ($a, $b) use ($bulanFormatted, $tahun, $isSensusEkonomiMode, $sensusAlokasiByPetugas) {
            // Get tanggal berakhir untuk SPK A
            $petugasA = $a->alokasiPetugas?->petugas;
            $allAlokasiA = $isSensusEkonomiMode
                ? ($sensusAlokasiByPetugas->get((int) ($petugasA?->id ?? 0)) ?? collect())
                : $this->getEffectiveAlokasiForPetugasInMonth((int) ($petugasA?->id ?? 0), $bulanFormatted, (int) $tahun)
                    ->filter(function ($alokasi) {
                        return (int) ($alokasi->jumlah_satuan ?? 0) > 0 || (int) ($alokasi->jumlah_satuan_listing ?? 0) > 0;
                    });

            $tanggalBerakhirA = $allAlokasiA->map(function ($alokasi) {
                return $this->getAlokasiLatestTanggalSelesai($alokasi);
            })->filter()->max();

            if (! $tanggalBerakhirA) {
                $tanggalBerakhirA = $a->tanggal_selesai_kerja ?? $a->tanggal_mulai_kerja;
            }

            // Get tanggal berakhir untuk SPK B
            $petugasB = $b->alokasiPetugas?->petugas;
            $allAlokasiB = $isSensusEkonomiMode
                ? ($sensusAlokasiByPetugas->get((int) ($petugasB?->id ?? 0)) ?? collect())
                : $this->getEffectiveAlokasiForPetugasInMonth((int) ($petugasB?->id ?? 0), $bulanFormatted, (int) $tahun)
                    ->filter(function ($alokasi) {
                        return (int) ($alokasi->jumlah_satuan ?? 0) > 0 || (int) ($alokasi->jumlah_satuan_listing ?? 0) > 0;
                    });

            $tanggalBerakhirB = $allAlokasiB->map(function ($alokasi) {
                return $this->getAlokasiLatestTanggalSelesai($alokasi);
            })->filter()->max();

            if (! $tanggalBerakhirB) {
                $tanggalBerakhirB = $b->tanggal_selesai_kerja ?? $b->tanggal_mulai_kerja;
            }

            // Compare dates first
            $dateCompare = strcmp($tanggalBerakhirA, $tanggalBerakhirB);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            // If dates are equal, compare by nama petugas (A-Z)
            return strcmp($petugasA?->nama ?? '', $petugasB?->nama ?? '');
        })->values();

        // Format data SPK dengan detail kegiatan yang diikuti petugas
        $spkList = $spks->map(function ($spk, $index) use ($bulan, $bulanFormatted, $tahun, $nomorUrutStart, $isSensusEkonomiMode, $sensusAlokasiByPetugas) {
            $petugas = $spk->alokasiPetugas?->petugas;

            // Ambil SEMUA alokasi petugas untuk bulan ini (semua kegiatan yang diikuti petugas di bulan yang sama)
            $allAlokasi = $isSensusEkonomiMode
                ? ($sensusAlokasiByPetugas->get((int) ($petugas?->id ?? 0)) ?? collect())->values()
                : $this->getEffectiveAlokasiForPetugasInMonth((int) ($petugas?->id ?? 0), $bulanFormatted, (int) $tahun)
                    ->filter(function ($alokasi) {
                        return
                            (float) ($alokasi->total_honor ?? 0) > 0 ||
                            (float) ($alokasi->total_honor_listing ?? 0) > 0;
                    })
                    ->load([
                        'periodeAlokasi.kegiatan',
                        'spk' => function ($query) {
                            $query->orderByDesc('addendum_number');
                        },
                    ])
                    ->values();

            // Kumpulkan kegiatan unik dengan detail dari semua alokasi petugas
            $kegiatanList = $allAlokasi->map(function ($alokasi) {

                $kegiatan = $alokasi->periodeAlokasi?->kegiatan;
                $spkTerkait = $alokasi->spk?->first();
                $periodeAlokasi = $alokasi->periodeAlokasi;

                // Kumpulkan semua tanggal selesai yang relevan (listing & pencacahan)
                $tanggalSelesaiArr = [];
                $isPengolahanRole = in_array($alokasi->peran, self::PENGOLAHAN_ROLES);
                if ($isPengolahanRole) {
                    if (! empty($periodeAlokasi?->jadwal_pengolahan_listing_selesai)) {
                        $tanggalSelesaiArr[] = $periodeAlokasi->jadwal_pengolahan_listing_selesai;
                    }
                    if (! empty($periodeAlokasi?->jadwal_pengolahan_pencacahan_selesai)) {
                        $tanggalSelesaiArr[] = $periodeAlokasi->jadwal_pengolahan_pencacahan_selesai;
                    }
                } else {
                    if (! empty($periodeAlokasi?->tanggal_selesai_listing)) {
                        $tanggalSelesaiArr[] = $periodeAlokasi->tanggal_selesai_listing;
                    }
                    if (! empty($periodeAlokasi?->tanggal_selesai)) {
                        $tanggalSelesaiArr[] = $periodeAlokasi->tanggal_selesai;
                    }
                }
                $tanggalSelesai = ! empty($tanggalSelesaiArr) ? collect($tanggalSelesaiArr)->max() : null;

                $tanggalSelesaiLabel = '';
                if (! empty($tanggalSelesai)) {
                    try {
                        // Convert to string if it's already a Carbon instance
                        $dateString = $tanggalSelesai instanceof Carbon
                            ? $tanggalSelesai->format('Y-m-d')
                            : $tanggalSelesai;

                        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dateString)) {
                            $tanggalSelesaiLabel = Carbon::parse($dateString)->locale('id')->isoFormat('D MMMM YYYY');
                        }
                    } catch (\Exception $e) {
                        $tanggalSelesaiLabel = '';
                    }
                }

                return [
                    'kegiatan_id' => $kegiatan?->id,
                    'kode_kegiatan' => $kegiatan?->kode_kegiatan,
                    'nama_kegiatan' => $kegiatan?->nama_kegiatan,
                    'jenis_kegiatan' => $kegiatan?->jenis_kegiatan,
                    'nomor_spk' => $spkTerkait?->nomor_spk ?? 'Belum ada SPK',
                    'tanggal_selesai' => $tanggalSelesai,
                    'tanggal_selesai_label' => $tanggalSelesaiLabel,
                    'peran' => $alokasi->peran,
                    'hasil_listing' => $this->resolveLampiranCumulativeVolume($alokasi, 'listing'),
                    'hasil_pendataan_lapangan' => $this->resolveLampiranCumulativeVolume($alokasi, 'pencacahan'),
                    'hasil_pengolahan' => in_array($alokasi->peran, self::PENGOLAHAN_ROLES) ? $this->resolveLampiranCumulativeVolume($alokasi, 'pencacahan') : null,
                    'hasil_pengolahan_listing' => in_array($alokasi->peran, self::PENGOLAHAN_ROLES) ? $this->resolveLampiranCumulativeVolume($alokasi, 'listing') : null,
                    'spk_id' => $spkTerkait?->id,
                ];
            })->filter()->values();

            $isSensusEkonomi = $isSensusEkonomiMode || $kegiatanList->contains(function ($kegiatanItem) {
                return $this->isSensusEkonomiName((string) ($kegiatanItem['nama_kegiatan'] ?? null));
            });

            $prelistBreakdown = $isSensusEkonomi
                ? $this->calculateSensusPrelistBreakdown($allAlokasi)
                : [
                    'keluarga' => 0,
                    'usaha' => 0,
                    'total' => (int) round($allAlokasi->sum(function ($alokasi) {
                        return (float) $alokasi->getEffectiveJumlahSatuan();
                    })),
                ];

            $muatanPrelistDefault = (int) $prelistBreakdown['total'];

            $unitSampelPencacahanItems = $allAlokasi
                ->map(fn ($alokasi) => $alokasi->periodeAlokasi?->kegiatan)
                ->filter()
                ->flatMap(function (Kegiatan $kegiatan) {
                    return $kegiatan->unitSampelPencacahanItems()
                        ->map(fn ($unit) => ['id' => (int) $unit->id, 'nama' => (string) $unit->nama])
                        ->values();
                })
                ->unique('id')
                ->values();

            // Hitung tanggal berakhir paling akhir dari kegiatan yang diikuti petugas ini
            $tanggalBerakhirPetugasIni = $kegiatanList->map(function ($kegiatan) {
                return $kegiatan['tanggal_selesai'];
            })->filter()->max();

            // Fallback ke tanggal SPK jika tidak ada tanggal dari kegiatan
            if (! $tanggalBerakhirPetugasIni) {
                $tanggalBerakhirPetugasIni = $spk->tanggal_selesai_kerja;
            }

            $ketuaTim = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->ketuaTim;

            $existingBast = $spk->bast
                ->filter(function (Bast $bast) use ($tahun, $bulan): bool {
                    if (! $bast->tanggal_bast) {
                        return false;
                    }

                    return (int) $bast->tanggal_bast->format('Y') === (int) $tahun
                        && (int) $bast->tanggal_bast->format('n') === (int) $bulan;
                })
                ->sortByDesc('id')
                ->first();

            // Generate nomor BAST untuk SPK ini dengan nomor urut yang increment
            $nomorUrut = $nomorUrutStart + $index;
            $nomorBastPreview = $tanggalBerakhirPetugasIni
                ? $this->formatBastNomor(
                    $nomorUrut,
                    $tanggalBerakhirPetugasIni instanceof Carbon
                        ? $tanggalBerakhirPetugasIni->year
                        : Carbon::parse($tanggalBerakhirPetugasIni)->year,
                    $isSensusEkonomi
                )
                : null;

            return [
                'spk_id' => $spk->id,
                'spk_hashed_id' => $spk->hashed_id,
                'nomor_spk' => $spk->nomor_spk,
                'nomor_bast_preview' => $nomorBastPreview,
                'tanggal_spk' => $spk->tanggal_spk?->format('Y-m-d'),
                'tanggal_mulai_kerja' => $spk->tanggal_mulai_kerja?->format('Y-m-d'),
                'tanggal_selesai_kerja_asli' => $spk->tanggal_selesai_kerja?->format('Y-m-d'),
                'tanggal_berakhir_paling_akhir' => $tanggalBerakhirPetugasIni instanceof Carbon ? $tanggalBerakhirPetugasIni->format('Y-m-d') : $tanggalBerakhirPetugasIni,
                'nama_ppk' => $spk->nama_ppk,
                'nip_ppk' => $spk->nip_ppk,
                'petugas' => [
                    'id' => $petugas?->id,
                    'nama' => $petugas?->nama,
                    'nik' => $petugas?->nik,
                    'alamat' => $petugas?->alamat,
                ],
                'ketua_tim' => [
                    'nama' => $ketuaTim?->name,
                    'nip' => $ketuaTim?->nip,
                ],
                'is_sensus_ekonomi' => $isSensusEkonomi,
                'has_bast' => $existingBast !== null,
                'existing_bast_hashed_id' => $existingBast?->hashed_id,
                'existing_bast_nomor' => $existingBast?->nomor_bast,
                'existing_bast_nomor_urut' => $this->extractSensusBastSequence($existingBast?->nomor_bast),
                'existing_bast_tanggal' => $existingBast?->tanggal_bast?->format('Y-m-d'),
                'bapp_termin_ii_complete' => $isSensusEkonomi ? $this->getBappSeTerminDataForSpk((int) $spk->id)['termin_ii_complete'] : null,
                'muatan_prelist_default' => $muatanPrelistDefault,
                'muatan_prelist_keluarga_default' => (int) $prelistBreakdown['keluarga'],
                'muatan_prelist_usaha_default' => (int) $prelistBreakdown['usaha'],
                'unit_sampel_pencacahan_items' => $unitSampelPencacahanItems,
                'kegiatan_list' => $kegiatanList,
                'jumlah_kegiatan' => is_array($kegiatanList) ? count($kegiatanList) : (method_exists($kegiatanList, 'count') ? $kegiatanList->count() : 0),
            ];
        })->values();

        // Encrypt sensitive data
        $encryptedSpkList = encryptData($spkList);

        return Inertia::render('Bast/CreateForMonth', [
            'bulan' => (int) $bulan,
            'tahun' => $tahun,
            'bulan_label' => $this->getBulanLabel((int) $bulan),
            'index_mode' => $mode,
            'nomor_bast_suffix' => $isSensusEkonomiMode
                ? '/BAST-SE2026/1373/PL.200/'.(int) $tahun
                : null,
            'spk_list' => [
                'encrypted' => $encryptedSpkList,
            ],
        ]);
    }

    /**
     * Generate BAST secara batch untuk multiple SPK
     * Prinsip: 1 SPK = 1 BAST dengan lampiran per kegiatan
     */
    public function generateBatch(Request $request): RedirectResponse
    {
        // Decrypt payload
        $decrypted = [];
        if ($request->has('encrypted_filters')) {
            $decrypted = decryptFilters($request->input('encrypted_filters'));
        }

        $request->merge($decrypted);

        $request->validate([
            'spk_ids' => 'required|array|min:1',
            'spk_ids.*' => 'required|integer|exists:spk,id',
            'se_inputs' => 'nullable|array',
            'se_inputs.*.muatan_input' => 'nullable|integer|min:0',
            'se_inputs.*.muatan_prelist' => 'nullable|integer|min:0',
            'se_inputs.*.realisasi_unit_sampel' => 'nullable|array',
            'se_inputs.*.realisasi_unit_sampel.*' => 'nullable|numeric|min:0',
        ]);

        // Load semua SPKs yang dipilih dengan relasi yang dibutuhkan
        $spks = Spk::with([
            'alokasiPetugas.petugas',
            'alokasiPetugas.periodeAlokasi.kegiatan.ketuaTim',
        ])->whereIn('id', $request->spk_ids)->get();

        $hasSensusOutsideAugust = $spks->contains(function ($spk) {
            if (! $this->isSensusEkonomiSpk($spk)) {
                return false;
            }

            $endDate = $spk->tanggal_selesai_kerja ?? $spk->tanggal_mulai_kerja;
            if (! $endDate) {
                return true;
            }

            $endMonth = $endDate instanceof Carbon
                ? (int) $endDate->format('n')
                : (int) Carbon::parse($endDate)->format('n');

            return $endMonth !== 8;
        });

        if ($hasSensusOutsideAugust) {
            return redirect()->route('bast.index', ['mode' => 'sensus-ekonomi'])
                ->with('error', 'BAST Sensus Ekonomi hanya dapat dibuat pada bulan Agustus sesuai akhir pelaksanaan PK.');
        }

        $seInputsBySpkId = collect($request->input('se_inputs', []))
            ->mapWithKeys(function ($payload, $spkId) {
                return [(int) $spkId => [
                    'muatan_input' => isset($payload['muatan_input']) ? (int) $payload['muatan_input'] : null,
                    'muatan_prelist' => isset($payload['muatan_prelist']) ? (int) $payload['muatan_prelist'] : null,
                    'realisasi_unit_sampel' => isset($payload['realisasi_unit_sampel']) && is_array($payload['realisasi_unit_sampel'])
                        ? collect($payload['realisasi_unit_sampel'])
                            ->map(fn ($value) => is_numeric($value) ? (float) $value : null)
                            ->filter(fn ($value) => $value !== null)
                            ->all()
                        : null,
                ]];
            });

        // Urutkan SPKs berdasarkan tanggal_berakhir_paling_akhir kemudian nama petugas (A-Z)
        $spksSorted = $spks->sort(function ($a, $b) {
            $bulanA = date('m', strtotime($a->tanggal_mulai_kerja));
            $tahunA = date('Y', strtotime($a->tanggal_mulai_kerja));
            $petugasA = $a->alokasiPetugas?->petugas;

            $allAlokasiA = AlokasiPetugas::where('petugas_id', $petugasA?->id)
                ->whereHas('periodeAlokasi', function ($q) use ($bulanA, $tahunA) {
                    $q->whereIn('bulan', $this->resolveBulanCandidates($bulanA))
                        ->where('tahun', $tahunA)
                        ->whereIn('status', ['dikirim', 'perubahan']);
                })
                ->where(function ($query) {
                    $query->where('jumlah_satuan', '>', 0)
                        ->orWhere('jumlah_satuan_listing', '>', 0);
                })
                ->with('periodeAlokasi')
                ->get();

            $tanggalBerakhirA = $allAlokasiA->map(function ($alokasi) {
                return $this->getAlokasiLatestTanggalSelesai($alokasi);
            })->filter()->max();

            if (! $tanggalBerakhirA) {
                $tanggalBerakhirA = $a->tanggal_selesai_kerja ?? $a->tanggal_mulai_kerja;
            }

            $bulanB = date('m', strtotime($b->tanggal_mulai_kerja));
            $tahunB = date('Y', strtotime($b->tanggal_mulai_kerja));
            $petugasB = $b->alokasiPetugas?->petugas;

            $allAlokasiB = AlokasiPetugas::where('petugas_id', $petugasB?->id)
                ->whereHas('periodeAlokasi', function ($q) use ($bulanB, $tahunB) {
                    $q->whereIn('bulan', $this->resolveBulanCandidates($bulanB))
                        ->where('tahun', $tahunB)
                        ->whereIn('status', ['dikirim', 'perubahan']);
                })
                ->where(function ($query) {
                    $query->where('jumlah_satuan', '>', 0)
                        ->orWhere('jumlah_satuan_listing', '>', 0);
                })
                ->with('periodeAlokasi')
                ->get();

            $tanggalBerakhirB = $allAlokasiB->map(function ($alokasi) {
                return $this->getAlokasiLatestTanggalSelesai($alokasi);
            })->filter()->max();

            if (! $tanggalBerakhirB) {
                $tanggalBerakhirB = $b->tanggal_selesai_kerja ?? $b->tanggal_mulai_kerja;
            }

            // Compare dates first
            $dateCompare = strcmp($tanggalBerakhirA, $tanggalBerakhirB);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            // If dates are equal, compare by nama petugas (A-Z)
            return strcmp($petugasA?->nama ?? '', $petugasB?->nama ?? '');
        })->values();

        // Initialize nomor urut BAST berdasarkan SPK pertama yang sudah diurutkan
        $nomorUrutBast = 0;
        $bulanBast = null;
        $tahunBast = null;

        if ($spksSorted->isNotEmpty()) {
            $firstSpk = $spksSorted->first();
            $firstPetugas = $firstSpk->alokasiPetugas?->petugas;
            $firstBulan = date('m', strtotime($firstSpk->tanggal_mulai_kerja));
            $firstTahun = date('Y', strtotime($firstSpk->tanggal_mulai_kerja));

            // Get tanggal berakhir for first SPK to determine bulan/tahun BAST
            $firstAllAlokasi = AlokasiPetugas::where('petugas_id', $firstPetugas?->id)
                ->whereHas('periodeAlokasi', function ($q) use ($firstBulan, $firstTahun) {
                    $q->whereIn('bulan', $this->resolveBulanCandidates($firstBulan))
                        ->where('tahun', $firstTahun)
                        ->whereIn('status', ['dikirim', 'perubahan']);
                })
                ->with('periodeAlokasi')
                ->get();

            $firstTanggalBerakhir = $firstAllAlokasi->map(function ($alokasi) {
                return $this->getAlokasiLatestTanggalSelesai($alokasi);
            })->filter()->max();

            if (! $firstTanggalBerakhir) {
                $firstTanggalBerakhir = $firstSpk->tanggal_selesai_kerja ?? $firstSpk->tanggal_mulai_kerja;
            }

            // Convert and adjust for weekend
            if ($firstTanggalBerakhir instanceof Carbon) {
                $firstTanggalBerakhir = $firstTanggalBerakhir->format('Y-m-d');
            }

            $carbonTarget = Carbon::parse($firstTanggalBerakhir);
            while (in_array($carbonTarget->dayOfWeekIso, [6, 7])) {
                $carbonTarget->subDay();
            }

            $bulanBast = $carbonTarget->month;
            $tahunBast = $carbonTarget->year;

            // Ambil semua BAST di bulan dan tahun yang sama dan cari nomor tertinggi
            $allBast = Bast::whereYear('tanggal_bast', $tahunBast)
                ->whereMonth('tanggal_bast', $bulanBast)
                ->pluck('nomor_bast');

            $maxUrut = 0;
            foreach ($allBast as $existingNomor) {
                // Pattern: PPIS/13730/{urut}/BAST/{tahun}
                if (preg_match('/PPIS\/13730\/(\d+)\/BAST\/\d{4}/', $existingNomor, $matches)) {
                    $urut = (int) $matches[1];
                    if ($urut > $maxUrut) {
                        $maxUrut = $urut;
                    }
                }
            }

            $nomorUrutBast = $maxUrut;
        }

        $successCount = 0;
        $failedSpk = [];

        try {

            $usedUruts = [];
            foreach ($spksSorted as $spk) {
                // Gunakan transaction terpisah untuk setiap SPK
                DB::beginTransaction();
                try {
                    // Cek apakah sudah ada BAST untuk petugas dan periode_alokasi yang sama
                    $alokasi = $spk->alokasiPetugas;
                    $petugasId = $alokasi?->petugas_id;
                    $periodeAlokasiId = $alokasi?->periode_alokasi_id;
                    $bastSudahAda = false;
                    if ($petugasId && $periodeAlokasiId) {
                        $bastSudahAda = Bast::where('periode_alokasi_id', $periodeAlokasiId)
                            ->whereHas('spk.alokasiPetugas', function ($q) use ($petugasId) {
                                $q->where('petugas_id', $petugasId);
                            })->exists();
                    }
                    if ($bastSudahAda) {
                        $failedSpk[] = [
                            'nomor_spk' => $spk->nomor_spk,
                            'reason' => 'BAST sudah ada',
                        ];
                        DB::rollBack();

                        continue;
                    }

                    $petugas = $spk->alokasiPetugas?->petugas;
                    $bulan = date('m', strtotime($spk->tanggal_mulai_kerja));
                    $tahun = date('Y', strtotime($spk->tanggal_mulai_kerja));

                    // Ambil semua alokasi untuk petugas yang sama dalam bulan dan tahun yang sama
                    // Exclude status 'direvisi' karena tidak perlu masuk ke lampiran
                    $allAlokasi = AlokasiPetugas::where('petugas_id', $petugas->id)
                        ->whereHas('periodeAlokasi', function ($q) use ($bulan, $tahun) {
                            $q->whereIn('bulan', $this->resolveBulanCandidates($bulan))
                                ->where('tahun', $tahun)
                                ->whereIn('status', ['dikirim', 'perubahan']);
                        })
                        ->whereHas('petugas', function ($q) {
                            $q->where('jenis_petugas', 'non-organik');
                        })
                        ->where(function ($query) {
                            $query->where('total_honor', '>', 0)
                                ->orWhere('total_honor_listing', '>', 0);
                        })
                        ->with([
                            'periodeAlokasi.kegiatan.rateHonors.satuan',
                            'periodeAlokasi.kegiatan.rateHonors.satuanListing',
                            'periodeAlokasi.kegiatan.ketuaTim',
                            'spk',
                        ])
                        ->get();

                    // Skip jika tidak ada alokasi dengan pekerjaan
                    if ($allAlokasi->isEmpty()) {
                        $failedSpk[] = [
                            'nomor_spk' => $spk->nomor_spk,
                            'reason' => 'Tidak ada alokasi dengan pekerjaan',
                        ];
                        DB::rollBack();

                        continue;
                    }

                    $ketuaTim = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->ketuaTim;
                    $petugasUtama = $spk->alokasiPetugas->petugas;
                    $bulanUtama = date('m', strtotime($spk->tanggal_mulai_kerja));
                    $tahunUtama = date('Y', strtotime($spk->tanggal_mulai_kerja));
                    $allAlokasi = AlokasiPetugas::where('petugas_id', $petugasUtama->id)
                        ->whereHas('periodeAlokasi', function ($q) use ($bulanUtama, $tahunUtama) {
                            $q->whereIn('bulan', $this->resolveBulanCandidates($bulanUtama))
                                ->where('tahun', $tahunUtama)
                                ->whereIn('status', ['dikirim', 'perubahan']);
                        })
                        ->whereHas('petugas', function ($q) {
                            $q->where('jenis_petugas', 'non-organik');
                        })
                        ->where(function ($query) {
                            $query->where('total_honor', '>', 0)
                                ->orWhere('total_honor_listing', '>', 0);
                        })
                        ->with([
                            'periodeAlokasi.kegiatan.rateHonors.satuan',
                            'periodeAlokasi.kegiatan.rateHonors.satuanListing',
                            'periodeAlokasi.kegiatan.ketuaTim',
                            'spk',
                        ])
                        ->get();

                    $tanggalBerakhirPalingAkhir = $allAlokasi->map(function ($alokasi) {
                        return $this->getAlokasiLatestTanggalSelesai($alokasi);
                    })->filter()->max();

                    if (! $tanggalBerakhirPalingAkhir) {
                        $tanggalBerakhirPalingAkhir = $spk->tanggal_selesai_kerja;
                    }
                    if (! $tanggalBerakhirPalingAkhir) {
                        $tanggalBerakhirPalingAkhir = $spk->tanggal_mulai_kerja;
                    }
                    if ($tanggalBerakhirPalingAkhir instanceof Carbon) {
                        $tanggalBerakhirPalingAkhir = $tanggalBerakhirPalingAkhir->format('Y-m-d');
                    }
                    if (empty($tanggalBerakhirPalingAkhir) || $tanggalBerakhirPalingAkhir === '-') {
                        $tanggalBerakhirPalingAkhir = Carbon::now()->format('Y-m-d');
                    }
                    $carbonTarget = Carbon::parse($tanggalBerakhirPalingAkhir);
                    while (in_array($carbonTarget->dayOfWeekIso, [6, 7])) {
                        $carbonTarget->subDay();
                    }
                    $tanggalBerakhirPalingAkhir = $carbonTarget->format('Y-m-d');

                    $nomorBastTemp = null;
                    $isSensusEkonomi = $this->isSensusEkonomiSpk($spk);
                    // Cek apakah SPK ini sudah punya alokasi nomor BAST dari lampiran preview
                    $existingAllocation = BastNumberAllocation::query()->where('spk_id', $spk->id)->first();
                    if ($existingAllocation?->nomor_bast) {
                        $nomorBast = $existingAllocation->nomor_bast;
                    } else {
                        // Ambil nomor urut terakhir dari database untuk tahun ini
                        $allBast = Bast::whereYear('tanggal_bast', $carbonTarget->year)
                            ->pluck('nomor_bast');
                        $maxUrut = 0;
                        foreach ($allBast as $existingNomor) {
                            $urut = $this->extractBastSequenceForScheme($existingNomor, $isSensusEkonomi);
                            if ($urut > $maxUrut) {
                                $maxUrut = $urut;
                            }
                        }
                        // Cek nomor yang sudah dialokasikan via bast_number_allocations
                        $maxUrutAllocation = BastNumberAllocation::query()
                            ->where('tahun', $carbonTarget->year)
                            ->pluck('nomor_bast')
                            ->map(fn (?string $n) => $this->extractBastSequenceForScheme($n, $isSensusEkonomi))
                            ->max() ?? 0;
                        $maxUrut = max($maxUrut, $maxUrutAllocation);
                        // Cek juga nomor urut yang sudah dipakai di batch ini
                        if (! empty($usedUruts[$carbonTarget->year][$carbonTarget->month])) {
                            $maxUrutBatch = max($maxUrut, max($usedUruts[$carbonTarget->year][$carbonTarget->month]));
                        } else {
                            $maxUrutBatch = $maxUrut;
                        }
                        $nomorBastTemp = $maxUrutBatch + 1;
                        $nomorBast = $this->formatBastNomor($nomorBastTemp, $carbonTarget->year, $isSensusEkonomi);
                    }

                    // Ambil PPK aktif
                    $ppk = Penandatangan::where('jenis_penandatangan', 'ppk')
                        ->where('is_active', true)
                        ->first();

                    // For SE SPKs, override se_input with BAPP Termin I+II data
                    $resolvedSeInput = $seInputsBySpkId->get((int) $spk->id);
                    if ($this->isSensusEkonomiName($spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->nama_kegiatan ?? '')) {
                        $bappDataForExport = $this->getBappSeTerminDataForSpk((int) $spk->id);
                        $realisasiUnitSampelForExport = $bappDataForExport['realisasi_unit_sampel'];
                        $resolvedSeInput = [
                            'muatan_input' => $this->sumRealisasiUnitSampelValues($realisasiUnitSampelForExport),
                            'muatan_prelist' => (int) ($spk->muatan_prelist_keluarga_default ?? 0) + (int) ($spk->muatan_prelist_usaha_default ?? 0),
                            'realisasi_unit_sampel' => $realisasiUnitSampelForExport,
                        ];
                    }

                    // Siapkan data BAST main menggunakan logic export yang sama.
                    $viewData = $this->prepareBastDataForExport(
                        $spk,
                        $allAlokasi,
                        $nomorBast,
                        $tanggalBerakhirPalingAkhir,
                        $ppk,
                        $resolvedSeInput
                    );

                    // Generate file BAST utama tanpa lampiran.
                    try {
                        $pdfMain = Pdf::loadView('bast', $viewData)
                            ->setPaper('a4', 'portrait');
                        $filename = 'BAST_MAIN_'.$this->sanitizeDocumentSegment($nomorBast).'_'.$this->sanitizeDocumentSegment($spk->alokasiPetugas->petugas->nama).'.pdf';
                        $filePath = $this->writePdfToPublicDirectory($filename, $pdfMain->output(), 'main');
                    } catch (\Exception $pdfException) {
                        throw new \Exception('Gagal generate PDF: '.$pdfException->getMessage());
                    }

                    // Jika PDF berhasil, baru simpan ke database.
                    $bast = Bast::create([
                        'spk_id' => $spk->id,
                        'kegiatan_id' => $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->id,
                        'periode_alokasi_id' => $spk->alokasiPetugas?->periodeAlokasi?->id,
                        'nomor_bast' => $nomorBast,
                        'tanggal_bast' => $tanggalBerakhirPalingAkhir,
                        'tanggal_serah_terima' => $tanggalBerakhirPalingAkhir,
                        'uraian_pekerjaan' => $spk->alokasiPetugas?->catatan ?? '-',
                        'nama_ketua_tim' => $ketuaTim?->name ?? '-',
                        'nip_ketua_tim' => $ketuaTim?->nip ?? '-',
                        'nama_ppk' => $ppk->nama,
                        'nip_ppk' => $ppk->nip ?? '-',
                        'menggunakan_fasih' => $this->isMenggunakanFasih($allAlokasi),
                        'hasil_pekerjaan' => $spk->alokasiPetugas?->catatan ?? '-',
                        'file_path' => $filePath,
                        'compiled_file_path' => null,
                        'main_signed_file_path' => null,
                        'signed_file_path' => null,
                        'lokasi_kegiatan' => 'Kota Sawahlunto',
                        'status' => 'draft',
                        'created_by' => Auth::id(),
                    ]);

                    // Create BastPetugas record - hanya 1 per petugas per BAST
                    $alokasi = $spk->alokasiPetugas;
                    $petugas = $alokasi->petugas;
                    if ($petugas) {
                        $seInput = $seInputsBySpkId->get((int) $spk->id, []);
                        $isPendataanRole = in_array($alokasi->peran, self::PENDATAAN_ROLES, true);
                        $isPengolahanRole = in_array($alokasi->peran, self::PENGOLAHAN_ROLES, true);
                        // Aggregate hasil dari semua SPK (original + addendum)
                        $totalListing = 0;
                        $totalPendataan = 0;
                        $totalPengolahan = 0;
                        $catatan = [];
                        foreach ($allAlokasi as $alokasiSpk) {
                            $kegiatanSpk = $alokasiSpk?->periodeAlokasi?->kegiatan;
                            if (! $kegiatanSpk) {
                                continue;
                            }
                            $hasListing = ($kegiatanSpk->has_listing_updating ?? false)
                                || $alokasiSpk->getEffectiveJumlahSatuanListing() > 0;
                            if ($hasListing && $isPendataanRole) {
                                $totalListing += $alokasiSpk->getEffectiveJumlahSatuanListing();
                            }
                            if ($isPendataanRole) {
                                $totalPendataan += $alokasiSpk->getEffectiveJumlahSatuan();
                            }
                            if ($isPengolahanRole) {
                                $totalPengolahan += $alokasiSpk->getEffectiveJumlahSatuan();
                            }
                            if ($alokasiSpk->catatan) {
                                $catatan[] = $alokasiSpk->catatan;
                            }
                        }
                        $bastPetugasPayload = [
                            'bast_id' => $bast->id,
                            'petugas_id' => $alokasi->petugas_id,
                            'spk_id' => $spk->id, // SPK utama
                            'nomor_spk' => $spk->nomor_spk,
                            'nama_petugas' => $petugas->nama,
                            'hasil_listing' => $totalListing > 0 ? $totalListing : null,
                            'hasil_pendataan_lapangan' => $totalPendataan > 0 ? $totalPendataan : null,
                            'hasil_pengolahan' => $totalPengolahan > 0 ? $totalPengolahan : null,
                            'hasil_pengolahan_listing' => $totalListing > 0 && $isPengolahanRole ? $totalListing : null,
                            'catatan' => ! empty($catatan) ? implode('; ', $catatan) : null,
                        ];

                        if ($this->supportsSensusPetugasColumns()) {
                            // For SE spks, always derive realisasi from BAPP Termin I+II instead of manual input
                            $isSensusEkonomiSpk = $this->isSensusEkonomiName(
                                $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->nama_kegiatan ?? ''
                            );
                            if ($isSensusEkonomiSpk) {
                                $bappData = $this->getBappSeTerminDataForSpk((int) $spk->id);
                                $bastPetugasPayload['muatan_input'] = $this->sumRealisasiUnitSampelValues($bappData['realisasi_unit_sampel']);
                                $bastPetugasPayload['muatan_prelist'] = ($totalPendataan > 0) ? (int) $totalPendataan : null;
                                $bastPetugasPayload['realisasi_unit_sampel'] = $bappData['realisasi_unit_sampel'];
                                $bastPetugasPayload['fasih_screenshot_path'] = $bappData['fasih_screenshot_path'];
                            } else {
                                $bastPetugasPayload['muatan_input'] = isset($seInput['muatan_input']) ? (int) $seInput['muatan_input'] : null;
                                $bastPetugasPayload['muatan_prelist'] = isset($seInput['muatan_prelist']) ? (int) $seInput['muatan_prelist'] : ($totalPendataan > 0 ? (int) $totalPendataan : null);
                                $bastPetugasPayload['realisasi_unit_sampel'] = $seInput['realisasi_unit_sampel'] ?? null;
                                $bastPetugasPayload['fasih_screenshot_path'] = null;
                            }
                        }

                        BastPetugas::create($bastPetugasPayload);
                    }

                    collect($viewData['bast']->kegiatan_list)
                        ->unique(fn (array $item) => $this->makeBastKegiatanKey($item['kegiatan_id'], $item['periode_alokasi_id']))
                        ->each(function (array $item) use ($bast) {
                            BastKegiatan::create([
                                'bast_id' => $bast->id,
                                'kegiatan_id' => $item['kegiatan_id'],
                                'periode_alokasi_id' => $item['periode_alokasi_id'],
                                'kode_kegiatan' => $item['kode_kegiatan'],
                                'nama_kegiatan' => $item['nama_kegiatan'],
                                'bulan' => str_pad((string) $bast->tanggal_bast->month, 2, '0', STR_PAD_LEFT),
                                'tahun' => $bast->tanggal_bast->year,
                                'jenis_kegiatan' => $item['jenis_kegiatan'],
                            ]);
                        });

                    $this->adoptPreviewLampiranFiles($bast->fresh('bastKegiatan'));

                    $successCount++;
                    // Simpan nomor urut yang baru saja dipakai ke array batch (hanya jika tidak dari allocation)
                    if (isset($nomorBastTemp)) {
                        $usedUruts[$carbonTarget->year][$carbonTarget->month][] = $nomorBastTemp;
                    }
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    if (isset($filePath)) {
                        $this->deleteStoredDocument($filePath);
                    }
                    Log::error('BAST generate-batch item failed', [
                        'spk_id' => $spk->id ?? null,
                        'nomor_spk' => $spk->nomor_spk ?? null,
                        'message' => $e->getMessage(),
                    ]);
                    $failedSpk[] = [
                        'nomor_spk' => $spk->nomor_spk ?? 'Unknown',
                        'reason' => $e->getMessage(),
                    ];
                }
            }
            if (count($failedSpk) === 0) {
                $message = "Berhasil generate {$successCount} BAST.";

                return redirect()->route('bast.index')->with('success', $message);
            } else {
                $failedList = collect($failedSpk)->map(function ($f) {
                    $reason = $f['reason'];
                    // Sederhanakan pesan error duplicate entry
                    if (str_contains($reason, 'Duplicate entry')) {
                        $reason = 'Nomor BAST sudah digunakan';
                    } elseif (str_contains($reason, 'BAST sudah ada')) {
                        $reason = 'BAST sudah pernah dibuat';
                    } elseif (str_contains($reason, 'Integrity constraint violation')) {
                        $reason = 'Data tidak valid atau sudah ada.';
                    } elseif (str_contains($reason, 'Gagal generate PDF')) {
                        $reason = 'Gagal membuat file BAST.';
                    }

                    return "{$f['nomor_spk']} ($reason)";
                })->join(', ');
                $message = "Gagal generate BAST untuk: {$failedList}. Berhasil: {$successCount}.";

                return redirect()->route('bast.index')->with('error', $message);
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal generate BAST: '.$e->getMessage());
        }
    }

    /**
     * Prepare BAST data for export (sama dengan preview)
     */
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

    /**
     * Prepare BAST data for PDF export
     */
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

    /**
     * Generate nomor BAST otomatis untuk SPK
     */
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

    /**
     * Convert month number to Roman numeral
     */
    private function getRomanMonth(int $month): string
    {
        $romans = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        return $romans[$month - 1] ?? 'I';
    }

    /**
     * Generate uraian pekerjaan berdasarkan jenis penugasan, tahapan, dan periode
     */
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

    /**
     * Preview BAST untuk specific SPK
     */
    public function previewForSpk(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        // Decrypt payload
        $decrypted = [];
        if ($request->has('encrypted_filters')) {
            $decrypted = decryptFilters($request->input('encrypted_filters'));
        }

        $request->merge($decrypted);

        $request->validate([
            'spk_id' => 'required|integer|exists:spk,id',
            'nomor_bast' => 'nullable|string',
        ]);

        $spk = Spk::with([
            'alokasiPetugas.petugas',
            'alokasiPetugas.periodeAlokasi.kegiatan.ketuaTim',
        ])->findOrFail($request->spk_id);

        $seInputForPreview = $this->resolveSensusPreviewInput($spk, $request);
        $isSeSpk = $this->isSensusEkonomiSpk($spk);

        $petugas = $spk->alokasiPetugas?->petugas;
        $bulan = (int) date('n', strtotime($spk->tanggal_mulai_kerja));
        $tahun = (int) date('Y', strtotime($spk->tanggal_mulai_kerja));

        // Ambil semua alokasi untuk petugas yang sama dalam bulan dan tahun yang sama
        // Filter by kegiatan type: SE BAST hanya memuat alokasi SE, non-SE BAST hanya memuat non-SE
        $allAlokasi = AlokasiPetugas::where('petugas_id', $petugas->id)
            ->whereHas('periodeAlokasi', function ($q) use ($bulan, $tahun) {
                $q->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'perubahan']); // Exclude 'direvisi'
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
                'spk',
            ])
            ->get();

        // Untuk tanggal BAST utama, gunakan tanggal paling akhir dari semua kegiatan
        $tanggalBerakhirPalingAkhir = $allAlokasi->map(function ($alokasi) {
            // Prioritas 1: tanggal_selesai dari periode alokasi (tanggal kegiatan sebenarnya)
            if ($alokasi->periodeAlokasi?->tanggal_selesai) {
                return $alokasi->periodeAlokasi->tanggal_selesai;
            }
            // Prioritas 2: tanggal_selesai_kerja dari SPK
            if ($alokasi->spk?->first()?->tanggal_selesai_kerja) {
                return $alokasi->spk->first()->tanggal_selesai_kerja;
            }

            // Prioritas 3: tanggal_selesai dari alokasi itu sendiri
            return $alokasi->tanggal_selesai ?? null;
        })->filter()->max(); // Gunakan max() untuk tanggal BAST utama

        // Fallback ke tanggal SPK original jika tidak ada yang lain
        if (! $tanggalBerakhirPalingAkhir) {
            $tanggalBerakhirPalingAkhir = $spk->tanggal_selesai_kerja;
        }

        if (! $tanggalBerakhirPalingAkhir) {
            return back()->with('error', 'Tidak ada tanggal selesai kerja pada SPK ini');
        }

        $ketuaTim = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->ketuaTim;

        // Gunakan alokasi nomor yang sama seperti proses generate final agar preview konsisten.
        $noUrutBAST = $request->input('nomor_bast');
        if (! $noUrutBAST) {
            $tanggalBastCarbon = $tanggalBerakhirPalingAkhir instanceof Carbon
                ? $tanggalBerakhirPalingAkhir
                : Carbon::parse($tanggalBerakhirPalingAkhir);

            $noUrutBAST = $this->allocateNomorBastForSpk($spk, $tanggalBastCarbon);
        }

        $sensusNarrativeData = $this->buildSensusEkonomiNarrativeData($allAlokasi, $seInputForPreview);

        // Format data untuk preview
        $bastData = [
            'nomor_bast' => $noUrutBAST,
            'tanggal_bast' => $tanggalBerakhirPalingAkhir,
            'tanggal_pelaksanaan' => $spk->tanggal_mulai_kerja,
            'tanggal_selesai' => $tanggalBerakhirPalingAkhir,
            'muatan_input' => $seInputForPreview['muatan_input'] ?? null,
            'muatan_prelist' => $seInputForPreview['muatan_prelist'] ?? null,
            'realisasi_unit_sampel' => $seInputForPreview['realisasi_unit_sampel'] ?? null,
            'target_jumlah_frame_sampel' => $sensusNarrativeData['target_jumlah_frame_sampel'],
            'target_muatan_prelist_keluarga' => $sensusNarrativeData['target_muatan_prelist_keluarga'],
            'target_muatan_prelist_usaha' => $sensusNarrativeData['target_muatan_prelist_usaha'],
            'hasil_jumlah_frame_sampel' => $sensusNarrativeData['hasil_jumlah_frame_sampel'],
            'hasil_realisasi_keluarga' => $sensusNarrativeData['hasil_realisasi_keluarga'],
            'hasil_realisasi_usaha' => $sensusNarrativeData['hasil_realisasi_usaha'],
            'is_sensus_ekonomi' => $isSeSpk,
            'lokasi_kegiatan' => 'Kota Sawahlunto',
            'nama_ppk' => $spk->nama_ppk,
            'nip_ppk' => $spk->nip_ppk,
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

            // Cari SPK dari petugas ini untuk kegiatan yang sama di bulan yang sama
            // Tidak harus dari alokasi_id yang sama karena bisa jadi SPK dibuat dari periode lain yang sudah direvisi
            $spkPetugas = Spk::whereHas('alokasiPetugas', function ($q) use ($petugas, $kegiatan, $periode) {
                $q->where('petugas_id', $petugas->id)
                    ->whereHas('periodeAlokasi', function ($qq) use ($kegiatan, $periode) {
                        $qq->where('kegiatan_id', $kegiatan->id)
                            ->where('bulan', $periode->bulan)
                            ->where('tahun', $periode->tahun);
                    });
            })->first();
            $nomorSpk = $spkPetugas?->nomor_spk ?? 'Belum ada SPK';

            // Ambil tanggal selesai berdasarkan jenis peran dan tahapan kegiatan
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
            // Ambil ketua tim dari kegiatan ini
            $ketuaTimKegiatan = $kegiatan->ketuaTim;

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

            // Tanggal hari kerja terdekat dari tanggal selesai
            $tanggalSelesaiFinal = $tanggalSelesaiKegiatan;
            if ($tanggalSelesaiKegiatan && $tanggalSelesaiKegiatan !== 'Belum ada SPK') {
                $carbonTanggal = Carbon::parse($tanggalSelesaiKegiatan);
                // Jika Sabtu (6) atau Minggu (7), fallback ke Jumat atau hari kerja sebelumnya
                while (in_array($carbonTanggal->dayOfWeekIso, [6, 7])) {
                    $carbonTanggal->subDay();
                }
                $tanggalSelesaiFinal = $carbonTanggal->format('Y-m-d');
            }
            $bastData['kegiatan_list'][] = [
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                'nomor_spk' => $nomorSpk,
                'tanggal_selesai' => $tanggalSelesaiFinal,
                'tanggal_selesai_formatted' => ($tanggalSelesaiFinal && $tanggalSelesaiFinal !== 'Belum ada SPK')
                    ? Carbon::parse($tanggalSelesaiFinal)->locale('id')->isoFormat('D MMMM YYYY')
                    : '-',
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
                'satuan_pengolahan' => $isPengolahanRole ? $rateHonor?->satuan?->nama : null,
                'hasil_pengolahan_listing' => $isPengolahanRole ? $effectiveListingVolume : null,
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

        $bastObject = (object) $bastData;

        $kepala = Penandatangan::where('jenis_penandatangan', 'kepala')
            ->where('is_active', true)
            ->first();

        $viewData = [
            'bast' => $bastObject,
            'nomor_bast' => $bastData['nomor_bast'],
            'tanggal_akhir_kegiatan' => Carbon::parse($tanggalBerakhirPalingAkhir)->locale('id')->isoFormat('D MMMM YYYY'),
            'hari' => Carbon::parse($tanggalBerakhirPalingAkhir)->locale('id')->isoFormat('dddd'),
            'menggunakan_fasih' => $this->isMenggunakanFasih($allAlokasi),
            'jabatan_ppk' => 'Pejabat Pembuat Komitmen Badan Pusat Statistik Kota Sawahlunto',
            'alamat_unit_kerja' => 'Jl. Bagindo Aziz Chan, Kel. Aur Mulyo, Kec. Lembah Segar, Kota Sawahlunto',
            'nama_kepala' => $kepala?->nama,
        ];

        $cleanNomorBast = str_replace(['/', '\\'], '-', $bastData['nomor_bast']);
        $pdfMain = Pdf::loadView('bast', $viewData)
            ->setPaper('a4', 'portrait');

        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        $previewPath = $tempPath.'/bast_main_preview_'.time().'_'.uniqid().'.pdf';
        file_put_contents($previewPath, $pdfMain->output());

        return response()->file($previewPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview_BAST_MAIN_'.$cleanNomorBast.'-'.$bastData['petugas']['nama'].'.pdf"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
        ])->deleteFileAfterSend(true);
    }

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
    public function preview(Request $request)
    {
        // First do basic validation
        $basicValidated = $request->validate([
            'kegiatan_id' => 'required|exists:kegiatan,id',
            'tanggal_bast' => 'required|date',
            'menggunakan_fasih' => 'required|boolean',
            'petugas' => 'required|array|min:1',
            'petugas.*.petugas_id' => 'required|exists:petugas,id',
            'petugas.*.spk_id' => 'required|exists:spk,id',
            'petugas.*.nomor_spk' => 'required|string',
            'petugas.*.nama_petugas' => 'required|string',
            'petugas.*.hasil_listing' => 'nullable|numeric',
            'petugas.*.satuan_listing' => 'nullable|string',
            'petugas.*.instrumen_listing' => 'nullable|string',
            'petugas.*.hasil_pendataan_lapangan' => 'nullable|numeric',
            'petugas.*.satuan_pendataan_lapangan' => 'nullable|string',
            'petugas.*.instrumen_pendataan_lapangan' => 'nullable|string',
            'petugas.*.hasil_pengolahan' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan' => 'nullable|string',
            'petugas.*.hasil_pengolahan_listing' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan_listing' => 'nullable|string',
            'petugas.*.catatan' => 'nullable|string',
            'dokumen_rekap' => 'nullable|array',
            'dokumen_rekap.*.nama' => 'nullable|string',
            'dokumen_rekap.*.kode' => 'nullable|string',
            'dokumen_rekap.*.didata' => 'nullable|numeric',
            'dokumen_rekap.*.non_respon' => 'nullable|numeric',
            'dokumen_rekap.*.keterangan' => 'nullable|string',
        ]);

        // Check if we need to validate instruments based on data availability
        $alokasiPetugas = collect($basicValidated['petugas']);

        // Check for listing data (pencacah roles) - treat null as 0
        $hasActualListingData = $alokasiPetugas->some(function ($petugas) {
            return ($petugas['hasil_listing'] ?? 0) > 0;
        });

        // Check for pendataan data (pencacah roles) - treat null as 0
        $hasActualPendataanData = $alokasiPetugas->some(function ($petugas) {
            return ($petugas['hasil_pendataan_lapangan'] ?? 0) > 0;
        });

        // Dynamic validation for instruments based on data
        $instrumentValidation = [];
        if ($hasActualListingData || $hasActualPendataanData) {
            // Only require if we have actual data to report
            if ($hasActualListingData) {
                $instrumentValidation['instrumen_listing'] = 'required|string';
            } else {
                $instrumentValidation['instrumen_listing'] = 'nullable|string';
            }
            if ($hasActualPendataanData) {
                $instrumentValidation['instrumen_pendataan_lapangan'] = 'required|string';
            } else {
                $instrumentValidation['instrumen_pendataan_lapangan'] = 'nullable|string';
            }
        } else {
            // No data, instruments are optional
            $instrumentValidation['instrumen_listing'] = 'nullable|string';
            $instrumentValidation['instrumen_pendataan_lapangan'] = 'nullable|string';
        }

        // Re-validate with conditional instrument rules
        $validated = $request->validate(array_merge([
            'kegiatan_id' => 'required|exists:kegiatan,id',
            'tanggal_bast' => 'required|date',
            'menggunakan_fasih' => 'required|boolean',
            'petugas' => 'required|array|min:1',
            'petugas.*.petugas_id' => 'required|exists:petugas,id',
            'petugas.*.spk_id' => 'required|exists:spk,id',
            'petugas.*.nomor_spk' => 'required|string',
            'petugas.*.nama_petugas' => 'required|string',
            'petugas.*.hasil_listing' => 'nullable|numeric',
            'petugas.*.satuan_listing' => 'nullable|string',
            'petugas.*.instrumen_listing' => 'nullable|string',
            'petugas.*.hasil_pendataan_lapangan' => 'nullable|numeric',
            'petugas.*.satuan_pendataan_lapangan' => 'nullable|string',
            'petugas.*.instrumen_pendataan_lapangan' => 'nullable|string',
            'petugas.*.hasil_pengolahan' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan' => 'nullable|string',
            'petugas.*.hasil_pengolahan_listing' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan_listing' => 'nullable|string',
            'petugas.*.catatan' => 'nullable|string',
            'dokumen_rekap' => 'nullable|array',
            'dokumen_rekap.*.nama' => 'nullable|string',
            'dokumen_rekap.*.kode' => 'nullable|string',
            'dokumen_rekap.*.didata' => 'nullable|numeric',
            'dokumen_rekap.*.non_respon' => 'nullable|numeric',
            'dokumen_rekap.*.keterangan' => 'nullable|string',
        ], $instrumentValidation));

        // Ambil data kegiatan
        $kegiatan = Kegiatan::with('ketuaTim')->findOrFail($validated['kegiatan_id']);

        // Nomor dan tanggal
        $nomorBast = $this->generateNomorBast($validated['kegiatan_id']);
        $tanggalBast = Carbon::parse($validated['tanggal_bast']);
        $hari = $this->getHariIndonesia($tanggalBast->dayOfWeek);
        $tanggalFormatted = $tanggalBast->isoFormat('D MMMM YYYY');

        // PPK
        $penandatangan = Penandatangan::ppk()
            ->active()
            ->where(function ($q) {
                $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', today());
            })
            ->orderByDesc('periode_mulai')
            ->first();
        $namaPpk = $penandatangan ? $this->stripGelar($penandatangan->nama) : 'N/A';

        // Periode
        $targetPeriode = $this->getTargetPeriode($validated['kegiatan_id']);
        $bulanLabel = $targetPeriode?->bulan
            ? Carbon::create((int) $targetPeriode->tahun, (int) $targetPeriode->bulan)->isoFormat('MMMM')
            : $tanggalBast->isoFormat('MMMM');
        $tahunPeriode = $targetPeriode?->tahun ?? (int) $tanggalBast->year;

        // Kepala BPS
        $kepala = Penandatangan::kepala()
            ->active()
            ->where(function ($q) {
                $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', today());
            })
            ->orderByDesc('periode_mulai')
            ->first();
        $kepalaBps = $kepala ? $this->stripGelar($kepala->nama) : null;

        // Cari NIP ketua tim dari data petugas dengan nama yang sama
        $namaKetuaTim = $kegiatan->ketuaTim->name ?? 'N/A';
        $nipKetuaTim = $kegiatan->ketuaTim->nip ?? null;

        // Prioritas: cari dari data petugas dengan nama yang sama
        if ($namaKetuaTim !== 'N/A') {
            $petugasKetuaTim = Petugas::whereRaw('LOWER(nama) = ?', [strtolower($namaKetuaTim)])->first();
            if ($petugasKetuaTim && $petugasKetuaTim->nip) {
                $nipKetuaTim = $petugasKetuaTim->nip;
            }
        }

        // Data utama
        $viewData = [
            'nomor_bast' => $nomorBast,
            'hari' => $hari,
            'tanggal_bast' => $tanggalFormatted,
            'tanggal_angka' => $tanggalBast->day,
            'bulan_label' => $bulanLabel,
            'tahun' => $tahunPeriode,
            'nama_ppk' => $namaPpk,
            'nip_ppk' => $penandatangan->nip ?? 'N/A',
            'nama_ketua_tim' => $namaKetuaTim,
            'nip_ketua_tim' => $nipKetuaTim,
            'nama_kegiatan' => $kegiatan->nama_kegiatan,
            'nama_instansi' => config('app.instansi_name', 'Badan Pusat Statistik Kota Sawahlunto'),
            'menggunakan_fasih' => $validated['menggunakan_fasih'],
            'petugas' => $validated['petugas'],
            'dokumen_rekap' => $validated['dokumen_rekap'] ?? [],
            'instrumen_listing' => $validated['instrumen_listing'] ?? null,
            'instrumen_pendataan_lapangan' => $validated['instrumen_pendataan_lapangan'] ?? null,
            'kepalaBps' => $kepalaBps,
        ];

        // PDF utama
        $pdfMain = Pdf::loadView('bast', $viewData)
            ->setPaper('a4', 'portrait');
        $mainContent = $pdfMain->output();

        // PDF lampiran: selalu render sebagai halaman terpisah
        $viewData['pageNumberOffset'] = $this->resolveLampiranPageNumberOffset(null, $mainContent);
        $pdfLampiran = Pdf::loadView('bast-lampiran', $viewData)
            ->setPaper('a4', 'landscape');
        $lampiranContent = $pdfLampiran->output();

        // Gabungkan PDF
        $merged = $this->mergePdfStrings([$mainContent, $lampiranContent]);
        $fileName = 'BAST_PREVIEW_'.str_replace('/', '-', $nomorBast).'.pdf';

        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }
        $tempFile = $tempPath.'/bast_preview_'.time().'_'.uniqid().'.pdf';
        file_put_contents($tempFile, $merged);
        unset($merged);

        return response()->file($tempFile, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // First do basic validation
        $basicValidated = $request->validate([
            'kegiatan_id' => 'required|exists:kegiatan,id',
            'bulan' => 'nullable|string|min:1|max:2',
            'tahun' => 'nullable|integer',
            'tanggal_bast' => 'required|date',
            'menggunakan_fasih' => 'required|boolean',
            'petugas' => 'required|array|min:1',
            'petugas.*.petugas_id' => 'required|exists:petugas,id',
            'petugas.*.spk_id' => 'required|exists:spk,id',
            'petugas.*.nomor_spk' => 'required|string',
            'petugas.*.nama_petugas' => 'required|string',
            'petugas.*.hasil_listing' => 'nullable|numeric',
            'petugas.*.satuan_listing' => 'nullable|string',
            'petugas.*.instrumen_listing' => 'nullable|string',
            'petugas.*.hasil_pendataan_lapangan' => 'nullable|numeric',
            'petugas.*.satuan_pendataan_lapangan' => 'nullable|string',
            'petugas.*.instrumen_pendataan_lapangan' => 'nullable|string',
            'petugas.*.hasil_pengolahan' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan' => 'nullable|string',
            'petugas.*.hasil_pengolahan_listing' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan_listing' => 'nullable|string',
            'petugas.*.catatan' => 'nullable|string',
            'dokumen_rekap' => 'nullable|array',
            'dokumen_rekap.*.nama' => 'nullable|string',
            'dokumen_rekap.*.kode' => 'nullable|string',
            'dokumen_rekap.*.didata' => 'nullable|numeric',
            'dokumen_rekap.*.non_respon' => 'nullable|numeric',
            'dokumen_rekap.*.keterangan' => 'nullable|string',
        ]);

        // Check if we need to validate instruments based on data availability
        $alokasiPetugas = collect($basicValidated['petugas']);

        // Check for listing data (pencacah roles) - treat null as 0
        $hasActualListingData = $alokasiPetugas->some(function ($petugas) {
            return ($petugas['hasil_listing'] ?? 0) > 0;
        });

        // Check for pendataan data (pencacah roles) - treat null as 0
        $hasActualPendataanData = $alokasiPetugas->some(function ($petugas) {
            return ($petugas['hasil_pendataan_lapangan'] ?? 0) > 0;
        });

        // Dynamic validation for instruments based on data
        $instrumentValidation = [];
        if ($hasActualListingData || $hasActualPendataanData) {
            // Only require if we have actual data to report
            if ($hasActualListingData) {
                $instrumentValidation['instrumen_listing'] = 'required|string';
            } else {
                $instrumentValidation['instrumen_listing'] = 'nullable|string';
            }
            if ($hasActualPendataanData) {
                $instrumentValidation['instrumen_pendataan_lapangan'] = 'required|string';
            } else {
                $instrumentValidation['instrumen_pendataan_lapangan'] = 'nullable|string';
            }
        } else {
            // No data, instruments are optional
            $instrumentValidation['instrumen_listing'] = 'nullable|string';
            $instrumentValidation['instrumen_pendataan_lapangan'] = 'nullable|string';
        }

        // Re-validate with conditional instrument rules
        $validated = $request->validate(array_merge([
            'kegiatan_id' => 'required|exists:kegiatan,id',
            'tanggal_bast' => 'required|date',
            'menggunakan_fasih' => 'required|boolean',
            'petugas' => 'required|array|min:1',
            'petugas.*.petugas_id' => 'required|exists:petugas,id',
            'petugas.*.spk_id' => 'required|exists:spk,id',
            'petugas.*.nomor_spk' => 'required|string',
            'petugas.*.nama_petugas' => 'required|string',
            'petugas.*.hasil_listing' => 'nullable|numeric',
            'petugas.*.satuan_listing' => 'nullable|string',
            'petugas.*.instrumen_listing' => 'nullable|string',
            'petugas.*.hasil_pendataan_lapangan' => 'nullable|numeric',
            'petugas.*.satuan_pendataan_lapangan' => 'nullable|string',
            'petugas.*.instrumen_pendataan_lapangan' => 'nullable|string',
            'petugas.*.hasil_pengolahan' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan' => 'nullable|string',
            'petugas.*.hasil_pengolahan_listing' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan_listing' => 'nullable|string',
            'petugas.*.catatan' => 'nullable|string',
            'dokumen_rekap' => 'nullable|array',
            'dokumen_rekap.*.nama' => 'nullable|string',
            'dokumen_rekap.*.kode' => 'nullable|string',
            'dokumen_rekap.*.didata' => 'nullable|numeric',
            'dokumen_rekap.*.non_respon' => 'nullable|numeric',
            'dokumen_rekap.*.keterangan' => 'nullable|string',
        ], $instrumentValidation));

        DB::beginTransaction();

        try {
            $kegiatan = Kegiatan::with('ketuaTim')->findOrFail($validated['kegiatan_id']);

            // Get periode alokasi ID - use bulan/tahun if provided
            $targetPeriode = null;
            if (! empty($validated['bulan']) && ! empty($validated['tahun'])) {
                $targetPeriode = PeriodeAlokasi::where('kegiatan_id', $validated['kegiatan_id'])
                    ->where('bulan', $validated['bulan'])
                    ->where('tahun', $validated['tahun'])
                    ->whereIn('status', ['dikirim', 'perubahan'])
                    ->orderBy('status', 'desc') // perubahan > dikirim alphabetically
                    ->first();
            }

            // Fallback to getTargetPeriode if not found
            if (! $targetPeriode) {
                $targetPeriode = $this->getTargetPeriode($validated['kegiatan_id']);
            }

            if (! $targetPeriode) {
                throw new \RuntimeException('Periode alokasi tidak ditemukan untuk kegiatan ini.');
            }

            if (! ($targetPeriode instanceof PeriodeAlokasi)) {
                Log::error('Invalid targetPeriode type', [
                    'type' => gettype($targetPeriode),
                    'class' => is_object($targetPeriode) ? get_class($targetPeriode) : null,
                ]);
                throw new \RuntimeException('Periode alokasi tidak valid.');
            }

            $periodeId = $targetPeriode->id;

            // Get PPK
            // Get active PPK from penandatangan (by jenis_penandatangan + active + valid date range)
            $penandatangan = Penandatangan::ppk()
                ->active()
                ->where(function ($q) {
                    $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', today());
                })
                ->where(function ($q) {
                    $q->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', today());
                })
                ->orderByDesc('periode_mulai')
                ->first();

            // Generate nomor BAST
            $nomorBast = $this->generateNomorBast($validated['kegiatan_id']);

            $bulanLabel = $targetPeriode?->bulan
                ? Carbon::create((int) $targetPeriode->tahun, (int) $targetPeriode->bulan)->isoFormat('MMMM')
                : Carbon::parse($validated['tanggal_bast'])->isoFormat('MMMM');
            $tahunPeriode = $targetPeriode?->tahun ?? (int) Carbon::parse($validated['tanggal_bast'])->year;

            // Ambil data petugas dari AlokasiPetugas untuk periode ini
            // Fetch ALL allocations for each petugas in the same bulan/tahun across all kegiatan
            $bulanTarget = $targetPeriode->bulan;
            $tahunTarget = $targetPeriode->tahun;

            // Get all periode alokasi in the same bulan/tahun
            $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanTarget)
                ->where('tahun', $tahunTarget)
                ->whereIn('status', ['dikirim', 'perubahan', 'disetujui'])
                ->pluck('id');

            // Get all alokasi petugas in that period
            $allAlokasiPetugas = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
                ->whereHas('petugas', function ($q) {
                    $q->where('jenis_petugas', 'non-organik');
                })
                ->where(function ($query) {
                    $query->where('total_honor', '>', 0)
                        ->orWhere('total_honor_listing', '>', 0);
                })
                ->with([
                    'spk' => function ($query) {
                        $query->orderByDesc('created_at')->limit(1);
                    },
                    'petugas',
                    'periodeAlokasi.kegiatan',
                ])
                ->get();

            // Group by petugas and aggregate their work across all activities
            $petugasGrouped = $allAlokasiPetugas->groupBy('petugas_id');

            // Collect kegiatan details for attachments
            $kegiatanAttachments = $allAlokasiPetugas->map(function ($alokasi) {
                return [
                    'kegiatan_id' => $alokasi->periodeAlokasi->kegiatan->id,
                    'periode_alokasi_id' => $alokasi->periode_alokasi_id,
                    'kode_kegiatan' => $alokasi->periodeAlokasi->kegiatan->kode_kegiatan,
                    'nama_kegiatan' => $alokasi->periodeAlokasi->kegiatan->nama_kegiatan,
                    'bulan' => $alokasi->periodeAlokasi->bulan,
                    'tahun' => $alokasi->periodeAlokasi->tahun,
                    'jenis_kegiatan' => $alokasi->periodeAlokasi->jenis_kegiatan,
                ];
            })->unique(function ($item) {
                return $item['kegiatan_id'].'_'.$item['periode_alokasi_id'];
            })->values();

            $petugas = $petugasGrouped->map(function ($alokasiGroup, $petugasId) {
                // Get the first alokasi to get petugas info and latest SPK
                $firstAlokasi = $alokasiGroup->first();
                $latestSpk = $firstAlokasi->spk?->first(); // relasi HasMany, ambil yang pertama

                // Aggregate results across all activities for this petugas
                $totalHasilListing = 0;
                $totalHasilPendataanLapangan = 0;
                $totalHasilPengolahan = 0;
                $totalHasilPengolahanListing = 0;

                $satuanListing = null;
                $satuanPendataanLapangan = null;
                $satuanPengolahan = null;
                $satuanPengolahanListing = null;

                $peranList = [];
                $catatanList = [];

                foreach ($alokasiGroup as $alokasi) {
                    $isPendataanRole = in_array($alokasi->peran, ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'], true);
                    $isPengolahanRole = in_array($alokasi->peran, ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'], true);
                    $effectiveListingVolume = $alokasi->getEffectiveJumlahSatuanListing();
                    $effectivePencacahanVolume = $alokasi->getEffectiveJumlahSatuan();

                    if ($isPendataanRole) {
                        $totalHasilListing += $effectiveListingVolume;
                        $totalHasilPendataanLapangan += $effectivePencacahanVolume;
                        // Get satuan from the first non-null value
                        $satuanListing = $satuanListing ?? $alokasi->satuan_listing;
                        $satuanPendataanLapangan = $satuanPendataanLapangan ?? $alokasi->satuan_pendataan_lapangan;
                    }

                    if ($isPengolahanRole) {
                        $totalHasilPengolahan += $effectivePencacahanVolume;
                        $totalHasilPengolahanListing += $effectiveListingVolume;
                        // Get satuan from the first non-null value
                        $satuanPengolahan = $satuanPengolahan ?? $alokasi->satuan_pengolahan;
                        $satuanPengolahanListing = $satuanPengolahanListing ?? $alokasi->satuan_listing;
                    }

                    // Collect unique peran
                    if (! in_array($alokasi->peran, $peranList)) {
                        $peranList[] = $alokasi->peran;
                    }

                    // Collect non-empty catatan
                    if (! empty($alokasi->catatan)) {
                        $catatanList[] = $alokasi->catatan;
                    }
                }

                // Determine primary peran (use the first one or most common)
                $primaryPeran = $peranList[0] ?? 'pcl_ppl';
                $isPendataanRole = in_array($primaryPeran, ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'], true);
                $isPengolahanRole = in_array($primaryPeran, ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'], true);

                return [
                    'petugas_id' => $petugasId,
                    'spk_id' => $latestSpk?->id,
                    'nomor_spk' => $latestSpk?->nomor_spk ?? '-',
                    'nama_petugas' => $firstAlokasi->petugas?->nama ?? '-',
                    'peran' => $primaryPeran,
                    'hasil_listing' => $isPendataanRole && $totalHasilListing > 0 ? $totalHasilListing : null,
                    'satuan_listing' => $isPendataanRole ? $satuanListing : null,
                    'instrumen_listing' => $isPendataanRole ? ($firstAlokasi->instrumen_listing ?? null) : null,
                    'hasil_pendataan_lapangan' => $isPendataanRole && $totalHasilPendataanLapangan > 0 ? $totalHasilPendataanLapangan : null,
                    'satuan_pendataan_lapangan' => $isPendataanRole ? $satuanPendataanLapangan : null,
                    'instrumen_pendataan_lapangan' => $isPendataanRole ? ($firstAlokasi->instrumen_pendataan_lapangan ?? null) : null,
                    'hasil_pengolahan' => $isPengolahanRole && $totalHasilPengolahan > 0 ? $totalHasilPengolahan : null,
                    'satuan_pengolahan' => $isPengolahanRole ? $satuanPengolahan : null,
                    'hasil_pengolahan_listing' => $isPengolahanRole && $totalHasilPengolahanListing > 0 ? $totalHasilPengolahanListing : null,
                    'satuan_pengolahan_listing' => $isPengolahanRole ? $satuanPengolahanListing : null,
                    'catatan' => implode('; ', $catatanList),
                ];
            })->values()->toArray();

            // Filter hanya petugas yang punya SPK
            $petugas = array_filter($petugas, function ($p) {
                return ! empty($p['spk_id']);
            });
            $petugas = array_values($petugas); // Re-index array

            // Inject ke $validated agar generateBastPdf menggunakan data petugas dari alokasi
            $validated['petugas'] = $petugas;

            // Cari NIP ketua tim dari data petugas dengan nama yang sama
            $namaKetuaTim = $kegiatan->ketuaTim->name ?? null;
            $nipKetuaTim = $kegiatan->ketuaTim->nip ?? null;

            // Prioritas: cari dari data petugas dengan nama yang sama
            if ($namaKetuaTim) {
                $petugasKetuaTim = Petugas::whereRaw('LOWER(nama) = ?', [strtolower($namaKetuaTim)])->first();
                if ($petugasKetuaTim && $petugasKetuaTim->nip) {
                    $nipKetuaTim = $petugasKetuaTim->nip;
                }
            }

            // Create BAST (without file first)
            $bast = Bast::create([
                'nomor_bast' => $nomorBast,
                'kegiatan_id' => $validated['kegiatan_id'],
                'periode_alokasi_id' => $periodeId,
                'spk_id' => $validated['petugas'][0]['spk_id'], // Take first SPK as reference
                'tanggal_bast' => $validated['tanggal_bast'],
                'tanggal_serah_terima' => $validated['tanggal_bast'],
                'menggunakan_fasih' => $validated['menggunakan_fasih'],
                'uraian_pekerjaan' => $kegiatan->nama_kegiatan.' Bulan '.$bulanLabel.' Tahun '.$tahunPeriode,
                'nama_ketua_tim' => $namaKetuaTim,
                'nip_ketua_tim' => $nipKetuaTim,
                'nama_ppk' => $penandatangan ? $this->stripGelar($penandatangan->nama) : null,
                'nip_ppk' => $penandatangan?->nip ?? null,
                'file_path' => null, // Will be updated after PDF generation
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            // Create BAST Petugas records
            foreach ($validated['petugas'] as $petugasData) {
                BastPetugas::create([
                    'bast_id' => $bast->id,
                    'petugas_id' => $petugasData['petugas_id'],
                    'spk_id' => $petugasData['spk_id'],
                    'nomor_spk' => $petugasData['nomor_spk'],
                    'nama_petugas' => $petugasData['nama_petugas'],
                    'hasil_listing' => $petugasData['hasil_listing'],
                    'satuan_listing' => $petugasData['satuan_listing'],
                    'instrumen_listing' => $petugasData['instrumen_listing'] ?? null,
                    'hasil_pendataan_lapangan' => $petugasData['hasil_pendataan_lapangan'],
                    'satuan_pendataan_lapangan' => $petugasData['satuan_pendataan_lapangan'],
                    'instrumen_pendataan_lapangan' => $petugasData['instrumen_pendataan_lapangan'] ?? null,
                    'hasil_pengolahan_listing' => $petugasData['hasil_pengolahan_listing'],
                    'satuan_pengolahan_listing' => $petugasData['satuan_pengolahan_listing'],
                    'hasil_pengolahan' => $petugasData['hasil_pengolahan'],
                    'satuan_pengolahan' => $petugasData['satuan_pengolahan'],
                    'catatan' => $petugasData['catatan'] ?? null,
                ]);
            }

            // Create BAST Kegiatan records (activity attachments)
            foreach ($kegiatanAttachments as $attachment) {
                BastKegiatan::create([
                    'bast_id' => $bast->id,
                    'kegiatan_id' => $attachment['kegiatan_id'],
                    'periode_alokasi_id' => $attachment['periode_alokasi_id'],
                    'kode_kegiatan' => $attachment['kode_kegiatan'],
                    'nama_kegiatan' => $attachment['nama_kegiatan'],
                    'bulan' => $attachment['bulan'],
                    'tahun' => $attachment['tahun'],
                    'jenis_kegiatan' => $attachment['jenis_kegiatan'],
                ]);
            }

            // Generate PDF after data saved to database
            $filePath = $this->generateBastPdf(
                $kegiatan,
                $validated,
                $nomorBast,
                $penandatangan,
                $bulanLabel,
                $tahunPeriode
            );

            // Update BAST with file path
            $bast->update(['file_path' => $filePath]);

            $this->adoptPreviewLampiranFiles($bast->fresh('bastKegiatan'));

            DB::commit();

            ActivityLog::log(
                'Buat BAST',
                'bast',
                "Berhasil membuat BAST: {$nomorBast} untuk {$kegiatan->nama_kegiatan} periode {$bulanLabel} {$tahunPeriode}",
                'success',
                [
                    'bast_id' => $bast->id,
                    'nomor_bast' => $nomorBast,
                    'kegiatan_id' => $kegiatan->id,
                    'kegiatan_nama' => $kegiatan->nama_kegiatan,
                    'bulan' => $bulanLabel,
                    'tahun' => $tahunPeriode,
                    'jumlah_petugas' => count($validated['petugas']),
                ]
            );

            return redirect()->route('bast.index')
                ->with('success', 'BAST berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Gagal membuat BAST: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Bast $bast): Response
    {
        $bast->load([
            'kegiatan',
            'periodeAlokasi',
            'bastPetugas.petugas',
            'bastPetugas.spk',
            'bastKegiatan.kegiatan:id,kode_kegiatan,nama_kegiatan,jenis_kegiatan,ketua_tim_user_id,pj_lainnya_id',
            'createdBy:id,name',
            'spk.alokasiPetugas.petugas',
            'spk.alokasiPetugas.periodeAlokasi.kegiatan',
        ]);

        $user = $this->getRequestUser($request);
        $canManageMain = $this->userCanManageBastMain($request);
        $periode = $bast->periodeAlokasi;
        $bulanFormatted = str_pad((string) $periode->bulan, 2, '0', STR_PAD_LEFT);
        $detailReferencePeriod = $this->resolveBastDetailReferencePeriod($request, $bast);

        $isKetuaTim = $user?->active_role === 'ketua_tim';

        abort_unless($canManageMain || $isKetuaTim, 403);

        $bulanLabel = $this->getBulanLabel((int) $periode->bulan);
        $viewData = $this->prepareStoredBastViewData($bast);
        $this->syncBastKegiatanFromPayload($bast, $viewData['bast']->kegiatan_list ?? []);
        $bast->load('bastKegiatan.kegiatan:id,kode_kegiatan,nama_kegiatan,jenis_kegiatan,ketua_tim_user_id,pj_lainnya_id');
        $kegiatanPayloadMap = collect($viewData['bast']->kegiatan_list)
            ->keyBy(fn (array $item) => $this->makeBastKegiatanKey($item['kegiatan_id'], $item['periode_alokasi_id']));
        $isLegacyMode = (int) $periode->tahun < 2026
            || ((int) $periode->tahun === 2026 && (int) $periode->bulan < 4);
        $isSensusEkonomiMode = str_contains((string) $bast->nomor_bast, 'BAST-SE2026');

        $bastList = $this->buildBastListForPeriod($periode, $canManageMain, $isKetuaTim, $request, $bast, $isSensusEkonomiMode);

        // Include eligible petugas without BAST in the period so unavailable data is still visible.
        $existingBastPetugasIds = Bast::with('spk:id,petugas_id')->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $periode) {
            $q->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                ->where('tahun', $periode->tahun);
        })
            ->when($isSensusEkonomiMode, function ($query) {
                $this->applyBastNomorModeFilter($query, true);
            }, function ($query) {
                $this->applyBastNomorModeFilter($query, false);
            })
            ->get()
            ->pluck('spk.petugas_id')
            ->filter()
            ->unique();

        $isLegacyBastMode = $this->isLegacyBastAttachmentMode($bulanFormatted, (int) $periode->tahun);

        $eligibleWithoutBast = Spk::with('alokasiPetugas.petugas')
            ->whereNotIn('petugas_id', $existingBastPetugasIds)
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $periode) {
                $q->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                    ->where('tahun', $periode->tahun)
                    ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan']);
            })
            ->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($q) use ($isSensusEkonomiMode) {
                if ($isSensusEkonomiMode) {
                    $q->where('nama_kegiatan', 'like', '%Sensus Ekonomi%');

                    return;
                }

                $q->where('nama_kegiatan', 'not like', '%Sensus Ekonomi%');
            })
            ->when($isKetuaTim, function ($query) use ($user, $bulanFormatted, $periode) {
                $alokasiIds = AlokasiPetugas::whereHas('periodeAlokasi', function ($q) use ($user, $bulanFormatted, $periode) {
                    $q->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                        ->where('tahun', $periode->tahun)
                        ->whereHas('kegiatan', function ($qk) use ($user) {
                            $qk->where(function ($sub) use ($user) {
                                $sub->where('ketua_tim_user_id', $user?->id)
                                    ->orWhere('pj_lainnya_id', $user?->id);
                            });
                        });
                })
                    ->pluck('id')
                    ->toArray();

                if (empty($alokasiIds)) {
                    $query->whereRaw('0 = 1');

                    return;
                }

                $query->where(function ($inner) use ($alokasiIds) {
                    foreach ($alokasiIds as $id) {
                        $inner->orWhereJsonContains('alokasi_petugas_ids', $id);
                    }
                });
            })
            ->get()
            ->map(function ($spk) {
                $petugas = $spk->alokasiPetugas?->petugas;

                return [
                    'petugas_nama' => $petugas?->nama ?? 'Petugas tidak diketahui',
                    'petugas_id' => $petugas?->id,
                ];
            })
            ->filter(function (array $item) use ($isLegacyBastMode, $bulanFormatted, $periode) {
                $petugasId = (int) ($item['petugas_id'] ?? 0);
                if ($petugasId === 0) {
                    return false;
                }

                if ($isLegacyBastMode) {
                    return $this->hasPositiveBastAttachmentPayloadForPetugas($petugasId, $bulanFormatted, (int) $periode->tahun);
                }

                return $this->hasPositiveEffectiveAlokasiForPetugasInMonth($petugasId, $bulanFormatted, (int) $periode->tahun);
            })
            ->unique('petugas_id')
            ->sortBy('petugas_nama')
            ->values()
            ->toArray();

        $spk = $bast->spk;
        $petugas = $spk?->alokasiPetugas?->petugas;
        $lampiranList = $bast->bastKegiatan
            ->sortBy('nama_kegiatan')
            ->values()
            ->map(function (BastKegiatan $item) use ($kegiatanPayloadMap, $request) {
                $payload = $kegiatanPayloadMap->get(
                    $this->makeBastKegiatanKey($item->kegiatan_id, $item->periode_alokasi_id),
                    []
                );
                $canManageLampiran = $this->userCanManageLampiran($request, $item);
                $readyToGenerate = ! empty($payload) && $this->isLampiranGenerationAllowed($payload);
                $usesFasihScreenshot = $this->shouldUseLampiranFasihScreenshot($item->nama_kegiatan, $payload['peran'] ?? null);
                $hasStoredLampiranFile = filled($item->signed_file_path) || filled($item->file_path);

                return [
                    'id' => $item->id,
                    'kegiatan_id' => $item->kegiatan_id,
                    'periode_alokasi_id' => $item->periode_alokasi_id,
                    'kode_kegiatan' => $item->kode_kegiatan,
                    'nama_kegiatan' => $item->nama_kegiatan,
                    'jenis_kegiatan' => $item->jenis_kegiatan,
                    'peran' => $payload['peran'] ?? null,
                    'tanggal_selesai' => $payload['tanggal_selesai'] ?? null,
                    'tanggal_selesai_formatted' => $payload['tanggal_selesai_formatted'] ?? '-',
                    'ketua_tim_nama' => $payload['ketua_tim']['nama'] ?? null,
                    'file_path' => $item->file_path,
                    'signed_file_path' => $item->signed_file_path,
                    'fasih_screenshot_path' => $payload['fasih_screenshot_path'] ?? null,
                    'generated_at' => $item->generated_at?->format('d M Y H:i'),
                    'signed_uploaded_at' => $item->signed_uploaded_at?->format('d M Y H:i'),
                    'fasih_screenshot_uploaded_at' => null,
                    'status' => $item->signed_file_path ? 'signed' : ($item->file_path ? 'generated' : 'pending'),
                    'can_download' => $canManageLampiran && ($readyToGenerate || $hasStoredLampiranFile),
                    'can_generate' => $canManageLampiran && $readyToGenerate,
                    'can_upload_signed' => $canManageLampiran && filled($item->file_path),
                    'can_upload_fasih_screenshot' => false,
                    'can_preview' => $readyToGenerate,
                    'ready_to_generate' => $readyToGenerate,
                    'uses_fasih_screenshot' => $usesFasihScreenshot,
                ];
            });

        // Ketua tim only sees lampiran for kegiatan they manage.
        if ($isKetuaTim) {
            $lampiranList = $lampiranList
                ->filter(fn (array $item) => $item['can_download'])
                ->values();
        }

        $generatedLampiranCount = $lampiranList->whereNotNull('file_path')->count();
        $signedLampiranCount = $lampiranList->whereNotNull('signed_file_path')->count();
        $allLampiranGenerated = $lampiranList->isNotEmpty() && $generatedLampiranCount === $lampiranList->count();
        $allLampiranSigned = $lampiranList->isNotEmpty() && $signedLampiranCount === $lampiranList->count();
        $finalSignedReady = $isLegacyMode
            ? filled($bast->signed_file_path)
            : filled($bast->main_signed_file_path) && $allLampiranSigned && filled($bast->signed_file_path);
        $primaryBastPetugas = $bast->bastPetugas->first();
        $isSensusEkonomiBast = $this->isSensusEkonomiName($bast->kegiatan?->nama_kegiatan);
        $sensusReference = $isSensusEkonomiBast
            ? $this->buildSensusReferencePayload($spk, $detailReferencePeriod['bulan'], $detailReferencePeriod['tahun'], $primaryBastPetugas)
            : null;

        return Inertia::render('Bast/Show', [
            'bast' => [
                'id' => $bast->id,
                'hashed_id' => $bast->hashed_id,
                'nomor_bast' => $bast->nomor_bast,
                'tanggal_bast' => $bast->tanggal_bast->format('d M Y'),
                'tanggal_serah_terima' => $bast->tanggal_serah_terima->format('d M Y'),
                'menggunakan_fasih' => $bast->menggunakan_fasih,
                'uraian_pekerjaan' => $bast->uraian_pekerjaan,
                'nama_ketua_tim' => $bast->nama_ketua_tim,
                'nip_ketua_tim' => $bast->nip_ketua_tim,
                'nama_ppk' => $bast->nama_ppk,
                'nip_ppk' => $bast->nip_ppk,
                'hasil_pekerjaan' => $bast->hasil_pekerjaan,
                'file_path' => $bast->file_path,
                'compiled_file_path' => $bast->compiled_file_path,
                'main_signed_file_path' => $bast->main_signed_file_path,
                'signed_file_path' => $bast->signed_file_path,
                'lokasi_kegiatan' => $bast->lokasi_kegiatan,
                'status' => $bast->status,
                'catatan' => $bast->catatan,
                'is_sensus_ekonomi' => $isSensusEkonomiBast,
                'muatan_input' => $sensusReference['muatan_input'] ?? $primaryBastPetugas?->muatan_input,
                'muatan_prelist' => $sensusReference['muatan_prelist'] ?? $primaryBastPetugas?->muatan_prelist,
                'realisasi_unit_sampel' => $sensusReference['realisasi_unit_sampel'] ?? $primaryBastPetugas?->realisasi_unit_sampel,
                'fasih_screenshot_path' => $sensusReference['fasih_screenshot_path'] ?? null,
                'created_by' => $bast->createdBy?->name ?? 'System',
                'created_at' => $bast->created_at->format('d M Y H:i'),
                'is_legacy_mode' => $isLegacyMode,
            ],
            'spk' => $spk ? [
                'id' => $spk->id,
                'hashed_id' => $spk->hashed_id,
                'nomor_spk' => $spk->nomor_spk,
                'tanggal_spk' => $spk->tanggal_spk->format('d M Y'),
                'nilai_kontrak' => $spk->nilai_kontrak,
            ] : null,
            'petugas' => $petugas ? [
                'id' => $petugas->id,
                'hashed_id' => $petugas->hashed_id,
                'nama' => $petugas->nama,
                'nik' => $petugas->nik,
                'alamat' => $petugas->alamat,
                'no_hp' => $petugas->no_hp,
            ] : null,
            'kegiatan' => [
                'id' => $bast->kegiatan->id,
                'hashed_id' => $bast->kegiatan->hashed_id,
                'kode_kegiatan' => $bast->kegiatan->kode_kegiatan,
                'nama_kegiatan' => $bast->kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $bast->kegiatan->jenis_kegiatan,
                'tahun_anggaran' => $bast->kegiatan->tahun_anggaran,
            ],
            'periode' => [
                'id' => $periode->id,
                'hashed_id' => $periode->hashed_id,
                'bulan' => (int) $periode->bulan,
                'tahun' => $periode->tahun,
                'bulan_label' => $bulanLabel,
            ],
            'lampiran' => $lampiranList->toArray(),
            'bast_list' => $bastList->values()->toArray(),
            'eligible_without_bast' => array_values(array_filter(
                $eligibleWithoutBast,
                function ($item) use ($bastList) {
                    $idsWithBast = $bastList->pluck('petugas_id')->filter()->unique()->toArray();

                    return ! in_array($item['petugas_id'] ?? null, $idsWithBast, true);
                }
            )),
            'permissions' => [
                'can_manage_main' => $canManageMain,
                'is_ketua_tim' => $isKetuaTim,
                'can_upload_main' => in_array($user?->active_role, ['admin', 'operator'], true),
            ],
            'summary' => [
                'total_lampiran' => $lampiranList->count(),
                'generated_lampiran' => $generatedLampiranCount,
                'signed_lampiran' => $signedLampiranCount,
                'all_lampiran_generated' => $allLampiranGenerated,
                'all_lampiran_signed' => $allLampiranSigned,
                'main_signed_uploaded' => filled($bast->main_signed_file_path),
                'final_signed_ready' => $finalSignedReady,
            ],
            'sensus_reference' => $sensusReference,
            'mode' => $isSensusEkonomiMode ? 'sensus-ekonomi' : 'regular',
            'bulan' => $detailReferencePeriod['bulan'],
            'tahun' => $detailReferencePeriod['tahun'],
            'bulan_label' => $detailReferencePeriod['bulan_label'],
        ]);
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

    /**
     * Upload signed BAST file
     */
    public function uploadSigned(Request $request, Bast $bast): RedirectResponse
    {
        $bast->loadMissing('bastKegiatan.kegiatan');
        $periode = $bast->periodeAlokasi;
        $isLegacyMode = (int) $periode->tahun < 2026
            || ((int) $periode->tahun === 2026 && (int) $periode->bulan < 4);

        abort_unless($this->userCanManageBastMain($request) && $this->userCanAccessBast($request, $bast), 403);

        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // max 10MB
        ]);

        try {
            $file = $request->file('file');
            $filename = 'BAST_MAIN_SIGNED_'.$this->sanitizeDocumentSegment($bast->nomor_bast).'-'.time().'.pdf';
            $directory = $this->ensureBastExportDirectory('main-signed');
            $file->move($directory, $filename);

            $mainSignedPath = trim('bast-export/main-signed/'.$filename, '/');

            // Legacy mode (before Apr 2026) does not require per-lampiran signed upload.
            // Re-uploading main signed file should keep final signed download available.
            if ($isLegacyMode) {
                $this->deleteStoredDocument($bast->main_signed_file_path);
                $this->deleteStoredDocument($bast->signed_file_path);

                $bast->update([
                    'main_signed_file_path' => $mainSignedPath,
                    'signed_file_path' => $mainSignedPath,
                    'status' => 'diserahkan',
                ]);

                return redirect()->back()->with('success', 'BAST bertanda tangan berhasil diunggah');
            }

            if ($bast->bastKegiatan->isEmpty()) {
                $this->deleteStoredDocument($bast->signed_file_path);
                $bast->update([
                    'main_signed_file_path' => $mainSignedPath,
                    'signed_file_path' => $mainSignedPath,
                    'status' => 'diserahkan',
                ]);

                return redirect()->back()->with('success', 'BAST bertanda tangan berhasil diunggah');
            }

            $this->deleteStoredDocument($bast->main_signed_file_path);

            $bast->update([
                'main_signed_file_path' => $mainSignedPath,
                'status' => 'draft',
            ]);

            $this->syncCompiledBastFiles($bast->fresh('bastKegiatan'));
            $bast->refresh()->load('bastKegiatan');

            $allSignedSourcesReady = $bast->bastKegiatan->isNotEmpty()
                && $bast->bastKegiatan->every(fn (BastKegiatan $item) => filled($item->signed_file_path));

            if ($allSignedSourcesReady && blank($bast->signed_file_path)) {
                return redirect()->back()->with(
                    'error',
                    'BAST utama dan seluruh lampiran signed sudah tersedia, tetapi PDF gabungan gagal dibuat. Periksa laravel.log untuk detail engine PDF.'
                );
            }

            return redirect()->back()->with('success', 'BAST main bertanda tangan berhasil diunggah');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengunggah file: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bast $bast)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bast $bast)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bast $bast)
    {
        //
    }

    /**
     * Generate nomor BAST
     */
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

    /**
     * Generate BAST PDF
     */
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

    /**
     * Merge multiple PDF binary strings into one PDF preserving page orientations.
     */
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

    /**
     * Get hari in Indonesian
     */
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

    /**
     * Strip academic titles / honorifics from a full name.
     */
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

    /**
     * Ambil periode target: prioritas status perubahan (terbaru), jika tidak ada ambil dikirim (terbaru).
     */
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

    /**
     * Get bulan label (Indonesian month name)
     */
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
