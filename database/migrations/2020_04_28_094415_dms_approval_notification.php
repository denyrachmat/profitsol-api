<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsApprovalNotification extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->create('dms_approval_notification', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('apprv_user_from');
            $table->string('apprv_user_to')->nullable();
            $table->string('apprv_hist_from_id');
            $table->string('apprv_hist_to_id')->nullable();
            $table->datetime('apprv_read_flag')->nullable();
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
        Schema::connection('sqlsrv_dms')->dropIfExists('dms_approval_notification');
    }
}
