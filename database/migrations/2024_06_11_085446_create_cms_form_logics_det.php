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
        Schema::connection('sqlsrv_cms')->create('cms_form_logics_det', function (Blueprint $table) {
            $table->id();
            $table->integer('cfm_id');
            $table->string('cfld_opr');
            $table->string('cfld_val');
            $table->string('cfld_opr_ctrl');
            $table->string('cfld_res');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_cms')->dropIfExists('cms_form_logics_det');
    }
};
