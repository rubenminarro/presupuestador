<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 13.4–13.6: tiempo estimado, horas reales y costo del mecánico.
 * Todo es opcional: el taller puede operar sin registrar horas.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('estimated_hours', 6, 2)->nullable()->after('standard_price');
        });

        Schema::table('budget_items', function (Blueprint $table) {
            $table->decimal('estimated_hours', 8, 2)->nullable()->after('total');
        });

        Schema::table('work_order_item_mechanics', function (Blueprint $table) {
            $table->decimal('hours', 6, 2)->nullable()->after('commission_percentage');
            $table->decimal('hour_cost', 10, 2)->nullable()->after('hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_item_mechanics', function (Blueprint $table) {
            $table->dropColumn(['hours', 'hour_cost']);
        });

        Schema::table('budget_items', function (Blueprint $table) {
            $table->dropColumn('estimated_hours');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('estimated_hours');
        });
    }
};
