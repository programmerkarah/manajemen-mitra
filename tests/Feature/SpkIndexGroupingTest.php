<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpkIndexGroupingTest extends TestCase
{
    use RefreshDatabase;

    public function test_spk_index_collapses_same_month_periods_into_one_row(): void
    {
        $approverRole = Role::firstOrCreate(
            ['name' => 'approver'],
            ['display_name' => 'Approver', 'description' => 'Role approver']
        );

        $user = User::factory()->create();
        $user->roles()->attach($approverRole->id);

        $this->actingAs($user)
            ->withSession(['active_role_id' => $approverRole->id, 'active_role_user_id' => $user->id]);

        $tahun = ActiveYearService::get();
        Carbon::setTestNow("{$tahun}-05-15 10:00:00");

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatanWithSpk = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $kegiatanWithoutSpk = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeWithSpk = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanWithSpk->id,
            'bulan' => '5',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeWithoutSpk = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanWithoutSpk->id,
            'bulan' => '05',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiWithSpk = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeWithSpk->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
            'total_honor_listing' => 0,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeWithoutSpk->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 2,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 200000,
            'total_honor_listing' => 0,
        ]);

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/2026/001',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiWithSpk->id,
            'alokasi_petugas_ids' => [$alokasiWithSpk->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 1,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'Perjanjian kerja awal',
            'nilai_kontrak' => 300000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $user->id,
        ]);

        $response = $this->get('/spk');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Spk/Index'));

        $page = $response->viewData('page');
        $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

        $mayRows = collect($periodeList)->filter(function (array $item) use ($tahun) {
            return (int) ($item['tahun'] ?? 0) === (int) $tahun
                && (int) ($item['bulan'] ?? 0) === 5;
        });

        $this->assertCount(1, $mayRows);
        $this->assertSame(1, (int) ($mayRows->first()['total_spk'] ?? 0));
        $this->assertSame(1, (int) ($mayRows->first()['total_petugas_non_organik'] ?? 0));
        $this->assertCount(2, $mayRows->first()['kegiatan_list'] ?? []);
    }
}
