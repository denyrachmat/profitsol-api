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
        Schema::connection('sqlsrv_bim')->table('cirten_tenlist_tbl', function (Blueprint $table) {
            $table->string('CTT_SUBJECT')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_bim')->table('cirten_tenlist_tbl', function (Blueprint $table) {
            $table->string('CTT_SUBJECT');
        });
    }
};
