<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsFormApprovalLogic extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->create('dms_logic_mstr', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('logic_id');
            $table->string('apprv_id');
            $table->string('comp_group_id');
            $table->string('comp_name');
            $table->string('logic_cond');
            $table->string('logic_value');
            $table->boolean('logic_apprv_flag');
            $table->boolean('logic_notif_flag');
            $table->boolean('logic_only_flag');
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
