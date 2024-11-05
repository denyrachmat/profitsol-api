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
        Schema::connection('sqlsrv_ams')->create('ams_apprv_attch_set', function (Blueprint $table) {
            $table->id();
            $table->integer('aasd_id');
            $table->string('aats_name')->unique();
            $table->string('aats_method');
            $table->string('aats_host');
            $table->string('aats_header')->nullable();
            $table->string('aats_param')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_ams')->dropIfExists('ams_apprv_attch_set');
    }
};
