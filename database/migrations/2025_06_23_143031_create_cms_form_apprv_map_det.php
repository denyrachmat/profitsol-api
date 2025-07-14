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
        Schema::connection('sqlsrv_cms')->create('cms_form_ams_map_det', function (Blueprint $table) {
            $table->id();
            $table->integer('cfamd_cfm_id');
            $table->integer('cfamd_cfaud_batch');
            $table->string('cfamd_amstd_token');
            $table->string('cfamd_desc');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_cms')->dropIfExists('cms_form_ams_map_det');
    }
};
