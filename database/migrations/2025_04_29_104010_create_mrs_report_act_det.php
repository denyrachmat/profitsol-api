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
        Schema::connection('sqlsrv_mrs')->create('mrs_report_act_det', function (Blueprint $table) {
            $table->id();
            $table->string('mrm_id');
            $table->string('mrad_action');
            $table->string('mrad_label');
            $table->string('mrad_url');
            $table->string('mrad_target');
            $table->string('mrad_icon')->nullable();
            $table->integer('mrad_parent_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_mrs')->dropIfExists('mrs_report_act_det');
    }
};
