<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HrmsUserMstr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_hrms')->create('hrms_user_mstr', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username')->index()->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->index()->unique();
            $table->string('token')->nullable();
            $table->string('password_hash');
            $table->string('password_sha');
            $table->string('role_id')->nullable();
            $table->string('division_id')->nullable();
            $table->string('signature_id')->nullable();
            $table->string('occ_id')->nullable();
            $table->string('domain_id')->nullable();
            $table->boolean('status');
            $table->datetime('verified_at')->nullable();
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
        Schema::connection('sqlsrv_hrms')->dropIfExists('hrms_user_mstr');
    }
}
