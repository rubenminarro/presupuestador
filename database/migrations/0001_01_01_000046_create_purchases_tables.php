<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compras a proveedores: la factura del proveedor (costos con IVA incluido) se
 * carga en borrador y al recibirla entra el stock al costo sin IVA.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('status', 20)->default('draft');

            // Factura del proveedor: obligatoria para recibir.
            $table->string('supplier_document_number', 50)->nullable();
            $table->date('supplier_document_date')->nullable();
            $table->string('supplier_stamp_number', 20)->nullable();

            $table->string('condition', 10)->default('cash');
            $table->date('due_date')->nullable();

            // Importes con IVA incluido salvo net_total.
            $table->decimal('total_exempt', 14, 2)->default(0);
            $table->decimal('total_5', 14, 2)->default(0);
            $table->decimal('total_10', 14, 2)->default(0);
            $table->decimal('iva_5', 14, 2)->default(0);
            $table->decimal('iva_10', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('net_total', 14, 2)->default(0);

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['supplier_id', 'supplier_document_number']);
            $table->index(['status', 'supplier_document_date']);
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('parts')->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 14, 2);
            $table->unsignedTinyInteger('tax_rate');
            $table->decimal('total', 14, 2);
            $table->decimal('net_unit_cost', 12, 4);
            $table->foreignId('inventory_movement_id')->nullable()->constrained('inventory_movements')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};
