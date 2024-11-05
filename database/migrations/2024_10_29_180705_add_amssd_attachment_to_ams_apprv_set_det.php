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
        Schema::connection('sqlsrv_ams')->table('ams_apprv_set_det', function (Blueprint $table) {
            $table->boolean('amssd_attachment')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_ams')->table('ams_apprv_set_det', function (Blueprint $table) {
            $table->boolean('amssd_attachment')->default(0);
        });
    }
};
