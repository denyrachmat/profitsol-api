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
        Schema::connection('sqlsrv_ems2')->create('TYOA_BC_MSTR', function (Blueprint $table) {
            $table->id();
            $table->string('TYOAM_PONO');
            $table->string('TYOAM_ITMCD');
            $table->integer('TYOAM_QTY');
            $table->string('TYOAM_JOBNO');
            $table->date('TYOAM_DLVDT');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_ems2')->dropIfExists('TYOA_BC_MSTR');
    }
};
