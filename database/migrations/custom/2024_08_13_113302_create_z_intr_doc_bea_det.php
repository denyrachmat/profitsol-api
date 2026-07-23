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
        Schema::connection('sqlsrv_log')->create('Z_INTR_DOC_BEA_DET', function (Blueprint $table) {
            $table->id();
            $table->string('ZIDBD_DOCCD');
            $table->string('ZIDBD_DOCNM')->nullable();
            $table->string('ZIDBD_DOCNMINTR')->nullable();
            $table->string('ZIDBD_LINK')->nullable();
            $table->string('ZIDBD_TLINK')->nullable();
            $table->string('ZIDBD_DESC')->nullable();
            $table->string('ZIDBD_DESCINTR')->nullable();
            $table->timestamps();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_log')->dropIfExists('Z_INTR_DOC_BEA_DET');
    }
};
