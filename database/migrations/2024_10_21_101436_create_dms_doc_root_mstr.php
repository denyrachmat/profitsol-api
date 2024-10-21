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
        Schema::connection('sqlsrv_dms')->create('dms_doc_root_mstr', function (Blueprint $table) {
            $table->id();
            $table->string('ddrm_name');
            $table->string('ddrm_driver');
            $table->string('ddrm_root');
            $table->string('ddrm_desc')->nullable();
            $table->string('ddrm_host')->nullable();
            $table->string('ddrm_username')->nullable();
            $table->string('ddrm_password')->nullable();
            $table->string('ddrm_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_dms')->dropIfExists('dms_doc_root_mstr');
    }
};
