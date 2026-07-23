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
        Schema::connection('sqlsrv_pc')->create('AMAIL_DOPCK_LOG', function (Blueprint $table) {
            $table->id();
            $table->string('AMDL_LOCCD');
            $table->string('AMDL_BSGRP');
            $table->string('AMDL_DONO');
            $table->string('AMDL_DELCD');
            $table->string('AMDL_STAT');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amail_dopck_log');
    }
};
