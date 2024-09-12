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
        Schema::connection('sqlsrv_ams')->create('ams_apprv_map_det', function (Blueprint $table) {
            $table->id();
            $table->integer('amsm_id');
            $table->string('amsmd_username');
            $table->integer('amsmd_order');
            $table->boolean('amsmd_reqaprv')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_ams')->dropIfExists('ams_apprv_map_det');
    }
};
