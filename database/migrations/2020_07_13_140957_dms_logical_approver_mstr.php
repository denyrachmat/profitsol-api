<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsLogicalApproverMstr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->create('dms_logical_mstr', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('logical_id');
            $table->string('apprv_group_id');
            $table->string('apprv_id');
            $table->string('logical_connection')->nullable();
            $table->string('logical_type');
            $table->string('logical_cond');
            $table->string('logical_param')->nullable();
            $table->string('logical_val_opt')->nullable();
            $table->string('logical_val');
            $table->string('logical_parent');
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
        Schema::connection('sqlsrv_hrms')->dropIfExists('dms_logical_mstr');
    }
}
