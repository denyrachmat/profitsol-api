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
        Schema::connection('sqlsrv_log')->create('Z_INTR_REG_DET', function (Blueprint $table) {
            $table->id();
            $table->string('ZID_HSCODE');
            $table->string('ZIRD_TYPE');
            $table->string('ZIRD_NMIJIN');
            $table->string('ZIRD_KDIJIN');
            $table->text('ZIRD_DESC');
            $table->string('ZIRD_BEALIST');
            $table->string('ZIRD_LEGAL');
            $table->string('ZIRD_MODUL');
            $table->text('ZIRD_SKEPNO');
            $table->timestamps();
            $table->dateTime('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_log')->dropIfExists('Z_INTR_IMP_REG_DET');
    }
};
