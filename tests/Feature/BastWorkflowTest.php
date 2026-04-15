<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Bast;
use App\Models\BastNumberAllocation;
use App\Models\Kegiatan;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\Spk;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BastWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_operator_generates_main_bast_and_registers_lampiran_records(): void
    {
        $context = $this->createBastGenerationContext();

        $response = $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.generate-batch'), [
                'spk_ids' => [$context['spk']->id],
            ]);

        $response->assertRedirect(route('bast.index'));
        $response->assertSessionHas('success');

        $bast = Bast::query()
            ->with('bastKegiatan')
            ->where('spk_id', $context['spk']->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertNotNull($bast->file_path);
        $this->assertNull($bast->compiled_file_path);
        $this->assertNull($bast->main_signed_file_path);
        $this->assertNull($bast->signed_file_path);
        $this->assertCount(3, $bast->bastKegiatan); // 3 alokasi: ownCompleted, ownFuture, other
        $this->assertTrue($bast->bastKegiatan->every(fn ($item) => $item->file_path === null));
    }

    public function test_ketua_tim_can_only_generate_completed_lampiran_for_their_own_kegiatan(): void
    {
        $context = $this->createBastGenerationContext();
        $bast = $this->generateMainBast($context);

        $ownCompletedLampiran = $bast->bastKegiatan
            ->firstWhere('kegiatan_id', $context['kegiatanOwnCompleted']->id);
        $ownFutureLampiran = $bast->bastKegiatan
            ->firstWhere('kegiatan_id', $context['kegiatanOwnFuture']->id);
        $otherLampiran = $bast->bastKegiatan
            ->firstWhere('kegiatan_id', $context['kegiatanOther']->id);

        $this->assertNotNull($ownCompletedLampiran);
        $this->assertNotNull($ownFutureLampiran);
        $this->assertNotNull($otherLampiran);

        $generateOwnCompleted = $this
            ->actingAsWithRole($context['ketuaTimOwn'], 'ketua_tim')
            ->post(route('bast.lampiran.generate', [
                'bast' => $bast->hashed_id,
                'bastKegiatan' => $ownCompletedLampiran->id,
            ]));

        $generateOwnCompleted->assertRedirect();
        $generateOwnCompleted->assertSessionHas('success');

        $this->assertNotNull($ownCompletedLampiran->fresh()->file_path);

        $generateOwnFuture = $this
            ->actingAsWithRole($context['ketuaTimOwn'], 'ketua_tim')
            ->post(route('bast.lampiran.generate', [
                'bast' => $bast->hashed_id,
                'bastKegiatan' => $ownFutureLampiran->id,
            ]));

        $generateOwnFuture->assertRedirect();
        $generateOwnFuture->assertSessionHas('error');
        $this->assertNull($ownFutureLampiran->fresh()->file_path);

        $generateOther = $this
            ->actingAsWithRole($context['ketuaTimOwn'], 'ketua_tim')
            ->post(route('bast.lampiran.generate', [
                'bast' => $bast->hashed_id,
                'bastKegiatan' => $otherLampiran->id,
            ]));

        $generateOther->assertForbidden();
        $this->assertNull($otherLampiran->fresh()->file_path);
    }

    public function test_signed_final_file_is_compiled_only_after_main_and_all_lampiran_are_uploaded(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20'));

        $context = $this->createBastGenerationContext(includeFutureOwnKegiatan: false);
        $bast = $this->generateMainBast($context)->fresh('bastKegiatan');

        foreach ($bast->bastKegiatan as $lampiran) {
            $this
                ->actingAsWithRole($context['operator'], 'operator')
                ->post(route('bast.lampiran.generate', [
                    'bast' => $bast->hashed_id,
                    'bastKegiatan' => $lampiran->id,
                ]))
                ->assertRedirect();
        }

        $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.upload-signed', $bast->hashed_id), [
                'file' => $this->makePdfUpload('bast-main-signed.pdf'),
            ])
            ->assertRedirect();

        $bast->refresh();
        $this->assertNotNull($bast->main_signed_file_path);
        $this->assertNull($bast->signed_file_path);

        $firstLampiran = $bast->bastKegiatan()->orderBy('id')->firstOrFail();
        $secondLampiran = $bast->bastKegiatan()->orderBy('id')->skip(1)->firstOrFail();

        $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.lampiran.upload-signed', [
                'bast' => $bast->hashed_id,
                'bastKegiatan' => $firstLampiran->id,
            ]), [
                'file' => $this->makePdfUpload('lampiran-1-signed.pdf'),
            ])
            ->assertRedirect();

        $this->assertNull($bast->fresh()->signed_file_path);

        $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.lampiran.upload-signed', [
                'bast' => $bast->hashed_id,
                'bastKegiatan' => $secondLampiran->id,
            ]), [
                'file' => $this->makePdfUpload('lampiran-2-signed.pdf'),
            ])
            ->assertRedirect();

        $bast->refresh();

        $this->assertNotNull($bast->signed_file_path);
        $this->assertFileExists(public_path($bast->signed_file_path));
    }

    public function test_preview_petugas_without_bast_keeps_existing_bast_list_visible(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20'));

        $context = $this->createBastGenerationContext(includeFutureOwnKegiatan: false);
        $existingBast = $this->generateMainBast($context);

        $pendingPetugas = Petugas::factory()->create([
            'nama' => 'Petugas Tanpa BAST',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $pendingKegiatan = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Kegiatan Preview Tanpa BAST',
            'tahun_anggaran' => 2026,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'ketua_tim_user_id' => $context['ketuaTimOwn']->id,
        ]);

        $pendingPeriode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $pendingKegiatan->id,
            'bulan' => '04',
            'tahun' => 2026,
            'jenis_kegiatan' => 'survei',
            'status' => 'dikirim',
            'tanggal_selesai' => '2026-04-15',
            'tanggal_selesai_listing' => '2026-04-10',
        ]);

        $pendingAlokasi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $pendingPeriode->id,
            'petugas_id' => $pendingPetugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 400000,
            'total_honor_listing' => 0,
        ]);

        Spk::query()->create([
            'nomor_spk' => 'SPK/BAST/002',
            'petugas_id' => $pendingPetugas->id,
            'alokasi_petugas_id' => $pendingAlokasi->id,
            'addendum_number' => 0,
            'nomor_urut_base' => 2,
            'tanggal_spk' => '2026-04-05',
            'tanggal_mulai_kerja' => '2026-04-05',
            'tanggal_selesai_kerja' => '2026-04-25',
            'uraian_pekerjaan' => 'SPK preview tanpa BAST',
            'nilai_kontrak' => 400000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $context['operator']->id,
        ]);

        $response = $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->get(route('bast.list', [
                'bulan' => 4,
                'tahun' => 2026,
                'petugas_id' => $pendingPetugas->id,
            ]));

        $response->assertOk();

        $page = $response->viewData('page');
        $props = $page['props'];

        $this->assertSame('-', $props['bast']['nomor_bast']);
        $this->assertTrue(collect($props['bast_list'])->contains(function (array $item) use ($existingBast, $context) {
            return $item['hashed_id'] === $existingBast->hashed_id
                && $item['petugas_nama'] === $context['petugas']->nama;
        }));
        $this->assertTrue(collect($props['eligible_without_bast'])->contains(function (array $item) use ($pendingPetugas) {
            return (int) $item['petugas_id'] === $pendingPetugas->id
                && $item['petugas_nama'] === $pendingPetugas->nama;
        }));
    }

    public function test_preview_mode_download_lampiran_persists_temporary_generated_file_without_creating_bast(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20'));

        $context = $this->createBastGenerationContext(includeFutureOwnKegiatan: false);

        $response = $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.generate-download-lampiran-preview'), [
                'spk_id' => $context['spk']->id,
                'kegiatan_id' => $context['kegiatanOwnCompleted']->id,
            ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $this->assertFalse(Bast::query()->where('spk_id', $context['spk']->id)->exists());

        $listResponse = $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->get(route('bast.list', [
                'bulan' => 4,
                'tahun' => 2026,
                'petugas_id' => $context['petugas']->id,
            ]));

        $listResponse->assertOk();

        $page = $listResponse->viewData('page');
        $lampiranItem = collect($page['props']['lampiran'])
            ->firstWhere('kegiatan_id', $context['kegiatanOwnCompleted']->id);

        $this->assertNotNull($lampiranItem);
        $this->assertSame('generated', $lampiranItem['status']);
        $this->assertNotNull($lampiranItem['file_path']);
        $this->assertTrue($lampiranItem['can_upload_signed']);
        $this->assertNotNull($lampiranItem['generated_at']);
        $this->assertFileExists(public_path($lampiranItem['file_path']));
    }

    public function test_generate_download_lampiran_uses_existing_generated_file_when_signed_file_not_available(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20'));

        $context = $this->createBastGenerationContext(includeFutureOwnKegiatan: false);
        $bast = $this->generateMainBast($context)->fresh('bastKegiatan');
        $lampiran = $bast->bastKegiatan()->orderBy('id')->firstOrFail();

        $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.lampiran.generate', [
                'bast' => $bast->hashed_id,
                'bastKegiatan' => $lampiran->id,
            ]))
            ->assertRedirect();

        $response = $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->get(route('bast.lampiran.generate-download', [
                'bast' => $bast->hashed_id,
                'bastKegiatan' => $lampiran->id,
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'LAMPIRAN_',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_generate_download_lampiran_prefers_signed_file_when_available(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20'));

        $context = $this->createBastGenerationContext(includeFutureOwnKegiatan: false);
        $bast = $this->generateMainBast($context)->fresh('bastKegiatan');
        $lampiran = $bast->bastKegiatan()->orderBy('id')->firstOrFail();

        $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.lampiran.generate', [
                'bast' => $bast->hashed_id,
                'bastKegiatan' => $lampiran->id,
            ]))
            ->assertRedirect();

        $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.lampiran.upload-signed', [
                'bast' => $bast->hashed_id,
                'bastKegiatan' => $lampiran->id,
            ]), [
                'file' => $this->makePdfUpload('lampiran-signed-for-download.pdf'),
            ])
            ->assertRedirect();

        $response = $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->get(route('bast.lampiran.generate-download', [
                'bast' => $bast->hashed_id,
                'bastKegiatan' => $lampiran->id,
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'LAMPIRAN_SIGNED_',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_preview_lampiran_signed_upload_via_preview_endpoint_redirects_to_detail_page(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20'));

        $context = $this->createBastGenerationContext(includeFutureOwnKegiatan: false);

        $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.generate-download-lampiran-preview'), [
                'spk_id' => $context['spk']->id,
                'kegiatan_id' => $context['kegiatanOwnCompleted']->id,
            ])
            ->assertOk();

        $redirectUrl = route('bast.list', [
            'bulan' => 4,
            'tahun' => 2026,
            'petugas_id' => $context['petugas']->id,
        ], false);

        $response = $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->withHeader('referer', '/bast/preview-lampiran')
            ->post(route('bast.preview-lampiran'), [
                'spk_id' => $context['spk']->id,
                'kegiatan_id' => $context['kegiatanOwnCompleted']->id,
                'periode_alokasi_id' => $context['spk']->alokasiPetugas->periode_alokasi_id,
                'kode_kegiatan' => $context['kegiatanOwnCompleted']->kode_kegiatan,
                'redirect_url' => $redirectUrl,
                'file' => $this->makePdfUpload('preview-lampiran-signed.pdf'),
            ]);

        $response->assertRedirect($redirectUrl);
        $response->assertSessionHas('success');

        $previewRecord = \App\Models\BastKegiatan::query()
            ->whereNull('bast_id')
            ->where('spk_id', $context['spk']->id)
            ->where('kegiatan_id', $context['kegiatanOwnCompleted']->id)
            ->where('periode_alokasi_id', $context['spk']->alokasiPetugas->periode_alokasi_id)
            ->first();

        $this->assertNotNull($previewRecord);
        $this->assertNotNull($previewRecord->signed_file_path);
    }

    public function test_preview_mode_preview_lampiran_uses_signed_file_when_available(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20'));

        $context = $this->createBastGenerationContext(includeFutureOwnKegiatan: false);
        $periodeAlokasiId = (int) $context['spk']->alokasiPetugas->periode_alokasi_id;

        $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.generate-download-lampiran-preview'), [
                'spk_id' => $context['spk']->id,
                'kegiatan_id' => $context['kegiatanOwnCompleted']->id,
                'periode_alokasi_id' => $periodeAlokasiId,
            ])
            ->assertOk();

        $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.preview-lampiran'), [
                'spk_id' => $context['spk']->id,
                'kegiatan_id' => $context['kegiatanOwnCompleted']->id,
                'periode_alokasi_id' => $periodeAlokasiId,
                'kode_kegiatan' => $context['kegiatanOwnCompleted']->kode_kegiatan,
                'file' => $this->makePdfUpload('preview-lampiran-signed-priority.pdf'),
            ])
            ->assertRedirect();

        $response = $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.preview-lampiran'), [
                'spk_id' => $context['spk']->id,
                'kegiatan_id' => $context['kegiatanOwnCompleted']->id,
                'periode_alokasi_id' => $periodeAlokasiId,
            ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'preview_Lampiran_Signed_',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_ketua_tim_can_download_signed_lampiran_in_preview_mode(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20'));

        $context = $this->createBastGenerationContext(includeFutureOwnKegiatan: false);
        $periodeAlokasiId = (int) $context['spk']->alokasiPetugas->periode_alokasi_id;

        $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.generate-download-lampiran-preview'), [
                'spk_id' => $context['spk']->id,
                'kegiatan_id' => $context['kegiatanOwnCompleted']->id,
                'periode_alokasi_id' => $periodeAlokasiId,
            ])
            ->assertOk();

        $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.preview-lampiran'), [
                'spk_id' => $context['spk']->id,
                'kegiatan_id' => $context['kegiatanOwnCompleted']->id,
                'periode_alokasi_id' => $periodeAlokasiId,
                'kode_kegiatan' => $context['kegiatanOwnCompleted']->kode_kegiatan,
                'file' => $this->makePdfUpload('preview-lampiran-signed-ketua-tim.pdf'),
            ])
            ->assertRedirect();

        $response = $this
            ->actingAsWithRole($context['ketuaTimOwn'], 'ketua_tim')
            ->post(route('bast.download-lampiran-preview'), [
                'spk_id' => $context['spk']->id,
                'kegiatan_id' => $context['kegiatanOwnCompleted']->id,
                'periode_alokasi_id' => $periodeAlokasiId,
            ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'LAMPIRAN_SIGNED_',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_bast_lampiran_signed_upload_redirects_to_bast_show_page_instead_of_post_endpoint(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20'));

        $context = $this->createBastGenerationContext(includeFutureOwnKegiatan: false);
        $bast = $this->generateMainBast($context)->fresh('bastKegiatan');

        $lampiran = $bast->bastKegiatan()->orderBy('id')->firstOrFail();

        $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.lampiran.generate', [
                'bast' => $bast->hashed_id,
                'bastKegiatan' => $lampiran->id,
            ]))
            ->assertRedirect();

        $redirectUrl = route('bast.show', $bast->hashed_id, false);

        $response = $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->withHeader('referer', route('bast.lampiran.upload-signed', [
                'bast' => $bast->hashed_id,
                'bastKegiatan' => $lampiran->id,
            ], false))
            ->post(route('bast.lampiran.upload-signed', [
                'bast' => $bast->hashed_id,
                'bastKegiatan' => $lampiran->id,
            ]), [
                'redirect_url' => $redirectUrl,
                'file' => $this->makePdfUpload('stored-lampiran-signed.pdf'),
            ]);

        $response->assertRedirect($redirectUrl);
        $response->assertSessionHas('success');

        $this->assertNotNull($lampiran->fresh()->signed_file_path);
    }

    public function test_preview_bast_uses_allocated_number_sequence_like_generate_flow(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20'));

        $context = $this->createBastGenerationContext(includeFutureOwnKegiatan: false);

        $otherPetugas = Petugas::factory()->create([
            'nama' => 'Petugas Alokasi Nomor',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $otherAlokasi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $context['spk']->alokasiPetugas->periode_alokasi_id,
            'petugas_id' => $otherPetugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 2,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 200000,
            'total_honor_listing' => 0,
        ]);

        $otherSpk = Spk::query()->create([
            'nomor_spk' => 'SPK/BAST/999',
            'petugas_id' => $otherPetugas->id,
            'alokasi_petugas_id' => $otherAlokasi->id,
            'addendum_number' => 0,
            'nomor_urut_base' => 999,
            'tanggal_spk' => '2026-04-01',
            'tanggal_mulai_kerja' => '2026-04-01',
            'tanggal_selesai_kerja' => '2026-04-30',
            'uraian_pekerjaan' => 'SPK nomor alokasi',
            'nilai_kontrak' => 200000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $context['operator']->id,
        ]);

        BastNumberAllocation::query()->create([
            'spk_id' => $otherSpk->id,
            'nomor_bast' => 'PPIS/13730/15/BAST/2026',
            'tahun' => 2026,
            'bulan' => 4,
            'status' => 'used',
            'allocated_at' => now(),
            'used_at' => now(),
        ]);

        $response = $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.preview-bast'), [
                'spk_id' => $context['spk']->id,
            ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $currentSpkAllocation = BastNumberAllocation::query()
            ->where('spk_id', $context['spk']->id)
            ->first();

        $this->assertNotNull($currentSpkAllocation);
        $this->assertSame('PPIS/13730/16/BAST/2026', $currentSpkAllocation->nomor_bast);
        $this->assertSame('allocated', $currentSpkAllocation->status);
    }

    private function actingAsWithRole(User $user, string $roleName): self
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst(str_replace('_', ' ', $roleName))]
        );

        $user->assignRole($roleName);

        return $this->actingAs($user)->withSession([
            'active_role_id' => $role->id,
        ]);
    }

    private function createBastGenerationContext(bool $includeFutureOwnKegiatan = true): array
    {
        $this->seedPenandatangan();

        Role::query()->firstOrCreate(['name' => 'guest'], ['display_name' => 'Guest']);
        Role::query()->firstOrCreate(['name' => 'operator'], ['display_name' => 'Operator']);
        Role::query()->firstOrCreate(['name' => 'ketua_tim'], ['display_name' => 'Ketua Tim']);

        $operator = User::factory()->create();
        $ketuaTimOwn = User::factory()->create([
            'name' => 'Ketua Tim Sendiri',
        ]);
        $ketuaTimOther = User::factory()->create([
            'name' => 'Ketua Tim Lain',
        ]);

        $tahun = 2026;
        $bulan = '04';

        $kegiatanOwnCompleted = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Survei Sendiri Selesai',
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'ketua_tim_user_id' => $ketuaTimOwn->id,
            'jenis_kegiatan' => 'survei',
        ]);

        $kegiatanOther = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Survei Ketua Tim Lain',
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'ketua_tim_user_id' => $ketuaTimOther->id,
            'jenis_kegiatan' => 'survei',
        ]);

        $kegiatanOwnFuture = null;

        $periodeOwnCompleted = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanOwnCompleted->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'dikirim',
            'tanggal_selesai' => '2026-04-10',
            'tanggal_selesai_listing' => '2026-04-05',
        ]);

        $periodeOther = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanOther->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'dikirim',
            'tanggal_selesai' => '2026-04-12',
            'tanggal_selesai_listing' => '2026-04-08',
        ]);

        $periodeOwnFuture = null;

        if ($includeFutureOwnKegiatan) {
            $kegiatanOwnFuture = Kegiatan::factory()->create([
                'nama_kegiatan' => 'Survei Sendiri Belum Selesai',
                'tahun_anggaran' => $tahun,
                'status' => 'divalidasi',
                'ketua_tim_user_id' => $ketuaTimOwn->id,
                'jenis_kegiatan' => 'survei',
            ]);

            $periodeOwnFuture = PeriodeAlokasi::factory()->create([
                'kegiatan_id' => $kegiatanOwnFuture->id,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'jenis_kegiatan' => 'survei',
                'status' => 'dikirim',
                'tanggal_selesai' => now()->addDays(10)->format('Y-m-d'),
                'tanggal_selesai_listing' => now()->addDays(5)->format('Y-m-d'),
            ]);
        }

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Bast Test',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $alokasiOwnCompleted = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeOwnCompleted->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 6,
            'jumlah_satuan_listing' => 1,
            'total_honor' => 600000,
            'total_honor_listing' => 100000,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeOther->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 400000,
            'total_honor_listing' => 0,
        ]);

        if ($periodeOwnFuture && $kegiatanOwnFuture) {
            AlokasiPetugas::factory()->create([
                'periode_alokasi_id' => $periodeOwnFuture->id,
                'petugas_id' => $petugas->id,
                'peran' => 'pcl_ppl',
                'status_kepegawaian' => 'non_organik',
                'jumlah_satuan' => 3,
                'jumlah_satuan_listing' => 0,
                'total_honor' => 300000,
                'total_honor_listing' => 0,
            ]);
        }

        $spk = Spk::query()->create([
            'nomor_spk' => 'SPK/BAST/001',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiOwnCompleted->id,
            'addendum_number' => 0,
            'nomor_urut_base' => 1,
            'tanggal_spk' => '2026-04-01',
            'tanggal_mulai_kerja' => '2026-04-01',
            'tanggal_selesai_kerja' => '2026-04-30',
            'uraian_pekerjaan' => 'SPK BAST test',
            'nilai_kontrak' => 1000000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $operator->id,
        ]);

        return [
            'operator' => $operator,
            'ketuaTimOwn' => $ketuaTimOwn,
            'ketuaTimOther' => $ketuaTimOther,
            'petugas' => $petugas,
            'spk' => $spk,
            'kegiatanOwnCompleted' => $kegiatanOwnCompleted,
            'kegiatanOther' => $kegiatanOther,
            'kegiatanOwnFuture' => $kegiatanOwnFuture,
        ];
    }

    private function generateMainBast(array $context): Bast
    {
        $this
            ->actingAsWithRole($context['operator'], 'operator')
            ->post(route('bast.generate-batch'), [
                'spk_ids' => [$context['spk']->id],
            ])
            ->assertRedirect(route('bast.index'));

        return Bast::query()
            ->with('bastKegiatan')
            ->where('spk_id', $context['spk']->id)
            ->latest('id')
            ->firstOrFail();
    }

    private function seedPenandatangan(): void
    {
        Penandatangan::query()->create([
            'nama' => 'PPK Test',
            'nip' => '198001012010011001',
            'jenis_penandatangan' => 'ppk',
            'jabatan' => 'PPK',
            'periode_mulai' => Carbon::parse('2026-01-01'),
            'periode_selesai' => Carbon::parse('2026-12-31'),
            'is_active' => true,
        ]);

        Penandatangan::query()->create([
            'nama' => 'Kepala BPS Test',
            'nip' => '197901012005011001',
            'jenis_penandatangan' => 'kepala',
            'jabatan' => 'Kepala BPS',
            'periode_mulai' => Carbon::parse('2026-01-01'),
            'periode_selesai' => Carbon::parse('2026-12-31'),
            'is_active' => true,
        ]);
    }

    private function makePdfUpload(string $filename): UploadedFile
    {
        $path = storage_path('framework/testing/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, Pdf::loadHTML('<p>dokumen signed test</p>')->output());

        return new UploadedFile(
            $path,
            $filename,
            'application/pdf',
            null,
            true
        );
    }
}
