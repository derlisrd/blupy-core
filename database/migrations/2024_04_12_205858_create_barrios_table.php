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
        Schema::create('barrios', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('departamento_id')->unsigned()->nullable();
            $table->bigInteger('ciudad_id')->unsigned()->nullable();
            $table->string('nombre');
            $table->string('codigo', 50);
            $table->timestamps();
            $table->foreign('departamento_id')->references('id')->on('departamentos');
            $table->foreign('ciudad_id')->references('id')->on('ciudades');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barrios');
    }
};
