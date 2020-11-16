<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HrmsFormKeyAnswers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_hrms')->create('hrms_form_key_answers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('form_id');
            $table->string('ans_key');
            $table->string('ans_val');
            $table->string('ans_creator');
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
        Schema::connection('sqlsrv_hrms')->dropIfExists('hrms_form_key_answers');
    }
}
