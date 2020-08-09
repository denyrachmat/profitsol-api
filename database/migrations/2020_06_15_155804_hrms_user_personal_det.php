<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HrmsUserPersonalDet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_hrms')->create('hrms_user_personal_det', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username')->unique();
            $table->string('user_nat')->nullable(); //Indonesia
            $table->string('user_province1')->nullable(); //Jawa Barat
            $table->string('user_province2')->nullable();
            $table->string('user_city1')->nullable(); //Bekasi
            $table->string('user_city2')->nullable();
            $table->string('user_district1')->nullable(); //Cibarusah
            $table->string('user_district2')->nullable();
            $table->string('user_subdistrict1')->nullable(); //Sindang Mulya
            $table->string('user_subdistrict2')->nullable();
            $table->string('user_zip1')->nullable();
            $table->string('user_zip2')->nullable();
            $table->text('user_detaddr1')->nullable();
            $table->text('user_detaddr2')->nullable();
            $table->string('user_phone')->nullable();
            $table->string('user_handphone')->nullable();
            $table->string('user_religion')->nullable();
            $table->string('user_bloodtype')->nullable();
            $table->string('user_height')->nullable();
            $table->string('user_weight')->nullable();
            $table->string('user_gender')->nullable();
            $table->boolean('user_marital_status')->nullable();
            $table->integer('user_marital_wive')->nullable();
            $table->integer('user_marital_child')->nullable();
            $table->string('user_birthplace');
            $table->date('user_birthdate');
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
        Schema::connection('sqlsrv_hrms')->dropIfExists('hrms_user_personal_det');
    }
}
