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
        Schema::table('device_new_requests', function (Blueprint $table) {
            $table->string('device_id_app')->after('ip')->nullable();
            $table->string('time')->after('device')->nullable();
            $table->string('build_version')->after('version')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_new_requests', function (Blueprint $table) {
            $table->dropColumn('device_id_app');
            $table->dropColumn('time');
            $table->dropColumn('build_version');
        });
    }
};
