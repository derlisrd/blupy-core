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
        Schema::create('comisiones', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('cliente_id')->unsigned();
            $table->bigInteger('vendedor_id')->nullable()->unsigned();
            $table->string('cedula')->nullable();
            $table->string('usuario')->nullable();
            $table->string('tipo');
            $table->foreign('cliente_id')->references('id')->on('clientes');
            $table->foreign('vendedor_id')->references('id')->on('vendedores');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comisiones');
    }
};
