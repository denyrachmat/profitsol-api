<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePortalAppMstrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('portal_app_mstr', function (Blueprint $table) {
            $table->id();
            $table->string('am_app_code');
            $table->string('am_app_name');
            $table->string('am_app_desc')->nullable();
            $table->string('am_app_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('portal_app_mstr');
    }
}
