<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsApprovalNotificationApprovalHistAddContentDefId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->table('dms_approval_notification', function (Blueprint $table) {
            $table->string('content_def_id')->nullable();
        });

        Schema::connection('sqlsrv_dms')->table('dms_apprv_hist', function (Blueprint $table) {
            $table->string('content_def_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('sqlsrv_dms')->table('dms_approval_notification', function (Blueprint $table) {
            $table->dropColumn(['content_def_id']);
        });

        Schema::connection('sqlsrv_dms')->table('dms_apprv_hist', function (Blueprint $table) {
            $table->dropColumn(['content_def_id']);
        });
    }
}
