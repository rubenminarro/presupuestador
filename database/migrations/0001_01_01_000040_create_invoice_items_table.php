<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 15: líneas de factura. base_unit_price (sin IVA) se conserva para
 * recalcular el precio con IVA si cambia la tasa en borrador.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->morphs('source');
            $table->string('description', 255);
            $table->decimal('quantity', 12, 3);
            $table->decimal('base_unit_price', 14, 2);
            $table->decimal('base_total', 14, 2);
            $table->unsignedTinyInteger('tax_rate');
            $table->decimal('unit_price', 14, 2);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
