<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pagos a proveedores contra compras recibidas (guaraníes enteros).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('purchase_id')->constrained('purchases')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();

            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->string('bank', 100)->nullable();
            // Recibo que entrega el proveedor (opcional).
            $table->string('supplier_receipt_number', 50)->nullable();
            $table->text('notes')->nullable();

            $table->string('status', 20)->default('registered');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['purchase_id', 'status']);
            $table->index(['supplier_id', 'status']);
            $table->index(['payment_date', 'method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
