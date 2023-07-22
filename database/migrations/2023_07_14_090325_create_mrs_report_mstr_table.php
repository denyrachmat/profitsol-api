<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMrsReportMstrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_mrs')->create('mrs_report_mstr', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->string('mdm_id');
            $table->string('mrm_name');
            $table->string('mrm_db');
            $table->string('mrm_table');
            $table->text('mrm_query');
            $table->string('mrm_url_gen');
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
        Schema::dropIfExists('mrs_report_mstr');
    }
}
