<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HrmsFormMapping extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_hrms')->create('hrms_form_content_mapping', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('div_id');
            $table->string('form_name');
            $table->string('content_id');
            $table->text('div_content');
            $table->string('div_type')->nullable();
            $table->string('div_username');
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
        Schema::connection('sqlsrv_hrms')->dropIfExists('hrms_form_content_mapping');
    }
}
