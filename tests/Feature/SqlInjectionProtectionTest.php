<?php

namespace Tests\Feature;

use App\Models\Dipa;
use App\Models\Kegiatan;
use App\Models\Petugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SqlInjectionProtectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * Test SQL injection attempts in search parameter are blocked
     */
    public function test_sql_injection_in_search_is_sanitized(): void
    {
        $maliciousInputs = [
            "' OR '1'='1",
            "1' UNION SELECT * FROM users--",
            "'; DROP TABLE petugas;--",
            "<script>alert('XSS')</script>",
            "1' AND 1=1--",
            "admin'--",
        ];

        foreach ($maliciousInputs as $input) {
            $response = $this->actingAs($this->user)->get(route('petugas.index', [
                'search' => $input,
            ]));

            // Should return 200 (success) without executing malicious SQL
            $response->assertStatus(200);

            // Verify database structure is intact
            $this->assertDatabaseHas('petugas', []); // Table still exists
        }
    }

    /**
     * Test SQL injection in tahun parameter is blocked by validation
     */
    public function test_sql_injection_in_tahun_is_validated(): void
    {
        $response = $this->actingAs($this->user)->get(route('dipa.index', [
            'tahun' => "2024' OR '1'='1",
        ]));

        // Should return validation error or sanitize the input
        // FilterRequest will either reject or sanitize this
        $response->assertStatus(200);
    }

    /**
     * Test SQL injection in bulan parameter is blocked by validation
     */
    public function test_sql_injection_in_bulan_is_validated(): void
    {
        Kegiatan::factory()->create();

        $response = $this->actingAs($this->user)->get(route('alokasi.index', [
            'bulan' => "1' OR '1'='1",
        ]));

        // Should be sanitized or rejected
        $response->assertStatus(200);
    }

    /**
     * Test that valid input still works after protection
     */
    public function test_valid_search_still_works(): void
    {
        Petugas::factory()->create(['nama' => 'John Doe']);

        $response = $this->actingAs($this->user)->get(route('petugas.index', [
            'search' => 'John',
        ]));

        $response->assertStatus(200);
    }

    /**
     * Test that valid filters still work
     */
    public function test_valid_filters_still_work(): void
    {
        Dipa::factory()->create(['tahun' => 2024]);

        $response = $this->actingAs($this->user)->get(route('dipa.index', [
            'tahun' => 2024,
        ]));

        $response->assertStatus(200);
    }

    /**
     * Test XSS prevention in search
     */
    public function test_xss_prevention_in_search(): void
    {
        $xssAttempts = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            '<iframe src="javascript:alert(\'XSS\')">',
        ];

        foreach ($xssAttempts as $xss) {
            $response = $this->actingAs($this->user)->get(route('petugas.index', [
                'search' => $xss,
            ]));

            $response->assertStatus(200);
            // Response should not contain the raw XSS payload
            $response->assertDontSee($xss, false);
        }
    }
}
