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
        Schema::create('portal_rpa_param_det', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prpd_prmid')->constrained('portal_rpa_mstr')->onDelete('cascade');
            $table->string('prpd_param_name');
            $table->boolean('prpd_param_required')->default(false);
            $table->string('prpd_param_type')->default('string'); // e.g., string, integer, boolean, etc.
            $table->text('prpd_param_desc')->nullable();
            $table->string('prpd_param_default')->nullable(); // Default value for the parameter
            $table->boolean('prpd_isactive')->default(true); // Indicates if the parameter is active
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_rpa_param_det');
    }
};
