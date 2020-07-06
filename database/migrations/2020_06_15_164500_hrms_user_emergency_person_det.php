<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HrmsUserEmergencyPersonDet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_hrms')->create('hrms_user_emergency_person_det', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username')->unique();
            $table->string('emerg_first_name');
            $table->string('emerg_last_name')->nullable();
            $table->string('emerg_email')->nullable();
            $table->string('emerg_phone');
            $table->string('emerg_handphone')->nullable();
            $table->string('emerg_province');
            $table->string('emerg_city');
            $table->string('emerg_urban')->nullable();
            $table->string('emerg_suburban')->nullable();
            $table->string('emerg_addr_det')->nullable();
            $table->string('emerg_desc')->nullable();
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
        Schema::connection('sqlsrv_hrms')->dropIfExists('hrms_user_emergency_person_det');
    }
}
