<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 15: timbrados (Paraguay). Numeración 001-001-0000001 =
 * establecimiento - punto de expedición - correlativo.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_stamps', function (Blueprint $table) {
            $table->id();
            $table->string('stamp_number', 8);
            $table->string('establishment', 3);
            $table->string('expedition_point', 3);
            $table->date('valid_from');
            $table->date('valid_until');
            $table->unsignedInteger('next_number')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['stamp_number', 'establishment', 'expedition_point']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_stamps');
    }
};
