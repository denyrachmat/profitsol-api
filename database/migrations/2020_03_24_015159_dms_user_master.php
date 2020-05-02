<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsUserMaster extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->create('dms_user_mstr', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username')->index()->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->index()->unique();
            $table->string('token')->nullable();
            $table->string('password_hash');
            $table->string('password_sha');
            $table->string('role_id')->nullable();
            $table->boolean('status');
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
        Schema::connection('sqlsrv_dms')->dropIfExists('dms_user_mstr');
    }
}
