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
        Schema::connection('sqlsrv_ams')->create('ams_apprv_attch_hist_det', function (Blueprint $table) {
            $table->id();
            $table->integer('amshd_id');
            $table->string('amaad_source');
            $table->string('amaad_filename');
            $table->string('amaad_path');
            $table->string('amaad_size');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_ams')->dropIfExists('ams_apprv_attch_hist_det');
    }
};
