<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HrmsFormLogics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_hrms')->create('hrms_form_logics_mstr', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('content_id');
            $table->string('form_logics_id');
            $table->string('form_trigger')->nullable();
            $table->string('form_logics')->nullable();
            $table->integer('form_content_id')->nullable();
            $table->string('form_cond')->nullable();
            $table->string('form_val')->nullable();
            $table->string('form_actions')->nullable();
            $table->integer('form_order');
            $table->string('form_parent_logics_id');
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
        Schema::connection('sqlsrv_hrms')->dropIfExists('hrms_form_logics_mstr');
    }
}
