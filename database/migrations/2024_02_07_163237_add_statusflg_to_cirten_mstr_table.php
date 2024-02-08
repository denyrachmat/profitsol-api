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
        Schema::connection('sqlsrv_bim')->table('CIRTEN_MSTR', function (Blueprint $table) {
            $table->boolean('CIRTEN_STATUSFLG')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_bim')->table('CIRTEN_MSTR', function (Blueprint $table) {
            //
        });
    }
};
