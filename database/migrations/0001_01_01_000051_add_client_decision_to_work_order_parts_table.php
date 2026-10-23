<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repuestos extra (no presupuestados) con la decisión registrada del cliente.
 *
 * Además, con SQL puro (las migraciones no llaman a código de la aplicación),
 * los extras todavía abiertos (pending|reserved) pasan a awaiting_approval y
 * se libera su reserva en el depósito por defecto. Los extras ya consumidos,
 * devueltos o cancelados quedan como están, sin decisión registrada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_order_parts', function (Blueprint $table) {
            $table->string('client_decision', 20)->nullable()->after('unit_price');
            $table->timestamp('decided_at')->nullable()->after('client_decision');
            $table->foreignId('decided_by')->nullable()->after('decided_at')->constrained('users')->restrictOnDelete();
            $table->string('decision_channel', 20)->nullable()->after('decided_by');
            $table->string('decision_contact', 150)->nullable()->after('decision_channel');
            $table->string('decision_notes', 500)->nullable()->after('decision_contact');
        });

        DB::statement("
            UPDATE part_stocks SET quantity_reserved = quantity_reserved - (
                SELECT COALESCE(SUM(wop.quantity), 0) FROM work_order_parts wop
                WHERE wop.part_id = part_stocks.part_id
                    AND wop.work_order_item_id IS NULL AND wop.status = 'reserved'
            )
            WHERE warehouse_id = (SELECT id FROM warehouses WHERE is_default = 1 ORDER BY id LIMIT 1)
        ");

        DB::table('work_order_parts')
            ->whereNull('work_order_item_id')
            ->whereIn('status', ['pending', 'reserved'])
            ->update(['status' => 'awaiting_approval']);
    }

    /**
     * Los extras sin decisión vuelven a pending (sin reserva: se reservan a mano).
     */
    public function down(): void
    {
        DB::table('work_order_parts')
            ->where('status', 'awaiting_approval')
            ->update(['status' => 'pending']);

        Schema::table('work_order_parts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('decided_by');
            $table->dropColumn(['client_decision', 'decided_at', 'decision_channel', 'decision_contact', 'decision_notes']);
        });
    }
};
