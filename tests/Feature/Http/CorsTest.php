<?php

namespace Tests\Feature\Http;

use Tests\TestCase;

class CorsTest extends TestCase
{
    private const FRONTEND_ORIGIN = 'http://localhost:5173';

    public function test_frontend_origin_can_read_content_disposition(): void
    {
        // Sin token alcanza: los headers CORS se agregan también a las respuestas de error.
        $this->withHeaders(['Origin' => self::FRONTEND_ORIGIN])
            ->getJson('/api/clients')
            ->assertUnauthorized()
            ->assertHeader('Access-Control-Allow-Origin', self::FRONTEND_ORIGIN)
            ->assertHeader('Access-Control-Expose-Headers', 'Content-Disposition');
    }

    public function test_preflight_allows_the_frontend_origin(): void
    {
        $this->withHeaders([
            'Origin' => self::FRONTEND_ORIGIN,
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'authorization',
        ])->options('/api/clients')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', self::FRONTEND_ORIGIN);
    }

    public function test_other_origins_are_not_allowed(): void
    {
        // Con un único origen configurado se responde siempre ese origen; el navegador bloquea si no coincide.
        $allowed = $this->withHeaders(['Origin' => 'http://otro-sitio.test'])
            ->getJson('/api/clients')
            ->headers->get('Access-Control-Allow-Origin');

        $this->assertNotSame('http://otro-sitio.test', $allowed);
    }
}
