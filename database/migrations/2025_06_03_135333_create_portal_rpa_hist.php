<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
// Make sure the migration for 'portal_rpa_mstr' runs before this one by renaming its migration file to have an earlier timestamp than 2025_06_03_134829.
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('portal_rpa_hist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prh_prmid')->constrained('portal_rpa_mstr')->onDelete('cascade');
            $table->string('prh_robotnm');
            $table->text('prh_command')->nullable();
            $table->integer('prh_flag')->default(0); // 0: pending, 1: running, 2: success, 3: failed
            $table->text('prh_result')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_rpa_hist');
    }
};
