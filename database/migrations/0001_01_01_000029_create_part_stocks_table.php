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
        Schema::create('part_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('parts')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->decimal('quantity_on_hand', 12, 3)->default(0);
            $table->decimal('quantity_reserved', 12, 3)->default(0);
            $table->decimal('average_cost', 12, 4)->default(0);
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamps();

            $table->unique(['part_id', 'warehouse_id']);
        });

        // Saldo en cero para los repuestos existentes en el depósito por defecto.
        $warehouseId = DB::table('warehouses')->where('is_default', true)->value('id');

        DB::table('parts')->orderBy('id')->chunkById(500, function ($parts) use ($warehouseId) {
            DB::table('part_stocks')->insert(
                $parts->map(fn ($part) => [
                    'part_id' => $part->id,
                    'warehouse_id' => $warehouseId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all()
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_stocks');
    }
};
