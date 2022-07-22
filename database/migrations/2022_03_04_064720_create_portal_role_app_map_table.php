<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePortalRoleAppMapTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('portal_role_app_map', function (Blueprint $table) {
            $table->id();
            $table->integer('u_username');
            $table->integer('rm_role_id');
            $table->integer('am_app_id');
            $table->integer('am_app_parent_id')->nullable();
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
        Schema::dropIfExists('portal_role_app_map');
    }
}
