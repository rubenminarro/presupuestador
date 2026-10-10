<?php

use App\Enums\WorkOrderPartStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_order_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->restrictOnDelete();
            $table->foreignId('work_order_item_id')->nullable()->constrained('work_order_items')->restrictOnDelete();
            $table->foreignId('part_id')->constrained('parts')->restrictOnDelete();
            $table->string('status', 20)->default(WorkOrderPartStatus::PENDING->value);
            $table->decimal('quantity', 12, 3);
            $table->decimal('quantity_consumed', 12, 3)->default(0);
            $table->decimal('quantity_returned', 12, 3)->default(0);
            $table->decimal('unit_cost', 12, 4)->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->text('notes')->nullable();
            $table->foreignId('added_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('consumed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['work_order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_parts');
    }
};
