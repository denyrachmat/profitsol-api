<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HrmsUserEducationDet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_hrms')->create('hrms_user_education_det', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username');
            $table->integer('user_edu_level')->nullable();
            $table->string('user_edu_level_desc')->nullable();
            $table->string('user_edu_sc_name')->nullable();
            $table->string('user_edu_major')->nullable();
            $table->string('user_edu_city')->nullable();
            $table->integer('user_edu_month_from')->nullable();
            $table->year('user_edu_year_from')->nullable();
            $table->integer('user_edu_month_to')->nullable();
            $table->year('user_edu_year_to')->nullable();
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
        Schema::connection('sqlsrv_hrms')->dropIfExists('hrms_user_education_det');
    }
}
