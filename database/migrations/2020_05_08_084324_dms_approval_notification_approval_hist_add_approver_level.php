<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsApprovalNotificationApprovalHistAddApproverLevel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->table('dms_approval_notification', function (Blueprint $table) {
            $table->string('approver_level')->nullable();
        });

        Schema::connection('sqlsrv_dms')->table('dms_apprv_hist', function (Blueprint $table) {
            $table->string('approver_level')->nullable();
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
            $table->dropColumn(['approver_level']);
        });

        Schema::connection('sqlsrv_dms')->table('dms_apprv_hist', function (Blueprint $table) {
            $table->dropColumn(['approver_level']);
        });
    }
}
