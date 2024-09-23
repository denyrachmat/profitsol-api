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
        Schema::connection('sqlsrv_ams')->create('ams_apprv_hist_det', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->integer('amsm_id');
            $table->integer('amsmd_id');
            $table->string('amshd_token');
            // $table->string('amshd_username');
            $table->string('amshd_username_apprv');
            $table->string('amshd_stat');
            $table->text('amshd_remarks')->nullable();
            $table->datetime('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_ams')->dropIfExists('ams_apprv_hist_det');
    }
};
