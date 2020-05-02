<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsMenuMaster extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->create('dms_menu_mstr', function (Blueprint $table) {
            $table->string('id')->index();
            $table->string('menu_name');
            $table->string('menu_desc');
            $table->string('menu_parent');
            $table->string('menu_url')->nullable();
            $table->string('menu_icon')->nullable();
            $table->integer('menu_status')->nullable();
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
        Schema::connection('sqlsrv_dms')->dropIfExists('dms_menu_mstr');
    }
}
