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
        Schema::connection('sqlsrv_bim')->create('CIRTEN_TENLIST_ITEM_DET', function (Blueprint $table) {
            $table->id();
            $table->integer('CTT_ID');
            $table->string('CTID_ITEMCDOLD');
            $table->string('CTID_ITEMCDNEW');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv_bim')->dropIfExists('cirten_tenlist_item_det');
    }
};
