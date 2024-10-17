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
        Schema::create('portal_domain', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->string('pd_name');
            $table->text('pd_desc');
            $table->string('pd_dbtype');
            $table->string('pd_host');
            $table->string('pd_port');
            $table->string('pd_username');
            $table->string('pd_password');
            $table->string('pd_prefix_db')->unique();
            $table->string('pd_img')->nullable();
            $table->string('pd_base_color')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_domain');
    }
};
