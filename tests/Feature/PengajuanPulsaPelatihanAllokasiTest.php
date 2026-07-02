<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\User;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests that verify the pelatihan pulsa petugas allocation logic:
 *
 * 1. Normal case: petugas for pelatihan are loaded from bulan_pelatihan+1 allocations.
 * 2. Special case: if kegiatan starts in bulan_pelatihan, use bulan_pelatihan allocations.
 * 3. Pendataan petugas always come from the current selected bulan allocations.
 */
class PengajuanPulsaPelatihanAllokasiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(string $roleName): array
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst(str_replace('_', ' ', $roleName)), 'description' => '']
        );
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return [$user, $role];
    }

    /**
     * Helper to create a PeriodeAlokasi and link a non-organik Petugas to it.
     */
    private function createPeriodeWithPetugas(int $kegiatanId, string $bulan, int $tahun, int $petugasId): PeriodeAlokasi
    {
        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanId,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'disetujui',
            'jenis_kegiatan' => 'survei',
        ]);

        DB::table('alokasi_petugas')->insert([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugasId,
            'jumlah_satuan' => 10,
            'non_response' => 0,
            'jumlah_satuan_listing' => 0,
            'non_response_listing' => 0,
            'total_honor' => 500000,
            'total_honor_listing' => 0,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'catatan' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $periode;
    }

    /**
     * Normal case: pelatihan petugas should come from bulan_pelatihan+1 allocation.
     *
     * Kegiatan: bulan_pelatihan=6, tanggal_mulai=July (month 7)
     * PetugasA: allocated in July (month 7) → should appear in petugasPerKegiatanPelatihan
     * PetugasB: allocated in June (month 6) → should NOT appear in petugasPerKegiatanPelatihan
     */
    public function test_pelatihan_petugas_comes_from_next_month_allocation(): void
    {
        [$user, $role] = $this->makeUserWithRole('admin');
        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'metode_pelatihan' => 'daring',
            'bulan_pelatihan' => 6,
            'metode_pendataan_pencacahan' => 'PAPI',
            'tahun_anggaran' => $tahun,
            'tanggal_mulai' => "{$tahun}-07-01",
            'tanggal_selesai' => "{$tahun}-09-30",
        ]);

        $petugasA = Petugas::factory()->create(['jenis_petugas' => 'non-organik']);
        $petugasB = Petugas::factory()->create(['jenis_petugas' => 'non-organik']);

        // PetugasA in July (bulan_pelatihan + 1) — should be in pelatihan petugas list
        $this->createPeriodeWithPetugas($kegiatan->id, '07', $tahun, $petugasA->id);

        // PetugasB in June (bulan_pelatihan) — should NOT be in pelatihan petugas list
        $this->createPeriodeWithPetugas($kegiatan->id, '06', $tahun, $petugasB->id);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/pengajuan-pulsa/create?bulan=06');

        $response->assertStatus(200);
        $response->assertInertia(function ($page) use ($kegiatan, $petugasA, $petugasB) {
            $page->component('PengajuanPulsa/Create')
                ->has('petugasPerKegiatanPelatihan')
                ->where("petugasPerKegiatanPelatihan.{$kegiatan->id}", function ($list) use ($petugasA, $petugasB) {
                    $ids = collect($list)->pluck('id')->toArray();

                    return in_array($petugasA->id, $ids) && ! in_array($petugasB->id, $ids);
                });
        });
    }

    /**
     * Special case: kegiatan starts in bulan_pelatihan → use bulan_pelatihan allocations.
     *
     * Kegiatan: bulan_pelatihan=6, tanggal_mulai=June (same as bulan_pelatihan)
     * PetugasA: allocated in June → should appear in petugasPerKegiatanPelatihan
     */
    public function test_pelatihan_petugas_uses_same_month_when_kegiatan_starts_in_bulan_pelatihan(): void
    {
        [$user, $role] = $this->makeUserWithRole('admin');
        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'metode_pelatihan' => 'luring',
            'bulan_pelatihan' => 6,
            'metode_pendataan_pencacahan' => 'PAPI',
            'tahun_anggaran' => $tahun,
            'tanggal_mulai' => "{$tahun}-06-01", // starts in June = bulan_pelatihan
            'tanggal_selesai' => "{$tahun}-08-31",
        ]);

        $petugas = Petugas::factory()->create(['jenis_petugas' => 'non-organik']);

        // Allocated in June (same as bulan_pelatihan, because kegiatan also starts in June)
        $this->createPeriodeWithPetugas($kegiatan->id, '06', $tahun, $petugas->id);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/pengajuan-pulsa/create?bulan=06');

        $response->assertStatus(200);
        $response->assertInertia(function ($page) use ($kegiatan, $petugas) {
            $page->component('PengajuanPulsa/Create')
                ->has('petugasPerKegiatanPelatihan')
                ->where("petugasPerKegiatanPelatihan.{$kegiatan->id}", function ($list) use ($petugas) {
                    $ids = collect($list)->pluck('id')->toArray();

                    return in_array($petugas->id, $ids);
                });
        });
    }

    /**
     * Pendataan petugas should always come from current bulan allocation, not bulan+1.
     * Uses ketua_tim role because admin users see all-month allocations by design.
     *
     * Kegiatan: bulan_pelatihan=6, CAPI
     * PetugasA: allocated in June (current bulan) → should be in petugasPerKegiatan (pendataan)
     * PetugasB: allocated in July (next bulan) → should NOT be in petugasPerKegiatan (pendataan for bulan=06)
     */
    public function test_pendataan_petugas_comes_from_current_month_allocation(): void
    {
        [$user, $role] = $this->makeUserWithRole('ketua_tim');
        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $user->id,
            'metode_pelatihan' => 'daring',
            'bulan_pelatihan' => 6,
            'metode_pendataan_pencacahan' => 'CAPI',
            'tahun_anggaran' => $tahun,
            'tanggal_mulai' => "{$tahun}-07-01",
            'tanggal_selesai' => "{$tahun}-09-30",
        ]);

        $petugasA = Petugas::factory()->create(['jenis_petugas' => 'non-organik']);
        $petugasB = Petugas::factory()->create(['jenis_petugas' => 'non-organik']);

        // PetugasA in June (current bulan) → should be in pendataan list
        $this->createPeriodeWithPetugas($kegiatan->id, '06', $tahun, $petugasA->id);

        // PetugasB in July (next bulan) → in pelatihan list, but NOT in pendataan list for bulan=06
        $this->createPeriodeWithPetugas($kegiatan->id, '07', $tahun, $petugasB->id);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/pengajuan-pulsa/create?bulan=06');

        $response->assertStatus(200);
        $response->assertInertia(function ($page) use ($kegiatan, $petugasA, $petugasB) {
            $page->component('PengajuanPulsa/Create')
                ->has('petugasPerKegiatan')
                ->where("petugasPerKegiatan.{$kegiatan->id}", function ($list) use ($petugasA, $petugasB) {
                    $ids = collect($list)->pluck('id')->toArray();

                    // PetugasA (June alloc) should be in pendataan, PetugasB (July alloc) should not
                    return in_array($petugasA->id, $ids) && ! in_array($petugasB->id, $ids);
                });
        });
    }

    /**
     * Kegiatan with no allocation in the computed pelatihan bulan should NOT
     * appear as a key in petugasPerKegiatanPelatihan.
     */
    public function test_pelatihan_kegiatan_without_allocation_in_next_month_not_shown(): void
    {
        [$user, $role] = $this->makeUserWithRole('admin');
        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'metode_pelatihan' => 'daring',
            'bulan_pelatihan' => 6,
            'metode_pendataan_pencacahan' => 'PAPI',
            'tahun_anggaran' => $tahun,
            'tanggal_mulai' => "{$tahun}-07-01",
            'tanggal_selesai' => "{$tahun}-09-30",
        ]);

        // No allocation in July — kegiatan should not appear in pelatihan map at all

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/pengajuan-pulsa/create?bulan=06');

        $response->assertStatus(200);
        $response->assertInertia(function ($page) use ($kegiatan) {
            $page->component('PengajuanPulsa/Create')
                ->missing("petugasPerKegiatanPelatihan.{$kegiatan->id}");
        });
    }

    /**
     * December bulan_pelatihan should wrap to January of the next year for allocation lookup.
     */
    public function test_december_pelatihan_uses_january_next_year_allocation(): void
    {
        [$user, $role] = $this->makeUserWithRole('admin');
        $tahun = ActiveYearService::get();
        $nextTahun = $tahun + 1;

        $kegiatan = Kegiatan::factory()->create([
            'metode_pelatihan' => 'hybrid',
            'bulan_pelatihan' => 12,
            'metode_pendataan_pencacahan' => 'PAPI',
            'tahun_anggaran' => $tahun,
            'tanggal_mulai' => "{$nextTahun}-01-01", // starts in January next year
            'tanggal_selesai' => "{$nextTahun}-03-31",
        ]);

        $petugas = Petugas::factory()->create(['jenis_petugas' => 'non-organik']);

        // Allocated in January next year (December + 1 wraps to January next year)
        $this->createPeriodeWithPetugas($kegiatan->id, '01', $nextTahun, $petugas->id);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/pengajuan-pulsa/create?bulan=12');

        $response->assertStatus(200);
        $response->assertInertia(function ($page) use ($kegiatan, $petugas) {
            $page->component('PengajuanPulsa/Create')
                ->has('petugasPerKegiatanPelatihan')
                ->where("petugasPerKegiatanPelatihan.{$kegiatan->id}", function ($list) use ($petugas) {
                    $ids = collect($list)->pluck('id')->toArray();

                    return in_array($petugas->id, $ids);
                });
        });
    }

    public function test_sensus_ekonomi_appears_from_june_to_august_when_allocations_exist(): void
    {
        [$user, $role] = $this->makeUserWithRole('admin');
        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'kode_kegiatan' => 'SE-'.substr((string) $tahun, -2).'-001',
            'nama_kegiatan' => 'Sensus Ekonomi '.$tahun,
            'jenis_kegiatan' => 'sensus',
            'metode_pendataan_pencacahan' => 'PAPI',
            'metode_pelatihan' => 'tidak_ada_pelatihan',
            'bulan_pelatihan' => null,
            'tahun_anggaran' => $tahun,
            'tanggal_mulai' => "{$tahun}-06-01",
            'tanggal_selesai' => "{$tahun}-08-31",
        ]);

        $petugas = Petugas::factory()->create(['jenis_petugas' => 'non-organik']);

        $this->createPeriodeWithPetugas($kegiatan->id, '06', $tahun, $petugas->id);

        foreach (['06', '07', '08'] as $bulan) {
            $response = $this->actingAs($user)
                ->withSession(['active_role_id' => $role->id])
                ->get('/pengajuan-pulsa/create?bulan='.$bulan);

            $response->assertStatus(200);
            $response->assertInertia(fn ($page) => $page
                ->component('PengajuanPulsa/Create')
                ->where("petugasPerKegiatan.{$kegiatan->id}", function ($list) use ($petugas) {
                    return in_array($petugas->id, collect($list)->pluck('id')->toArray());
                })
            );
        }

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/pengajuan-pulsa/create?bulan=05');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('PengajuanPulsa/Create')
            ->missing('eligibleKegiatan.0.id')
        );
    }

    public function test_sensus_ekonomi_july_and_august_petugas_are_found_with_non_padded_period_months(): void
    {
        [$user, $role] = $this->makeUserWithRole('admin');
        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'kode_kegiatan' => 'SE-'.substr((string) $tahun, -2).'-002',
            'nama_kegiatan' => 'Sensus Ekonomi '.$tahun,
            'jenis_kegiatan' => 'sensus',
            'metode_pendataan_pencacahan' => 'PAPI',
            'metode_pelatihan' => 'tidak_ada_pelatihan',
            'bulan_pelatihan' => null,
            'tahun_anggaran' => $tahun,
            'tanggal_mulai' => "{$tahun}-06-01",
            'tanggal_selesai' => "{$tahun}-08-31",
        ]);

        $petugasJuli = Petugas::factory()->create(['jenis_petugas' => 'non-organik']);
        $petugasAgustus = Petugas::factory()->create(['jenis_petugas' => 'non-organik']);

        $this->createPeriodeWithPetugas($kegiatan->id, '7', $tahun, $petugasJuli->id);
        $this->createPeriodeWithPetugas($kegiatan->id, '8', $tahun, $petugasAgustus->id);

        $responseJuli = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/pengajuan-pulsa/create?bulan=07');

        $responseJuli->assertStatus(200);
        $responseJuli->assertInertia(fn ($page) => $page
            ->component('PengajuanPulsa/Create')
            ->where("petugasPerKegiatan.{$kegiatan->id}.0.id", $petugasJuli->id)
        );

        $responseAgustus = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/pengajuan-pulsa/create?bulan=08');

        $responseAgustus->assertStatus(200);
        $responseAgustus->assertInertia(fn ($page) => $page
            ->component('PengajuanPulsa/Create')
            ->where("petugasPerKegiatan.{$kegiatan->id}.0.id", $petugasAgustus->id)
        );
    }
}
