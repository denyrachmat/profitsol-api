<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HrmsContentLetterDet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_hrms')->create('hrms_content_form_det', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('content_id');
            $table->string('form_label');
            $table->string('form_type');
            $table->string('form_comp');
            $table->boolean('form_req');
            $table->integer('form_parent');
            $table->string('username');
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
        Schema::connection('sqlsrv_hrms')->dropIfExists('hrms_content_form_det');
    }
}
