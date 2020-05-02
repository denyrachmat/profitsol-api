<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsApprvDef extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->create('dms_apprv_mstr', function (Blueprint $table) {
            $table->string('id')->index();
            $table->string('apprv_author');
            $table->string('apprv_approver');
            $table->boolean('apprv_email_notify');
            $table->integer('apprv_level');
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
        Schema::connection('sqlsrv_dms')->dropIfExists('dms_apprv_mstr');
    }
}
