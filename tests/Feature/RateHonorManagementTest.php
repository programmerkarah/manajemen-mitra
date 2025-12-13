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
        $this->satuan = Satuan::factory()->create([
            'kode' => 'OB',
            'nama' => 'Orang Bulan',
            'status' => 'aktif',
        ]);

        // Create kegiatan
        $this->kegiatan = Kegiatan::factory()->create();
    }

    public function test_operator_can_access_rate_honor_management_page(): void
    {
        $response = $this->actingAs($this->operator)
            ->get('/kegiatan/rate-honor');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Kegiatan/RateHonorManagement')
            ->has('kegiatan')
            ->has('satuan')
        );
    }

    public function test_operator_can_set_rate_honor_with_satuan_and_rate(): void
    {
        $response = $this->actingAs($this->operator)
            ->post("/kegiatan/{$this->kegiatan->hashed_id}/rate-honor", [
                'satuan_id' => $this->satuan->id,
                'rate' => 250000,
                'notes' => 'Setting rate honor untuk kegiatan',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify kegiatan updated
        $this->kegiatan->refresh();
        $this->assertNotNull($this->kegiatan->rate_honor_id);
        $this->assertEquals('pending', $this->kegiatan->rate_honor_status);
        $this->assertEquals('Setting rate honor untuk kegiatan', $this->kegiatan->rate_honor_notes);

        // Verify rate honor created
        $rateHonor = RateHonor::find($this->kegiatan->rate_honor_id);
        $this->assertNotNull($rateHonor);
        $this->assertEquals($this->satuan->id, $rateHonor->satuan_id);
        $this->assertEquals(250000, $rateHonor->rate);
        $this->assertEquals($this->kegiatan->nama_kegiatan, $rateHonor->posisi);
        $this->assertEquals($this->kegiatan->tahun_anggaran, $rateHonor->tahun_berlaku);
    }

    public function test_rate_honor_is_updated_when_same_posisi_and_satuan_already_exists(): void
    {
        // Create initial rate honor
        $this->actingAs($this->operator)
            ->post("/kegiatan/{$this->kegiatan->hashed_id}/rate-honor", [
                'satuan_id' => $this->satuan->id,
                'rate' => 200000,
                'notes' => 'Initial rate',
            ]);

        $this->kegiatan->refresh();
        $initialRateHonorId = $this->kegiatan->rate_honor_id;

        // Update with new rate
        $this->actingAs($this->operator)
            ->post("/kegiatan/{$this->kegiatan->hashed_id}/rate-honor", [
                'satuan_id' => $this->satuan->id,
                'rate' => 300000,
                'notes' => 'Updated rate',
            ]);

        $this->kegiatan->refresh();

        // Should use same rate honor ID (updated, not created new)
        $this->assertEquals($initialRateHonorId, $this->kegiatan->rate_honor_id);

        // Verify rate updated
        $rateHonor = RateHonor::find($initialRateHonorId);
        $this->assertEquals(300000, $rateHonor->rate);
    }

    public function test_approver_can_approve_rate_honor(): void
    {
        // Set rate honor first
        $this->actingAs($this->operator)
            ->post("/kegiatan/{$this->kegiatan->hashed_id}/rate-honor", [
                'satuan_id' => $this->satuan->id,
                'rate' => 250000,
            ]);

        $this->kegiatan->refresh();

        // Approve
        $response = $this->actingAs($this->approver)
            ->post("/kegiatan/{$this->kegiatan->hashed_id}/rate-honor/approve", [
                'notes' => 'Approved',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->kegiatan->refresh();
        $this->assertEquals('approved', $this->kegiatan->rate_honor_status);
        $this->assertEquals($this->approver->id, $this->kegiatan->rate_honor_approved_by);
        $this->assertNotNull($this->kegiatan->rate_honor_approved_at);
    }

    public function test_validation_requires_satuan_id_and_rate(): void
    {
        $response = $this->actingAs($this->operator)
            ->post("/kegiatan/{$this->kegiatan->hashed_id}/rate-honor", [
                'notes' => 'Missing required fields',
            ]);

        $response->assertSessionHasErrors(['satuan_id', 'rate']);
    }

    public function test_validation_requires_rate_to_be_numeric_and_positive(): void
    {
        $response = $this->actingAs($this->operator)
            ->post("/kegiatan/{$this->kegiatan->hashed_id}/rate-honor", [
                'satuan_id' => $this->satuan->id,
                'rate' => -1000,
            ]);

        $response->assertSessionHasErrors(['rate']);
    }
}
