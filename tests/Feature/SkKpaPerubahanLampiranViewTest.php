<?php

namespace Tests\Feature;

use Illuminate\Support\Collection;
use Tests\TestCase;

class SkKpaPerubahanLampiranViewTest extends TestCase
{
    private function makeAlokasiWithTwoRoles(string $nama): object
    {
        return (object) [
            'nama' => $nama,
            'nip' => '-',
            'golongan' => 'Non PNS',
            'jabatan' => 'Mitra Statistik',
            'roles' => [
                (object) [
                    'peran' => 'Petugas Pengolahan - Listing',
                    'biaya_satuan' => 'Rp. 35.000,- / Dokumen',
                ],
                (object) [
                    'peran' => 'Petugas Pengolahan',
                    'biaya_satuan' => 'Rp. 26.000,- / DOK',
                ],
            ],
        ];
    }

    /**
     * @param  Collection<int, object>  $alokasiList
     */
    private function renderPerubahanLampiran(Collection $alokasiList): string
    {
        $kegiatan = (object) [
            'nama_kegiatan' => 'Survei Harga Produsen',
            'tahun_anggaran' => 2026,
        ];

        $periode = (object) [
            'tahun' => 2026,
        ];

        return view('sk-petugas-perubahan', [
            'kegiatan' => $kegiatan,
            'periode' => $periode,
            'nomorSk' => '123',
            'tanggalSk' => '13-04-2026',
            'tahunSk' => '2026',
            'kategoriKeputusan' => 'KEPUTUSAN',
            'kepalaBps' => 'Arieswaty',
            'dipa' => '000.00.0.00.000000/2026',
            'tanggalDipa' => '01-01-2026',
            'dasarHukum' => [
                (object) [
                    'nama_lengkap' => 'Undang-Undang Contoh',
                    'tentang' => 'Ketentuan Contoh',
                    'lembaran' => null,
                ],
            ],
            'alokasiList' => $alokasiList,
            'revisionNumber' => 1,
            'revisionSkNumber' => null,
            'revisionSkYear' => null,
            'firstSkNumber' => '100',
            'firstSkYear' => '2026',
            'deletedPetugas' => [],
            'addedPetugas' => [],
            'allCurrentPetugas' => $alokasiList->map(fn ($alokasi) => $alokasi->nama)->all(),
        ])->render();
    }

    public function test_multi_role_rows_do_not_create_a_second_petugas_table(): void
    {
        $html = $this->renderPerubahanLampiran(collect([
            $this->makeAlokasiWithTwoRoles('Fissy Erlieta Hadi'),
            $this->makeAlokasiWithTwoRoles('Mochamad Agistiana Tanjung'),
            $this->makeAlokasiWithTwoRoles('Petugas Tiga'),
            $this->makeAlokasiWithTwoRoles('Petugas Empat'),
            $this->makeAlokasiWithTwoRoles('Petugas Lima'),
        ]));

        $this->assertSame(1, substr_count($html, '<table class="petugas">'));
        $this->assertSame(10, substr_count($html, '<tr class=" petugas-'));
    }
}
