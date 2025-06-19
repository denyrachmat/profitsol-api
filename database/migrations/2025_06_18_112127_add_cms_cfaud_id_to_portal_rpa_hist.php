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
        Schema::table('portal_rpa_hist', function (Blueprint $table): void {
            $table->integer('prh_cfaud_id')->nullable()->after('prh_robotnm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portal_rpa_hist', function (Blueprint $table): void {
            $table->dropColumn('prh_cfaud_id');
        });
    }
};
