<?php

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
        Schema::create('reception_checklists', function (Blueprint $table) {
            
            $table->id();
            $table->foreignId('reception_id')->constrained()->cascadeOnDelete();
            $table->string('item', 150);
            $table->enum('status', [
                'good',
                'damaged',
                'missing',
                'observed',
            ])->default('good');
            $table->text('notes')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reception_checklists');
    }
};