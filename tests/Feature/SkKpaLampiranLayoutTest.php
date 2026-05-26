<?php

namespace Tests\Feature;

use Illuminate\Support\Collection;
use Tests\TestCase;

class SkKpaLampiranLayoutTest extends TestCase
{
    private function makeAlokasi(string $nama): object
    {
        return (object) [
            'nama' => $nama,
            'nip' => '-',
            'golongan' => 'Non PNS',
            'jabatan' => 'Mitra Statistik',
            'roles' => [
                (object) [
                    'peran' => 'Petugas Pencacahan',
                    'biaya_satuan' => 'Rp. 66.000,- / DOK',
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
            'nama_kegiatan' => 'Updating Direktori Perusahaan Pertambangan dan Energi',
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
                    'teks_lengkap' => 'Undang-Undang Contoh tentang Ketentuan Contoh',
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

    public function test_lampiran_does_not_repeat_table_header_for_short_list(): void
    {
        $html = $this->renderPerubahanLampiran(collect([
            $this->makeAlokasi('Cici Liani Indrias Putri'),
            $this->makeAlokasi('Miranda Melliana'),
        ]));

        $this->assertSame(1, substr_count($html, '<table class="petugas">'));
    }

    public function test_lampiran_keeps_single_table_for_long_list(): void
    {
        $alokasiList = collect();

        for ($i = 1; $i <= 10; $i++) {
            $alokasiList->push($this->makeAlokasi('Petugas '.$i));
        }

        $html = $this->renderPerubahanLampiran($alokasiList);

        $this->assertSame(1, substr_count($html, '<table class="petugas">'));
    }
}
