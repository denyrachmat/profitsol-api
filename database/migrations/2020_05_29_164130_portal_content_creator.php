<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PortalContentCreator extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('portal_content_creator', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('content_var_id');
            $table->string('content_var_value');
            $table->string('content_users');
            $table->string('content_creator_id');
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
        Schema::dropIfExists('portal_content_creator');
    }
}
