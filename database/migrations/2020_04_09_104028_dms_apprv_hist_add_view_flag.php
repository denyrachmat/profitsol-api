<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsApprvHistAddViewFlag extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->table('dms_apprv_hist', function (Blueprint $table) {
            $table->datetime('apprv_hist_vwtime')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('sqlsrv_dms')->table('dms_apprv_hist', function (Blueprint $table) {
            $table->dropColumn(['apprv_hist_vwtime']);
        });
    }
}
