<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsUserMstrAddSignatureId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->table('dms_user_mstr', function (Blueprint $table) {
            $table->string('signature_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('sqlsrv_dms')->table('dms_user_mstr', function (Blueprint $table) {
            $table->dropColumn(['signature_id']);
        });
    }
}
