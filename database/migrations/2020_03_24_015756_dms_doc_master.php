<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsDocMaster extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->create('dms_doc_mstr', function (Blueprint $table) {
            $table->string('doc_id')->index();
            $table->string('doc_name');
            $table->string('doc_path');
            $table->string('doc_real_path');
            $table->string('doc_author');
            $table->float('doc_ver');
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
        Schema::connection('sqlsrv_dms')->dropIfExists('dms_doc_mstr');
    }
}
