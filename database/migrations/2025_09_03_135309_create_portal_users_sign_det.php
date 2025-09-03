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
        Schema::create('portal_users_sign_det', function (Blueprint $table) {
            $table->string('pusd_unique_id')->unique()->primary();
            $table->string('u_username');
            $table->string('pusd_name');
            $table->string('pusd_desc');
            $table->text('pusd_sign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_users_sign_det');
    }
};
