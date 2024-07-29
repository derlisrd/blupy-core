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
        Schema::create('vendedores', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('encargado_id')->unsigned()->nullable();
            $table->string('cedula')->unique();
            $table->text('nombre');
            $table->string('punto');
            $table->text('direccion')->nullable();
            $table->string('organigrama')->nullable();
            $table->bigInteger('qr_generado')->default(0);
            $table->foreign('encargado_id')->references('id')->on('encargados');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendedores');
    }
};
