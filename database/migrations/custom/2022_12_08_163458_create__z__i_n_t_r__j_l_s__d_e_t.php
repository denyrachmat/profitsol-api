<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZINTRJLSDET extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_log')->create('Z_INTR_JLS_DET', function (Blueprint $table) {
            $table->id();
            $table->string('ZID_HSCODE');
            $table->text('ZIJD_DET_ID');
            $table->text('ZIJD_DET_EN');
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
        Schema::connection('sqlsrv_log')->dropIfExists('Z_INTR_JLS_DET');
    }
}
