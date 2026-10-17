<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 19: mantenimiento preventivo. Planes generales (por km y/o días) y su
 * seguimiento por vehículo, con intervalos ajustables para un vehículo puntual.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            // Servicio del catálogo que cumple el plan (registro automático desde la OT).
            $table->foreignId('service_id')->nullable()->constrained('services')->restrictOnDelete();
            $table->unsignedInteger('interval_km')->nullable();
            $table->unsignedSmallInteger('interval_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vehicle_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('maintenance_plan_id')->constrained('maintenance_plans')->restrictOnDelete();

            // Intervalos propios del vehículo (null = los del plan).
            $table->unsignedInteger('interval_km')->nullable();
            $table->unsignedSmallInteger('interval_days')->nullable();

            $table->date('last_done_at')->nullable();
            $table->unsignedInteger('last_done_mileage')->nullable();
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->date('next_due_at')->nullable();
            $table->unsignedInteger('next_due_mileage')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'maintenance_plan_id']);
            $table->index('next_due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenances');
        Schema::dropIfExists('maintenance_plans');
    }
};
