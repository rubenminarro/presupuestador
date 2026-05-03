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
        Schema::create('reception_check_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_check_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('check_list_item_id')->constrained()->restrictOnDelete();
            $table->string('value')->nullable(); 
            $table->text('observation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reception_check_list_items');
    }
};
