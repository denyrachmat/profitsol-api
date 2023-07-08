<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMrsDbMstrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mrs_db_mstr', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->string('mdm_host');
            $table->string('mdm_name');
            $table->string('mdm_username');
            $table->string('mdm_password');
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
        Schema::dropIfExists('mrs_db_mstr');
    }
}
