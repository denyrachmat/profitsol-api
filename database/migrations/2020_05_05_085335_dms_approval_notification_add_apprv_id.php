<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsApprovalNotificationAddApprvId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->table('dms_approval_notification', function (Blueprint $table) {
            $table->string('apprv_id')->nullable();
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
            $table->dropColumn(['apprv_id']);
        });
    }
}
