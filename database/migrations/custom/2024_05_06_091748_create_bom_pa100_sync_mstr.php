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
        Schema::connection('sqlsrv_pc')->create('BOM_PA100_SYNC_MSTR', function (Blueprint $table) {
            $table->id();
            $table->string('BPSM_ITMCD');
            $table->string('BPSM_MDLCD');
            $table->string('BPSM_REV');
            $table->integer('BPSM_STAT');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_pc')->dropIfExists('BOM_PA100_SYNC_MSTR');
    }
};
