<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZINTRSATDET extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_log')->create('Z_INTR_SAT_DET', function (Blueprint $table) {
            $table->id();
            $table->string('ZID_HSCODE');
            $table->string('ZISD_TYPE');
            $table->string('ZISD_SERI');
            $table->string('ZISD_JENIS');
            $table->string('ZISD_SATUAN');
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
        Schema::connection('sqlsrv_log')->dropIfExists('Z_INTR_SAT_DET');
    }
}
