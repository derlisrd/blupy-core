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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('cliente_id')->unsigned()->nullable();
            $table->string('codigo')->index();
            $table->string('documento');
            $table->string('adicional')->nullable();
            $table->string('factura_numero');
            $table->bigInteger('importe');
            $table->bigInteger('descuento');
            $table->bigInteger('importe_final');
            $table->bigInteger('forma_codigo');
            $table->string('forma_pago');
            $table->text('descripcion')->nullable();
            $table->string('sucursal');
            $table->string('forma_venta');
            $table->dateTime('fecha');
            $table->timestamps();
            $table->foreign('cliente_id')->references('id')->on('clientes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
