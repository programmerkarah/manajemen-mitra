<?php

namespace Database\Seeders;

use App\Models\Sbml;
use Illuminate\Database\Seeder;

class SbmlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tahunList = [2024, 2025];

        $combinations = [
            // Survei - Non Organik (4 jenis - TANPA koseka)
            ['jenis_kegiatan' => 'survei', 'status_kepegawaian' => 'non_organik', 'jenis_penugasan' => 'pcl_ppl', 'honor_max' => 5000000],
            ['jenis_kegiatan' => 'survei', 'status_kepegawaian' => 'non_organik', 'jenis_penugasan' => 'pml', 'honor_max' => 7000000],
            ['jenis_kegiatan' => 'survei', 'status_kepegawaian' => 'non_organik', 'jenis_penugasan' => 'pengolahan', 'honor_max' => 4000000],
            ['jenis_kegiatan' => 'survei', 'status_kepegawaian' => 'non_organik', 'jenis_penugasan' => 'pengawas_pengolahan', 'honor_max' => 4000000],
            // Survei - Organik (4 jenis - TANPA koseka)
            ['jenis_kegiatan' => 'survei', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'pcl_ppl', 'honor_max' => 3000000],
            ['jenis_kegiatan' => 'survei', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'pml', 'honor_max' => 4000000],
            ['jenis_kegiatan' => 'survei', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'pengolahan', 'honor_max' => 2500000],
            ['jenis_kegiatan' => 'survei', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'pengawas_pengolahan', 'honor_max' => 3500000],
            // Sensus - Non Organik (5 jenis - dengan pengawas dan koseka)
            ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'non_organik', 'jenis_penugasan' => 'pcl_ppl', 'honor_max' => 5000000],
            ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'non_organik', 'jenis_penugasan' => 'pml', 'honor_max' => 7000000],
            ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'non_organik', 'jenis_penugasan' => 'pengolahan', 'honor_max' => 4000000],
            ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'non_organik', 'jenis_penugasan' => 'pengawas_pengolahan', 'honor_max' => 5500000],
            ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'non_organik', 'jenis_penugasan' => 'koseka', 'honor_max' => 4000000],
            // Sensus - Organik (5 jenis - dengan pengawas dan koseka)
            ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'pcl_ppl', 'honor_max' => 3000000],
            ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'pml', 'honor_max' => 4000000],
            ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'pengolahan', 'honor_max' => 2500000],
            ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'pengawas_pengolahan', 'honor_max' => 3500000],
            ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'koseka', 'honor_max' => 2500000],
        ];

        foreach ($tahunList as $tahun) {
            foreach ($combinations as $combo) {
                Sbml::create([
                    'tahun_anggaran' => $tahun,
                    'jenis_kegiatan' => $combo['jenis_kegiatan'],
                    'status_kepegawaian' => $combo['status_kepegawaian'],
                    'jenis_penugasan' => $combo['jenis_penugasan'],
                    'honor_max' => $combo['honor_max'],
                    'keterangan' => "SBML {$tahun} - ".ucfirst($combo['jenis_kegiatan']).' - '.ucfirst(str_replace('_', ' ', $combo['status_kepegawaian'])).' - '.strtoupper(str_replace('_', '/', $combo['jenis_penugasan'])),
                    'status' => 'aktif',
                ]);
            }
        }
    }
}
