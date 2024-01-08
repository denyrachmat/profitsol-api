<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRealQuizDateToCfsd extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_cms')->table('cms_form_setup_det', function (Blueprint $table) {
            $table->dateTime('cfsd_real_start_quiz')->nullable();
            $table->dateTime('cfsd_real_end_quiz')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('sqlsrv_cms')->table('cms_form_setup_det', function (Blueprint $table) {
            //
        });
    }
}
