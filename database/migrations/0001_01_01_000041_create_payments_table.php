<?php

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 16: pagos contra facturas emitidas (guaraníes enteros).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();

            // Recibo numerado: solo en pagos de facturas a crédito.
            $table->unsignedInteger('receipt_sequence')->nullable()->unique();
            $table->string('receipt_number', 15)->nullable()->unique();

            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->string('bank', 100)->nullable();
            $table->text('notes')->nullable();

            $table->string('status', 20)->default(PaymentStatus::REGISTERED->value);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
            $table->index(['payment_date', 'method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
