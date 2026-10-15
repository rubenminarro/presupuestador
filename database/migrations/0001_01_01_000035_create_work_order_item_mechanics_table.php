<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_order_item_mechanics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_item_id')->constrained('work_order_items')->cascadeOnDelete();
            $table->foreignId('mechanic_id')->constrained('mechanics')->restrictOnDelete();
            $table->decimal('share_percentage', 5, 2);
            $table->decimal('commission_percentage', 5, 2);
            $table->timestamps();

            $table->unique(['work_order_item_id', 'mechanic_id']);
        });

        // Las OT existentes asignan sus trabajos (labor/service) al mecánico responsable.
        DB::table('work_order_item_mechanics')->insertUsing(
            ['work_order_item_id', 'mechanic_id', 'share_percentage', 'commission_percentage', 'created_at', 'updated_at'],
            DB::table('work_order_items')
                ->join('work_orders', 'work_orders.id', '=', 'work_order_items.work_order_id')
                ->join('mechanics', 'mechanics.id', '=', 'work_orders.mechanic_id')
                ->whereIn('work_order_items.type', ['labor', 'service'])
                ->whereNull('work_order_items.deleted_at')
                ->select([
                    'work_order_items.id',
                    'work_orders.mechanic_id',
                    DB::raw('100'),
                    'mechanics.commission_percentage',
                    DB::raw('CURRENT_TIMESTAMP'),
                    DB::raw('CURRENT_TIMESTAMP'),
                ])
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_item_mechanics');
    }
};
