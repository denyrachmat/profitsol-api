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
        Schema::connection('sqlsrv_log')->create('HSCD_BEAGRP_DET', function (Blueprint $table) {
            $table->id();
            $table->string('HSCD_BEADOCNM');
            $table->integer('HSCD_BEAGRP_PRNT')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_log')->dropIfExists('HSCD_BEAGRP_DET');
    }
};
