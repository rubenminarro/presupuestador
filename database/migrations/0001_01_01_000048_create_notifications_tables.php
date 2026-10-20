<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notificaciones (FASE 23): bandeja interna de los usuarios (tabla estándar de
 * Laravel), registro de avisos a clientes y baja de avisos del cliente.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('client_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->string('type', 30);
            $table->string('channel', 20);
            // Documento que origina el aviso (presupuesto, recepción, factura, mantenimiento).
            $table->morphs('subject');
            // Destino al momento de registrar el aviso (null si el cliente no tiene).
            $table->string('recipient', 100)->nullable();
            $table->string('status', 20)->default('pending');
            // Motivo de la omisión o del fallo del envío.
            $table->text('error')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            // Evita repetir un aviso automático (null en los avisos que pueden repetirse).
            $table->string('dedupe_key', 150)->nullable()->unique();
            $table->foreignId('resent_from_id')->nullable()->constrained('client_notifications')->restrictOnDelete();
            // Usuario que reenvió el aviso (null en los automáticos).
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'created_at']);
            $table->index(['type', 'status']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('notifications_enabled')->default(true)->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('notifications_enabled');
        });

        Schema::dropIfExists('client_notifications');
        Schema::dropIfExists('notifications');
    }
};
