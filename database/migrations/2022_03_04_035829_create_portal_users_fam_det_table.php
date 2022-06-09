<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePortalUsersFamDetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('portal_users_fam_det', function (Blueprint $table) {
            $table->id();
            $table->string('u_username');
            $table->string('pufd_first_name');
            $table->string('pufd_last_name')->nullable();
            $table->string('pufd_relation');
            $table->string('pufd_phone')->nullable();
            $table->date('pufd_birthday')->nullable();
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
        Schema::dropIfExists('portal_users_fam_det');
    }
}
