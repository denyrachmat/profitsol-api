<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDmsDocTagsMap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->create('dms_doc_tags_map', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->integer('tm_id');
            $table->integer('ddm_id')->nullable();
            $table->integer('dfm_id')->nullable();
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
        Schema::dropIfExists('dms_doc_tags_map');
    }
}
