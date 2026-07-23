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
        Schema::table('portal_gencode_mstr', function (Blueprint $table) {
            $table->softDeletes('deleted_at')->after('pgm_created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portal_gencode_mstr', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
