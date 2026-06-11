<?php

namespace Tests\Feature;

use Tests\TestCase;

class SicakepManualGuideTest extends TestCase
{
    public function test_sicakep_manual_guide_renders_current_application_terms(): void
    {
        $html = view('panduan.sicakep-petunjuk-penggunaan')->render();

        $this->assertStringContainsString('PETUNJUK PENGGUNAAN SIMANTIK', $html);
        $this->assertStringContainsString('simantik.sawahlunto.io', $html);
        $this->assertStringContainsString('Latar Belakang', $html);
        $this->assertStringContainsString('Manfaat Aplikasi SICAKEP', $html);
        $this->assertStringContainsString('SOP Pengajuan CKP-T', $html);
        $this->assertStringContainsString('SOP pengajuan CKP-R oleh anggota tim', $html);
        $this->assertStringContainsString('Menu Login', $html);
        $this->assertStringContainsString('Tampilan Dashboard', $html);
        $this->assertStringContainsString('Menu Entri CKP', $html);
        $this->assertStringContainsString('Menu Unduh CKP', $html);
        $this->assertStringContainsString('Ringkasan Status Pengajuan CKP', $html);
        $this->assertStringContainsString('Sudah Dinilai', $html);
    }

    public function test_sicakep_manual_guide_is_accessible_via_web_route(): void
    {
        $this->get(route('panduan.sicakep'))
            ->assertOk()
            ->assertSee('PETUNJUK PENGGUNAAN SIMANTIK')
            ->assertSee('SOP pengajuan CKP-T oleh anggota tim')
            ->assertSee('Ringkasan Status Pengajuan CKP');
    }
}
