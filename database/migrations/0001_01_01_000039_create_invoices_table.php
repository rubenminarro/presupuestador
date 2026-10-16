<?php

use App\Enums\InvoiceCondition;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 15: facturas (precios con IVA incluido, importes en guaraníes).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->restrictOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->string('status', 20)->default(InvoiceStatus::DRAFT->value);

            // Numeración: se asigna al emitir.
            $table->foreignId('invoice_stamp_id')->nullable()->constrained('invoice_stamps')->restrictOnDelete();
            $table->string('stamp_number', 8)->nullable();
            $table->date('stamp_valid_until')->nullable();
            $table->string('number', 15)->nullable()->unique();

            // Datos del cliente copiados (editables en borrador).
            $table->string('customer_name', 150);
            $table->string('customer_tax_id', 30)->nullable();
            $table->string('customer_address', 255)->nullable();

            $table->string('condition', 10)->default(InvoiceCondition::CASH->value);
            $table->date('due_date')->nullable();

            $table->decimal('total_exempt', 14, 2)->default(0);
            $table->decimal('total_5', 14, 2)->default(0);
            $table->decimal('total_10', 14, 2)->default(0);
            $table->decimal('iva_5', 14, 2)->default(0);
            $table->decimal('iva_10', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->date('issue_date')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['work_order_id', 'status']);
            $table->index('issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
