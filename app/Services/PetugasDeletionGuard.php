<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetugasDeletionGuard
{
    /**
     * @return array<int, string>
     */
    public function blockingTables(int $petugasId): array
    {
        $checks = [
            'alokasi_petugas' => ['petugas_id'],
            'spk' => ['petugas_id'],
            'bast_petugas' => ['petugas_id'],
            'review_petugas' => ['petugas_id'],
            'pengajuan_pulsa' => ['petugas_id'],
            'bapp_se_termin' => ['petugas_id'],
            'sensus_ekonomi_pkpp_contracts' => ['petugas_id'],
            'sensus_ekonomi_petugas_replacements' => [
                'petugas_berhenti_id',
                'petugas_pengganti_id',
                'pml_cover_petugas_id',
            ],
        ];

        $blocked = [];

        foreach ($checks as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                if (DB::table($table)->where($column, $petugasId)->exists()) {
                    $blocked[] = $table;
                    break;
                }
            }
        }

        return array_values(array_unique($blocked));
    }

    public function canDelete(int $petugasId): bool
    {
        return $this->blockingTables($petugasId) === [];
    }
}
