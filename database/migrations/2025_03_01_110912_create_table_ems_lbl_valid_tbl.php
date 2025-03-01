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
        Schema::connection('sqlsrv_it')->create('EMS_LBL_VALID', function (Blueprint $table) {
            $table->id();
            $table->string('MBCSCNH_ITMCD');
            $table->integer('MBCSCNH_QTY');
            $table->string('MBCSCNH_LOT');
            $table->string('MBCSCNH_VALID');
            $table->string('MBCSCNH_REMARKS');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_it')->dropIfExists('EMS_LBL_VALID');
    }
};
