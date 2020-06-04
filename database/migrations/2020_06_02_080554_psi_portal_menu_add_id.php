<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PsiPortalMenuAddId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('psi_portal_menu', function (Blueprint $table) {
            $table->integer('menu_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('psi_portal_menu', function (Blueprint $table) {
            $table->integer('menu_id')->nullable();
        });
    }
}
