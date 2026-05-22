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
        Schema::connection('sqlsrv_log')->create('HSCD_FILES_DET', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->string('HSCD_DOCNO');
            $table->string('HSCD_ITMCD');
            $table->string('HFD_FILENAME');
            $table->string('HFD_FILEPATH');
            $table->timestamps();
            $table->datetime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_log')->dropIfExists('HSCD_FILES_DET');
    }
};
