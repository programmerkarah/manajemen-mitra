<?php

namespace Tests\Unit;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use App\Services\SpkActionDecisionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpkActionDecisionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_determine_final_action_prioritizes_revision_over_new_activity(): void
    {
        $service = app(SpkActionDecisionService::class);

        $this->assertSame('generate_addendum', $service->determineFinalAction(
            hasExistingSpk: true,
            hasExistingAddendum: false,
            hasReplacementChange: true,
            hasNewAllocation: true,
            hasUncoveredPostAddendumChange: false,
        ));

        $this->assertSame('regenerate_addendum', $service->determineFinalAction(
            hasExistingSpk: true,
            hasExistingAddendum: true,
            hasReplacementChange: true,
            hasNewAllocation: true,
            hasUncoveredPostAddendumChange: false,
        ));

        $this->assertSame('regenerate_addendum', $service->determineFinalAction(
            hasExistingSpk: true,
            hasExistingAddendum: true,
            hasReplacementChange: false,
            hasNewAllocation: true,
            hasUncoveredPostAddendumChange: false,
        ));
    }

    public function test_resolve_for_month_assigns_each_petugas_to_only_one_final_action(): void
    {
        Carbon::setTestNow('2026-05-15 09:00:00');

        try {
            $tahun = ActiveYearService::get();
            $service = app(SpkActionDecisionService::class);
            $creator = User::factory()->create();

            $petugasGeneratePk = Petugas::factory()->create([
                'nama' => 'Petugas Generate PK',
                'jenis_petugas' => 'non-organik',
                'status' => 'aktif',
            ]);

            $petugasRegeneratePk = Petugas::factory()->create([
                'nama' => 'Petugas Regenerate PK',
                'jenis_petugas' => 'non-organik',
                'status' => 'aktif',
            ]);

            $petugasGenerateAddendum = Petugas::factory()->create([
                'nama' => 'Petugas Generate Addendum',
                'jenis_petugas' => 'non-organik',
                'status' => 'aktif',
            ]);

            $petugasRegenerateAddendum = Petugas::factory()->create([
                'nama' => 'Petugas Regenerate Addendum',
                'jenis_petugas' => 'non-organik',
                'status' => 'aktif',
            ]);

            $petugasNoAction = Petugas::factory()->create([
                'nama' => 'Petugas No Action',
                'jenis_petugas' => 'non-organik',
                'status' => 'aktif',
            ]);

            $kegiatanA = Kegiatan::factory()->create([
                'tahun_anggaran' => $tahun,
                'status' => 'divalidasi',
                'jenis_kegiatan' => 'survei',
            ]);

            $kegiatanB = Kegiatan::factory()->create([
                'tahun_anggaran' => $tahun,
                'status' => 'divalidasi',
                'jenis_kegiatan' => 'survei',
            ]);

            $kegiatanC = Kegiatan::factory()->create([
                'tahun_anggaran' => $tahun,
                'status' => 'divalidasi',
                'jenis_kegiatan' => 'survei',
            ]);

            $kegiatanD = Kegiatan::factory()->create([
                'tahun_anggaran' => $tahun,
                'status' => 'divalidasi',
                'jenis_kegiatan' => 'survei',
            ]);

            $periodeGeneratePk = $this->createPeriodeWithAllocation($tahun, '05', $kegiatanA, $petugasGeneratePk, 'dikirim', 100000);

            $periodeOriginalRegeneratePk = $this->createPeriodeWithAllocation($tahun, '05', $kegiatanA, $petugasRegeneratePk, 'dikirim', 120000);
            $this->createPeriodeWithAllocation($tahun, '05', $kegiatanB, $petugasRegeneratePk, 'dikirim', 80000);
            $this->createOriginalSpk($creator, $petugasRegeneratePk, $periodeOriginalRegeneratePk, 120000, 'SPK/ORI/REGPK/001');

            $periodeOriginalAddendum = $this->createPeriodeWithAllocation($tahun, '05', $kegiatanA, $petugasGenerateAddendum, 'direvisi', 150000);
            $periodePerubahanAddendum = $this->createPeriodeWithAllocation($tahun, '05', $kegiatanA, $petugasGenerateAddendum, 'perubahan', 165000);
            $this->createOriginalSpk($creator, $petugasGenerateAddendum, $periodeOriginalAddendum, 150000, 'SPK/ORI/ADD/001');

            $periodeOriginalRegenerateAddendum = $this->createPeriodeWithAllocation($tahun, '05', $kegiatanC, $petugasRegenerateAddendum, 'direvisi', 200000);
            $periodeAddendumRegenerate = $this->createPeriodeWithAllocation($tahun, '05', $kegiatanC, $petugasRegenerateAddendum, 'perubahan', 200000);
            $periodeNewAfterAddendum = $this->createPeriodeWithAllocation($tahun, '05', $kegiatanD, $petugasRegenerateAddendum, 'dikirim', 90000);
            $originalRegenerateAddendum = $this->createOriginalSpk($creator, $petugasRegenerateAddendum, $periodeOriginalRegenerateAddendum, 200000, 'SPK/ORI/ADDREG/001');
            $this->createAddendumSpk($creator, $petugasRegenerateAddendum, $originalRegenerateAddendum, $periodeAddendumRegenerate, 200000, 'SPK/ADD/ADDREG/001');

            $periodeNoAction = $this->createPeriodeWithAllocation($tahun, '05', $kegiatanA, $petugasNoAction, 'dikirim', 70000);
            $this->createOriginalSpk($creator, $petugasNoAction, $periodeNoAction, 70000, 'SPK/ORI/NOACTION/001');

            $decisions = $service->resolveForMonth($tahun, 5);

            $this->assertCount(5, $decisions);
            $this->assertSame($decisions->count(), $decisions->pluck('petugas_id')->unique()->count());

            $actionsByPetugas = $decisions->mapWithKeys(function (array $item): array {
                return [(int) $item['petugas_id'] => (string) $item['final_action']];
            });

            $this->assertSame('generate_pk', $actionsByPetugas[$petugasGeneratePk->id]);
            $this->assertSame('regenerate_pk', $actionsByPetugas[$petugasRegeneratePk->id]);
            $this->assertSame('generate_addendum', $actionsByPetugas[$petugasGenerateAddendum->id]);
            $this->assertSame('regenerate_addendum', $actionsByPetugas[$petugasRegenerateAddendum->id]);
            $this->assertSame('no_action', $actionsByPetugas[$petugasNoAction->id]);

            $addendumCandidates = $service->resolveAddendumCandidatesForMonth($tahun, 5);
            $this->assertCount(2, $addendumCandidates);
            $this->assertTrue($addendumCandidates->contains(fn (array $item): bool => (int) $item['petugas_id'] === $petugasGenerateAddendum->id));
            $this->assertTrue($addendumCandidates->contains(fn (array $item): bool => (int) $item['petugas_id'] === $petugasRegenerateAddendum->id));
            $this->assertFalse($addendumCandidates->contains(fn (array $item): bool => (int) $item['petugas_id'] === $petugasGeneratePk->id));
            $this->assertFalse($addendumCandidates->contains(fn (array $item): bool => (int) $item['petugas_id'] === $petugasRegeneratePk->id));
            $this->assertFalse($addendumCandidates->contains(fn (array $item): bool => (int) $item['petugas_id'] === $petugasNoAction->id));
        } finally {
            Carbon::setTestNow();
        }
    }

    private function createPeriodeWithAllocation(
        int $tahun,
        string $bulan,
        Kegiatan $kegiatan,
        Petugas $petugas,
        string $status,
        float $totalHonor,
    ): PeriodeAlokasi {
        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => $status,
            'jenis_kegiatan' => 'survei',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 1,
            'jumlah_satuan_listing' => 0,
            'total_honor' => $totalHonor,
            'total_honor_listing' => 0,
        ]);

        return $periode;
    }

    private function createOriginalSpk(
        User $creator,
        Petugas $petugas,
        PeriodeAlokasi $periode,
        float $nilaiKontrak,
        string $nomorSpk,
    ): Spk {
        $alokasi = $periode->alokasiPetugas()->where('petugas_id', $petugas->id)->firstOrFail();

        return Spk::query()->create([
            'nomor_spk' => $nomorSpk,
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasi->id,
            'alokasi_petugas_ids' => [$alokasi->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 1,
            'tanggal_spk' => '2026-05-05',
            'tanggal_mulai_kerja' => '2026-05-01',
            'tanggal_selesai_kerja' => '2026-05-31',
            'uraian_pekerjaan' => 'Perjanjian kerja',
            'nilai_kontrak' => $nilaiKontrak,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);
    }

    private function createAddendumSpk(
        User $creator,
        Petugas $petugas,
        Spk $parentSpk,
        PeriodeAlokasi $periode,
        float $nilaiKontrak,
        string $nomorSpk,
    ): Spk {
        $alokasi = $periode->alokasiPetugas()->where('petugas_id', $petugas->id)->firstOrFail();

        return Spk::query()->create([
            'nomor_spk' => $nomorSpk,
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasi->id,
            'alokasi_petugas_ids' => [$alokasi->id],
            'parent_spk_id' => $parentSpk->id,
            'addendum_number' => 1,
            'nomor_urut_base' => 1,
            'tanggal_spk' => '2026-05-15',
            'tanggal_mulai_kerja' => '2026-05-01',
            'tanggal_selesai_kerja' => '2026-05-31',
            'uraian_pekerjaan' => 'Addendum kerja',
            'nilai_kontrak' => $nilaiKontrak,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);
    }
}
