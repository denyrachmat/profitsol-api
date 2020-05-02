<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsApprvHist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->create('dms_apprv_hist', function (Blueprint $table) {
            $table->string('id')->index();
            $table->string('apprv_parent');
            $table->string('apprv_hist_user');
            $table->string('apprv_hist_doc');
            $table->string('apprv_hist_comment');
            $table->integer('apprv_hist_status');
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
        Schema::connection('sqlsrv_dms')->dropIfExists('dms_apprv_hist');
    }
}
