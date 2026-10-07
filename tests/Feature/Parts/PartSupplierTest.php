<?php

namespace Tests\Feature\Parts;

class PartSupplierTest extends PartsTestCase
{
    public function test_admin_can_attach_update_and_detach_supplier(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();
        $supplier = $this->createSupplier();

        $this->postJson("/api/parts/{$part['id']}/suppliers", [
            'supplier_id' => $supplier->id,
            'supplier_part_code' => 'MANN-W712',
            'last_cost' => 34000,
            'is_preferred' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.suppliers.0.id', $supplier->id)
            ->assertJsonPath('data.suppliers.0.pivot.supplier_part_code', 'MANN-W712')
            ->assertJsonPath('data.suppliers.0.pivot.is_preferred', true);

        $this->patchJson("/api/parts/{$part['id']}/suppliers/{$supplier->id}", ['last_cost' => 36000])
            ->assertOk()
            ->assertJsonPath('data.suppliers.0.pivot.last_cost', '36000.00');

        $this->deleteJson("/api/parts/{$part['id']}/suppliers/{$supplier->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data.suppliers');

        $this->getJson("/api/parts?supplier_id={$supplier->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_only_one_preferred_supplier_per_part(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();
        $first = $this->createSupplier();
        $second = $this->createSupplier();

        $this->postJson("/api/parts/{$part['id']}/suppliers", ['supplier_id' => $first->id, 'is_preferred' => true])
            ->assertCreated();

        $this->postJson("/api/parts/{$part['id']}/suppliers", ['supplier_id' => $second->id, 'is_preferred' => true])
            ->assertCreated();

        $this->assertDatabaseHas('part_supplier', ['supplier_id' => $first->id, 'is_preferred' => false]);
        $this->assertDatabaseHas('part_supplier', ['supplier_id' => $second->id, 'is_preferred' => true]);
    }

    public function test_cannot_attach_same_supplier_twice(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();
        $supplier = $this->createSupplier();

        $this->postJson("/api/parts/{$part['id']}/suppliers", ['supplier_id' => $supplier->id])
            ->assertCreated();

        $this->postJson("/api/parts/{$part['id']}/suppliers", ['supplier_id' => $supplier->id])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'PART_SUPPLIER_ALREADY_ATTACHED');
    }

    public function test_cannot_attach_inactive_supplier(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();
        $supplier = $this->createSupplier(['is_active' => false]);

        $this->postJson("/api/parts/{$part['id']}/suppliers", ['supplier_id' => $supplier->id])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'SUPPLIER_INACTIVE');
    }

    public function test_supplier_not_linked_to_part_returns_not_found(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();
        $otherPart = $this->createPartViaApi();
        $supplier = $this->createSupplier();

        $this->postJson("/api/parts/{$otherPart['id']}/suppliers", ['supplier_id' => $supplier->id])
            ->assertCreated();

        $this->patchJson("/api/parts/{$part['id']}/suppliers/{$supplier->id}", ['last_cost' => 1])
            ->assertNotFound()
            ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');

        $this->deleteJson("/api/parts/{$part['id']}/suppliers/{$supplier->id}")
            ->assertNotFound();
    }

    public function test_mechanic_cannot_manage_part_suppliers(): void
    {
        $this->actingAsAdmin();
        $part = $this->createPartViaApi();
        $supplier = $this->createSupplier();

        $this->actingAsMechanic();

        $this->postJson("/api/parts/{$part['id']}/suppliers", ['supplier_id' => $supplier->id])
            ->assertForbidden();
    }
}
