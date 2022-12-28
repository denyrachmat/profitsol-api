<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmsFormMultiDetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_cms')->create('cms_form_multi_det', function (Blueprint $table) {
            $table->id();
            $table->integer('cfm_id');
            $table->string('cfmd_value');
            $table->string('cfmd_label');
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
        Schema::connection('sqlsrv_cms')->dropIfExists('cms_form_multi_det');
    }
}
