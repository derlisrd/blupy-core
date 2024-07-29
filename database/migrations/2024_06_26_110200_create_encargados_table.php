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
        Schema::create('encargados', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('supervisor_id')->unsigned()->nullable();
            $table->string('nombre_encargado');
            $table->string('cedula_encargado');
            $table->string('puntos');
            $table->foreign('supervisor_id')->references('id')->on('supervisores');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encargados');
    }
};
