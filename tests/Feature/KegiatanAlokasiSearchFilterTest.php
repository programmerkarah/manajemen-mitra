<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\User;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KegiatanAlokasiSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Admin', 'description' => 'Role admin']
        );

        $this->user = User::factory()->create();
        $this->user->roles()->attach($this->adminRole->id);
    }

    private function actingAsAdmin(): static
    {
        return $this->actingAs($this->user)
            ->withSession(['active_role_id' => $this->adminRole->id]);
    }

    public function test_kegiatan_search_filters_by_name_or_description_not_code(): void
    {
        $activeYear = (int) ActiveYearService::get();

        $matched = Kegiatan::factory()->create([
            'kode_kegiatan' => 'KG-UMKM-001',
            'nama_kegiatan' => 'Survei Harga Konsumen',
            'deskripsi' => 'Pendataan UMKM Kota Sawahlunto',
            'tahun_anggaran' => $activeYear,
        ]);

        Kegiatan::factory()->create([
            'kode_kegiatan' => 'KG-OTHER-002',
            'nama_kegiatan' => 'Survei Perikanan',
            'deskripsi' => 'Pendataan nelayan',
            'tahun_anggaran' => $activeYear,
        ]);

        $responseByDescription = $this->actingAsAdmin()->get(route('kegiatan.index', [
            'search' => 'UMKM',
        ]));

        $responseByDescription->assertStatus(200);

        $byDescription = collect(decryptData($responseByDescription->inertiaProps('kegiatans.encrypted')));
        $this->assertCount(1, $byDescription);
        $this->assertSame($matched->id, $byDescription->first()['id']);

        $responseByCode = $this->actingAsAdmin()->get(route('kegiatan.index', [
            'search' => 'KG-UMKM-001',
        ]));

        $responseByCode->assertStatus(200);

        $byCode = collect(decryptData($responseByCode->inertiaProps('kegiatans.encrypted')));
        $this->assertCount(0, $byCode);
    }

    public function test_alokasi_search_filters_by_name_or_description_not_code(): void
    {
        $activeYear = (int) ActiveYearService::get();

        $matched = Kegiatan::factory()->create([
            'kode_kegiatan' => 'AL-UMKM-001',
            'nama_kegiatan' => 'Survei Harga Konsumen',
            'deskripsi' => 'Pendataan UMKM Kota Sawahlunto',
            'tahun_anggaran' => $activeYear,
            'status' => 'divalidasi',
        ]);

        $other = Kegiatan::factory()->create([
            'kode_kegiatan' => 'AL-OTHER-002',
            'nama_kegiatan' => 'Survei Perikanan',
            'deskripsi' => 'Pendataan nelayan',
            'tahun_anggaran' => $activeYear,
            'status' => 'divalidasi',
        ]);

        PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $matched->id,
            'bulan' => '04',
            'tahun' => $activeYear,
            'status' => 'draft',
            'jenis_kegiatan' => 'survei',
        ]);

        PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $other->id,
            'bulan' => '04',
            'tahun' => $activeYear,
            'status' => 'draft',
            'jenis_kegiatan' => 'survei',
        ]);

        $responseByDescription = $this->actingAsAdmin()->get(route('alokasi.index', [
            'search' => 'UMKM',
        ]));

        $responseByDescription->assertStatus(200);

        $byDescription = collect(decryptData($responseByDescription->inertiaProps('alokasi.encrypted')));
        $this->assertCount(1, $byDescription);
        $this->assertSame($matched->id, data_get($byDescription->first(), 'kegiatan.id'));

        $responseByCode = $this->actingAsAdmin()->get(route('alokasi.index', [
            'search' => 'AL-UMKM-001',
        ]));

        $responseByCode->assertStatus(200);

        $byCode = collect(decryptData($responseByCode->inertiaProps('alokasi.encrypted')));
        $this->assertCount(0, $byCode);
    }

    public function test_alokasi_index_uses_partial_honor_for_estimasi_and_sisa_pagu(): void
    {
        $activeYear = (int) ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Survei Harga Konsumen',
            'deskripsi' => 'Kegiatan dengan honor parsial',
            'tahun_anggaran' => $activeYear,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'pagu_pencacahan' => 1000000,
            'pagu_listing' => 0,
            'has_listing_updating' => false,
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '04',
            'tahun' => $activeYear,
            'status' => 'draft',
            'jenis_kegiatan' => 'survei',
        ]);

        $petugas = Petugas::factory()->create([
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 1000000,
            'is_partial_payment' => true,
            'partial_jumlah_satuan' => 1,
            'estimasi_honor_partial' => 600000,
            'jumlah_satuan_listing' => 1,
            'total_honor_listing' => 0,
            'is_partial_payment_listing' => false,
            'partial_jumlah_satuan_listing' => null,
            'estimasi_honor_partial_listing' => null,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $response = $this->actingAsAdmin()->get(route('alokasi.index'));

        $response->assertStatus(200);

        $alokasi = collect(decryptData($response->inertiaProps('alokasi.encrypted')));
        $periodData = $alokasi->firstWhere('periode_id', $periode->id);

        $this->assertNotNull($periodData);
        $this->assertEquals(600000, data_get($periodData, 'estimasi_honor'));
        $this->assertEquals(600000, data_get($periodData, 'total_honor'));
        $this->assertEquals(400000, data_get($periodData, 'sisa_pagu'));
        $this->assertEquals(600000, data_get($periodData, 'pagu_terpakai'));
        $this->assertEquals(600000, data_get($periodData, 'total_terpakai_untuk_budget_info'));
    }
}
