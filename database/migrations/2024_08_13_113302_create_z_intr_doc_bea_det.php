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
            $table->string('ZIDBD_DOCNM');
            $table->string('ZIDBD_DOCNMINTR');
            $table->string('ZIDBD_LINK');
            $table->string('ZIDBD_TLINK');
            $table->string('ZIDBD_DESC');
            $table->string('ZIDBD_DESCINTR');
            $table->timestamps();
            $table->dateTime('deleted_at');
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
