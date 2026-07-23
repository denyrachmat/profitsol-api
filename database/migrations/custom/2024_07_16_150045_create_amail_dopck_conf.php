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
        Schema::connection('sqlsrv_pc')->create('AMAIL_DOPCK_CONF', function (Blueprint $table) {
            $table->id();
            $table->string('AMDC_BSGRP');
            $table->string('AMDC_DELCD');
            $table->string('AMDC_EMAIL');
            $table->string('AMDC_EMAILTYPE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amail_dopck_conf');
    }
};
