<?php

namespace Tests\Feature\WorkOrderParts;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Repuestos extra (fuera del presupuesto aprobado): no reservan ni se
 * consumen hasta registrar la respuesta del cliente (deuda #11).
 */
class ExtraPartApprovalTest extends WorkOrderPartsTestCase
{
    /**
     * OT en progreso con un repuesto presupuestado y otro con stock para extras.
     *
     * @return array{0: array, 1: array}
     */
    private function startedWorkOrderWithExtraStock(int $stock = 5): array
    {
        $budgeted = $this->createPartViaApi();
        $this->purchase($budgeted['id'], 5, 100)->assertOk();

        $extra = $this->createPartViaApi(null, ['sale_price' => 90]);
        $this->purchase($extra['id'], $stock, 40)->assertOk();

        $workOrder = $this->workOrderWithPart($budgeted, 1);
        $this->startWorkOrder($workOrder);

        return [$workOrder, $extra];
    }

    private function addExtra(array $workOrder, array $part, int|float $quantity = 2): array
    {
        return $this->postJson($this->partsUrl($workOrder), ['part_id' => $part['id'], 'quantity' => $quantity])
            ->assertCreated()
            ->json('data');
    }

    public function test_extra_waits_for_the_client_before_reserving_or_consuming(): void
    {
        $this->actingAsAdmin();

        [$workOrder, $part] = $this->startedWorkOrderWithExtraStock();
        $line = $this->addExtra($workOrder, $part);

        $this->assertSame('awaiting_approval', $line['status']['value']);
        $this->assertSame('Pendiente de aprobación', $line['status']['label']);
        $this->assertSame('0.000', $this->stockOf($part['id'])->quantity_reserved);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'reserve'))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_PART_NOT_APPROVED');

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'consume'), ['quantity_used' => 2])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_PART_NOT_APPROVED');

        // Sin decisión del cliente la OT no se completa (y por lo tanto no se factura).
        $budgetedLine = $workOrder['parts'][0];
        $this->postJson($this->partsUrl($workOrder, $budgetedLine['id'], 'consume'), ['quantity_used' => 1])->assertOk();
        $this->completeAllItems($workOrder);

        $this->postJson("/api/work-orders/{$workOrder['id']}/complete")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_HAS_UNRESOLVED_PARTS');

        $this->getJson("/api/work-orders/{$workOrder['id']}")
            ->assertOk()
            ->assertJsonPath('data.parts_summary.unresolved_count', 1);
    }

    public function test_approval_is_recorded_and_reserves_the_stock(): void
    {
        $admin = $this->actingAsAdmin();

        [$workOrder, $part] = $this->startedWorkOrderWithExtraStock();
        $line = $this->addExtra($workOrder, $part);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'approve'), [
            'channel' => 'whatsapp',
            'contact' => 'Carlos (chofer)',
            'notes' => 'Aceptó por mensaje',
        ])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'reserved')
            ->assertJsonPath('data.client_decision.value', 'approved')
            ->assertJsonPath('data.client_decision.label', 'Aprobado por el cliente')
            ->assertJsonPath('data.client_decision.decided_by', $admin->id)
            ->assertJsonPath('data.client_decision.channel.value', 'whatsapp')
            ->assertJsonPath('data.client_decision.channel.label', 'WhatsApp')
            ->assertJsonPath('data.client_decision.contact', 'Carlos (chofer)')
            ->assertJsonPath('data.client_decision.notes', 'Aceptó por mensaje');

        $this->assertSame('2.000', $this->stockOf($part['id'])->quantity_reserved);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'approve'), ['channel' => 'phone'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_PART_INVALID_STATUS_TRANSITION');

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'reject'), ['channel' => 'phone', 'notes' => 'Tarde'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_PART_INVALID_STATUS_TRANSITION');

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'consume'), ['quantity_used' => 2])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'consumed');

        // La línea tiene precio: sus cambios quedan en la auditoría.
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => 'work_order_part',
            'auditable_id' => $line['id'],
            'event' => 'updated',
        ]);
    }

    public function test_approved_extra_without_stock_stays_pending(): void
    {
        $this->actingAsAdmin();

        [$workOrder, $part] = $this->startedWorkOrderWithExtraStock(1);
        $line = $this->addExtra($workOrder, $part, 3);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'approve'), ['channel' => 'in_person'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'pending')
            ->assertJsonPath('data.client_decision.value', 'approved');

        $this->assertSame('0.000', $this->stockOf($part['id'])->quantity_reserved);
    }

    public function test_rejection_requires_a_reason_and_cancels_the_line(): void
    {
        $this->actingAsAdmin();

        [$workOrder, $part] = $this->startedWorkOrderWithExtraStock();
        $line = $this->addExtra($workOrder, $part);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'reject'), ['channel' => 'phone'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['notes']);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'reject'), [
            'channel' => 'phone',
            'notes' => 'Lo trae el cliente',
        ])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'cancelled')
            ->assertJsonPath('data.client_decision.value', 'rejected')
            ->assertJsonPath('data.client_decision.notes', 'Lo trae el cliente');

        $this->assertSame('0.000', $this->stockOf($part['id'])->quantity_reserved);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'approve'), ['channel' => 'phone'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_PART_INVALID_STATUS_TRANSITION');
    }

    public function test_decision_validation_and_closed_work_order(): void
    {
        $this->actingAsAdmin();

        [$workOrder, $part] = $this->startedWorkOrderWithExtraStock();
        $line = $this->addExtra($workOrder, $part);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'approve'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['channel']);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'approve'), ['channel' => 'fax'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['channel']);

        // Una línea presupuestada no espera aprobación: ya la tiene el presupuesto.
        $this->postJson($this->partsUrl($workOrder, $workOrder['parts'][0]['id'], 'approve'), ['channel' => 'phone'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_PART_INVALID_STATUS_TRANSITION');

        // Cancelar el extra sigue disponible (cargado por error).
        $this->postJson($this->partsUrl($workOrder, $line['id'], 'cancel'))
            ->assertOk()
            ->assertJsonPath('data.status.value', 'cancelled')
            ->assertJsonPath('data.client_decision', null);
    }

    public function test_cancelling_the_work_order_cancels_extras_awaiting_approval(): void
    {
        $this->actingAsAdmin();

        $budgeted = $this->createPartViaApi();
        $extra = $this->createPartViaApi();
        $workOrder = $this->workOrderWithPart($budgeted, 1);
        $line = $this->addExtra($workOrder, $extra, 1);

        $this->postJson("/api/work-orders/{$workOrder['id']}/cancel", ['reason' => 'El cliente desistió'])->assertOk();

        $this->assertDatabaseHas('work_order_parts', ['id' => $line['id'], 'status' => 'cancelled']);
    }

    public function test_approved_quantity_and_price_cannot_change(): void
    {
        $this->actingAsAdmin();

        [$workOrder, $part] = $this->startedWorkOrderWithExtraStock();
        $line = $this->addExtra($workOrder, $part);

        // Antes de la decisión se puede corregir.
        $this->patchJson($this->partsUrl($workOrder, $line['id']), ['quantity' => 3, 'unit_price' => 85])
            ->assertOk()
            ->assertJsonPath('data.quantity', '3.000')
            ->assertJsonPath('data.unit_price', '85.00');

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'approve'), ['channel' => 'phone'])->assertOk();

        $this->patchJson($this->partsUrl($workOrder, $line['id']), ['quantity' => 4])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_PART_APPROVED_NOT_EDITABLE');

        $this->patchJson($this->partsUrl($workOrder, $line['id']), ['unit_price' => 95])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_PART_APPROVED_NOT_EDITABLE');

        // Reenviar los mismos valores (formulario completo) y cambiar notas sí.
        $this->patchJson($this->partsUrl($workOrder, $line['id']), [
            'quantity' => 3,
            'unit_price' => 85,
            'notes' => 'Marca original',
        ])
            ->assertOk()
            ->assertJsonPath('data.notes', 'Marca original');

        $this->assertSame('3.000', $this->stockOf($part['id'])->quantity_reserved);
    }

    public function test_extra_cannot_use_more_than_approved(): void
    {
        $this->actingAsAdmin();

        [$workOrder, $part] = $this->startedWorkOrderWithExtraStock();
        $line = $this->addApprovedExtra($workOrder, ['part_id' => $part['id'], 'quantity' => 2]);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'consume'), ['quantity_used' => 3])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_PART_EXCEEDS_APPROVED');

        $this->assertSame('2.000', $this->stockOf($part['id'])->quantity_reserved);

        // Usar menos de lo aprobado sí: se factura lo usado.
        $this->postJson($this->partsUrl($workOrder, $line['id'], 'consume'), ['quantity_used' => 1])
            ->assertOk()
            ->assertJsonPath('data.quantity_consumed', '1.000')
            ->assertJsonPath('data.total_price', '90.00');
    }

    public function test_budgeted_lines_can_still_use_more_than_reserved(): void
    {
        $this->actingAsAdmin();

        [$workOrder] = $this->startedWorkOrderWithExtraStock();

        $this->postJson($this->partsUrl($workOrder, $workOrder['parts'][0]['id'], 'consume'), ['quantity_used' => 2])
            ->assertOk()
            ->assertJsonPath('data.quantity_consumed', '2.000');
    }

    public function test_roles_that_add_and_approve_extras(): void
    {
        $this->actingAsAdmin();

        [$workOrder, $part] = $this->startedWorkOrderWithExtraStock(10);

        // Depósito agrega pero no aprueba.
        $this->actingAsRole('deposito');
        $first = $this->addExtra($workOrder, $part, 1);
        $this->postJson($this->partsUrl($workOrder, $first['id'], 'approve'), ['channel' => 'phone'])
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        // Recepción ve los repuestos y registra la respuesta, pero no agrega.
        $this->actingAsRole('recepcionista');
        $this->getJson($this->partsUrl($workOrder))->assertOk();
        $this->postJson($this->partsUrl($workOrder), ['part_id' => $part['id'], 'quantity' => 1])->assertForbidden();
        $this->postJson($this->partsUrl($workOrder, $first['id'], 'approve'), ['channel' => 'phone'])->assertOk();

        // Jefe de taller agrega y registra el rechazo.
        $this->actingAsRole('jefe-taller');
        $second = $this->addExtra($workOrder, $part, 1);
        $this->postJson($this->partsUrl($workOrder, $second['id'], 'reject'), ['channel' => 'phone', 'notes' => 'No autoriza'])
            ->assertOk();

        // El mecánico no agrega ni decide.
        $this->actingAsMechanic();
        $this->postJson($this->partsUrl($workOrder), ['part_id' => $part['id'], 'quantity' => 1])->assertForbidden();
        $this->postJson($this->partsUrl($workOrder, $second['id'], 'approve'), ['channel' => 'phone'])->assertForbidden();
    }

    public function test_adding_an_extra_notifies_reception_and_workshop_manager(): void
    {
        $reception = $this->userWithRole('recepcionista');
        $manager = $this->userWithRole('jefe-taller');
        $warehouse = $this->userWithRole('deposito');

        $this->actingAsAdmin();

        [$workOrder, $part] = $this->startedWorkOrderWithExtraStock();
        $this->addExtra($workOrder, $part);

        $type = 'extra_part_awaiting_approval';

        $this->assertSame(1, $reception->notifications()->where('type', $type)->count());
        $this->assertSame(1, $manager->notifications()->where('type', $type)->count());
        $this->assertSame(0, $warehouse->notifications()->where('type', $type)->count());

        $this->assertStringContainsString(
            $workOrder['code'],
            $reception->notifications()->where('type', $type)->sole()->data['message']
        );
    }

    public function test_migration_moves_open_legacy_extras_to_awaiting_approval(): void
    {
        $this->actingAsAdmin();

        [$workOrder, $part] = $this->startedWorkOrderWithExtraStock();

        // Estado previo a la migración: extras reservados, pendientes y consumidos sin decisión.
        $reserved = $this->addApprovedExtra($workOrder, ['part_id' => $part['id'], 'quantity' => 2]);
        $pending = $this->addExtra($workOrder, $part, 1);
        $consumed = $this->addApprovedExtra($workOrder, ['part_id' => $part['id'], 'quantity' => 1]);
        $this->postJson($this->partsUrl($workOrder, $consumed['id'], 'consume'), ['quantity_used' => 1])->assertOk();

        DB::table('work_order_parts')->where('id', $pending['id'])->update(['status' => 'pending']);
        $this->assertSame('2.000', $this->stockOf($part['id'])->quantity_reserved);

        $migration = require database_path('migrations/0001_01_01_000051_add_client_decision_to_work_order_parts_table.php');
        $migration->down();
        $migration->up();

        $this->assertDatabaseHas('work_order_parts', ['id' => $reserved['id'], 'status' => 'awaiting_approval', 'client_decision' => null]);
        $this->assertDatabaseHas('work_order_parts', ['id' => $pending['id'], 'status' => 'awaiting_approval']);
        $this->assertDatabaseHas('work_order_parts', ['id' => $consumed['id'], 'status' => 'consumed']);
        // La línea presupuestada (reservada) no se toca.
        $this->assertDatabaseHas('work_order_parts', ['id' => $workOrder['parts'][0]['id'], 'status' => 'reserved']);

        $this->assertSame('0.000', $this->stockOf($part['id'])->quantity_reserved);
    }

    private function userWithRole(string $role): User
    {
        $user = User::create([
            'name' => $role . '_' . Str::random(6),
            'first_name' => 'Test',
            'last_name' => ucfirst($role),
            'email' => Str::random(10) . '@test.local',
            'password' => bcrypt('password'),
        ]);

        return $user->assignRole($role);
    }
}
