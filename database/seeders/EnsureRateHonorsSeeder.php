<?php

namespace Database\Seeders;

use App\Models\Kegiatan;
use App\Models\RateHonor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EnsureRateHonorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $year = Carbon::now()->year;

        $jenisPenugasan = [
            'pcl_ppl',
            'pml',
            'pengolahan',
            'pengawas_pengolahan',
        ];

        $statusKepegawaian = [
            'organik',
            'non_organik',
        ];

        Kegiatan::chunkById(100, function ($kegiatans) use ($jenisPenugasan, $statusKepegawaian, $year) {
            foreach ($kegiatans as $kegiatan) {
                foreach ($statusKepegawaian as $status) {
                    foreach ($jenisPenugasan as $jenis) {
                        $exists = RateHonor::where('kegiatan_id', $kegiatan->id)
                            ->where('jenis_penugasan', $jenis)
                            ->where('status_kepegawaian', $status)
                            ->exists();

                        if (! $exists) {
                            $roleLabels = [
                                'pcl_ppl' => 'PCL/PPL',
                                'pml' => 'PML',
                                'pengolahan' => 'Pengolahan',
                                'pengawas_pengolahan' => 'Pengawas Pengolahan',
                            ];

                            $statusLabels = [
                                'organik' => 'Organik (PNS/PPPK)',
                                'non_organik' => 'Non-Organik',
                            ];

                            $posisi = sprintf('%s - %s - %s', $kegiatan->nama_kegiatan ?? $kegiatan->kode_kegiatan, $statusLabels[$status] ?? $status, $roleLabels[$jenis] ?? $jenis);

                            RateHonor::create([
                                'kegiatan_id' => $kegiatan->id,
                                'posisi' => $posisi,
                                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                                'jenis_penugasan' => $jenis,
                                'status_kepegawaian' => $status,
                                'deskripsi' => null,
                                'satuan_id' => 4,
                                'rate' => 0,
                                'rate_listing' => 0,
                                'satuan_listing_id' => null,
                                'tahun_berlaku' => $year,
                                'status' => 1,
                            ]);
                        }
                    }
                }
            }
        });
    }
}
