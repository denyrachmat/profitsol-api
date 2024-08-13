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
        Schema::connection('sqlsrv_log')->create('Z_INTR_RULES_MSTR', function (Blueprint $table) {
            $table->id();
            $table->string('ZIRM_NO');
            $table->string('ZIRM_TYPE');
            $table->date('ZIRM_ISSDT');
            $table->string('ZIRM_FILE');
            $table->string('ZIRM_INSTANCE');
            $table->string('ZIRM_TITLEHEAD');
            $table->string('ZIRM_TITLE');
            $table->date('ZIRM_STARTDT');
            $table->date('ZIRM_RULEDT');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_log')->dropIfExists('Z_INTR_RULES_MSTR');
    }
};
