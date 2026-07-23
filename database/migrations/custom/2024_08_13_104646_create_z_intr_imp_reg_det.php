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
            $table->string('ZIRD_NMIJIN')->nullable();
            $table->string('ZIRD_KDIJIN')->nullable();
            $table->text('ZIRD_DESC')->nullable();
            $table->string('ZIRD_BEALIST')->nullable();
            $table->string('ZIRD_LEGAL')->nullable();
            $table->string('ZIRD_MODUL')->nullable();
            $table->text('ZIRD_SKEPNO')->nullable();
            $table->timestamps();
            $table->dateTime('deleted_at')->nullable();
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
