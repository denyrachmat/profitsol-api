<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HrmsMenuMstr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_hrms')->create('hrms_menu_mstr', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('menu_order')->index()->unique();
            $table->string('menu_name');
            $table->string('menu_desc');
            $table->string('menu_parent_id');
            $table->string('menu_url')->nullable();
            $table->string('menu_icon');
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
        Schema::connection('sqlsrv_hrms')->dropIfExists('hrms_user_mstr');
    }
}
