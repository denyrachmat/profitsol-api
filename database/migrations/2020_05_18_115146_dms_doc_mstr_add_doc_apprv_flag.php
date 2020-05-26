<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsDocMstrAddDocApprvFlag extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->table('dms_doc_mstr', function (Blueprint $table) {
            $table->string('doc_lapprv_flag')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('sqlsrv_dms')->table('dms_doc_mstr', function (Blueprint $table) {
            $table->dropColumn(['doc_lapprv_flag']);
        });
    }
}
