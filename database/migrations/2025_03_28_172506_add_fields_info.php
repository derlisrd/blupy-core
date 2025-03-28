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
        Schema::table('informaciones', function (Blueprint $table) {
            $table->boolean('digital')->after('general')->default(0);
            $table->boolean('aso')->after('digital')->default(0);
            $table->boolean('farma')->after('aso')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('informaciones', function (Blueprint $table) {
            $table->dropColumn('digital');
            $table->dropColumn('aso');
            $table->dropColumn('farma');
        });
    }
};
