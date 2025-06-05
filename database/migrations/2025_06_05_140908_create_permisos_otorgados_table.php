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
        Schema::create('permisos_otorgados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->onDelete('cascade');
            $table->foreignId('permiso_id')->constrained('permisos')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['admin_id', 'permiso_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permisos_otorgados');
    }
};
