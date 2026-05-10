<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\StatusPriority;
use App\Enums\Status;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('diagnostics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mechanic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('customer_complaint')->nullable();
            $table->longText('diagnosis')->nullable();
            $table->longText('recommendation')->nullable();
            $table->string('priority')->default(StatusPriority::MEDIUM->value);
            $table->string('status')->default(Status::PENDING->value);
            $table->boolean('requires_parts')->default(false);
            $table->boolean('requires_repair')->default(false);
            $table->timestamp('diagnosed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostics');
    }
};
