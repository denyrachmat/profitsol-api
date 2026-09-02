<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::connection('sqlsrv_ams')->table('ams_apprv_set_det', function (Blueprint $table) {
            $table->json('amssd_content_variables')->nullable()->after('amssd_content')->comment('JSON schema of content variables with data types: [{name, type, label, group}]');
        });
    }

    public function down()
    {
        Schema::connection('sqlsrv_ams')->table('ams_apprv_set_det', function (Blueprint $table) {
            $table->dropColumn('amssd_content_variables');
        });
    }
};
