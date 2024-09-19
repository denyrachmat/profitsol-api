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
        Schema::connection('sqlsrv_ams')->create('ams_apprv_mstr', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->string('ams_idapv');
            $table->string('ams_title');
            $table->text('ams_content')->nullable();
            $table->boolean('ams_active')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_ams')->dropIfExists('ams_apprv_mstr');
    }
};
