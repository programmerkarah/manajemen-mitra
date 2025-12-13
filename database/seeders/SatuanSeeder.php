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
                'kode' => 'RT',
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
                'kode' => 'OB',
                'nama' => 'OB',
                'deskripsi' => 'Observasi',
                'status' => 'aktif',
            ],
        ];

        foreach ($satuans as $satuan) {
            Satuan::create($satuan);
        }
    }
}
