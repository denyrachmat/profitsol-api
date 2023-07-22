<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMrsReportColsDetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_mrs')->create('mrs_report_cols_det', function (Blueprint $table) {
            $table->id();
            $table->string('mrm_id');
            $table->string('mrcd_field');
            $table->string('mrcd_label');
            $table->boolean('mrcd_isActive')->default(0);
            $table->boolean('mrcd_sortable')->default(0);
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
        Schema::dropIfExists('mrs_report_cols_det');
    }
}
