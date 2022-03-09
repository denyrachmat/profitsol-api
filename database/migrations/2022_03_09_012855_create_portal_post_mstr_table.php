<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePortalPostMstrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('portal_post_mstr', function (Blueprint $table) {
            $table->id();
            $table->string('pm_post_name');
            $table->string('pm_post_desc')->nullable();
            $table->integer('pm_post_parent_id')->nullable();
            $table->integer('pm_post_max_reg')->default(1);
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
        Schema::dropIfExists('portal_post_mstr');
    }
}
