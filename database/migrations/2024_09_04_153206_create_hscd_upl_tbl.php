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
        Schema::connection('sqlsrv_log')->create('HSCD_UPL_TBL', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->string('HSCD_DOCNO');
            $table->string('HSCD_BG');
            $table->string('HSCD_ITMCD');
            $table->string('HSCD_SERIES')->nullable();
            $table->string('HSCD_MKHSCD');
            $table->string('HSCD_STXICD');
            $table->string('HSCD_UPLTYFORM');
            $table->datetime('HSCD_ISSDT')->nullable();
            $table->datetime('HSCD_APPRVDT')->nullable();
            $table->datetime('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_log')->dropIfExists('HSCD_UPL_TBL');
    }
};
