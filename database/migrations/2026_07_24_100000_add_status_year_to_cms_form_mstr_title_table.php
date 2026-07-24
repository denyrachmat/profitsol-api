<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::connection('sqlsrv_cms')->table('cms_form_mstr_title', function (Blueprint $table) {
            $table->string('cfmt_status')->default('draft')->after('cfmt_quiz_flag');
            $table->string('cfmt_year')->nullable()->after('cfmt_status');
        });
    }

    public function down()
    {
        Schema::connection('sqlsrv_cms')->table('cms_form_mstr_title', function (Blueprint $table) {
            $table->dropColumn(['cfmt_status', 'cfmt_year']);
        });
    }
};
