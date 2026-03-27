<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\RateHonor;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateHonorManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $operator;

    protected User $approver;

    protected Kegiatan $kegiatan;

    protected Satuan $satuan;

    protected Satuan $obSatuan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create or get roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );
        $operatorRole = Role::firstOrCreate(
            ['name' => 'operator'],
            ['display_name' => 'Operator']
        );
        $approverRole = Role::firstOrCreate(
            ['name' => 'approver'],
            ['display_name' => 'Approver']
        );

        // Create users with roles
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole->id);

        $this->operator = User::factory()->create();
        $this->operator->roles()->attach($operatorRole->id);

        $this->approver = User::factory()->create();
        $this->approver->roles()->attach($approverRole->id);

        // Create satuan
        $this->obSatuan = Satuan::firstOrCreate(
            ['kode' => 'O-B'],
            ['nama' => 'Orang/Bulan', 'status' => 'aktif']
        );

        $this->satuan = Satuan::factory()->create([
            'kode' => 'RT',
            'nama' => 'Rumah Tangga',
            'status' => 'aktif',
        ]);

        // Create kegiatan with valid status for rate honor management
        $this->kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
        ]);
    }

    public function test_operator_can_access_rate_honor_management_page(): void
    {
        $response = $this->actingAs($this->operator)
            ->get("/kegiatan/{$this->kegiatan->hashed_id}/rate-honor/manage");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Kegiatan/ManageRateHonor')
            ->has('kegiatan')
            ->has('satuans')
        );
    }

    public function test_operator_can_set_rate_honor_with_satuan_and_rate(): void
    {
        $response = $this->actingAs($this->operator)
            ->post("/kegiatan/{$this->kegiatan->hashed_id}/rate-honor/bulk", [
                'satuan_id' => $this->satuan->id,
                'rate_honors' => [
                    [
                        'status_kepegawaian' => 'non_organik',
                        'jenis_penugasan' => 'pcl_ppl',
                        'rate' => 250000,
                        'satuan_id' => $this->satuan->id,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify rate honor created for kegiatan
        $rateHonor = RateHonor::where('kegiatan_id', $this->kegiatan->id)
            ->where('status_kepegawaian', 'non_organik')
            ->where('jenis_penugasan', 'pcl_ppl')
            ->first();

        $this->assertNotNull($rateHonor);
        $this->assertEquals($this->obSatuan->id, $rateHonor->satuan_id);
        $this->assertEquals(250000, $rateHonor->rate);
        $this->assertEquals($this->kegiatan->tahun_anggaran, $rateHonor->tahun_berlaku);
    }

    public function test_rate_honor_is_updated_when_same_posisi_and_satuan_already_exists(): void
    {
        // Create initial rate honor
        $this->actingAs($this->operator)
            ->post("/kegiatan/{$this->kegiatan->hashed_id}/rate-honor/bulk", [
                'satuan_id' => $this->satuan->id,
                'rate_honors' => [
                    [
                        'status_kepegawaian' => 'non_organik',
                        'jenis_penugasan' => 'pcl_ppl',
                        'rate' => 200000,
                        'satuan_id' => $this->satuan->id,
                    ],
                ],
            ]);

        // Update with new rate
        $this->actingAs($this->operator)
            ->post("/kegiatan/{$this->kegiatan->hashed_id}/rate-honor/bulk", [
                'satuan_id' => $this->satuan->id,
                'rate_honors' => [
                    [
                        'status_kepegawaian' => 'non_organik',
                        'jenis_penugasan' => 'pcl_ppl',
                        'rate' => 300000,
                        'satuan_id' => $this->satuan->id,
                    ],
                ],
            ]);

        $this->assertEquals(1, RateHonor::where('kegiatan_id', $this->kegiatan->id)->count());

        // Verify rate replaced with latest
        $rateHonor = RateHonor::where('kegiatan_id', $this->kegiatan->id)->first();
        $this->assertEquals(300000, $rateHonor->rate);
    }

    public function test_rate_honor_cannot_be_managed_for_draft_kegiatan(): void
    {
        $draftKegiatan = Kegiatan::factory()->create([
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->approver)
            ->post("/kegiatan/{$draftKegiatan->hashed_id}/rate-honor/bulk", [
                'satuan_id' => $this->satuan->id,
                'rate_honors' => [
                    [
                        'status_kepegawaian' => 'non_organik',
                        'jenis_penugasan' => 'pcl_ppl',
                        'rate' => 250000,
                        'satuan_id' => $this->satuan->id,
                    ],
                ],
            ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, RateHonor::where('kegiatan_id', $draftKegiatan->id)->count());
    }

    public function test_validation_requires_rate(): void
    {
        $response = $this->actingAs($this->operator)
            ->post("/kegiatan/{$this->kegiatan->hashed_id}/rate-honor/bulk", [
                'rate_honors' => [
                    [
                        'status_kepegawaian' => 'non_organik',
                        'jenis_penugasan' => 'pcl_ppl',
                    ],
                ],
            ]);

        $response->assertSessionHasErrors(['rate_honors.0.rate']);
    }

    public function test_validation_requires_rate_to_be_numeric_and_positive(): void
    {
        $response = $this->actingAs($this->operator)
            ->post("/kegiatan/{$this->kegiatan->hashed_id}/rate-honor/bulk", [
                'satuan_id' => $this->satuan->id,
                'rate_honors' => [
                    [
                        'status_kepegawaian' => 'non_organik',
                        'jenis_penugasan' => 'pcl_ppl',
                        'rate' => -1000,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors(['rate_honors.0.rate']);
    }

    public function test_fasih_only_skips_pengolahan_and_pengawas_pengolahan(): void
    {
        $kegiatanFasih = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'metode_pendataan_pencacahan' => 'CAPI',
            'has_listing_updating' => false,
            'metode_pendataan_listing' => null,
        ]);

        $response = $this->actingAs($this->operator)
            ->post("/kegiatan/{$kegiatanFasih->hashed_id}/rate-honor/bulk", [
                'rate_honors' => [
                    [
                        'status_kepegawaian' => 'non_organik',
                        'jenis_penugasan' => 'pcl_ppl',
                        'rate' => 250000,
                    ],
                    [
                        'status_kepegawaian' => 'non_organik',
                        'jenis_penugasan' => 'pengolahan',
                        'rate' => 300000,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rate_honor', [
            'kegiatan_id' => $kegiatanFasih->id,
            'jenis_penugasan' => 'pcl_ppl',
        ]);

        $this->assertDatabaseMissing('rate_honor', [
            'kegiatan_id' => $kegiatanFasih->id,
            'jenis_penugasan' => 'pengolahan',
        ]);
    }

    public function test_operator_can_set_rate_honor_koseka(): void
    {
        $response = $this->actingAs($this->operator)
            ->post("/kegiatan/{$this->kegiatan->hashed_id}/rate-honor/bulk", [
                'rate_honors' => [
                    [
                        'status_kepegawaian' => 'non_organik',
                        'jenis_penugasan' => 'koseka',
                        'rate' => 275000,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rate_honor', [
            'kegiatan_id' => $this->kegiatan->id,
            'status_kepegawaian' => 'non_organik',
            'jenis_penugasan' => 'koseka',
            'rate' => 275000,
            'satuan_id' => $this->obSatuan->id,
        ]);
    }
}
