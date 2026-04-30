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
        /**
         * @var array<int, array{jenis_kegiatan: string, status_kepegawaian: string, jenis_penugasan: string, honor_max: int}>
         */
        // $combinations = [
        //     // Survei - Non Organik (3 jenis - TANPA pengawas_pengolahan dan koseka)
        //     ['jenis_kegiatan' => 'survei', 'status_kepegawaian' => 'non_organik', 'jenis_penugasan' => 'pcl_ppl', 'honor_max' => sbml honor max survei non organik pcl_ppl],
        //     ['jenis_kegiatan' => 'survei', 'status_kepegawaian' => 'non_organik', 'jenis_penugasan' => 'pml', 'honor_max' => sbml honor max survei non organik pml],
        //     ['jenis_kegiatan' => 'survei', 'status_kepegawaian' => 'non_organik', 'jenis_penugasan' => 'pengolahan', 'honor_max' => sbml honor max survei non organik pengolahan],
        //     // Survei - Organik (4 jenis - TANPA koseka)
        //     ['jenis_kegiatan' => 'survei', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'pcl_ppl', 'honor_max' => sbml honor max survei organik pcl_ppl],
        //     ['jenis_kegiatan' => 'survei', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'pml', 'honor_max' => sbml honor max survei organik pml],
        //     ['jenis_kegiatan' => 'survei', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'pengolahan', 'honor_max' => sbml honor max survei organik pengolahan],
        //     ['jenis_kegiatan' => 'survei', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'pengawas_pengolahan', 'honor_max' => sbml honor max survei organik pengawas_pengolahan],
        //     // Sensus - Non Organik (4 jenis - TANPA koseka)
        //     ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'non_organik', 'jenis_penugasan' => 'pcl_ppl', 'honor_max' => sbml honor max sensus non organik pcl_ppl],
        //     ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'non_organik', 'jenis_penugasan' => 'pml', 'honor_max' => sbml honor max sensus non organik pml],
        //     ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'non_organik', 'jenis_penugasan' => 'pengolahan', 'honor_max' => sbml honor max sensus non organik pengolahan],
        //     ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'non_organik', 'jenis_penugasan' => 'pengawas_pengolahan', 'honor_max' => sbml honor max sensus non organik pengawas_pengolahan],
        //     // Sensus - Organik (4 jenis - TANPA koseka)
        //     ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'pcl_ppl', 'honor_max' => sbml honor max sensus organik pcl_ppl],
        //     ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'pml', 'honor_max' => sbml honor max sensus organik pml],
        //     ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'pengolahan', 'honor_max' => sbml honor max sensus organik pengolahan],
        //     ['jenis_kegiatan' => 'sensus', 'status_kepegawaian' => 'organik', 'jenis_penugasan' => 'pengawas_pengolahan', 'honor_max' => sbml honor max sensus organik pengawas_pengolahan],
        // ];

        /**
         * @var array<int, array{tahun: int, status: string}>
         */
        // $tahunList = [
        //     ['tahun' => 2025, 'status' => 'nonaktif'],
        //     ['tahun' => 2026, 'status' => 'aktif'],
        // ];

        // foreach ($tahunList as $entry) {
        //     foreach ($combinations as $combo) {
        //         Sbml::updateOrCreate(
        //             [
        //                 'tahun_anggaran' => $entry['tahun'],
        //                 'jenis_kegiatan' => $combo['jenis_kegiatan'],
        //                 'status_kepegawaian' => $combo['status_kepegawaian'],
        //                 'jenis_penugasan' => $combo['jenis_penugasan'],
        //             ],
        //             [
        //                 'honor_max' => $combo['honor_max'],
        //                 'keterangan' => "SBML Kegiatan Sensus Survei {$entry['tahun']}",
        //                 'status' => $entry['status'],
        //             ]
        //         );
        //     }
        // }
    }
}
