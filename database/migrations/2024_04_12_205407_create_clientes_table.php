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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('cliid')->nullable();
            $table->string('nombre_primero');
            $table->string('nombre_segundo')->nullable();
            $table->string('apellido_primero');
            $table->string('apellido_segundo')->nullable();
            $table->date('fecha_nacimiento');
            $table->string('cedula',12);
            $table->string('celular');
            $table->string('foto_ci_frente')->nullable();
            $table->string('foto_ci_dorso')->nullable();
            $table->string('departamento')->nullable();
            $table->string('departamento_id')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('ciudad_id')->nullable();
            $table->string('barrio')->nullable();
            $table->string('barrio_id')->nullable();
            $table->text('calle')->nullable();
            $table->string('numero_casa', 100)->nullable();
            $table->text('referencia_direccion')->nullable();
            $table->string('latitud_direccion')->nullable();
            $table->string('longitud_direccion')->nullable();
            $table->string('empresa')->nullable();
            $table->string('tipo_empresa')->nullable();
            $table->string('tipo_empresa_id')->nullable();
            $table->string('empresa_direccion')->nullable();
            $table->string('latitud_empresa')->nullable();
            $table->string('longitud_empresa')->nullable();
            $table->string('empresa_departamento')->nullable();
            $table->string('empresa_departamento_id')->nullable();
            $table->string('empresa_ciudad')->nullable();
            $table->string('empresa_ciudad_id')->nullable();
            $table->string('empresa_barrio')->nullable();
            $table->string('empresa_barrio_id')->nullable();
            $table->string('empresa_telefono', 100)->nullable();
            $table->string('empresa_celular', 100)->nullable();
            $table->string('profesion_id')->nullable();
            $table->string('profesion')->nullable();
            $table->bigInteger('salario')->nullable();
            $table->string('antiguedad_laboral', 100)->nullable();
            $table->string('antiguedad_laboral_mes', 100)->nullable();
            $table->string('empresa_email')->nullable();
            $table->boolean('asofarma')->default(0);
            $table->boolean('linea_farma')->default(0);
            $table->bigInteger('importe_credito_farma')->default(0);
            $table->boolean('direccion_completado')->default(0);
            $table->boolean('funcionario')->default(0);
            $table->boolean('solicitud_credito')->default(0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
