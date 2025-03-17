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
        Schema::connection('sqlsrv_cms')->table('cms_form_setup_det', function (Blueprint $table) {
            $table->integer('cfsd_quest_limit')->nullable();
            $table->boolean('cfsd_skip_next_btn_media_done')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_cms')->table('cms_form_setup_det', function (Blueprint $table) {
            $table->integer('cfsd_quest_limit')->nullable();
            $table->boolean('cfsd_skip_next_btn_media_done')->default(0);
        });
    }
};
