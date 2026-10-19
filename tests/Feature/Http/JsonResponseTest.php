<?php

namespace Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sin "Accept: application/json" la API igual responde con el contrato JSON
 * (EnsureJsonResponse), en lugar de HTML o redirecciones.
 */
class JsonResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_without_accept_header_returns_json(): void
    {
        $this->get('/api/receptions')
            ->assertStatus(401)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');
    }

    public function test_validation_error_without_accept_header_returns_json_instead_of_redirect(): void
    {
        $this->post('/api/login', [])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_unknown_route_and_wrong_method_without_accept_header_return_json(): void
    {
        $this->get('/api/no-existe')
            ->assertNotFound()
            ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');

        $this->get('/api/login')
            ->assertStatus(405)
            ->assertJsonPath('error_code', 'METHOD_NOT_ALLOWED');
    }
}
