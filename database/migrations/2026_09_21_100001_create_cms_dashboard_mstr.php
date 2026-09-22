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
        Schema::connection('sqlsrv_cms')->create('cms_dashboard_mstr', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username')->nullable();
            $table->string('cdm_code')->unique();
            $table->string('cdm_title');
            $table->string('cdm_desc')->nullable();
            // JSON layout: { items: [ { id, width, height, title, type,
            //   datasetCode, params, mapping: { label, values }, options } ] }
            $table->text('cdm_layout')->nullable();
            // JSON array of role id strings; empty = everyone.
            $table->text('cdm_roles')->nullable();
            $table->string('cdm_status')->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_cms')->dropIfExists('cms_dashboard_mstr');
    }
};
