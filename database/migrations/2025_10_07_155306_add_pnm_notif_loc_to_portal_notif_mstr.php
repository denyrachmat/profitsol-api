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
        Schema::table('portal_notif_mstr', function (Blueprint $table) {
            $table->string('pnm_notif_loc')->default('portal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portal_notif_mstr', function (Blueprint $table) {
            $table->dropColumn('pnm_notif_loc');
        });
    }
};
