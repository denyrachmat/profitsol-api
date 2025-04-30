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
        Schema::connection('sqlsrv_cms')->table('cms_form_logics_det', function (Blueprint $table) {
            $table->string('cfld_seq_name', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_cms')->table('cms_form_logics_det', function (Blueprint $table) {
            $table->string('cfld_seq_name', 50)->nullable();
        });
    }
};
