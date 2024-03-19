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
        Schema::connection('sqlsrv_ems2')->table('dlv_req_smt_tyo', function (Blueprint $table) {
            $table->string('DRST_SPLITDOCRMK')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_ems2')->table('dlv_req_smt_tyo', function (Blueprint $table) {
            $table->string('DRST_SPLITDOCRMK');
        });
    }
};
