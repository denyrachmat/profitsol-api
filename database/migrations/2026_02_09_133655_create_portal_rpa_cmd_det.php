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
        Schema::create('portal_rpa_cmd_det', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prcd_prpdid')->constrained('portal_rpa_param_det')->onDelete('cascade');
            $table->string('prcd_name');
            $table->json('prcd_params');
            $table->boolean('prcd_isactive')->default(true); // Indicates if the parameter is active
            $table->integer('prcd_order')->default(1); // Sequence order
            $table->text('prcd_action');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_rpa_cmd_det');
    }
};
