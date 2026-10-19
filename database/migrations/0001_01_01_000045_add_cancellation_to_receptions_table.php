<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Estados de la recepción derivados del flujo + cancelación manual.
 *
 * Además recalcula el estado de las recepciones existentes con SQL puro
 * (las migraciones no llaman a código de la aplicación). Misma regla que
 * ReceptionStatusService::sync():
 *   alguna OT in_progress|paused → repair; alguna OT pending → in_progress;
 *   OT completadas (resto canceladas) → completed; sin OT vigentes →
 *   in_progress si tiene presupuesto, si no pending.
 * Las recepciones delivered o cancelled no se tocan.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('status');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable()->after('cancelled_by');
        });

        $workOrders = fn (string $statuses) => "EXISTS (SELECT 1 FROM work_orders wo
            WHERE wo.reception_id = receptions.id AND wo.deleted_at IS NULL AND wo.status IN ({$statuses}))";

        DB::statement("
            UPDATE receptions SET status = CASE
                WHEN {$workOrders("'in_progress', 'paused'")} THEN 'repair'
                WHEN {$workOrders("'pending'")} THEN 'in_progress'
                WHEN {$workOrders("'completed'")} THEN 'completed'
                WHEN EXISTS (SELECT 1 FROM budgets b
                    WHERE b.reception_id = receptions.id AND b.deleted_at IS NULL) THEN 'in_progress'
                ELSE 'pending'
            END
            WHERE status NOT IN ('delivered', 'cancelled') AND deleted_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancelled_at', 'cancellation_reason']);
        });
    }
};
