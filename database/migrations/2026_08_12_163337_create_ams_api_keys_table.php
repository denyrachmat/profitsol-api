<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::connection('sqlsrv_ams')->create('ams_api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username')->comment('Creator username');
            $table->string('ak_name')->comment('Friendly name for this key');
            $table->string('ak_key_hash')->unique()->comment('Hashed API key (bcrypt)');
            $table->json('ak_allowed_amsm_ids')->nullable()->comment('Array of allowed approval workflow IDs, null = all');
            $table->boolean('ak_active')->default(true);
            $table->timestamps();

            $table->index('p_u_username');
            $table->index('ak_active');
        });
    }

    public function down()
    {
        Schema::connection('sqlsrv_ams')->dropIfExists('ams_api_keys');
    }
};
