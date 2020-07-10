<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsComponentsMstr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->create('dms_comp_mstr', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('comp_group_id');
            $table->string('comp_apprv_id');
            $table->string('comp_name');
            $table->string('comp_label');
            $table->string('comp_type');
            $table->string('comp_data');
            $table->boolean('comp_req');
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
        //
    }
}
