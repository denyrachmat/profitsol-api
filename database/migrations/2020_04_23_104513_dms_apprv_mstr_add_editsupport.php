<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsApprvMstrAddEditsupport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->table('dms_apprv_mstr', function (Blueprint $table) {
            $table->string('apprv_title')->nullable();
            $table->string('apprv_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('sqlsrv_dms')->table('dms_apprv_mstr', function (Blueprint $table) {
            $table->dropColumn(['apprv_title', 'apprv_id']);
        });
    }
}
