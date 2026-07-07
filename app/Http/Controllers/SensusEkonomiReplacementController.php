<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSensusEkonomiPkppContractRequest;
use App\Http\Requests\StoreSensusEkonomiReplacementRequest;
use App\Models\SensusEkonomiPetugasReplacement;
use App\Models\SensusEkonomiPkppContract;
use App\Models\Spk;
use App\Services\SensusEkonomiPkNumberService;
use App\Services\SensusEkonomiPkppSchemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class SensusEkonomiReplacementController extends Controller
{
    /**
     * @param  array<string, mixed>|null  $targetUnitSampel
     */
    private function resolveFrameTarget(?array $targetUnitSampel): float
    {
        if (! is_array($targetUnitSampel)) {
            return 0;
        }

        return (float) collect($targetUnitSampel)
            ->map(fn ($value) => max(0, (float) $value))
            ->sum();
    }

    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()?->isAdmin() && ! $request->user()?->isOperator() && ! $request->user()?->isKetuaTim()) {
            return redirect()->route('dashboard');
        }

        $replacements = collect();
        if (Schema::hasTable('sensus_ekonomi_petugas_replacements')) {
            $replacements = SensusEkonomiPetugasReplacement::query()
                ->with([
                    'petugasBerhenti:id,nama',
                    'petugasPengganti:id,nama',
                    'pmlCoverPetugas:id,nama',
                ])
                ->latest('id')
                ->get()
                ->map(function (SensusEkonomiPetugasReplacement $replacement): array {
                    return [
                        'id' => $replacement->id,
                        'hashed_id' => $replacement->hashed_id,
                        'petugas_berhenti_nama' => $replacement->petugasBerhenti?->nama,
                        'petugas_pengganti_nama' => $replacement->petugasPengganti?->nama,
                        'pml_cover_nama' => $replacement->pmlCoverPetugas?->nama,
                        'tanggal_berhenti' => $replacement->tanggal_berhenti?->format('Y-m-d'),
                        'tanggal_mulai_pkpp' => $replacement->tanggal_mulai_pkpp?->format('Y-m-d'),
                        'status' => $replacement->status,
                        'has_pkpp_contract' => Schema::hasTable('sensus_ekonomi_pkpp_contracts')
                            ? $replacement->pkppContracts()->exists()
                            : false,
                    ];
                });
        }

        return Inertia::render('Spk/PetugasPengganti/Index', [
            'replacements' => $replacements,
        ]);
    }

    public function createPkppContract(SensusEkonomiPetugasReplacement $replacement): Response|RedirectResponse
    {
        $replacement->loadMissing([
            'petugasBerhenti:id,nama',
            'petugasPengganti:id,nama',
            'pmlCoverPetugas:id,nama',
        ]);

        if (! $replacement->petugas_pengganti_id) {
            return back()->with('error', 'Replacement belum memiliki petugas pengganti. Tetapkan petugas pengganti terlebih dahulu.');
        }

        $periode = PeriodeAlokasi::query()->find($replacement->periode_alokasi_id);
        $petugasPengganti = Petugas::query()->find($replacement->petugas_pengganti_id);

        $existingContract = SensusEkonomiPkppContract::query()
            ->where('replacement_id', $replacement->id)
            ->where('petugas_id', $replacement->petugas_pengganti_id)
            ->first();

        $existingSpk = null;
        if ($replacement->petugas_pengganti_id && $replacement->periode_alokasi_id) {
            $existingSpk = Spk::query()
                ->whereHas('alokasiPetugas', function ($query) use ($replacement) {
                    $query->where('petugas_id', $replacement->petugas_pengganti_id)
                        ->where('periode_alokasi_id', $replacement->periode_alokasi_id);
                })
                ->latest('id')
                ->first();
        }

        return Inertia::render('Spk/PetugasPengganti/CreatePkppContract', [
            'replacement' => [
                'id' => $replacement->id,
                'hashed_id' => $replacement->hashed_id,
                'petugas_berhenti_nama' => $replacement->petugasBerhenti?->nama,
                'petugas_pengganti_nama' => $replacement->petugasPengganti?->nama,
                'pml_cover_nama' => $replacement->pmlCoverPetugas?->nama,
                'tanggal_berhenti' => $replacement->tanggal_berhenti?->format('Y-m-d'),
                'tanggal_mulai_pkpp' => $replacement->tanggal_mulai_pkpp?->format('Y-m-d'),
                'target_sisa' => (float) $replacement->target_sisa,
                'status' => $replacement->status,
                'periode_hashed_id' => $periode?->hashed_id,
                'petugas_pengganti_hashed_id' => $petugasPengganti?->hashed_id,
            ],
            'existing_contract' => $existingContract ? [
                'hashed_id' => $existingContract->hashed_id,
                'nomor_pkpp' => $existingContract->nomor_pkpp,
                'tanggal_kontrak' => $existingContract->tanggal_kontrak?->format('Y-m-d'),
                'tanggal_mulai_lapangan' => $existingContract->tanggal_mulai_lapangan?->format('Y-m-d'),
                'status' => $existingContract->status,
                'spk_hashed_id' => $existingContract->spk?->hashed_id,
                'spk_nomor_spk' => $existingContract->spk?->nomor_spk,
            ] : null,
            'existing_spk' => $existingSpk ? [
                'hashed_id' => $existingSpk->hashed_id,
                'nomor_spk' => $existingSpk->nomor_spk,
            ] : null,
            'action' => route('se-replacements.pkpp-contracts.store', $replacement),
            'default_tanggal_kontrak' => now()->format('Y-m-d'),
            'default_tanggal_mulai_lapangan' => $replacement->tanggal_mulai_pkpp?->format('Y-m-d'),
        ]);
    }

    public function storeReplacement(StoreSensusEkonomiReplacementRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $spkLama = Spk::query()->with('alokasiPetugas.frameSampelAllocations.kegiatanFrameSampel')->findOrFail((int) $validated['spk_lama_id']);
        $periodeAlokasiId = (int) ($spkLama->alokasiPetugas?->periode_alokasi_id ?? 0);
        if ($periodeAlokasiId <= 0) {
            return back()->with('error', 'Periode alokasi dari SPK lama tidak ditemukan.');
        }

        $frameAllocationsById = $spkLama->alokasiPetugas?->frameSampelAllocations
            ?->keyBy('id') ?? collect();

        $detailPayload = collect($validated['detail_rows'] ?? [])
            ->unique('alokasi_petugas_frame_sampel_id')
            ->values()
            ->map(function (array $row, int $index) use ($frameAllocationsById): array {
                $frameAllocationId = (int) ($row['alokasi_petugas_frame_sampel_id'] ?? 0);
                $frameAllocation = $frameAllocationsById->get($frameAllocationId);
                $kegiatanFrame = $frameAllocation?->kegiatanFrameSampel;

                $targetAwal = $this->resolveFrameTarget($kegiatanFrame?->target_unit_sampel);
                $realisasiPetugasBerhenti = max(0, (float) ($row['realisasi_petugas_berhenti'] ?? 0));
                $realisasiPmlCover = max(0, (float) ($row['realisasi_pml_cover'] ?? 0));

                return [
                    'alokasi_petugas_frame_sampel_id' => $frameAllocationId,
                    'kegiatan_frame_sampel_id' => $kegiatanFrame?->id,
                    'metadata' => is_array($kegiatanFrame?->identitas_tambahan)
                        ? $kegiatanFrame->identitas_tambahan
                        : null,
                    'target_awal' => $targetAwal,
                    'realisasi_petugas_berhenti' => $realisasiPetugasBerhenti,
                    'realisasi_pml_cover' => $realisasiPmlCover,
                    'target_sisa' => max(0, $targetAwal - $realisasiPetugasBerhenti - $realisasiPmlCover),
                    'urutan' => $index + 1,
                ];
            })
            ->all();

        $targetAwal = (float) collect($detailPayload)->sum('target_awal');
        $realisasiPetugasBerhenti = (float) collect($detailPayload)->sum('realisasi_petugas_berhenti');
        $realisasiPmlCover = (float) collect($detailPayload)->sum('realisasi_pml_cover');
        $targetSisa = (float) max(0, $targetAwal - $realisasiPetugasBerhenti - $realisasiPmlCover);

        $status = (string) ($validated['status'] ?? 'draft');
        if (! isset($validated['status'])) {
            if (! empty($validated['petugas_pengganti_id'])) {
                $status = 'pengganti_ditetapkan';
            } elseif (! empty($validated['pml_cover_petugas_id'])) {
                $status = 'pml_cover';
            }
        }

        $replacement = DB::transaction(function () use (
            $request,
            $validated,
            $periodeAlokasiId,
            $targetAwal,
            $realisasiPetugasBerhenti,
            $realisasiPmlCover,
            $targetSisa,
            $status,
            $detailPayload,
        ): SensusEkonomiPetugasReplacement {
            $replacement = SensusEkonomiPetugasReplacement::query()->create([
                'periode_alokasi_id' => $periodeAlokasiId,
                'petugas_berhenti_id' => $validated['petugas_berhenti_id'],
                'petugas_pengganti_id' => $validated['petugas_pengganti_id'] ?? null,
                'pml_cover_petugas_id' => $validated['pml_cover_petugas_id'] ?? null,
                'spk_lama_id' => $validated['spk_lama_id'] ?? null,
                'tanggal_berhenti' => $validated['tanggal_berhenti'],
                'tanggal_mulai_cover' => $validated['tanggal_mulai_cover'] ?? null,
                'tanggal_mulai_pkpp' => $validated['tanggal_mulai_pkpp'] ?? null,
                'target_awal' => $targetAwal,
                'realisasi_petugas_berhenti' => $realisasiPetugasBerhenti,
                'realisasi_pml_cover' => $realisasiPmlCover,
                'target_sisa' => $targetSisa,
                'status' => $status,
                'catatan' => $validated['catatan'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            if (Schema::hasTable('sensus_ekonomi_replacement_details') && ! empty($detailPayload)) {
                $replacement->details()->createMany($detailPayload);
            }

            return $replacement;
        });

        return back()->with('success', sprintf(
            'Replacement petugas berhasil dibuat (ID: %s).',
            $replacement->hashed_id,
        ));
    }

    public function storePkppContract(
        StoreSensusEkonomiPkppContractRequest $request,
        SensusEkonomiPetugasReplacement $replacement,
        SensusEkonomiPkNumberService $pkNumberService,
        SensusEkonomiPkppSchemeService $pkppSchemeService,
    ): RedirectResponse {
        $validated = $request->validated();

        $petugasPenggantiId = (int) ($replacement->petugas_pengganti_id ?? 0);
        if ($petugasPenggantiId <= 0) {
            return back()->with('error', 'Replacement belum memiliki petugas pengganti. Tetapkan petugas pengganti terlebih dahulu.');
        }

        $scheme = $pkppSchemeService->resolveScheme($validated['tanggal_kontrak']);
        $existingContract = SensusEkonomiPkppContract::query()
            ->where('replacement_id', $replacement->id)
            ->where('petugas_id', $petugasPenggantiId)
            ->first();

        $kontrakYear = (int) date('Y', strtotime((string) $validated['tanggal_kontrak']));
        $nomorPkpp = $existingContract?->nomor_pkpp ?: $pkNumberService->allocateNextNumber($kontrakYear);
        $targetSisa = (float) ($replacement->target_sisa ?? 0);
        $tanggalMulaiLapangan = $replacement->tanggal_mulai_pkpp?->format('Y-m-d')
            ?? ($validated['tanggal_mulai_lapangan'] ?? null);

        if (! $tanggalMulaiLapangan) {
            return back()->with('error', 'Tanggal mulai lapangan belum tersedia pada replacement ini.');
        }

        $targetTermin1 = (float) ($targetSisa * ((int) ($scheme['termin_targets'][0] ?? 0)) / 100);
        $targetTermin2 = null;
        if ((int) $scheme['termin_count'] === 2) {
            $targetTermin2 = (float) ($targetSisa * ((int) ($scheme['termin_targets'][1] ?? 0)) / 100);
        }

        $existingSpk = Spk::query()
            ->whereHas('alokasiPetugas', function ($query) use ($replacement, $petugasPenggantiId) {
                $query->where('petugas_id', $petugasPenggantiId)
                    ->where('periode_alokasi_id', $replacement->periode_alokasi_id);
            })
            ->latest('id')
            ->first();

        $contract = SensusEkonomiPkppContract::query()->updateOrCreate(
            [
                'replacement_id' => $replacement->id,
                'petugas_id' => $petugasPenggantiId,
            ],
            [
                'periode_alokasi_id' => (int) $replacement->periode_alokasi_id,
                'spk_id' => $existingSpk?->id,
                'nomor_pkpp' => $nomorPkpp,
                'tanggal_kontrak' => $validated['tanggal_kontrak'],
                'tanggal_mulai_lapangan' => $tanggalMulaiLapangan,
                'skema_kode' => $scheme['code'],
                'termin_count' => (int) $scheme['termin_count'],
                'honor_ob' => (float) $scheme['honor_ob'],
                'persentase_termin_1' => (int) ($scheme['termin_shares'][0] ?? 100),
                'persentase_termin_2' => isset($scheme['termin_shares'][1]) ? (int) $scheme['termin_shares'][1] : null,
                'target_termin_1' => ['target' => round($targetTermin1, 2)],
                'target_termin_2' => $targetTermin2 !== null ? ['target' => round($targetTermin2, 2)] : null,
                'target_total' => ['target' => round($targetSisa, 2)],
                'waktu_penyelesaian_termin_1' => $scheme['termin_satu_waktu'] ?? null,
                'waktu_penyelesaian_termin_akhir' => $scheme['termin_akhir_waktu'],
                'periode_pasal_7' => $scheme['pasal_7_periode'],
                'status' => $validated['status'] ?? 'draft',
                'created_by' => $request->user()?->id,
            ],
        );

        return back()->with('success', sprintf(
            'Kontrak PKPP berhasil disimpan (ID: %s).',
            $contract->hashed_id,
        ));
    }
}
