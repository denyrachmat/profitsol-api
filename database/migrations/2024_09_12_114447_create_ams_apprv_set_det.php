<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('sqlsrv_ams')->create('ams_apprv_set_det', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->integer('amsm_id');
            $table->integer('amssd_quotkn')->default(5);
            $table->boolean('amssd_isemail')->default(0);
            $table->boolean('amssd_iswa')->default(0);
            $table->boolean('amssd_issms')->default(0);
            $table->boolean('amssd_is_docsign')->default(0);
            $table->boolean('amssd_unread_autonotif')->default(0);
            $table->integer('amssd_unread_chktime')->default(0);
            $table->boolean('amssd_autorun')->default(0);
            $table->integer('amssd_autorun_chktime')->default(0);
            $table->text('amssd_content')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_ams')->dropIfExists('ams_apprv_set_det');
    }
};
