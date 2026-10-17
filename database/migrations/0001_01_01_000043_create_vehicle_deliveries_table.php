<?php

use App\Enums\VehicleDeliveryStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 17: entrega del vehículo al cliente (cierra la recepción).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicle_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained('receptions')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();

            // Comprobante de entrega: ENT-000001.
            $table->unsignedInteger('delivery_sequence')->unique();
            $table->string('delivery_number', 15)->unique();

            $table->string('status', 20)->default(VehicleDeliveryStatus::DELIVERED->value);
            $table->timestamp('delivered_at');
            $table->foreignId('delivered_by')->constrained('users')->restrictOnDelete();

            $table->unsignedInteger('final_mileage');
            $table->string('fuel_level', 20)->nullable();

            // Quién retira el vehículo (puede no ser el titular).
            $table->string('received_by_name', 150);
            $table->string('received_by_document', 30);
            $table->boolean('customer_accepted');
            $table->string('signature_path')->nullable();
            $table->text('observations')->nullable();

            // Estado de la recepción antes de entregar: se restaura al anular.
            $table->string('previous_reception_status', 20);

            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['reception_id', 'status']);
            $table->index('delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_deliveries');
    }
};
