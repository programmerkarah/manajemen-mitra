<?php

namespace Database\Seeders;

use App\Models\Satuan;
use Illuminate\Database\Seeder;

class SatuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $satuans = [
            [
                'kode' => 'RUTA',
                'nama' => 'Rumah Tangga',
                'deskripsi' => 'Satuan untuk menghitung jumlah rumah tangga',
                'status' => 'aktif',
            ],
            [
                'kode' => 'BS',
                'nama' => 'Blok Sensus',
                'deskripsi' => 'Satuan untuk menghitung jumlah blok sensus',
                'status' => 'aktif',
            ],
            [
                'kode' => 'SLS',
                'nama' => 'SLS',
                'deskripsi' => 'Satuan Lingkungan Setempat',
                'status' => 'aktif',
            ],
            [
                'kode' => 'EA',
                'nama' => 'EA',
                'deskripsi' => 'Enumeration Area',
                'status' => 'aktif',
            ],
            [
                'kode' => 'DOK',
                'nama' => 'Dokumen',
                'deskripsi' => 'Satuan untuk menghitung jumlah dokumen',
                'status' => 'aktif',
            ],
            [
                'kode' => 'O-B',
                'nama' => 'O-B',
                'deskripsi' => 'Orang/Bulan',
                'status' => 'aktif',
            ],
            [
                'kode' => 'SGMEN',
                'nama' => 'Segmen',
                'deskripsi' => 'Segmen untuk KSA',
                'status' => 'aktif',
            ],
        ];

        foreach ($satuans as $satuan) {
            Satuan::updateOrCreate(['kode' => $satuan['kode']], $satuan);
        }
    }
}
