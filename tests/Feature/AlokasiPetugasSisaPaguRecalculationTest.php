<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlokasiPetugasSisaPaguRecalculationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $ketuaTim;

    private Kegiatan $kegiatan;

    private Petugas $petugas1;

    private Petugas $petugas2;

    private RateHonor $rateHonor;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $ketuaTimRole = Role::factory()->create(['name' => 'ketua_tim']);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->ketuaTim = User::factory()->create();
        $this->ketuaTim->roles()->attach($ketuaTimRole);

        // Create satuan
        $satuan = Satuan::factory()->create([
            'nama' => 'Dokumen',
            'kode' => 'DOK',
        ]);

        // Create kegiatan with budget
        $this->kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $this->ketuaTim->id,
            'pagu_pencacahan' => 10000000, // 10 million
            'pagu_listing' => 0,
            'has_listing_updating' => false,
            'tahun_anggaran' => now()->year,
        ]);

        // Create rate honor
        $this->rateHonor = RateHonor::factory()->create([
            'kegiatan_id' => $this->kegiatan->id,
            'satuan_id' => $satuan->id,
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'rate' => 100000, // 100k per dokumen
            'rate_listing' => null,
        ]);

        // Create petugas
        $this->petugas1 = Petugas::factory()->create([
            'nama' => 'Petugas 1',
            'jenis_petugas' => 'mitra',
        ]);

        $this->petugas2 = Petugas::factory()->create([
            'nama' => 'Petugas 2',
            'jenis_petugas' => 'mitra',
        ]);
    }

    /** @test */
    public function it_recalculates_sisa_pagu_for_subsequent_periods_when_updating_earlier_period(): void
    {
        // Scenario: Jan, Feb, Apr, May, Jun exist but only Jan & Feb are sent
        // User modifies Apr (adds more budget)
        // System should recalculate sisa_pagu for May & Jun

        // Create January periode (sent) - 2M spent, 8M remaining
        $periodeJan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $this->kegiatan->id,
            'bulan' => 1,
            'tahun' => now()->year,
            'status' => 'dikirim',
            'sisa_pagu' => 8000000, // 10M - 2M
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeJan->id,
            'petugas_id' => $this->petugas1->id,
            'jumlah_satuan' => 20,
            'total_honor' => 2000000, // 20 * 100k
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        // Create February periode (sent) - 1.5M spent, 6.5M remaining
        $periodeFeb = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $this->kegiatan->id,
            'bulan' => 2,
            'tahun' => now()->year,
            'status' => 'dikirim',
            'sisa_pagu' => 6500000, // 8M - 1.5M
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeFeb->id,
            'petugas_id' => $this->petugas1->id,
            'jumlah_satuan' => 15,
            'total_honor' => 1500000, // 15 * 100k
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        // Create April periode (draft) - initially 1M spent, 5.5M remaining
        $periodeApr = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $this->kegiatan->id,
            'bulan' => 4,
            'tahun' => now()->year,
            'status' => 'draft',
            'sisa_pagu' => 5500000, // 6.5M - 1M
        ]);

        $alokasiApr = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeApr->id,
            'petugas_id' => $this->petugas1->id,
            'jumlah_satuan' => 10,
            'total_honor' => 1000000, // 10 * 100k
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        // Create May periode (draft) - 1.5M spent, 4M remaining
        $periodeMay = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $this->kegiatan->id,
            'bulan' => 5,
            'tahun' => now()->year,
            'status' => 'draft',
            'sisa_pagu' => 4000000, // 5.5M - 1.5M
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeMay->id,
            'petugas_id' => $this->petugas2->id,
            'jumlah_satuan' => 15,
            'total_honor' => 1500000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        // Create June periode (draft) - 2M spent, 2M remaining
        $periodeJun = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $this->kegiatan->id,
            'bulan' => 6,
            'tahun' => now()->year,
            'status' => 'draft',
            'sisa_pagu' => 2000000, // 4M - 2M
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeJun->id,
            'petugas_id' => $this->petugas2->id,
            'jumlah_satuan' => 20,
            'total_honor' => 2000000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        // User modifies April: change from 10 dokumen to 20 dokumen (double the budget)
        $this->actingAs($this->admin)
            ->put(route('alokasi.updatePeriode', [
                'kegiatan' => $this->kegiatan->kode_kegiatan,
                'tahun' => now()->year,
                'bulan' => '04',
            ]), [
                'alokasi' => [
                    [
                        'id' => $alokasiApr->id,
                        'petugas_id' => $this->petugas1->id,
                        'peran' => 'PCL',
                        'jumlah_satuan' => 20, // Changed from 10 to 20
                        'catatan' => null,
                    ],
                ],
            ])
            ->assertRedirect(route('alokasi.index'))
            ->assertSessionHas('success');

        // Refresh models
        $periodeApr->refresh();
        $periodeMay->refresh();
        $periodeJun->refresh();

        // April should now have 2M spent, 4.5M remaining (6.5M - 2M)
        $this->assertEquals(2000000, $periodeApr->alokasiPetugas->sum('total_honor'));
        $this->assertEquals(4500000, $periodeApr->sisa_pagu);

        // May should be recalculated: 4.5M - 1.5M = 3M remaining
        $this->assertEquals(1500000, $periodeMay->alokasiPetugas->sum('total_honor'));
        $this->assertEquals(3000000, $periodeMay->sisa_pagu);

        // June should be recalculated: 3M - 2M = 1M remaining
        $this->assertEquals(2000000, $periodeJun->alokasiPetugas->sum('total_honor'));
        $this->assertEquals(1000000, $periodeJun->sisa_pagu);
    }

    /** @test */
    public function it_recalculates_sisa_pagu_when_reducing_budget_in_middle_period(): void
    {
        // Test the opposite: reduce budget in middle periode

        // Jan - 2M spent, 8M remaining
        $periodeJan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $this->kegiatan->id,
            'bulan' => 1,
            'tahun' => now()->year,
            'status' => 'dikirim',
            'sisa_pagu' => 8000000,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeJan->id,
            'petugas_id' => $this->petugas1->id,
            'jumlah_satuan' => 20,
            'total_honor' => 2000000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        // Mar - 3M spent, 5M remaining
        $periodeMar = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $this->kegiatan->id,
            'bulan' => 3,
            'tahun' => now()->year,
            'status' => 'draft',
            'sisa_pagu' => 5000000,
        ]);

        $alokasiMar = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeMar->id,
            'petugas_id' => $this->petugas1->id,
            'jumlah_satuan' => 30,
            'total_honor' => 3000000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        // May - 2M spent, 3M remaining
        $periodeMay = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $this->kegiatan->id,
            'bulan' => 5,
            'tahun' => now()->year,
            'status' => 'draft',
            'sisa_pagu' => 3000000,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeMay->id,
            'petugas_id' => $this->petugas2->id,
            'jumlah_satuan' => 20,
            'total_honor' => 2000000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        // Reduce March from 30 to 10 dokumen
        $this->actingAs($this->admin)
            ->put(route('alokasi.updatePeriode', [
                'kegiatan' => $this->kegiatan->kode_kegiatan,
                'tahun' => now()->year,
                'bulan' => '03',
            ]), [
                'alokasi' => [
                    [
                        'id' => $alokasiMar->id,
                        'petugas_id' => $this->petugas1->id,
                        'peran' => 'PCL',
                        'jumlah_satuan' => 10, // Reduced from 30 to 10
                        'catatan' => null,
                    ],
                ],
            ])
            ->assertRedirect(route('alokasi.index'));

        // Refresh models
        $periodeMar->refresh();
        $periodeMay->refresh();

        // March now has 1M spent, 7M remaining (8M - 1M)
        $this->assertEquals(1000000, $periodeMar->alokasiPetugas->sum('total_honor'));
        $this->assertEquals(7000000, $periodeMar->sisa_pagu);

        // May should be recalculated: 7M - 2M = 5M remaining (increased from 3M)
        $this->assertEquals(2000000, $periodeMay->alokasiPetugas->sum('total_honor'));
        $this->assertEquals(5000000, $periodeMay->sisa_pagu);
    }
}
