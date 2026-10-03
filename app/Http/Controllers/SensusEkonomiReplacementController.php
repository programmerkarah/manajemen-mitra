<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSensusEkonomiPkppContractRequest;
use App\Http\Requests\StoreSensusEkonomiReplacementRequest;
use App\Models\SensusEkonomiPetugasReplacement;
use App\Models\SensusEkonomiPkppContract;
use App\Models\Petugas;
use App\Models\PeriodeAlokasi;
use App\Models\Spk;
use App\Services\SensusEkonomiPkNumberService;
use App\Services\SensusEkonomiPkppSchemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
        $replacementBastByReplacementId = Schema::hasTable('sensus_ekonomi_replacement_documents')
            ? DB::table('sensus_ekonomi_replacement_documents')
                ->where('document_type', 'bast')
                ->get()
                ->keyBy('replacement_id')
            : collect();

        if (Schema::hasTable('sensus_ekonomi_petugas_replacements')) {
            $replacements = SensusEkonomiPetugasReplacement::query()
                ->with([
                    'petugasBerhenti:id,nama',
                    'petugasPengganti:id,nama',
                    'pmlCoverPetugas:id,nama',
                    'pkppContracts.spk:id,signed_file_path,status',
                    'spkLama:id,nomor_spk,petugas_id',
                ])
                ->latest('id')
                ->get()
                ->map(function (SensusEkonomiPetugasReplacement $replacement) use ($replacementBastByReplacementId): array {
                    $pkpp = Schema::hasTable('sensus_ekonomi_pkpp_contracts')
                        ? $replacement->pkppContracts->sortByDesc('id')->first()
                        : null;

                    $replacementBast = $replacementBastByReplacementId->get($replacement->id);

                    return [
                        'id' => $replacement->id,
                        'hashed_id' => $replacement->hashed_id,
                        'spk_lama_id' => $replacement->spk_lama_id,
                        'petugas_berhenti_nama' => $replacement->petugasBerhenti?->nama,
                        'petugas_pengganti_nama' => $replacement->petugasPengganti?->nama,
                        'pml_cover_nama' => $replacement->pmlCoverPetugas?->nama,
                        'tanggal_berhenti' => $replacement->tanggal_berhenti?->format('Y-m-d'),
                        'termination_type' => $replacement->termination_type,
                        'termin_i_paid' => $replacement->termin_i_paid,
                        'requires_old_documents' => $replacement->termination_type === 'diberhentikan'
                            || ($replacement->termination_type === 'mengundurkan_diri' && $replacement->termin_i_paid !== false),
                        'spk_lama_nomor' => $replacement->spkLama?->nomor_spk,
                        'tanggal_mulai_pkpp' => $replacement->tanggal_mulai_pkpp?->format('Y-m-d'),
                        'status' => $replacement->status,
                        'has_pkpp_contract' => $pkpp !== null,
                        'replacement_bast' => $replacementBast ? [
                            'nomor' => $replacementBast->nomor_dokumen,
                            'nomor_urut' => preg_match('/^B-(\\d+)\\/BAST-SE2026\\//', (string) $replacementBast->nomor_dokumen, $matches) === 1
                                ? $matches[1]
                                : null,
                            'tanggal' => $replacementBast->tanggal_dokumen,
                            'uploaded_at' => $replacementBast->uploaded_at,
                        ] : null,
                        'pkpp' => $pkpp ? [
                            'nomor' => $pkpp->nomor_pkpp,
                            'skema_kode' => $pkpp->skema_kode,
                            'termin_count' => (int) $pkpp->termin_count,
                            'honor_ob' => (float) $pkpp->honor_ob,
                            'tanggal_kontrak' => $pkpp->tanggal_kontrak?->format('Y-m-d'),
                            'tanggal_mulai_lapangan' => $pkpp->tanggal_mulai_lapangan?->format('Y-m-d'),
                            'has_spk' => $pkpp->spk !== null,
                            'pk_uploaded' => filled($pkpp->signed_file_path)
                            || filled($pkpp->spk?->signed_file_path),
                        ] : null,
                    ];
                });
        }

        $canManage = $request->user()?->isAdmin() || $request->user()?->isOperator();

        $activeStoppedSpkIds = $replacements
            ->pluck('spk_lama_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $stoppedCandidates = Spk::query()
            ->where('addendum_number', 0)
            ->whereIn('lampiran_template', ['sensus_ekonomi', 'pml_sensus_ekonomi'])
            ->whereNotIn('id', $activeStoppedSpkIds)
            ->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($query): void {
                $query->where('jenis_kegiatan', 'sensus')
                    ->where('nama_kegiatan', 'like', '%Sensus Ekonomi%');
            })
            ->with(['petugas:id,nama,nik'])
            ->orderBy('nomor_spk')
            ->get()
            ->map(fn (Spk $spk) => [
                'spk_id' => $spk->id,
                'spk_hashed_id' => $spk->hashed_id,
                'nomor_spk' => $spk->nomor_spk,
                'petugas_id' => $spk->petugas_id,
                'petugas_nama' => $spk->petugas?->nama,
                'petugas_nik' => $spk->petugas?->nik,
            ])
            ->values();

        $usedReplacementIds = SensusEkonomiPetugasReplacement::query()
            ->where('status', '!=', 'dibatalkan')
            ->whereNotNull('petugas_pengganti_id')
            ->pluck('petugas_pengganti_id');

        $sensusPetugasIds = Spk::query()
            ->where('addendum_number', 0)
            ->whereIn('lampiran_template', ['sensus_ekonomi', 'pml_sensus_ekonomi'])
            ->pluck('petugas_id')
            ->filter()
            ->unique();

        $replacementCandidates = Petugas::query()
            ->where('status', 'aktif')
            ->where('jenis_petugas', 'non-organik')
            ->whereNotIn('id', $sensusPetugasIds)
            ->whereNotIn('id', $usedReplacementIds)
            ->orderBy('nama')
            ->get(['id', 'nama', 'nik'])
            ->map(fn (Petugas $petugas) => [
                'id' => $petugas->id,
                'hashed_id' => $petugas->hashed_id,
                'nama' => $petugas->nama,
                'nik' => $petugas->nik,
            ])
            ->values();

        return Inertia::render('Spk/PetugasPengganti/Index', [
            'replacements' => $replacements,
            'stopped_candidates' => $stoppedCandidates,
            'replacement_candidates' => $replacementCandidates,
            'can_manage' => $canManage,
        ]);
    }

    public function registerStop(Request $request): RedirectResponse
    {
        if (! $request->user()?->isAdmin() && ! $request->user()?->isOperator()) {
            abort(403);
        }

        $validated = $request->validate([
            'spk_id' => [
                'required',
                'integer',
                'exists:spk,id',
                Rule::unique('sensus_ekonomi_petugas_replacements', 'spk_lama_id')
                    ->where(fn ($query) => $query->where('status', '!=', 'dibatalkan')),
            ],
            'termination_type' => ['required', 'in:diberhentikan,mengundurkan_diri'],
            'termin_i_paid' => ['nullable', 'boolean', 'required_if:termination_type,mengundurkan_diri'],
            'tanggal_berhenti' => ['required', 'date'],
        ]);

        $spk = Spk::query()
            ->with([
                'alokasiPetugas.frameSampelAllocations.kegiatanFrameSampel',
                'alokasiPetugas.periodeAlokasi.kegiatan',
            ])
            ->findOrFail((int) $validated['spk_id']);

        $kegiatan = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan;
        if (
            ! $kegiatan
            || $kegiatan->jenis_kegiatan !== 'sensus'
            || ! str_contains(mb_strtolower((string) $kegiatan->nama_kegiatan), 'sensus ekonomi')
        ) {
            return back()->with('error', 'PK yang dipilih bukan PK Sensus Ekonomi.');
        }

        $details = $spk->alokasiPetugas?->frameSampelAllocations ?? collect();
        $targetAwal = (float) $details->sum(
            fn ($allocation) => $this->resolveFrameTarget(
                $allocation->kegiatanFrameSampel?->target_unit_sampel
            )
        );

        DB::transaction(function () use ($request, $validated, $spk, $details, $targetAwal): void {
            $replacement = SensusEkonomiPetugasReplacement::query()->create([
                'periode_alokasi_id' => (int) $spk->alokasiPetugas->periode_alokasi_id,
                'petugas_berhenti_id' => (int) $spk->petugas_id,
                'petugas_pengganti_id' => null,
                'pml_cover_petugas_id' => null,
                'spk_lama_id' => $spk->id,
                'termination_type' => $validated['termination_type'],
                'termin_i_paid' => $validated['termination_type'] === 'mengundurkan_diri'
                    ? (bool) $validated['termin_i_paid']
                    : true,
                'tanggal_berhenti' => $validated['tanggal_berhenti'],
                'target_awal' => $targetAwal,
                'realisasi_petugas_berhenti' => 0,
                'realisasi_pml_cover' => 0,
                'target_sisa' => $targetAwal,
                'status' => 'draft',
                'created_by' => $request->user()?->id,
            ]);

            if (Schema::hasTable('sensus_ekonomi_replacement_details')) {
                $payload = $details->values()->map(function ($allocation, int $index): array {
                    $target = $this->resolveFrameTarget(
                        $allocation->kegiatanFrameSampel?->target_unit_sampel
                    );

                    return [
                        'alokasi_petugas_frame_sampel_id' => $allocation->id,
                        'kegiatan_frame_sampel_id' => $allocation->kegiatan_frame_sampel_id,
                        'metadata' => $allocation->kegiatanFrameSampel?->identitas_tambahan,
                        'target_awal' => $target,
                        'realisasi_petugas_berhenti' => 0,
                        'realisasi_pml_cover' => 0,
                        'target_sisa' => $target,
                        'urutan' => $index + 1,
                    ];
                })->all();

                if ($payload !== []) {
                    $replacement->details()->createMany($payload);
                }
            }
        });

        return back()->with('success', 'Status petugas SE2026 berhasil dicatat. Dokumen petugas lama dapat dilengkapi terpisah.');
    }

    public function assignReplacement(
        Request $request,
        SensusEkonomiPetugasReplacement $replacement,
    ): RedirectResponse {
        if (! $request->user()?->isAdmin() && ! $request->user()?->isOperator()) {
            abort(403);
        }

        $validated = $request->validate([
            'petugas_pengganti_id' => ['required', 'integer', 'exists:petugas,id'],
            // Nama kolom dipertahankan untuk kompatibilitas database lama,
            // tetapi pada alur baru nilainya adalah tanggal kontrak awal PK pengganti.
            'tanggal_mulai_pkpp' => ['required', 'date', 'after_or_equal:'.$replacement->tanggal_berhenti?->format('Y-m-d')],
        ]);

        $petugasId = (int) $validated['petugas_pengganti_id'];

        if (
            Spk::query()
                ->where('petugas_id', $petugasId)
                ->where('addendum_number', 0)
                ->whereIn('lampiran_template', ['sensus_ekonomi', 'pml_sensus_ekonomi'])
                ->exists()
        ) {
            return back()->with('error', 'Petugas pengganti tidak boleh berasal dari petugas SE2026 aktif.');
        }

        if (
            SensusEkonomiPetugasReplacement::query()
                ->whereKeyNot($replacement->id)
                ->where('petugas_pengganti_id', $petugasId)
                ->where('status', '!=', 'dibatalkan')
                ->exists()
        ) {
            return back()->with('error', 'Petugas tersebut sudah dipakai sebagai pengganti aktif.');
        }

        $replacement->update([
            'petugas_pengganti_id' => $petugasId,
            'tanggal_mulai_pkpp' => $validated['tanggal_mulai_pkpp'],
            'status' => 'pengganti_ditetapkan',
        ]);

        return back()->with('success', 'Petugas pengganti berhasil ditetapkan. Selanjutnya tentukan skema PKPP.');
    }

    public function uploadReplacementBast(
        Request $request,
        SensusEkonomiPetugasReplacement $replacement,
    ): RedirectResponse {
        if (! $request->user()?->isAdmin() && ! $request->user()?->isOperator()) {
            abort(403);
        }

        if (! Schema::hasTable('sensus_ekonomi_replacement_documents')) {
            return back()->with('error', 'Tabel dokumen pengganti belum tersedia. Jalankan migration terlebih dahulu.');
        }

        if (! $replacement->petugas_pengganti_id) {
            return back()->with('error', 'Tetapkan petugas pengganti terlebih dahulu.');
        }

        $contract = SensusEkonomiPkppContract::query()
            ->where('replacement_id', $replacement->id)
            ->where('petugas_id', $replacement->petugas_pengganti_id)
            ->first();

        if (! $contract) {
            return back()->with('error', 'Tetapkan skema PK petugas pengganti terlebih dahulu.');
        }

        $validated = $request->validate([
            'nomor_bast' => ['required', 'string', 'regex:/^\\d+$/', 'max:12'],
            'tanggal_bast' => ['nullable', 'date'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $tahun = (int) ($contract->tanggal_kontrak?->year ?? now()->year);
        $sequence = preg_replace('/\\D+/', '', (string) $validated['nomor_bast']);
        $fullNumber = sprintf(
            'B-%s/BAST-SE2026/1373/PL.200/%d',
            $sequence,
            $tahun,
        );

        $existing = DB::table('sensus_ekonomi_replacement_documents')
            ->where('replacement_id', $replacement->id)
            ->where('document_type', 'bast')
            ->whereNull('termin')
            ->first();

        if ($existing && filled($existing->file_path)) {
            Storage::disk('public')->delete((string) $existing->file_path);
        }

        $safePetugas = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '_',
            (string) ($replacement->petugasPengganti?->nama ?: 'petugas')
        );
        $stored = $request->file('file')->storeAs(
            'replacement-documents/se2026/'.$tahun,
            'BAST_'.$sequence.'_'.$safePetugas.'_'.time().'.pdf',
            'public',
        );

        DB::table('sensus_ekonomi_replacement_documents')->updateOrInsert(
            [
                'replacement_id' => $replacement->id,
                'document_type' => 'bast',
                'termin' => null,
            ],
            [
                'nomor_dokumen' => $fullNumber,
                'tanggal_dokumen' => $validated['tanggal_bast'] ?? now()->toDateString(),
                'file_path' => $stored,
                'uploaded_at' => now(),
                'created_by' => $existing?->created_by ?: $request->user()?->id,
                'created_at' => $existing?->created_at ?: now(),
                'updated_at' => now(),
            ],
        );

        return back()->with('success', 'BAST petugas pengganti berhasil diunggah.');
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
                'spk_signed_uploaded' => filled($existingContract->signed_file_path)
                    || filled($existingContract->spk?->signed_file_path),
            ] : null,
            'existing_spk' => $existingSpk ? [
                'hashed_id' => $existingSpk->hashed_id,
                'nomor_spk' => $existingSpk->nomor_spk,
            ] : null,
            'action' => route('se-replacements.pkpp-contracts.store', $replacement),
            'default_tanggal_kontrak' => $existingContract?->tanggal_kontrak?->format('Y-m-d')
                ?? $replacement->tanggal_mulai_pkpp?->format('Y-m-d')
                ?? now()->format('Y-m-d'),
            'default_tanggal_mulai_lapangan' => $existingContract?->tanggal_mulai_lapangan?->format('Y-m-d'),
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
                'termination_type' => $validated['termination_type'] ?? null,
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

    public function uploadSignedPkpp(Request $request, SensusEkonomiPetugasReplacement $replacement): RedirectResponse
    {
        if (! $request->user()?->isAdmin() && ! $request->user()?->isOperator()) {
            abort(403);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $contract = SensusEkonomiPkppContract::query()
            ->where('replacement_id', $replacement->id)
            ->where('petugas_id', $replacement->petugas_pengganti_id)
            ->with(['spk.alokasiPetugas.periodeAlokasi', 'petugas'])
            ->first();

        if (! $contract) {
            return back()->with('error', 'Simpan data skema PK petugas pengganti terlebih dahulu.');
        }

        $tahun = (int) ($contract->tanggal_kontrak?->year ?? now()->year);
        $safeNomor = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($contract->nomor_pkpp ?: 'PKPP_'.$contract->id));
        $safePetugas = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($contract->petugas?->nama ?: 'petugas'));
        $fileName = 'PKPP_'.$safeNomor.'_'.$safePetugas.'_signed_'.time().'.pdf';

        if (filled($contract->signed_file_path)) {
            Storage::disk('public')->delete((string) $contract->signed_file_path);
        }

        $stored = $request->file('file')->storeAs(
            'pkpp/se2026/'.$tahun,
            $fileName,
            'public',
        );

        $contract->update([
            'signed_file_path' => $stored,
            'signed_uploaded_at' => now(),
        ]);

        // Jika workflow lama sudah memiliki record SPK pengganti, pertahankan
        // sinkronisasi file agar halaman SPK lama tetap dapat membuka dokumen final.
        if ($contract->spk) {
            $contract->spk->update([
                'signed_file_path' => 'storage/'.$stored,
                'status' => 'diterbitkan',
            ]);
        }

        return back()->with('success', 'PDF PK petugas pengganti berhasil diunggah.');
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
        $tanggalMulaiLapangan = $validated['tanggal_mulai_lapangan'];

        if (! $tanggalMulaiLapangan) {
            return back()->with('error', 'Tanggal mulai lapangan wajib ditentukan pada form skema pengganti.');
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
