<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmsFormMstrTitleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_cms')->create('cms_form_mstr_title', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username')->nullable();
            $table->string('cfmt_title');
            $table->integer('cfmt_quiz_flag')->default(0);
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
        Schema::connection('sqlsrv_cms')->dropIfExists('cms_form_mstr_title');
    }
}
