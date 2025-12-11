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
        Schema::create('terminos_aceptados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')
                  ->nullable()
                  ->constrained('clientes')
                  ->nullOnDelete();
            $table->string('cedula')->nullable();
            $table->text('telefono')->nullable();
            $table->string('termino_tipo')->nullable();   // ej: 'general', 'crediticio'
            $table->string('version')->nullable();        // ej: 'v1.0', '2025-01'
            $table->text('enlace')->nullable();
            $table->boolean('aceptado')->default(true);
            $table->timestamp('aceptado_fecha')->useCurrent();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terminos_aceptados');
    }
};
