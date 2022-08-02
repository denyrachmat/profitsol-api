<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDmsDocMstrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->create('dms_doc_mstr', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->string('dfm_id');
            $table->string('ddm_doc_name');
            $table->string('ddm_doc_real_name');
            $table->integer('ddm_doc_size');
            $table->boolean('ddm_doc_flag');
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
        Schema::dropIfExists('dms_doc_mstr');
    }
}
