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
        Schema::create('cms_form_event', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->integer('cfmt_id');
            $table->string('cfe_type');
            $table->string('cfe_opr');
            $table->string('cfe_opr');
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
        Schema::dropIfExists('cms_form_event');
    }
}
