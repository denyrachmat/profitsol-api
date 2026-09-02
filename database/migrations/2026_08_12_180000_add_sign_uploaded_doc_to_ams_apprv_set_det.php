<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::connection('sqlsrv_ams')->table('ams_apprv_set_det', function (Blueprint $table) {
            $table->boolean('amssd_sign_uploaded_doc')->default(false)->after('amssd_is_docsign')->comment('If true, API submitters must upload doc + setup sign boxes per submission');
        });
    }

    public function down()
    {
        Schema::connection('sqlsrv_ams')->table('ams_apprv_set_det', function (Blueprint $table) {
            $table->dropColumn('amssd_sign_uploaded_doc');
        });
    }
};
