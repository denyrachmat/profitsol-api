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
        Schema::table('portal_domain', function (Blueprint $table) {
            $table->boolean('pd_is_cms')->default(false)->after('pd_is_active')->comment('Indicates if the domain is a CMS domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portal_domain', function (Blueprint $table) {
            $table->dropColumn('pd_is_cms');
        });
    }
};
