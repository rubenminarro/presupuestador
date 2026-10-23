<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un ítem de checklist usado en recepciones no se elimina: se desactiva y deja de entrar en los checklists nuevos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_list_items', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('required');
        });
    }

    public function down(): void
    {
        Schema::table('check_list_items', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
