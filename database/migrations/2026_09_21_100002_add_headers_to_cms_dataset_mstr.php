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
        Schema::connection('sqlsrv_cms')->table('cms_dataset_mstr', function (Blueprint $table) {
            // JSON object of custom HTTP headers for API datasets
            // (e.g. {"Authorization": "Bearer ..."}).
            $table->text('cds_headers')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_cms')->table('cms_dataset_mstr', function (Blueprint $table) {
            $table->dropColumn('cds_headers');
        });
    }
};
