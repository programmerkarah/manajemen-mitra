<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpkAugustAddendumRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_august_period_with_direvisi_and_perubahan_flags_addendum_on_spk_index(): void
    {
        $this->withoutMiddleware();

        $tahun = (int) ActiveYearService::get();

        $admin = User::factory()->create();
        $this->actingAs($admin);

        $petugas = Petugas::factory()->create([
            'nama' => 'Fitri Yati',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Survei Harga Produsen Sektor Jasa (HPJ)',
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeDirevisi = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '8',
            'tahun' => $tahun,
            'status' => 'direvisi',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiDirevisi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDirevisi->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 144000,
            'total_honor_listing' => 0,
        ]);

        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '08',
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 240000,
            'total_honor_listing' => 0,
        ]);

        Spk::query()->create([
            'nomor_spk' => 'SPK/HPJ/AUGUST/FITRI',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiDirevisi->id,
            'alokasi_petugas_ids' => [$alokasiDirevisi->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 123,
            'tanggal_spk' => "{$tahun}-08-10",
            'tanggal_mulai_kerja' => "{$tahun}-08-01",
            'tanggal_selesai_kerja' => "{$tahun}-08-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Agustus',
            'nilai_kontrak' => 144000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $admin->id,
        ]);

        $response = $this->get('/spk');
        $response->assertStatus(200);

        $page = $response->viewData('page');
        $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

        $agustus = collect($periodeList)->first(function (array $item) use ($tahun): bool {
            return (int) ($item['tahun'] ?? 0) === $tahun
                && (int) ($item['bulan'] ?? 0) === 8;
        });

        $this->assertNotNull($agustus, 'Agustus 2026 harus muncul di daftar periode /spk');
        $this->assertTrue(
            (bool) ($agustus['has_incomplete_addendum'] ?? false),
            'Agustus harus ditandai perlu addendum karena ada perubahan dari direvisi ke perubahan'
        );
        $this->assertFalse(
            (bool) ($agustus['has_addendum_changes'] ?? false),
            'Kasus ini belum addendum, jadi bukan re-generate addendum'
        );

        $generateResponse = $this->get('/spk/periode/'.$periodePerubahan->hashed_id.'/generate');
        $generateResponse->assertStatus(200);
        $generateResponse->assertInertia(fn ($page) => $page->component('Spk/Generate'));

        $petugasList = collect($generateResponse->inertiaProps('petugas_list'));

        $fitri = $petugasList->first(function (array $item): bool {
            return ($item['petugas']['nama'] ?? '') === 'Fitri Yati';
        });

        $this->assertNotNull($fitri, 'Fitri Yati harus muncul pada daftar generate karena ada alokasi baru di SPK bulan tersebut.');
        $this->assertNotEmpty(
            $fitri['perubahan'] ?? [],
            'Kolom perubahan untuk regenerate harus berisi ringkasan selisih alokasi.'
        );
    }
}
