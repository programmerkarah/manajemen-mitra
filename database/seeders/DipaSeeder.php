<?php

namespace Database\Seeders;

use App\Models\Dipa;
use Illuminate\Database\Seeder;

class DipaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Dipa::create([
            'nomor_dipa' => 'SP-DIPA-015.01.428001/2024',
            'tahun' => 2025,
            'tanggal_dipa' => '2024-02-12',
            'is_active' => true,
        ]);
    }
}
