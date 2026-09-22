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
        Schema::connection('sqlsrv_cms')->create('cms_dataset_mstr', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username')->nullable();
            $table->string('cds_code')->unique();
            $table->string('cds_name');
            $table->string('cds_desc')->nullable();
            // sql = read-only SELECT on a whitelisted DB connection;
            // api = internal endpoint of this API.
            $table->string('cds_type')->default('sql');
            $table->string('cds_connection')->nullable();
            $table->text('cds_query')->nullable();
            $table->string('cds_endpoint')->nullable();
            $table->string('cds_method')->default('get');
            $table->text('cds_payload')->nullable();
            // JSON array of { name, label, type, default } — declared runtime params.
            $table->text('cds_params_schema')->nullable();
            $table->integer('cds_cache_ttl')->default(300);
            // JSON array of role id strings; empty = everyone.
            $table->text('cds_roles')->nullable();
            $table->string('cds_status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_cms')->dropIfExists('cms_dataset_mstr');
    }
};
