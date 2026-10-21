<?php

namespace Tests\Feature\MasterData;

use Tests\Feature\Parts\PartsTestCase;

class ClientTest extends PartsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
    }

    private function payload(array $overrides = []): array
    {
        return [
            'document_number' => 'QA-2001',
            'first_name' => 'Lucia',
            'last_name' => 'Benitez',
            'email' => 'qa.lucia.benitez@gmail.com',
            'phone' => '+595981000111',
            'notes' => 'Cliente frecuente.',
            ...$overrides,
        ];
    }

    public function test_crud_of_clients(): void
    {
        $client = $this->postJson('/api/clients', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.document_number', 'QA-2001')
            ->json('data');

        $this->getJson('/api/clients?search=Benitez')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.document_number', 'QA-2001');
        $this->getJson("/api/clients/{$client['id']}")->assertOk()->assertJsonPath('data.vehicles', []);

        $this->patchJson("/api/clients/{$client['id']}", ['phone' => '+595981999888', 'first_name' => 'Lucía'])
            ->assertOk()
            ->assertJsonPath('data.phone', '+595981999888')
            ->assertJsonPath('data.first_name', 'Lucía');

        $this->deleteJson("/api/clients/{$client['id']}")->assertOk();

        $this->assertSoftDeleted('clients', ['id' => $client['id']]);
        $this->getJson("/api/clients/{$client['id']}")->assertNotFound()->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');

        // Documento y email del eliminado quedan libres.
        $this->postJson('/api/clients', $this->payload())->assertCreated();
    }

    public function test_notes_can_be_cleared_on_update(): void
    {
        $client = $this->postJson('/api/clients', $this->payload())->assertCreated()->json('data');

        $this->patchJson("/api/clients/{$client['id']}", ['notes' => null])
            ->assertOk()
            ->assertJsonPath('data.notes', null);
    }

    public function test_client_validation(): void
    {
        $this->postJson('/api/clients', [])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['document_number', 'first_name', 'last_name', 'email', 'phone']);

        $this->postJson('/api/clients', $this->payload())->assertCreated();

        $this->postJson('/api/clients', $this->payload(['email' => 'otro@gmail.com']))->assertJsonValidationErrors(['document_number']);
        $this->postJson('/api/clients', $this->payload(['document_number' => 'QA-2002']))->assertJsonValidationErrors(['email']);

        $other = ['document_number' => 'QA-2003', 'email' => 'qa.otro@gmail.com'];
        $this->postJson('/api/clients', $this->payload([...$other, 'phone' => 'abc']))->assertJsonValidationErrors(['phone']);
        $this->postJson('/api/clients', $this->payload([...$other, 'first_name' => 'Ana123']))->assertJsonValidationErrors(['first_name']);
        $this->postJson('/api/clients', $this->payload([...$other, 'document_number' => 'QA 2003!']))->assertJsonValidationErrors(['document_number']);
    }

    public function test_email_domain_check_follows_the_configuration(): void
    {
        // ".invalid" está reservado y nunca resuelve: el resultado no depende de la red.
        $payload = $this->payload(['email' => 'qa.lucia@dominio.invalid']);

        config(['validation.email_dns' => true]);
        $this->postJson('/api/clients', $payload)->assertStatus(422)->assertJsonValidationErrors(['email']);

        // phpunit.xml lo desactiva (EMAIL_DNS_CHECK=false): solo se valida el formato.
        config(['validation.email_dns' => false]);
        $this->postJson('/api/clients', $payload)->assertCreated();
    }

    public function test_mechanic_cannot_manage_clients(): void
    {
        $this->actingAsMechanic();

        $this->getJson('/api/clients')->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
        $this->postJson('/api/clients', $this->payload())->assertForbidden();
    }
}
