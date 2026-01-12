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
        Schema::create('device_new_requests', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->string('ip')->nullable();
            $table->string('device')->nullable();
            $table->string('location')->nullable();
            $table->string('celular')->nullable();
            $table->string('email')->nullable();
            $table->string('cedula_frente_url')->nullable();
            $table->string('cedula_dorso_url')->nullable();
            $table->string('cedula_selfie_url')->nullable();
            $table->string('os')->nullable();
            $table->string('model')->nullable();
            $table->string('version')->nullable();
            $table->boolean('web')->default(0);
            $table->boolean('desktop')->default(0);
            $table->text('devicetoken')->nullable();
            $table->boolean('aprobado')->default(0);
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_new_requests');
    }
};
