<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmsFormEvent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_cms')->create('cms_form_event', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->integer('cfmt_id');
            $table->integer('cfm_id')->nullable();
            $table->string('cfe_event');
            $table->string('cfe_result');
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
        Schema::connection('sqlsrv_cms')->dropIfExists('cms_form_event');
    }
}
