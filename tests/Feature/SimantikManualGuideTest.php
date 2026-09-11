<?php

namespace Tests\Feature;

use Tests\TestCase;

class SimantikManualGuideTest extends TestCase
{
    public function test_simantik_manual_guide_renders_current_application_terms(): void
    {
        $html = view('panduan.simantik-petunjuk-penggunaan')->render();

        $this->assertStringContainsString('PETUNJUK PENGGUNAAN SIMANTIK', $html);
        $this->assertStringContainsString('simantik.sawahlunto.io', $html);
        $this->assertStringContainsString('Panduan ini ditulis ulang agar seluruh isi mengacu ke SIMANTIK', $html);
        $this->assertStringContainsString('Fokus Panduan', $html);
        $this->assertStringContainsString('Diagram Alur SOP', $html);
        $this->assertStringContainsString('SOP Utama Pengguna', $html);
        $this->assertStringContainsString('SOP Dokumen dan Review', $html);
        $this->assertStringContainsString('Fitur Per Bagian', $html);
        $this->assertStringContainsString('Login', $html);
        $this->assertStringContainsString('Kelola Kegiatan', $html);
        $this->assertStringContainsString('Tambah Alokasi', $html);
        $this->assertStringContainsString('Cetak SK KPA', $html);
        $this->assertStringContainsString('Cetak SPK', $html);
        $this->assertStringContainsString('Cetak BAST', $html);
        $this->assertStringContainsString('Ajukan Pulsa', $html);
        $this->assertStringContainsString('Review Petugas', $html);
        $this->assertStringContainsString('Dashboard SIMANTIK', $html);
        $this->assertStringContainsString('Dashboard Admin', $html);
        $this->assertStringContainsString('Menu Utama', $html);
        $this->assertStringContainsString('Ringkasan Status Proses', $html);
        $this->assertStringContainsString('Selesai', $html);
        $this->assertStringContainsString('Screenshot Tampilan dan Preview Dokumen', $html);
        $this->assertStringContainsString('Preview SK KPA', $html);
        $this->assertStringContainsString('Preview SPK', $html);
        $this->assertStringContainsString('Preview BAST', $html);
        $this->assertStringContainsString('Ajukan Pulsa dan Review Petugas', $html);
    }

    public function test_simantik_manual_guide_is_accessible_via_web_route(): void
    {
        $this->get(route('panduan.simantik'))
            ->assertOk()
            ->assertSee('PETUNJUK PENGGUNAAN SIMANTIK')
            ->assertSee('Diagram Alur SOP')
            ->assertSee('Fitur Per Bagian')
            ->assertSee('Ringkasan Status Proses')
            ->assertSee('Screenshot Tampilan dan Preview Dokumen');
    }
}
