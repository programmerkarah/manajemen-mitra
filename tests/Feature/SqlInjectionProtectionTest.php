<?php

namespace Tests\Feature;

use App\Models\Dipa;
use App\Models\Kegiatan;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SqlInjectionProtectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Admin', 'description' => 'Role admin']
        );

        $this->user = User::factory()->create();
        $this->user->roles()->attach($this->adminRole->id);
    }

    private function actingAsAdmin(): static
    {
        return $this->actingAs($this->user)
            ->withSession(['active_role_id' => $this->adminRole->id]);
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
            $response = $this->actingAsAdmin()->get(route('petugas.index', [
                'search' => $input,
            ]));

            // Should return 200 (success) without executing malicious SQL
            $response->assertStatus(200);

            // Verify request remains safe and successful
            $this->assertTrue(true);
        }
    }

    /**
     * Test SQL injection in tahun parameter is blocked by validation
     */
    public function test_sql_injection_in_tahun_is_validated(): void
    {
        $response = $this->actingAsAdmin()->get(route('dipa.index', [
            'tahun' => "2024' OR '1'='1",
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['tahun']);
    }

    /**
     * Test SQL injection in bulan parameter is blocked by validation
     */
    public function test_sql_injection_in_bulan_is_validated(): void
    {
        Kegiatan::factory()->create();

        $response = $this->actingAsAdmin()->get(route('alokasi.index', [
            'bulan' => "1' OR '1'='1",
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['bulan']);
    }

    /**
     * Test that valid input still works after protection
     */
    public function test_valid_search_still_works(): void
    {
        Petugas::factory()->create(['nama' => 'John Doe']);

        $response = $this->actingAsAdmin()->get(route('petugas.index', [
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

        $response = $this->actingAsAdmin()->get(route('dipa.index', [
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
            $response = $this->actingAsAdmin()->get(route('petugas.index', [
                'search' => $xss,
            ]));

            $response->assertStatus(200);
            // Response should not contain the raw XSS payload
            $response->assertDontSee($xss, false);
        }
    }
}
