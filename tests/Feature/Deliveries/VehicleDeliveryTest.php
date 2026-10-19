<?php

namespace Tests\Feature\Deliveries;

use App\Models\Reception;
use App\Models\Vehicle;
use App\Models\VehicleDelivery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Invoices\InvoiceTestCase;

class VehicleDeliveryTest extends InvoiceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->actingAsAdmin();
        $this->createStamp()->assertCreated();
    }

    /**
     * Recepción con una OT completada y facturada. $paid: monto a pagar
     * (null = el total de la factura, 0 = nada).
     *
     * @return array{reception_id: int, invoice: array}
     */
    private function invoicedReception(string $condition = 'cash', ?int $paid = null): array
    {
        $workOrder = $this->completedWorkOrder();
        $invoice = $this->generateInvoice($workOrder);

        $this->patchJson("/api/invoices/{$invoice['id']}", [
            'customer_tax_id' => '80012345-6',
            'condition' => $condition,
            'due_date' => $condition === 'credit' ? now()->addDays(30)->toDateString() : null,
        ])->assertOk();

        $invoice = $this->postJson("/api/invoices/{$invoice['id']}/issue")->assertOk()->json('data');

        $amount = $paid ?? (int) $invoice['totals']['total'];

        if ($amount > 0) {
            $this->postJson("/api/invoices/{$invoice['id']}/payments", ['amount' => $amount, 'method' => 'cash'])->assertCreated();
        }

        return ['reception_id' => $workOrder['reception_id'], 'invoice' => $invoice];
    }

    private function payload(array $overrides = []): array
    {
        return [
            'final_mileage' => 46000,
            'fuel_level' => 'half',
            'received_by_name' => 'Ana Gomez',
            'received_by_document' => '1234567',
            'customer_accepted' => true,
            'observations' => 'Se entrega lavado.',
            ...$overrides,
        ];
    }

    private function deliver(int $receptionId, array $overrides = [])
    {
        return $this->postJson("/api/receptions/{$receptionId}/delivery", $this->payload($overrides));
    }

    public function test_paid_cash_invoice_allows_delivery(): void
    {
        ['reception_id' => $receptionId] = $this->invoicedReception();

        $reception = Reception::findOrFail($receptionId);
        $reception->update(['mileage' => 45000]);

        $delivery = $this->deliver($receptionId, ['signature' => UploadedFile::fake()->image('firma.png')])
            ->assertCreated()
            ->assertJsonPath('data.delivery_number', 'ENT-000001')
            ->assertJsonPath('data.status.value', 'delivered')
            ->assertJsonPath('data.final_mileage', 46000)
            ->assertJsonPath('data.received_by.name', 'Ana Gomez')
            ->assertJsonPath('data.customer_accepted', true)
            ->assertJsonPath('data.reception.status', 'delivered')
            ->assertJsonPath('data.vehicle.mileage', 46000)
            ->json('data');

        $this->assertNotNull($delivery['signature_url']);
        Storage::disk('public')->assertExists(VehicleDelivery::findOrFail($delivery['id'])->signature_path);

        $this->getJson("/api/vehicle-deliveries/{$delivery['id']}")->assertOk()->assertJsonPath('data.client.id', $reception->client_id);
        $this->getJson("/api/vehicle-deliveries?reception_id={$receptionId}")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/vehicle-deliveries?search=ENT-000001')->assertOk()->assertJsonCount(1, 'data');

        $this->deliver($receptionId)
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'DELIVERY_ALREADY_REGISTERED');
    }

    public function test_cash_invoice_must_be_fully_paid(): void
    {
        ['reception_id' => $unpaid] = $this->invoicedReception('cash', 0);
        $this->deliver($unpaid)->assertStatus(422)->assertJsonPath('error_code', 'DELIVERY_INVOICE_NOT_PAID');

        ['reception_id' => $partial] = $this->invoicedReception('cash', 1000);
        $this->deliver($partial)->assertStatus(422)->assertJsonPath('error_code', 'DELIVERY_INVOICE_NOT_PAID');

        $this->assertSame(0, VehicleDelivery::count());
    }

    public function test_credit_invoice_allows_delivery_with_balance(): void
    {
        ['reception_id' => $receptionId] = $this->invoicedReception('credit', 0);

        $this->deliver($receptionId)->assertCreated()->assertJsonPath('data.status.value', 'delivered');
    }

    public function test_work_orders_must_be_completed_and_invoiced(): void
    {
        // Sin OT.
        $budgetId = $this->createBudgetWithItems([['type' => 'labor', 'description' => 'Trabajo', 'quantity' => 1, 'unit_price' => 1000]]);
        $receptionId = \App\Models\Budget::findOrFail($budgetId)->reception_id;
        $this->deliver($receptionId)->assertStatus(422)->assertJsonPath('error_code', 'DELIVERY_WITHOUT_COMPLETED_WORK_ORDER');

        // OT abierta.
        $this->approveBudget($budgetId);
        $workOrder = $this->createWorkOrder($budgetId);
        $this->deliver($workOrder['reception_id'])->assertStatus(422)->assertJsonPath('error_code', 'DELIVERY_WORK_ORDERS_OPEN');

        // OT completada sin factura y con factura en borrador.
        $completed = $this->completedWorkOrder();
        $this->deliver($completed['reception_id'])->assertStatus(422)->assertJsonPath('error_code', 'DELIVERY_WORK_ORDER_NOT_INVOICED');

        $this->generateInvoice($completed);
        $this->deliver($completed['reception_id'])->assertStatus(422)->assertJsonPath('error_code', 'DELIVERY_WORK_ORDER_NOT_INVOICED');
    }

    public function test_delivery_validation(): void
    {
        ['reception_id' => $receptionId] = $this->invoicedReception();

        $this->postJson("/api/receptions/{$receptionId}/delivery", [])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['final_mileage', 'received_by_name', 'received_by_document', 'customer_accepted']);

        $this->deliver($receptionId, ['customer_accepted' => false])->assertJsonValidationErrors(['customer_accepted']);
        $this->deliver($receptionId, ['fuel_level' => 'lleno'])->assertJsonValidationErrors(['fuel_level']);
        $this->deliver($receptionId, ['final_mileage' => -1])->assertJsonValidationErrors(['final_mileage']);
        $this->deliver($receptionId, ['received_by_name' => 'Ana 123'])->assertJsonValidationErrors(['received_by_name']);
        $this->deliver($receptionId, ['signature' => UploadedFile::fake()->create('firma.pdf', 10, 'application/pdf')])
            ->assertJsonValidationErrors(['signature']);

        Reception::whereKey($receptionId)->update(['mileage' => 50000]);

        $this->deliver($receptionId, ['final_mileage' => 49000])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'DELIVERY_MILEAGE_BELOW_RECEPTION');

        $this->deliver($receptionId, ['received_by_document' => 'RUC 800!'])->assertJsonValidationErrors(['received_by_document']);

        $this->deliver($receptionId, ['final_mileage' => 50000])->assertCreated();
    }

    public function test_vehicle_mileage_only_moves_forward(): void
    {
        ['reception_id' => $receptionId] = $this->invoicedReception();
        $vehicleId = Reception::findOrFail($receptionId)->vehicle_id;

        Vehicle::whereKey($vehicleId)->update(['mileage' => 60000]);

        $this->deliver($receptionId, ['final_mileage' => 46000])->assertCreated();

        $this->assertSame(60000, Vehicle::findOrFail($vehicleId)->mileage);
    }

    public function test_cancelling_restores_the_reception(): void
    {
        ['reception_id' => $receptionId] = $this->invoicedReception();

        $delivery = $this->deliver($receptionId)->assertCreated()->json('data');

        $this->postJson("/api/vehicle-deliveries/{$delivery['id']}/cancel", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        $this->postJson("/api/vehicle-deliveries/{$delivery['id']}/cancel", ['reason' => 'Se registró por error'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'cancelled')
            ->assertJsonPath('data.cancellation_reason', 'Se registró por error')
            ->assertJsonPath('data.reception.status', 'pending');

        $this->postJson("/api/vehicle-deliveries/{$delivery['id']}/cancel", ['reason' => 'Otra vez'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'DELIVERY_NOT_CANCELLABLE');

        // Se puede volver a entregar; el número anulado no se reutiliza.
        $this->deliver($receptionId)->assertCreated()->assertJsonPath('data.delivery_number', 'ENT-000002');

        $this->getJson('/api/vehicle-deliveries?status=cancelled')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_cancelling_is_blocked_when_the_vehicle_has_another_open_reception(): void
    {
        ['reception_id' => $receptionId] = $this->invoicedReception();

        $delivery = $this->deliver($receptionId)->assertCreated()->json('data');
        $delivered = Reception::findOrFail($receptionId);

        // El vehículo vuelve a ingresar al taller.
        $returned = Reception::create([
            'client_id' => $delivered->client_id,
            'vehicle_id' => $delivered->vehicle_id,
            'reception_date' => now()->toDateString(),
        ]);

        $this->postJson("/api/vehicle-deliveries/{$delivery['id']}/cancel", ['reason' => 'Se registró por error'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'DELIVERY_VEHICLE_HAS_OPEN_RECEPTION');

        // Nada cambió: la entrega sigue vigente y la recepción entregada.
        $this->assertSame('delivered', VehicleDelivery::findOrFail($delivery['id'])->status->value);
        $this->assertSame('delivered', $delivered->fresh()->status->value);

        // Cancelada la nueva recepción, la entrega se puede anular.
        $returned->update(['status' => 'cancelled']);

        $this->postJson("/api/vehicle-deliveries/{$delivery['id']}/cancel", ['reason' => 'Se registró por error'])
            ->assertOk()
            ->assertJsonPath('data.reception.status', 'pending');
    }

    public function test_deliveries_are_immutable(): void
    {
        ['reception_id' => $receptionId] = $this->invoicedReception();
        $delivery = VehicleDelivery::findOrFail($this->deliver($receptionId)->json('data.id'));

        $this->expectExceptionMessage('solo puede anularse');

        $delivery->update(['final_mileage' => 1]);
    }

    public function test_mechanic_cannot_manage_deliveries(): void
    {
        ['reception_id' => $receptionId] = $this->invoicedReception();

        $this->actingAsMechanic();

        $this->deliver($receptionId)->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
        $this->getJson('/api/vehicle-deliveries')->assertForbidden();
    }
}
