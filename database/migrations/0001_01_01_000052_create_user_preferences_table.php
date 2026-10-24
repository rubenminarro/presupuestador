<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preferencias visuales de cada usuario (tema, colores, tipografía, layout y
 * navegación del frontend). Una fila por usuario; settings guarda solo los
 * valores elegidos y el resto se completa con los predeterminados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->json('settings');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
