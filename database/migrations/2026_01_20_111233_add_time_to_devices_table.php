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
        Schema::table('devices', function (Blueprint $table) {
            $table->renameColumn('notitoken', 'device_id_app');
            $table->string('time')->after('user_id')->nullable();
            $table->string('build_version')->after('version')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->renameColumn('device_id_app', 'notitoken');
            $table->dropColumn('time');
            $table->dropColumn('build_version');
        });
    }
};
