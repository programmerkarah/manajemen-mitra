<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PenandatanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Penandatangan::create([
            'nama' => 'Arieswaty, SST',
            'nip' => '197003291990032001',
            'jenis_penandatangan' => 'kepala',
            'jabatan' => 'Kepala Badan Pusat Statistik',
            'periode_mulai' => '2023-01-01',
            'periode_selesai' => '2026-12-31',
            'is_active' => true,
        ]);

        \App\Models\Penandatangan::create([
            'nama' => 'Rahmat Zikri, S.Tr.Stat.',
            'nip' => '199710242019121001',
            'jenis_penandatangan' => 'ppk',
            'jabatan' => 'Pejabat Pembuat Komitmen Badan Pusat Statistik Kota Sawahlunto',
            'periode_mulai' => '2023-01-01',
            'periode_selesai' => '2026-12-31',
            'is_active' => true,
        ]);
    }
}
