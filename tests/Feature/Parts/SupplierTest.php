<?php

namespace Tests\Feature\Parts;

class SupplierTest extends PartsTestCase
{
    public function test_admin_can_manage_suppliers(): void
    {
        $this->actingAsAdmin();

        $supplier = $this->postJson('/api/suppliers', [
            'name' => 'Distribuidora Central',
            'tax_id' => '80012345-6',
            'email' => 'ventas@central.test',
        ])
            ->assertCreated()
            ->json('data');

        $this->postJson('/api/suppliers', ['name' => 'Otro', 'tax_id' => '80012345-6'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tax_id']);

        $this->patchJson("/api/suppliers/{$supplier['id']}", ['phone' => '021 555 555'])
            ->assertOk()
            ->assertJsonPath('data.phone', '021 555 555')
            ->assertJsonPath('data.tax_id', '80012345-6');

        $this->getJson('/api/suppliers?search=Central')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->deleteJson("/api/suppliers/{$supplier['id']}")->assertOk();

        $this->assertSoftDeleted('suppliers', ['id' => $supplier['id']]);
    }

    public function test_cannot_delete_supplier_linked_to_parts(): void
    {
        $this->actingAsAdmin();

        $supplier = $this->createSupplier();
        $part = $this->createPartViaApi();

        $this->postJson("/api/parts/{$part['id']}/suppliers", ['supplier_id' => $supplier->id])
            ->assertCreated();

        $this->deleteJson("/api/suppliers/{$supplier->id}")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'SUPPLIER_HAS_PARTS');
    }

    public function test_mechanic_cannot_access_suppliers(): void
    {
        $this->actingAsMechanic();

        $this->getJson('/api/suppliers')->assertForbidden();
        $this->postJson('/api/suppliers', ['name' => 'X'])->assertForbidden();
    }
}
