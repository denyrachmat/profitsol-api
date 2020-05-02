<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsLocdocMaster extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->create('dms_locdoc_mstr', function (Blueprint $table) {
            $table->string('id')->index();
            $table->string('parent_loc');
            $table->string('name_loc');
            $table->string('creator_loc');
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
        Schema::connection('sqlsrv_dms')->dropIfExists('dms_locdoc_mstr');
    }
}
