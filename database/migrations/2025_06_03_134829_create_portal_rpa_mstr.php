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
        Schema::create('portal_rpa_mstr', function (Blueprint $table) {
            $table->id();
            $table->string('prm_name');
            $table->string('prm_type')->default('url');
            $table->string('prm_host')->default('localhost');
            $table->string('prm_port')->nullable();
            $table->boolean('prm_isactive')->default(true);
            $table->string('prm_desc')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_rpa_mstr');
    }
};
