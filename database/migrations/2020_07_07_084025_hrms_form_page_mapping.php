<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HrmsFormPageMapping extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_hrms')->create('hrms_form_page_mapping', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('menu_id');
            $table->string('form_id');
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
        Schema::connection('sqlsrv_hrms')->dropIfExists('hrms_form_page_mapping');
    }
}
