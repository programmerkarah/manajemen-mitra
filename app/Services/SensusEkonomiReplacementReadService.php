<?php

namespace App\Services;

use App\Models\BappSeTermin;
use App\Models\SensusEkonomiPkppContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SensusEkonomiReplacementReadService
{
    /**
     * Read-only projection for manual SE2026 replacement contracts.
     *
     * The replacement workflow intentionally does not create a normal
     * alokasi_petugas/SPK row. This projection lets reporting/public pages
     * treat a saved PKPP as an allocation without re-enabling document
     * generation for the replacement officer.
     *
     * @return Collection<int,array<string,mixed>>
     */
    public function assignments(int $year, ?int $petugasId = null): Collection
    {
        if (! Schema::hasTable('sensus_ekonomi_pkpp_contracts')
            || ! Schema::hasTable('sensus_ekonomi_petugas_replacements')) {
            return collect();
        }

        $query = SensusEkonomiPkppContract::query()
            ->with([
                'petugas:id,nama,nik,jenis_petugas',
                'replacement.periodeAlokasi.kegiatan:id,kode_kegiatan,nama_kegiatan,jenis_kegiatan,tanggal_mulai,tanggal_selesai',
                'replacement.spkLama.alokasiPetugas',
            ])
            ->whereYear('tanggal_kontrak', $year)
            ->where('status', '!=', 'dibatalkan');

        if ($petugasId !== null) {
            $query->where('petugas_id', $petugasId);
        }

        $contracts = $query->get();

        $replacementIds = $contracts->pluck('replacement_id')->filter()->unique()->values();

        $documentRows = Schema::hasTable('sensus_ekonomi_replacement_documents')
            ? DB::table('sensus_ekonomi_replacement_documents')
                ->whereIn('replacement_id', $replacementIds)
                ->get()
                ->groupBy('replacement_id')
            : collect();

        $bappRows = Schema::hasTable('bapp_se_termin')
            && Schema::hasColumn('bapp_se_termin', 'replacement_id')
            && Schema::hasColumn('bapp_se_termin', 'document_type')
            ? BappSeTermin::query()
                ->whereIn('replacement_id', $replacementIds)
                ->where('document_type', 'replacement_pkpp')
                ->where('tahun', $year)
                ->whereNotNull('signed_file_path')
                ->get()
                ->groupBy('replacement_id')
            : collect();

        return $contracts->map(function (SensusEkonomiPkppContract $contract) use ($documentRows, $bappRows): ?array {
            $replacement = $contract->replacement;
            $periode = $replacement?->periodeAlokasi;
            $kegiatan = $periode?->kegiatan;
            $petugas = $contract->petugas;

            if (! $replacement || ! $periode || ! $kegiatan || ! $petugas) {
                return null;
            }

            $oldAlokasi = $replacement->spkLama?->alokasiPetugas;
            $peran = (string) ($oldAlokasi?->peran ?? '');
            $obRate = $this->obRateForRole($peran);
            $monthFractions = $this->monthFractions((string) $contract->skema_kode);
            $monthlyHonor = [];

            foreach ($monthFractions as $month => $fraction) {
                $monthlyHonor[(int) $month] = round($obRate * $fraction, 2);
            }

            $documents = $documentRows->get($replacement->id, collect());
            $bastDocument = $documents
                ->where('document_type', 'bast')
                ->sortByDesc('updated_at')
                ->first();

            $replacementBapps = $bappRows->get($replacement->id, collect());
            $bappTermin1 = $replacementBapps
                ->where('termin', 1)
                ->sortByDesc('updated_at')
                ->first();
            $bappTermin2 = $replacementBapps
                ->where('termin', 2)
                ->sortByDesc('updated_at')
                ->first();

            return [
                'contract_id' => (int) $contract->id,
                'replacement_id' => (int) $replacement->id,
                'petugas_id' => (int) $petugas->id,
                'petugas_nama' => (string) $petugas->nama,
                'petugas_nik' => $petugas->nik,
                'periode_id' => (int) $periode->id,
                'tahun' => (int) $periode->tahun,
                'bulan' => (int) $periode->bulan,
                'kegiatan_id' => (int) $kegiatan->id,
                'kegiatan_hashed_id' => $kegiatan->hashed_id,
                'kode_kegiatan' => (string) $kegiatan->kode_kegiatan,
                'nama_kegiatan' => (string) $kegiatan->nama_kegiatan,
                'jenis_kegiatan' => (string) $kegiatan->jenis_kegiatan,
                'target_sisa' => (float) ($replacement->target_sisa ?? 0),
                'peran' => $peran,
                'ob_rate' => $obRate,
                'honor_ob' => (float) $contract->honor_ob,
                'total_honor' => round(array_sum($monthlyHonor), 2),
                'monthly_honor' => $monthlyHonor,
                'tanggal_kontrak' => $contract->tanggal_kontrak?->format('Y-m-d'),
                'tanggal_mulai_lapangan' => $contract->tanggal_mulai_lapangan?->format('Y-m-d'),
                'termin_count' => (int) $contract->termin_count,
                'pk_available' => filled($contract->signed_file_path),
                'pk_file_path' => $contract->signed_file_path,
                'bast_available' => filled($bastDocument?->file_path),
                'bast_file_path' => $bastDocument?->file_path,
                'bast_nomor' => $bastDocument?->nomor_dokumen,
                'bapp_termin_i_available' => filled($bappTermin1?->signed_file_path),
                'bapp_termin_i_file_path' => $bappTermin1?->signed_file_path,
                'bapp_termin_ii_available' => filled($bappTermin2?->signed_file_path),
                'bapp_termin_ii_file_path' => $bappTermin2?->signed_file_path,
            ];
        })->filter()->values();
    }

    private function obRateForRole(string $role): float
    {
        return in_array(strtolower($role), ['pml', 'pemeriksa', 'pemeriksa_lapangan'], true)
            ? 5_036_000.0
            : 4_780_000.0;
    }

    /**
     * @return array<int,float>
     */
    private function monthFractions(string $scheme): array
    {
        return match ($scheme) {
            'scheme_1' => [7 => 1.0, 8 => 1.0],
            'scheme_2' => [7 => 0.75, 8 => 1.0],
            'scheme_3' => [7 => 0.5, 8 => 1.0],
            'scheme_4' => [7 => 0.25, 8 => 1.0],
            'scheme_5' => [8 => 1.0],
            default => [],
        };
    }
}
