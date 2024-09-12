<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZINTRDATA extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_log')->create('Z_INTR_DATA_MSTR', function (Blueprint $table) {
            $table->id();
            $table->string('ZID_HSCODE');
            $table->string('ZID_BAGIAN');
            $table->string('ZID_BAB');
            $table->string('ZID_HSPRNT');
            $table->text('ZID_HSPRNT_DESC_ID');
            $table->text('ZID_HSPRNT_DESC_EN');
            $table->string('ZID_HSPRNT_FRMT');
            $table->text('ZID_HSPRNT_FRMT_DESC_ID');
            $table->text('ZID_HSPRNT_FRMT_DESC_END');
            $table->string('ZID_MFN_BMMFN')->nullable();
            $table->string('ZID_MFN_BM')->nullable();
            $table->string('ZID_MFN_PPN')->nullable();
            $table->string('ZID_MFN_PPH')->nullable();
            $table->string('ZID_MFN_BMPPN')->nullable();
            $table->string('ZID_MFN_CUKAI')->nullable();
            $table->boolean('ZID_KOND');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('sqlsrv_log')->dropIfExists('Z_INTR_DATA_MSTR');
    }
}
