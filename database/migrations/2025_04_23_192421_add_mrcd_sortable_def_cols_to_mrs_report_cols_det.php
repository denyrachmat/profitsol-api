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
        Schema::connection('sqlsrv_mrs')->table('mrs_report_cols_det', function (Blueprint $table) {
            $table->boolean('mrcd_sortable_def')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_mrs')->table('mrs_report_cols_det', function (Blueprint $table) {
            $table->boolean('mrcd_sortable_def')->default(0);
        });
    }
};
