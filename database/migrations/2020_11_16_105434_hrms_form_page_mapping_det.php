<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HrmsFormPageMappingDet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_hrms')->create('hrms_form_page_mapping_det', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('mapping_id');
            $table->integer('domain_id')->nullable();
            $table->integer('division_id')->nullable();
            $table->string('username')->nullable();
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
        Schema::connection('sqlsrv_hrms')->dropIfExists('hrms_form_page_mapping_det');
    }
}
