<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Severity;
use App\Enums\DiagnosticItemStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('diagnostic_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnostic_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('severity')->default(Severity::MEDIUM->value);
            $table->string('status')->default(DiagnosticItemStatus::PENDING->value);
            $table->boolean('requires_repair')->default(false);
            $table->boolean('requires_replacement')->default(false);
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->integer('estimated_time')->nullable()->comment('Tiempo estimado en minutos');
            $table->text('recommendation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_items');
    }
};
