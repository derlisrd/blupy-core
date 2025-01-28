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
        Schema::table('validaciones', function (Blueprint $table) {
            $table->string('origen',25)->nullable()->after('validado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('validaciones', function (Blueprint $table) {
            $table->dropColumn('origen');
        });
    }
};
