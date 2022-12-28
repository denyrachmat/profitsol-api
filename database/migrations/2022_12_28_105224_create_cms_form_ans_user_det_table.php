<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmsFormAnsUserDetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_cms')->create('cms_form_ans_user_det', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username')->nullable();
            $table->integer('cfm_id');
            $table->integer('cfmd_id')->nullable();
            $table->string('cfm_val')->nullable();
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
        Schema::connection('sqlsrv_cms')->dropIfExists('cms_form_ans_user_det');
    }
}
