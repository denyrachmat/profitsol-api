<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDmsDocApprvMap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->create('dms_doc_apprv_map', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->integer('ddm_id')->nullable();
            $table->integer('dfm_id')->nullable();
            $table->integer('ams_id');
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
        Schema::dropIfExists('dms_doc_apprv_map');
    }
}
