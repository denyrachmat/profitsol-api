<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDmsUsersDocRootMstrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->create('dms_users_doc_root_mstr', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->string('dudrm_path');
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
        Schema::dropIfExists('dms_users_doc_root_mstr');
    }
}
