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

/**
 * Regression tests for the January 2026 multi-chain SPK false positive bug.
 *
 * Bug: A petugas with two original SPKs in the same calendar month (e.g.,
 * one for January allocations dated Jan-2 and another for February allocations
 * dated Jan-30) was incorrectly flagged for addendum because:
 *
 * 1. hasExistingAddendum was checking ALL addendums in the month, not just the
 *    chain of the earliest original SPK.
 * 2. latestDocument was matching ANY SPK in the same tanggal_spk month, which
 *    could pick up the second SPK containing different-month allocations.
 *
 * Fix: Scope hasExistingAddendum and latestDocument to the existingSpk chain only.
 */
class SpkMultiChainJanuaryRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Petugas with two original SPKs in same calendar month but different allocation months.
     * The addendum from the second chain should NOT affect the first chain's flags.
     */
    public function test_multi_chain_spk_same_month_does_not_cross_contaminate_addendum_flags(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();

        $petugas = Petugas::factory()->create([
            'nama' => 'Habibah Hayyum',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        // January kegiatan (Susenas training)
        $kegiatanJan = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Susenas Training January',
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        // February kegiatan (Susenas fieldwork)
        $kegiatanFeb = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Susenas Fieldwork February',
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $creator = User::factory()->create();

        // ===== January allocation (bulan=01) =====
        $periodeJan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanJan->id,
            'bulan' => 1,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiJan = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeJan->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 1,
            'jumlah_satuan_listing' => 1,
            'total_honor' => 0,
            'total_honor_listing' => 173000,
        ]);

        // ===== February allocation (bulan=02) but will be in SPK dated Jan-30 =====
        $periodeFeb = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanFeb->id,
            'bulan' => 2,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiFeb = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeFeb->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 10,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 2996000,
            'total_honor_listing' => 0,
        ]);

        // ===== SPK Chain 1: January allocations, dated Jan-2 =====
        $spkJanChain = Spk::query()->create([
            'nomor_spk' => 'SPK/CHAIN1/JAN',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiJan->id,
            'alokasi_petugas_ids' => [$alokasiJan->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 17,
            'tanggal_spk' => "{$tahun}-01-02",
            'tanggal_mulai_kerja' => "{$tahun}-01-01",
            'tanggal_selesai_kerja' => "{$tahun}-01-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Januari',
            'nilai_kontrak' => 173000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        // ===== SPK Chain 2: February allocations, but dated Jan-30 (same calendar month!) =====
        $spkFebChain = Spk::query()->create([
            'nomor_spk' => 'SPK/CHAIN2/FEB',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiFeb->id,
            'alokasi_petugas_ids' => [$alokasiFeb->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 70,
            'tanggal_spk' => "{$tahun}-01-30",
            'tanggal_mulai_kerja' => "{$tahun}-02-01",
            'tanggal_selesai_kerja' => "{$tahun}-02-28",
            'uraian_pekerjaan' => 'Perjanjian kerja Februari',
            'nilai_kontrak' => 2996000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        // ===== Addendum for Chain 2 (February chain) =====
        Spk::query()->create([
            'nomor_spk' => 'SPK/CHAIN2/FEB/ADD-1',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiFeb->id,
            'alokasi_petugas_ids' => [$alokasiFeb->id],
            'parent_spk_id' => $spkFebChain->id,
            'addendum_number' => 1,
            'nomor_urut_base' => 70,
            'tanggal_spk' => "{$tahun}-02-09",
            'tanggal_mulai_kerja' => "{$tahun}-02-01",
            'tanggal_selesai_kerja' => "{$tahun}-02-28",
            'uraian_pekerjaan' => 'Addendum Februari',
            'nilai_kontrak' => 3210000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        // ===== Test: January should NOT show addendum flags =====
        $response = $this->get('/spk');
        $response->assertStatus(200);

        $page = $response->viewData('page');
        $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

        $januari = collect($periodeList)->first(function (array $item) use ($tahun) {
            return (int) ($item['tahun'] ?? 0) === (int) $tahun
                && (int) ($item['bulan'] ?? 0) === 1;
        });

        $this->assertNotNull($januari, 'January period should exist in the list');

        // The addendum from Chain 2 (February) should NOT affect Chain 1 (January).
        // January's Chain 1 has no addendum, and the perubahan allocation matches the SPK.
        $this->assertFalse(
            (bool) ($januari['has_incomplete_addendum'] ?? false),
            'has_incomplete_addendum should be false: Chain 1 has no pending addendum'
        );
        $this->assertFalse(
            (bool) ($januari['has_addendum_changes'] ?? false),
            'has_addendum_changes should be false: Chain 2 addendum should not contaminate Chain 1'
        );
    }

    /**
     * When existingSpk has its own addendum, has_addendum should be true for that chain only.
     */
    public function test_has_addendum_scoped_to_correct_chain(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();

        $petugas = Petugas::factory()->create([
            'nama' => 'Fitri Yati',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Survei Test',
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $creator = User::factory()->create();

        // Original allocation (direvisi)
        $periodeDiservisi = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 1,
            'tahun' => $tahun,
            'status' => 'direvisi',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiDiservisi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDiservisi->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 500000,
            'total_honor_listing' => 0,
        ]);

        // Perubahan allocation (same values = no meaningful change)
        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 1,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiPerubahan = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 500000, // Same as direvisi
            'total_honor_listing' => 0,
        ]);

        // Original SPK with direvisi allocation
        $originalSpk = Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/JAN',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiDiservisi->id,
            'alokasi_petugas_ids' => [$alokasiDiservisi->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 15,
            'tanggal_spk' => "{$tahun}-01-02",
            'tanggal_mulai_kerja' => "{$tahun}-01-01",
            'tanggal_selesai_kerja' => "{$tahun}-01-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Januari',
            'nilai_kontrak' => 500000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        // Addendum for the original SPK (with perubahan allocation, same values)
        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/JAN/ADD-1',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiPerubahan->id,
            'alokasi_petugas_ids' => [$alokasiPerubahan->id],
            'parent_spk_id' => $originalSpk->id,
            'addendum_number' => 1,
            'nomor_urut_base' => 15,
            'tanggal_spk' => "{$tahun}-01-20",
            'tanggal_mulai_kerja' => "{$tahun}-01-01",
            'tanggal_selesai_kerja' => "{$tahun}-01-31",
            'uraian_pekerjaan' => 'Addendum Januari',
            'nilai_kontrak' => 500000, // Same as original
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        $response = $this->get('/spk');
        $response->assertStatus(200);

        $page = $response->viewData('page');
        $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

        $januari = collect($periodeList)->first(function (array $item) use ($tahun) {
            return (int) ($item['tahun'] ?? 0) === (int) $tahun
                && (int) ($item['bulan'] ?? 0) === 1;
        });

        $this->assertNotNull($januari);

        // The addendum exists and matches the current snapshot (same values).
        // Neither flag should be true since there's no meaningful change.
        $this->assertFalse(
            (bool) ($januari['has_incomplete_addendum'] ?? false),
            'has_incomplete_addendum should be false: addendum already exists with same values'
        );
        $this->assertFalse(
            (bool) ($januari['has_addendum_changes'] ?? false),
            'has_addendum_changes should be false: perubahan = direvisi, no meaningful change'
        );
    }
}
