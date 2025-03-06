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
        Schema::create('mbl_gencode', function (Blueprint $table) {
            $table->id();
            $table->string('MBLG_SETTYPE');
            $table->string('MBLG_SETVALUE');
            $table->string('MBLG_SETDESC');
            $table->string('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mbl_gencode');
    }
};
