<?php

namespace App\Services\Petugas;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetugasDeletionGuard
{
    /**
     * Direct references that must preserve a petugas ID for historical integrity.
     *
     * @var array<string, string>
     */
    private const REFERENCES = [
        'alokasi_petugas' => 'alokasi kegiatan',
        'spk' => 'Perjanjian Kerja',
        'bast_petugas' => 'BAST',
        'review_petugas' => 'penilaian mitra',
        'pengajuan_pulsa' => 'pengajuan pulsa',
        'bapp_se_termin' => 'BAPP Sensus Ekonomi',
        'sensus_ekonomi_pkpp_contracts' => 'kontrak PKPP Sensus Ekonomi',
        'sensus_ekonomi_petugas_replacements' => 'riwayat penggantian petugas Sensus Ekonomi',
    ];

    /**
     * @return array<string, int>
     */
    public function referencesFor(int $petugasId): array
    {
        $references = [];

        foreach (self::REFERENCES as $table => $label) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'petugas_id')) {
                continue;
            }

            $count = DB::table($table)
                ->where('petugas_id', $petugasId)
                ->count();

            if ($count > 0) {
                $references[$label] = $count;
            }
        }

        return $references;
    }

    public function canDelete(int $petugasId): bool
    {
        return $this->referencesFor($petugasId) === [];
    }

    public function reason(int $petugasId): ?string
    {
        $references = $this->referencesFor($petugasId);

        if ($references === []) {
            return null;
        }

        $labels = array_keys($references);

        return 'Petugas tidak dapat dihapus karena sudah terhubung dengan '
            .implode(', ', $labels)
            .'. Nonaktifkan petugas bila tidak lagi digunakan agar ID dan riwayat dokumen tetap utuh.';
    }
}
