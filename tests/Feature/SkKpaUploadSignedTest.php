<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Role;
use App\Models\SkKpa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SkKpaUploadSignedTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdminUser(): array
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return [$user, $role];
    }

    public function test_upload_signed_requires_file(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();

        $kegiatan = Kegiatan::factory()->create();

        $skKpa = SkKpa::create([
            'nomor_sk' => '001/SK/TEST',
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 1,
            'tahun' => (int) now()->format('Y'),
            'tanggal_sk' => now()->toDateString(),
            'nama_kpa' => 'Nama KPA',
            'perihal' => 'Perihal SK',
            'dasar_hukum' => json_encode([]),
            'file_path' => 'sk/test.pdf',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/sk-kpa/{$skKpa->hashed_id}/upload-signed", []);

        $response->assertSessionHasErrors(['signed_file']);
    }

    public function test_upload_signed_updates_sk_record(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();

        $kegiatan = Kegiatan::factory()->create();

        $skKpa = SkKpa::create([
            'nomor_sk' => '002/SK/TEST',
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 1,
            'tahun' => (int) now()->format('Y'),
            'tanggal_sk' => now()->toDateString(),
            'nama_kpa' => 'Nama KPA',
            'perihal' => 'Perihal SK',
            'dasar_hukum' => json_encode([]),
            'file_path' => 'sk/test.pdf',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $file = UploadedFile::fake()->create('signed-sk.pdf', 100, 'application/pdf');

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/sk-kpa/{$skKpa->hashed_id}/upload-signed", [
                'signed_file' => $file,
            ]);

        $response->assertRedirect(route('sk-kpa.show', ['skKpa' => $skKpa->hashed_id]));

        $skKpa->refresh();

        $this->assertTrue($skKpa->is_signed);
        $this->assertNotNull($skKpa->signed_file_path);
        $this->assertNotNull($skKpa->signed_at);
        $this->assertEquals($admin->id, $skKpa->signed_by);
    }
}
