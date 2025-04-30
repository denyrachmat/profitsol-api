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
        Schema::create('portal_gencode_mstr', function (Blueprint $table) {
            $table->id();
            $table->string('pgm_code');
            $table->string('pgm_value');
            $table->string('pgm_value2')->nullable();
            $table->string('pgm_value3')->nullable();
            $table->string('pgm_desc');
            $table->string('pgm_desc2')->nullable();
            $table->string('pgm_desc3')->nullable();
            $table->string('pgm_created_by')->nullable();
            $table->string('pgm_parent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_gencode_mstr');
    }
};
