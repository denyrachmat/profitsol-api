<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBcmgTbl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_itinv')->create('BCMG_TBL', function (Blueprint $table) {
            $table->id();
            $table->string('BCMG_TYPE');
            $table->string('BCMG_BCDOCNO');
            $table->date('BCMG_BCDOCDT');
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
        Schema::connection('sqlsrv_itinv')->dropIfExists('BCMG_TBL');
    }
}
