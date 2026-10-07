<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\PartStatus;
use App\Enums\PartUnit;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('sku', 100)->nullable()->unique();
            $table->string('part_number', 100)->nullable();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('brand', 100)->nullable();
            $table->foreignId('part_category_id')->constrained('part_categories')->restrictOnDelete();
            $table->string('unit', 20)->default(PartUnit::UNIT->value);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->decimal('minimum_stock', 10, 3)->default(0);
            $table->string('status', 20)->default(PartStatus::ACTIVE->value);
            $table->softDeletes();
            $table->timestamps();

            $table->index('part_number');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
