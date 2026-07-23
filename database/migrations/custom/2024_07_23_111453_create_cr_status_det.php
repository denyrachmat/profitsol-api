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
        Schema::connection('sqlsrv_ceisa40')->create('CR_STATUS_DET', function (Blueprint $table) {
            $table->id();
            $table->string('ID_HEADER');
            $table->string('CRSD_NOMOR_AJU');
            $table->string('CRSD_RESNM');
            $table->datetime('CRSD_RESDTFR');
            $table->datetime('CRSD_RESDTTO');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_ceisa40')->dropIfExists('CR_STATUS_DET');
    }
};
