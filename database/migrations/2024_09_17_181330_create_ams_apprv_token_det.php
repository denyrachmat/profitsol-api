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
        Schema::connection('sqlsrv_ams')->create('ams_apprv_token_det', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->integer('amsm_id');
            $table->string('amstd_token');
            $table->string('amstd_emailto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_ams')->dropIfExists('ams_apprv_token_det');
    }
};
