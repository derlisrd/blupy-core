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
            $table->integer('numero_tarjeta')->nullable()->after('mae_cuenta_id');
            $table->renameColumn('mae_cuenta_id', 'cuenta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adicionales', function (Blueprint $table) {
            $table->dropColumn('numero_tarjeta');
            $table->renameColumn('cuenta', 'mae_cuenta_id');
        });
    }
};
