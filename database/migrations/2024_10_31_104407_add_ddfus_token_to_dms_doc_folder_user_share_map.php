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
        Schema::connection('sqlsrv_dms')->table('dms_doc_folder_user_share_map', function (Blueprint $table) {
            $table->string('ddfus_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_dms')->table('dms_doc_folder_user_share_map', function (Blueprint $table) {
            $table->string('ddfus_token');
        });
    }
};
