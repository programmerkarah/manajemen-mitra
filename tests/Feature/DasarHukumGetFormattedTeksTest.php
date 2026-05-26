<?php

namespace Tests\Feature;

use App\Models\DasarHukum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DasarHukumGetFormattedTeksTest extends TestCase
{
    use RefreshDatabase;

    private function makeUu(string $nomor, int $tahun, string $tentang): DasarHukum
    {
        return DasarHukum::create([
            'kategori' => 'undang_undang',
            'nomor' => $nomor,
            'tentang' => $tentang,
            'tahun' => $tahun,
            'jenis' => 'pertama',
            'status' => 'aktif',
        ]);
    }

    public function test_original_regulation_returns_single_teks(): void
    {
        $dh = $this->makeUu('6', 2018, 'Kepabeanan');

        $this->assertSame(
            'Undang-Undang Nomor 6 Tahun 2018 tentang Kepabeanan',
            $dh->getFormattedTeks(),
        );
    }

    public function test_single_amendment_uses_diubah_dengan(): void
    {
        $induk = $this->makeUu('6', 2018, 'Kepabeanan');

        $perubahan = DasarHukum::create([
            'kategori' => 'undang_undang',
            'nomor' => '7',
            'tentang' => 'Perubahan atas Undang-Undang Nomor 6 Tahun 2018',
            'tahun' => 2020,
            'jenis' => 'perubahan',
            'induk_id' => $induk->id,
            'status' => 'aktif',
        ]);

        $perubahan->load('induk');

        $this->assertStringContainsString(
            'sebagaimana telah diubah dengan',
            $perubahan->getFormattedTeks(),
        );
        $this->assertStringNotContainsString(
            'beberapa kali',
            $perubahan->getFormattedTeks(),
        );
    }

    public function test_multiple_amendments_uses_beberapa_kali_diubah_terakhir(): void
    {
        $induk = $this->makeUu('6', 2018, 'Kepabeanan');

        DasarHukum::create([
            'kategori' => 'undang_undang',
            'nomor' => '7',
            'tentang' => 'Perubahan Pertama',
            'tahun' => 2019,
            'jenis' => 'perubahan',
            'induk_id' => $induk->id,
            'status' => 'nonaktif',
        ]);

        $perubahan2 = DasarHukum::create([
            'kategori' => 'undang_undang',
            'nomor' => '8',
            'tentang' => 'Perubahan Kedua',
            'tahun' => 2021,
            'jenis' => 'perubahan',
            'induk_id' => $induk->id,
            'status' => 'aktif',
        ]);

        $perubahan2->load('induk');

        $this->assertStringContainsString(
            'sebagaimana telah beberapa kali diubah terakhir dengan',
            $perubahan2->getFormattedTeks(),
        );
    }
}
