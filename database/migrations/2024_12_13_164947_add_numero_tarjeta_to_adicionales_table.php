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
        Schema::table('adicionales', function (Blueprint $table) {
            $table->unsignedBigInteger('numero_tarjeta')->nullable()->after('cuenta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adicionales', function (Blueprint $table) {
            $table->dropColumn('numero_tarjeta');
        });
    }
};
