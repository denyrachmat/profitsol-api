<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HrmsContentFormHist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_hrms')->create('hrms_form_hist', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('form_hist_id');
            $table->string('form_id');            
            $table->string('publish_token');      
            $table->string('publish_id');
            $table->string('form_hist_value');
            $table->string('form_hist_username');
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
        Schema::connection('sqlsrv_hrms')->dropIfExists('hrms_form_hist');
    }
}
