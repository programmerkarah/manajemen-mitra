<?php

namespace Tests\Feature;

use Tests\TestCase;

class EditRouteCompatibilityTest extends TestCase
{
    public function test_sbml_edit_url_accepts_patch_request(): void
    {
        $response = $this->patch('/sbml/2026/edit', []);

        $response->assertStatus(302);
        $this->assertStringContainsString('/login', (string) $response->headers->get('Location'));
    }

    public function test_sbml_edit_url_accepts_put_request(): void
    {
        $response = $this->put('/sbml/2026/edit', []);

        $response->assertStatus(302);
        $this->assertStringContainsString('/login', (string) $response->headers->get('Location'));
    }

    public function test_penandatangan_edit_url_accepts_patch_request(): void
    {
        $response = $this->patch('/penandatangan/1/edit', []);

        $response->assertStatus(302);
        $this->assertStringContainsString('/login', (string) $response->headers->get('Location'));
    }

    public function test_penandatangan_edit_url_accepts_put_request(): void
    {
        $response = $this->put('/penandatangan/1/edit', []);

        $response->assertStatus(302);
        $this->assertStringContainsString('/login', (string) $response->headers->get('Location'));
    }

    public function test_csrf_token_endpoint_regenerates_and_returns_token(): void
    {
        $firstResponse = $this->getJson('/csrf-token');
        $firstResponse->assertOk();

        $firstToken = $firstResponse->json('token');
        $this->assertIsString($firstToken);
        $this->assertNotEmpty($firstToken);

        $secondResponse = $this->getJson('/csrf-token');
        $secondResponse->assertOk();

        $secondToken = $secondResponse->json('token');
        $this->assertIsString($secondToken);
        $this->assertNotEmpty($secondToken);
        $this->assertNotSame($firstToken, $secondToken);
    }
}
