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
        Schema::table('portal_rpa_hist', function (Blueprint $table) {
            $table->integer('prh_cfaud_batch_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portal_rpa_hist', function (Blueprint $table) {
            $table->dropColumn('prh_cfaud_batch_id');
        });
    }
};
