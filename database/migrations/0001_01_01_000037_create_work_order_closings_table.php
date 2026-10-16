<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 14: cierre económico de la OT. Al completarse se congelan
 * venta, costos, margen y comisiones. Importes sin IVA.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_order_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->unique()->constrained('work_orders')->restrictOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('closed_at');
            $table->decimal('budgeted_labor', 12, 2);
            $table->decimal('budgeted_parts', 12, 2);
            $table->decimal('budgeted_total', 12, 2);
            $table->decimal('sale_labor', 12, 2);
            $table->decimal('sale_parts', 12, 2);
            $table->decimal('sale_total', 12, 2);
            $table->decimal('cost_parts', 12, 2);
            $table->decimal('cost_labor', 12, 2)->nullable();
            $table->decimal('commission_total', 12, 2);
            $table->decimal('cost_total', 12, 2);
            $table->decimal('gross_margin', 12, 2);
            $table->decimal('margin_percentage', 7, 2)->nullable();
            $table->boolean('is_cost_complete');
            $table->timestamps();
        });

        Schema::create('work_order_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->restrictOnDelete();
            $table->foreignId('work_order_item_id')->constrained('work_order_items')->restrictOnDelete();
            $table->foreignId('mechanic_id')->constrained('mechanics')->restrictOnDelete();
            $table->decimal('base_amount', 12, 2);
            $table->decimal('share_percentage', 5, 2);
            $table->decimal('commission_percentage', 5, 2);
            $table->decimal('amount', 12, 2);
            $table->timestamp('earned_at');
            $table->timestamp('settled_at')->nullable();
            $table->foreignId('settled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['work_order_item_id', 'mechanic_id']);
            $table->index(['mechanic_id', 'earned_at']);
        });

        // Solo estructura: una migración no debe depender de código de la app
        // (que evoluciona con el esquema). Las OT completadas antes de esta fase
        // se cierran con el comando `php artisan work-orders:close-completed`.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_commissions');
        Schema::dropIfExists('work_order_closings');
    }
};
