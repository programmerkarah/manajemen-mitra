<?php

namespace App\Http\Controllers\Concerns;

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

trait BappFrameSupport
{
    private function getUnitSampelItems(?Kegiatan $kegiatan): array
    {
        if (! $kegiatan) {
            return [];
        }

        $ids = $kegiatan->unit_sampel_pencacahan_ids ?? [];
        if (empty($ids)) {
            return [];
        }

        return MasterUnitSampel::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get()
            ->map(fn (MasterUnitSampel $u) => ['id' => $u->id, 'nama' => $u->nama])
            ->all();
    }

    private function extractSpkFrameSampelIds(Spk $spk): array
    {
        $alokasi = $spk->alokasiPetugas;
        if (! $alokasi) {
            return [];
        }

        $alokasi->loadMissing('frameSampelAllocations');

        return $alokasi->frameSampelAllocations
            ->pluck('kegiatan_frame_sampel_id')
            ->filter(fn ($id) => filled($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function getReplacementPetugasOptions(Collection $spks, Collection $activeReplacementRows): array
    {
        if (! Schema::hasTable('petugas')) {
            return [];
        }

        $sensusPetugasIds = collect();
        if (Schema::hasTable('alokasi_petugas') && Schema::hasTable('periode_alokasi') && Schema::hasTable('kegiatan')) {
            $sensusPetugasIds = AlokasiPetugas::query()
                ->whereNotNull('petugas_id')
                ->whereHas('periodeAlokasi.kegiatan', function ($query): void {
                    $query->where('jenis_kegiatan', 'sensus')
                        ->where('nama_kegiatan', 'like', '%sensus ekonomi%');
                })
                ->pluck('petugas_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique();
        }

        return Petugas::query()
            ->when(
                Schema::hasColumn('petugas', 'status'),
                fn ($query) => $query->where('status', 'aktif')
            )
            ->when(
                $sensusPetugasIds->isNotEmpty(),
                fn ($query) => $query->whereNotIn('id', $sensusPetugasIds->all())
            )
            ->orderBy('nama')
            ->get(['id', 'nama'])
            ->map(fn (Petugas $petugas) => [
                'id' => $petugas->id,
                'nama' => $petugas->nama,
            ])
            ->sortBy(fn (array $petugas): string => mb_strtolower((string) ($petugas['nama'] ?? '')))
            ->values()
            ->all();
    }

    private function buildFrameMetadataItems(?array $metadata): array
    {
        if (! is_array($metadata)) {
            return [];
        }

        return collect($metadata)
            ->filter(function ($value, $key): bool {
                return is_string($key)
                    && trim($key) !== ''
                    && ! str_ends_with(strtolower($key), '_label')
                    && ! is_array($value)
                    && ! is_object($value)
                    && filled($value);
            })
            ->map(function ($value, $key) use ($metadata): array {
                $normalizedKey = (string) $key;

                $storedLabel = $metadata[$normalizedKey.'_label'] ?? null;
                $label = is_scalar($storedLabel) && filled($storedLabel)
                    ? (string) $storedLabel
                    : str_replace('_', ' ', ucfirst($normalizedKey));

                return [
                    'key' => $normalizedKey,
                    'label' => $label,
                    'value' => (string) $value,
                ];
            })
            ->values()
            ->all();
    }

    private function resolveFrameTarget(?array $targetUnitSampel): float
    {
        if (! is_array($targetUnitSampel)) {
            return 0;
        }

        return (float) collect($targetUnitSampel)
            ->map(fn ($value) => max(0, (float) $value))
            ->sum();
    }

    private function buildReplacementWorkflowPayload(Collection $spks, ?string $activeRoleName, int $tahun): array
    {
        $workflowPeriodeIds = $spks
            ->pluck('alokasiPetugas.periode_alokasi_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $activeReplacementRows = collect();
        if (Schema::hasTable('sensus_ekonomi_petugas_replacements') && $workflowPeriodeIds->isNotEmpty()) {
            $activeReplacementRows = SensusEkonomiPetugasReplacement::query()
                ->whereIn('periode_alokasi_id', $workflowPeriodeIds->all())
                ->where('status', '!=', 'dibatalkan')
                ->with(['petugasBerhenti', 'petugasPengganti'])
                ->latest('id')
                ->get();
        }

        $spkOptions = $spks
            ->map(fn (Spk $spk) => [
                'id' => $spk->id,
                'hashed_id' => $spk->hashed_id,
                'nomor_spk' => $spk->nomor_spk,
                'petugas_id' => $spk->petugas_id,
                'petugas_nama' => $spk->petugas?->nama,
                'periode_alokasi_id' => $spk->alokasiPetugas?->periode_alokasi_id,
            ])
            ->values()
            ->all();

        $stoppedPetugasOptionsById = [];
        $spkLamaOptionsByStoppedPetugas = [];
        $pmlCandidateSpks = collect();

        foreach ($spks as $spk) {
            $petugas = $spk->petugas;
            if (! $petugas) {
                continue;
            }

            $stoppedPetugasOptionsById[(int) $petugas->id] = [
                'id' => (int) $petugas->id,
                'nama' => $petugas->nama,
            ];
        }

        foreach ($activeReplacementRows as $replacement) {
            $petugasBerhentiId = (int) ($replacement->petugas_berhenti_id ?? 0);
            $petugasPenggantiId = (int) ($replacement->petugas_pengganti_id ?? 0);
            if ($petugasPenggantiId > 0) {
                $stoppedPetugasOptionsById[$petugasPenggantiId] = [
                    'id' => $petugasPenggantiId,
                    'nama' => $replacement->petugasPengganti?->nama,
                ];
            }

            $spkLama = $replacement->spkLama;
            if (! $spkLama instanceof Spk) {
                continue;
            }

            $spkLamaOptionsByStoppedPetugas[$petugasBerhentiId] ??= [];
            $spkLamaOptionsByStoppedPetugas[$petugasBerhentiId][] = [
                'id' => $spkLama->id,
                'hashed_id' => $spkLama->hashed_id,
                'nomor_spk' => $spkLama->nomor_spk,
                'periode_alokasi_id' => $spkLama->alokasiPetugas?->periode_alokasi_id,
                'wilayah_keys' => [],
            ];
        }

        if ($stoppedPetugasOptionsById === [] && Schema::hasTable('alokasi_petugas') && Schema::hasTable('periode_alokasi') && Schema::hasTable('kegiatan')) {
            AlokasiPetugas::query()
                ->with('petugas')
                ->whereNotNull('petugas_id')
                ->whereHas('periodeAlokasi.kegiatan', function ($query): void {
                    $query->where('jenis_kegiatan', 'sensus')
                        ->where('nama_kegiatan', 'like', '%sensus ekonomi%');
                })
                ->get()
                ->each(function (AlokasiPetugas $alokasiPetugas) use (&$stoppedPetugasOptionsById): void {
                    $petugas = $alokasiPetugas->petugas;
                    if (! $petugas) {
                        return;
                    }

                    $stoppedPetugasOptionsById[(int) $petugas->id] = [
                        'id' => (int) $petugas->id,
                        'nama' => $petugas->nama,
                    ];
                });
        }

        foreach ($spks as $spk) {
            $petugasId = $spk->petugas_id;
            if (! $petugasId) {
                continue;
            }

            $peran = $spk->alokasiPetugas?->peran ?? 'pcl_ppl';
            $frameSampelIds = $this->extractSpkFrameSampelIds($spk);

            if ($peran === 'pml') {
                $pmlCandidateSpks->push([
                    'petugas_id' => (int) $petugasId,
                    'petugas_nama' => $spk->petugas?->nama,
                    'periode_alokasi_id' => $spk->alokasiPetugas?->periode_alokasi_id,
                    'frame_sampel_ids' => $frameSampelIds,
                ]);

                continue;
            }

            if (! isset($stoppedPetugasOptionsById[$petugasId])) {
                continue;
            }

            if (! isset($spkLamaOptionsByStoppedPetugas[$petugasId])) {
                $spkLamaOptionsByStoppedPetugas[$petugasId] = [];
            }

            $spkLamaOptionsByStoppedPetugas[$petugasId][] = [
                'id' => $spk->id,
                'hashed_id' => $spk->hashed_id,
                'nomor_spk' => $spk->nomor_spk,
                'periode_alokasi_id' => $spk->alokasiPetugas?->periode_alokasi_id,
                'wilayah_keys' => [],
            ];
        }

        foreach ($spkLamaOptionsByStoppedPetugas as $petugasId => $options) {
            $uniqueById = collect($options)
                ->unique('id')
                ->sortBy('nomor_spk')
                ->values()
                ->all();

            $spkLamaOptionsByStoppedPetugas[$petugasId] = $uniqueById;
        }

        $pmlCoverOptionsBySpkLamaId = [];

        foreach ($spkLamaOptionsByStoppedPetugas as $spkOptions) {
            foreach ($spkOptions as $spkOption) {
                $spkLamaId = (int) ($spkOption['id'] ?? 0);
                $selectedPeriodeAlokasiId = (int) ($spkOption['periode_alokasi_id'] ?? 0);

                $stoppedSpk = $spks->firstWhere('id', $spkLamaId);
                $stoppedFrameSampelIds = $stoppedSpk instanceof Spk
                    ? $this->extractSpkFrameSampelIds($stoppedSpk)
                    : [];

                if ($spkLamaId <= 0 || $selectedPeriodeAlokasiId <= 0 || empty($stoppedFrameSampelIds)) {
                    $pmlCoverOptionsBySpkLamaId[$spkLamaId] = [];

                    continue;
                }

                $pmlCoverOptionsBySpkLamaId[$spkLamaId] = $pmlCandidateSpks
                    ->filter(function (array $candidate) use ($selectedPeriodeAlokasiId, $stoppedFrameSampelIds): bool {
                        $candidatePeriodeAlokasiId = (int) ($candidate['periode_alokasi_id'] ?? 0);
                        if ($candidatePeriodeAlokasiId !== $selectedPeriodeAlokasiId) {
                            return false;
                        }

                        $candidateFrameSampelIds = array_values(array_unique(array_map(
                            'intval',
                            $candidate['frame_sampel_ids'] ?? []
                        )));

                        if (empty($candidateFrameSampelIds)) {
                            return false;
                        }

                        return count(array_intersect($stoppedFrameSampelIds, $candidateFrameSampelIds)) > 0;
                    })
                    ->unique('petugas_id')
                    ->map(fn (array $candidate) => [
                        'id' => (int) $candidate['petugas_id'],
                        'nama' => $candidate['petugas_nama'],
                    ])
                    ->sortBy('nama')
                    ->values()
                    ->all();
            }
        }

        $frameDetailOptionsBySpkLamaId = [];
        foreach ($spkLamaOptionsByStoppedPetugas as $spkOptionsForPetugas) {
            foreach ($spkOptionsForPetugas as $spkOption) {
                $spkLamaId = (int) ($spkOption['id'] ?? 0);
                $spkLama = $spks->firstWhere('id', $spkLamaId);

                if (! $spkLama instanceof Spk) {
                    $frameDetailOptionsBySpkLamaId[$spkLamaId] = [];

                    continue;
                }

                $spkLama->loadMissing('alokasiPetugas.frameSampelAllocations.kegiatanFrameSampel');

                $frameDetailOptionsBySpkLamaId[$spkLamaId] = $spkLama->alokasiPetugas?->frameSampelAllocations
                    ?->map(function ($frameAllocation): array {
                        $kegiatanFrame = $frameAllocation->kegiatanFrameSampel;

                        return [
                            'alokasi_petugas_frame_sampel_id' => (int) $frameAllocation->id,
                            'kegiatan_frame_sampel_id' => $kegiatanFrame?->id,
                            'target_awal' => $this->resolveFrameTarget($kegiatanFrame?->target_unit_sampel),
                            'metadata_items' => $this->buildFrameMetadataItems(
                                is_array($kegiatanFrame?->identitas_tambahan) ? $kegiatanFrame->identitas_tambahan : null
                            ),
                        ];
                    })
                    ->values()
                    ->all() ?? [];
            }
        }

        $replacementOptions = $activeReplacementRows
            ->filter(fn (SensusEkonomiPetugasReplacement $replacement) => filled($replacement->petugas_pengganti_id))
            ->map(fn (SensusEkonomiPetugasReplacement $replacement) => [
                'id' => $replacement->id,
                'hashed_id' => $replacement->hashed_id,
                'periode_alokasi_id' => (int) $replacement->periode_alokasi_id,
                'petugas_berhenti_id' => (int) $replacement->petugas_berhenti_id,
                'petugas_berhenti_nama' => $replacement->petugasBerhenti?->nama,
                'petugas_pengganti_id' => $replacement->petugas_pengganti_id,
                'petugas_pengganti_nama' => $replacement->petugasPengganti?->nama,
                'spk_lama_id' => $replacement->spk_lama_id,
                'tanggal_berhenti' => $replacement->tanggal_berhenti?->format('Y-m-d'),
                'tanggal_mulai_pkpp' => $replacement->tanggal_mulai_pkpp?->format('Y-m-d'),
                'target_sisa' => (float) $replacement->target_sisa,
                'status' => $replacement->status,
            ])
            ->values()
            ->all();

        $nextPkppNomorPreview = null;
        if (Schema::hasTable('spk') && Schema::hasTable('sensus_ekonomi_pkpp_contracts')) {
            $nextPkppNomorPreview = app(SensusEkonomiPkNumberService::class)->previewNextNumber($tahun);
        }

        return [
            'enabled' => in_array($activeRoleName, ['admin', 'operator'], true),
            'default_periode_alokasi_id' => $workflowPeriodeIds->first(),
            'stopped_petugas_options' => array_values($stoppedPetugasOptionsById),
            'replacement_petugas_options' => $this->getReplacementPetugasOptions($spks, $activeReplacementRows),
            'spk_lama_options_by_stopped_petugas' => $spkLamaOptionsByStoppedPetugas,
            'pml_cover_options_by_spk_lama_id' => $pmlCoverOptionsBySpkLamaId,
            'frame_detail_options_by_spk_lama_id' => $frameDetailOptionsBySpkLamaId,
            'spk_options' => $spkOptions,
            'replacement_options' => $replacementOptions,
            'next_pkpp_nomor_preview' => $nextPkppNomorPreview,
        ];
    }
}
