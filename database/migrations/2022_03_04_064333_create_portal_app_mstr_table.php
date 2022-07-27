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
            $table->string('u_username');
            $table->string('am_app_code');
            $table->string('am_app_name');
            $table->string('am_app_desc')->nullable();
            $table->string('am_app_url')->nullable();
            $table->string('am_app_icon')->nullable();
            $table->string('am_app_parent')->nullable();
            $table->boolean('am_is_drawer')->default(false);
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
