<?php

namespace Tests\Feature\Parts;

class TextValidationTest extends PartsTestCase
{
    public function test_part_name_brand_and_description_accept_real_world_values(): void
    {
        $this->actingAsAdmin();

        $category = $this->createCategory();

        $part = $this->postJson('/api/parts', $this->partPayload($category, [
            'name' => 'Aceite 5W-30 (sintético) 100%',
            'brand' => 'K&N',
            'description' => 'Para motor 1.6 (2015-2020), 4 cil. Ref: #A/12 - 1/2" + 10% °C',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.brand', 'K&N')
            ->json('data');

        foreach (['3M', 'Mann-Filter', 'A.C. Delco', 'Bosch / Siemens'] as $brand) {
            $this->patchJson("/api/parts/{$part['id']}", ['brand' => $brand])
                ->assertOk()
                ->assertJsonPath('data.brand', $brand);
        }
    }

    public function test_part_text_fields_reject_disallowed_characters(): void
    {
        $this->actingAsAdmin();

        $category = $this->createCategory();

        $this->postJson('/api/parts', $this->partPayload($category, [
            'name' => 'Aceite @oferta',
            'brand' => 'Bosch!',
            'description' => '<script>alert(1)</script>',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['name', 'brand', 'description']);

        $part = $this->createPartViaApi($category);

        $this->patchJson("/api/parts/{$part['id']}", ['brand' => 'Marca*'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['brand']);
    }

    public function test_supplier_name_and_address_accept_real_world_values(): void
    {
        $this->actingAsAdmin();

        $supplier = $this->postJson('/api/suppliers', [
            'name' => "García & Hijos (Repuestos) N° 2 - D'Angelo",
            'contact_name' => "María-José O'Neill Jr.",
            'address' => 'Av. España 1234 (frente al banco) - Km 5, Depto. 3º',
        ])
            ->assertCreated()
            ->json('data');

        $this->patchJson("/api/suppliers/{$supplier['id']}", [
            'name' => 'Lubricantes del Este S.A.',
            'address' => "Ruta 2 Km 30 / Calle O'Higgins",
        ])->assertOk();
    }

    public function test_supplier_text_fields_reject_disallowed_characters(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/suppliers', [
            'name' => 'Proveedor @QA',
            'contact_name' => 'Juan 123',
            'address' => 'Calle <falsa> 123',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['name', 'contact_name', 'address']);

        $supplier = $this->createSupplier();

        $this->patchJson("/api/suppliers/{$supplier->id}", ['contact_name' => 'Ana #1'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contact_name']);
    }
}
