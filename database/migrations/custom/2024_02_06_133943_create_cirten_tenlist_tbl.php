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
        Schema::connection('sqlsrv_bim')->create('CIRTEN_TENLIST_TBL', function (Blueprint $table) {
            $table->id();
            $table->string('CTT_SECTENNO');
            $table->string('CTT_IEITENNO');
            $table->date('CTT_EMLDT')->nullable();
            $table->date('CTT_EXCUPDT')->nullable();
            $table->date('CTT_ITMUPDT')->nullable();
            $table->date('CTT_BOMUPDT')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_bim')->dropIfExists('CIRTEN_TENLIST_TBL');
    }
};
